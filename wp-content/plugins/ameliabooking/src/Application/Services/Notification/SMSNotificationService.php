<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\NotificationStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationLogRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationSMSHistoryRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use Interop\Container\Exception\ContainerException;

/**
 * Class SMSNotificationService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
class SMSNotificationService extends AbstractNotificationService
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array        $appointmentArray
     * @param Notification $notification
     * @param bool         $logNotification
     *
     * @param int|null     $bookingKey
     *
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function sendNotification(
        $appointmentArray,
        $notification,
        $logNotification,
        $bookingKey = null
    ) {
        /** @var NotificationLogRepository $notificationsLogRepository */
        $notificationsLogRepository = $this->container->get('domain.notificationLog.repository');
        /** @var NotificationSMSHistoryRepository $notificationsSMSHistoryRepo */
        $notificationsSMSHistoryRepo = $this->container->get('domain.notificationSMSHistory.repository');
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        /** @var SMSAPIService $smsApiService */
        $smsApiService = $this->container->get('application.smsApi.service');
        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get("application.placeholder.{$appointmentArray['type']}.service");

        $token = isset($appointmentArray['bookings'][$bookingKey]) ?
            $bookingRepository->getToken($appointmentArray['bookings'][$bookingKey]['id']) : null;


        $data = $placeholderService->getPlaceholdersData(
            $appointmentArray,
            $bookingKey,
            isset($token['token']) ? $token['token'] : null
        );

        $text = $placeholderService->applyPlaceholders($notification->getContent()->getValue(), $data);

        $users = $this->getUsersInfo(
            $notification->getSendTo()->getValue(),
            $appointmentArray,
            $bookingKey,
            $data
        );

        foreach ($users as $user) {
            if ($user['phone']) {
                try {
                    $historyId = $notificationsSMSHistoryRepo->add([
                        'notificationId' => $notification->getId()->getValue(),
                        'userId'         => $user['id'],
                        'appointmentId'  =>
                            $appointmentArray['type'] === Entities::APPOINTMENT ? $appointmentArray['id'] : null,
                        'eventId'        =>
                            $appointmentArray['type'] === Entities::EVENT ? $appointmentArray['id'] : null,
                        'text'           => $text,
                        'phone'          => $user['phone'],
                        'alphaSenderId'  => $settingsService->getSetting('notifications', 'smsAlphaSenderId'),
                    ]);

                    $apiResponse = $smsApiService->send(
                        $user['phone'],
                        $text,
                        AMELIA_ACTION_URL . '/notifications/sms/history/' . $historyId
                    );

                    if ($apiResponse->status === 'OK') {
                        $notificationsSMSHistoryRepo->update($historyId, [
                            'logId'    => $apiResponse->message->logId,
                            'status'   => $apiResponse->message->status,
                            'price'    => $apiResponse->message->price,
                            'dateTime' => DateTimeService::getNowDateTimeInUtc(),
                            'segments' => $apiResponse->message->segments
                        ]);

                        if ($logNotification) {
                            $notificationsLogRepository->add(
                                $notification,
                                $userRepository->getById($user['id']),
                                $appointmentArray['type'] === Entities::APPOINTMENT ? $appointmentArray['id'] : null,
                                $appointmentArray['type'] === Entities::EVENT ? $appointmentArray['id'] : null
                            );
                        }
                    }
                } catch (NotFoundException $e) {
                } catch (QueryExecutionException $e) {
                } catch (ContainerException $e) {
                }
            }
        }
    }

    /**
     * @throws ContainerException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Exception
     */
    public function sendBirthdayGreetingNotifications()
    {
        /** @var Notification $notification */
        $notification = $this->getByNameAndType('customer_birthday_greeting', $this->type);

        // Check if notification is enabled and it is time to send notification
        if ($notification->getStatus()->getValue() === NotificationStatus::ENABLED &&
            DateTimeService::getNowDateTimeObject() >=
            DateTimeService::getCustomDateTimeObject($notification->getTime()->getValue())
        ) {
            /** @var NotificationLogRepository $notificationLogRepo */
            $notificationLogRepo = $this->container->get('domain.notificationLog.repository');
            /** @var NotificationSMSHistoryRepository $notificationsSMSHistoryRepo */
            $notificationsSMSHistoryRepo = $this->container->get('domain.notificationSMSHistory.repository');

            /** @var SMSAPIService $smsApiService */
            $smsApiService = $this->container->get('application.smsApi.service');
            /** @var PlaceholderService $placeholderService */
            $placeholderService = $this->container->get('application.placeholder.appointment.service');
            /** @var SettingsService $settingsService */
            $settingsService = $this->container->get('domain.settings.service');

            $customers = $notificationLogRepo->getBirthdayCustomers($this->type);
            $companyData = $placeholderService->getCompanyData();
            $customersArray = $customers->toArray();

            foreach ($customersArray as $bookingKey => $customerArray) {
                $data = [
                    'customer_email'      => $customerArray['email'],
                    'customer_first_name' => $customerArray['firstName'],
                    'customer_last_name'  => $customerArray['lastName'],
                    'customer_full_name'  => $customerArray['firstName'] . ' ' . $customerArray['lastName'],
                    'customer_phone'      => $customerArray['phone'],
                    'customer_id'         => $customerArray['id'],
                ];

                /** @noinspection AdditionOperationOnArraysInspection */
                $data += $companyData;

                $text = $placeholderService->applyPlaceholders(
                    $notification->getContent()->getValue(),
                    $data
                );

                if ($data['customer_phone']) {
                    try {
                        $historyId = $notificationsSMSHistoryRepo->add([
                            'notificationId' => $notification->getId()->getValue(),
                            'userId'         => $data['customer_id'],
                            'appointmentId'  => null,
                            'text'           => $text,
                            'phone'          => $data['customer_phone'],
                            'alphaSenderId'  => $settingsService->getSetting('notifications', 'smsAlphaSenderId'),
                        ]);

                        $smsApiService->send(
                            $data['customer_phone'],
                            $text,
                            AMELIA_ACTION_URL . '/notifications/sms/history/' . $historyId
                        );

                        $apiResponse = $smsApiService->send(
                            $data['customer_phone'],
                            $text,
                            AMELIA_ACTION_URL . '/notifications/sms/history/' . $historyId
                        );

                        if ($apiResponse->status === 'OK') {
                            $notificationsSMSHistoryRepo->update($historyId, [
                                'logId'    => $apiResponse->message->logId,
                                'status'   => $apiResponse->message->status,
                                'price'    => $apiResponse->message->price,
                                'dateTime' => DateTimeService::getNowDateTimeInUtc(),
                                'segments' => $apiResponse->message->segments
                            ]);

                            $notificationLogRepo->add(
                                $notification,
                                $customers->getItem($bookingKey)
                            );
                        }
                    } catch (QueryExecutionException $e) {
                    } catch (ContainerException $e) {
                    }
                }
            }
        }
    }
}
