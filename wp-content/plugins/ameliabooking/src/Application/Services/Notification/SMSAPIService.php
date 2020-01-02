<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class SMSAPIService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
class SMSAPIService
{
    /** @var string */
    const STATUS_STRING_OK = 'OK';

    /** @var Container */
    private $container;

    /**
     * ProviderApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $route
     * @param $authorize
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function sendRequest($route, $authorize, $data = null)
    {
        $ch = curl_init(AMELIA_SMS_API_URL . $route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // If there is data, request will be POST request, otherwise it will be GET
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        // If authorization is needed, send token to the request header
        if ($authorize) {
            /** @var SettingsService $settingsService */
            $settingsService = $this->container->get('domain.settings.service');

            $authorization = 'Authorization: Bearer ' . $settingsService->getSetting('notifications', 'smsApiToken');

            curl_setopt($ch, CURLOPT_HTTPHEADER, [$authorization]);
        }

        $response = curl_exec($ch);

        curl_close($ch);

        return json_decode($response);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function signUp($data)
    {
        $route = 'auth/signup';

        $response = $this->sendRequest($route, false, $data);

        if ($response->status === self::STATUS_STRING_OK) {
            $this->authorizeUser($response->token);
        }

        return $response;
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function signIn($data)
    {
        $route = 'auth/signin';

        $response = $this->sendRequest($route, false, $data);

        if ($response->status === self::STATUS_STRING_OK) {
            $this->authorizeUser($response->token);
        }

        return $response;
    }

    /**
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getUserInfo()
    {
        $route = 'auth/info';

        return $this->sendRequest($route, true);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function forgotPassword($data)
    {
        $route = 'auth/password/forgot';

        $data['redirectUrl'] = AMELIA_PAGE_URL . 'wpamelia-notifications&notificationTab=sms';

        return $this->sendRequest($route, false, $data);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function resetPassword($data)
    {
        $route = 'auth/password/reset';

        return $this->sendRequest($route, false, $data);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function changePassword($data)
    {
        $route = '/auth/password/change';

        return $this->sendRequest($route, true, $data);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getCountryPriceList($data)
    {
        $route = '/sms/prices/' . strtoupper($data['selectedCountry']);

        return $this->sendRequest($route, true);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function testNotification($data)
    {
        $route = '/sms/send';

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var EmailNotificationService $notificationService */
        $notificationService = $this->container->get('application.emailNotification.service');

        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get("application.placeholder.{$data['type']}.service");

        $notification = $notificationService->getByNameAndType($data['notificationTemplate'], 'sms');

        $dummyData = $placeholderService->getPlaceholdersDummyData();

        $body = $placeholderService->applyPlaceholders(
            $notification->getContent()->getValue(),
            $dummyData
        );

        $data = [
            'to'   => $data['recipientPhone'],
            'from' => $settingsService->getSetting('notifications', 'smsAlphaSenderId'),
            'body' => $body
        ];

        return $this->sendRequest($route, true, $data);
    }

    /**
     * @param $to
     * @param $body
     * @param $callbackUrl
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function send($to, $body, $callbackUrl)
    {
        $route = '/sms/send';

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $data = [
            'to'          => $to,
            'from'        => $settingsService->getSetting('notifications', 'smsAlphaSenderId'),
            'body'        => $body,
            'callbackUrl' => $callbackUrl
        ];

        return $this->sendRequest($route, true, $data);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function refreshSMSHistory($data)
    {
        $route = "/sms/refresh/{$data['logId']}";

        return $this->sendRequest($route, true);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function paymentCheckout($data)
    {
        $route = '/payment/checkout';

        $data['redirectUrl'] = AMELIA_PAGE_URL . 'wpamelia-notifications&notificationTab=sms';

        return $this->sendRequest($route, true, $data);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function paymentComplete($data)
    {
        $route = '/payment/complete';

        return $this->sendRequest($route, true, $data);
    }

    /**
     * @param $data
     *
     * @return mixed
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getPaymentHistory($data)
    {
        $route = '/payment/history';

        return $this->sendRequest($route, true, $data);
    }

    /**
     * @param $token
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function authorizeUser($token)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $settingsService->setSetting('notifications', 'smsSignedIn', true);

        $settingsService->setSetting('notifications', 'smsApiToken', $token);
    }
}
