<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\CustomField\CustomFieldApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Booking\Validator;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class WooCommercePaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class WooCommercePaymentCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookings',
        'couponCode',
        'payment'
    ];

    /**
     * @param WooCommercePaymentCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\ForbiddenFileUploadException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle(WooCommercePaymentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        WooCommerceService::setContainer($this->container);

        $validator = new Validator();

        $validator->setCouponValidation(true);
        $validator->setCustomFieldsValidation(true);
        $validator->setTimeSlotValidation(true);

        /** @var Reservation $reservation */
        $reservation = $reservationService->processBooking(
            $result,
            $bookingAS->getAppointmentData($command->getFields()),
            $validator,
            false
        );

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }

        /** @var CustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        $uploadedCustomFieldFilesNames = $customFieldService->saveUploadedFiles(
            0,
            $reservation->getUploadedCustomFieldFilesInfo(),
            '/tmp'
        );

        $appointmentData = $reservationService->getInfo(
            $reservation->getBookable(),
            $reservation->getBooking(),
            $reservation->getReservation(),
            $command->getFields()['payment']['gateway']
        );

        $appointmentData['uploadedCustomFieldFilesInfo'] = $uploadedCustomFieldFilesNames;

        try {
            $bookableSettings = $reservation->getBookable()->getSettings() ?
                json_decode($reservation->getBookable()->getSettings()->getValue(), true) : null;

            WooCommerceService::addToCart(
                $appointmentData,
                $bookableSettings && isset($bookableSettings['payments']['wc']['productId']) ?
                    $bookableSettings['payments']['wc']['productId'] : null
            );
        } catch (\Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['wc_error']);
            $result->setData([
                'wooCommerceError' => true
            ]);

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Proceed to WooCommerce Cart');
        $result->setData([
            'cartUrl' => WooCommerceService::getCartUrl()
        ]);

        return $result;
    }
}