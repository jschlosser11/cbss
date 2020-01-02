<?php

namespace AmeliaBooking\Infrastructure\WP\SettingsService;

use AmeliaBooking\Application\Services\Location\CurrentLocation;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsStorageInterface;

/**
 * Class SettingsStorage
 *
 * @package AmeliaBooking\Infrastructure\WP\SettingsService
 */
class SettingsStorage implements SettingsStorageInterface
{
    /** @var array|mixed */
    private $settingsCache;

    /** @var CurrentLocation */
    private $locationService;

    private static $wpSettings = [
        'dateFormat'     => 'date_format',
        'timeFormat'     => 'time_format',
        'startOfWeek'    => 'start_of_week',
        'timeZoneString' => 'timezone_string',
        'gmtOffset'      => 'gmt_offset'
    ];

    /**
     * SettingsStorage constructor.
     */
    public function __construct()
    {
        $this->locationService = new CurrentLocation();
        $this->settingsCache = json_decode(get_option('amelia_settings'), true);
        foreach (self::$wpSettings as $ameliaSetting => $wpSetting) {
            $this->settingsCache['wordpress'][$ameliaSetting] = get_option($wpSetting);
        }

        DateTimeService::setTimeZone($this->getAllSettings());
    }

    /**
     * @param $settingCategoryKey
     * @param $settingKey
     *
     * @return mixed
     */
    public function getSetting($settingCategoryKey, $settingKey)
    {
        return isset($this->settingsCache[$settingCategoryKey][$settingKey]) ?
            $this->settingsCache[$settingCategoryKey][$settingKey] : null;
    }

    /**
     * @param $settingCategoryKey
     *
     * @return mixed
     */
    public function getCategorySettings($settingCategoryKey)
    {
        return isset($this->settingsCache[$settingCategoryKey]) ?
            $this->settingsCache[$settingCategoryKey] : null;
    }

    /**
     * @return array|mixed|null
     */
    public function getAllSettings()
    {
        $settings = [];

        if (null !== $this->settingsCache) {
            foreach ((array)$this->settingsCache as $settingsCategoryName => $settingsCategory) {
                if ($settingsCategoryName !== 'daysOff') {
                    foreach ((array)$settingsCategory as $settingName => $settingValue) {
                        $settings[$settingName] = $settingValue;
                    }
                }
            }

            return $settings;
        }

        return null;
    }

    /**
     * @return array|mixed|null
     */
    public function getAllSettingsCategorized()
    {
        return isset($this->settingsCache) ? $this->settingsCache : null;
    }

    /**
     * Return settings for frontend
     *
     * @return array|mixed
     */
    public function getFrontendSettings()
    {
        $phoneCountryCode = $this->getSetting('general', 'phoneDefaultCountryCode');

        $capabilities = [];
        if (is_admin()) {
            $currentScreenId = get_current_screen()->id;
            $currentScreen = substr($currentScreenId, strrpos($currentScreenId, '-') + 1);

            $capabilities = [
                'canRead'        => current_user_can('amelia_read_' . $currentScreen),
                'canReadOthers'  => current_user_can('amelia_read_others_' . $currentScreen),
                'canWrite'       => current_user_can('amelia_write_' . $currentScreen),
                'canWriteOthers' => current_user_can('amelia_write_others_' . $currentScreen),
                'canDelete'      => current_user_can('amelia_delete_' . $currentScreen),
                'canWriteStatus' => current_user_can('amelia_write_status_' . $currentScreen),
            ];
        }

        $wpUser = wp_get_current_user();

        $userType = 'customer';

        if (in_array('administrator', $wpUser->roles, true)) {
            $userType = 'admin';
        } elseif (in_array('wpamelia-manager', $wpUser->roles, true)) {
            $userType = 'manager';
        } elseif (in_array('wpamelia-provider', $wpUser->roles, true)) {
            $userType = 'provider';
        }

        return [
            'capabilities'   => $capabilities,
            'daysOff'        => $this->getCategorySettings('daysOff'),
            'general'        => [
                'itemsPerPage'                    => $this->getSetting('general', 'itemsPerPage'),
                'phoneDefaultCountryCode'         => $phoneCountryCode === 'auto' ?
                    $this->locationService->getCurrentLocationCountryIso() : $phoneCountryCode,
                'timeSlotLength'                  => $this->getSetting('general', 'timeSlotLength'),
                'serviceDurationAsSlot'           => $this->getSetting('general', 'serviceDurationAsSlot'),
                'defaultAppointmentStatus'        => $this->getSetting('general', 'defaultAppointmentStatus'),
                'gMapApiKey'                      => $this->getSetting('general', 'gMapApiKey'),
                'addToCalendar'                   => $this->getSetting('general', 'addToCalendar'),
                'requiredPhoneNumberField'        => $this->getSetting('general', 'requiredPhoneNumberField'),
                'requiredEmailField'              => $this->getSetting('general', 'requiredEmailField'),
                'numberOfDaysAvailableForBooking' => $this->getSetting('general', 'numberOfDaysAvailableForBooking'),
                'minimumTimeRequirementPriorToBooking' =>
                    $this->getSetting('general', 'minimumTimeRequirementPriorToBooking'),
                'showClientTimeZone'              => $this->getSetting('general', 'showClientTimeZone'),
                'redirectUrlAfterAppointment'     => $this->getSetting('general', 'redirectUrlAfterAppointment'),
                'customFieldsUploadsPath'         => $this->getSetting('general', 'customFieldsUploadsPath'),
                'sortingServices'                 => $this->getSetting('general', 'sortingServices'),
            ],
            'googleCalendar' =>
                $this->getSetting('googleCalendar', 'clientID') && $this->getSetting('googleCalendar', 'clientSecret'),
            'notifications'  => [
                'senderName'       => $this->getSetting('notifications', 'senderName'),
                'senderEmail'      => $this->getSetting('notifications', 'senderEmail'),
                'notifyCustomers'  => $this->getSetting('notifications', 'notifyCustomers'),
                'cancelSuccessUrl' => $this->getSetting('notifications', 'cancelSuccessUrl'),
                'cancelErrorUrl'   => $this->getSetting('notifications', 'cancelErrorUrl'),
                'smsSignedIn'      => $this->getSetting('notifications', 'smsSignedIn'),
                'bccEmail'         => $this->getSetting('notifications', 'bccEmail'),
            ],
            'payments'       => [
                'currency'              => $this->getSetting('payments', 'currency'),
                'priceSymbolPosition'   => $this->getSetting('payments', 'priceSymbolPosition'),
                'priceNumberOfDecimals' => $this->getSetting('payments', 'priceNumberOfDecimals'),
                'priceSeparator'        => $this->getSetting('payments', 'priceSeparator'),
                'defaultPaymentMethod'  => $this->getSetting('payments', 'defaultPaymentMethod'),
                'onSite'                => $this->getSetting('payments', 'onSite'),
                'coupons'               => $this->getSetting('payments', 'coupons'),
                'payPal'                => [
                    'enabled'         => $this->getSetting('payments', 'payPal')['enabled'],
                    'sandboxMode'     => $this->getSetting('payments', 'payPal')['sandboxMode'],
                    'testApiClientId' => $this->getSetting('payments', 'payPal')['testApiClientId'],
                    'liveApiClientId' => $this->getSetting('payments', 'payPal')['liveApiClientId'],
                ],
                'stripe'                => [
                    'enabled'            => $this->getSetting('payments', 'stripe')['enabled'],
                    'testMode'           => $this->getSetting('payments', 'stripe')['testMode'],
                    'livePublishableKey' => $this->getSetting('payments', 'stripe')['livePublishableKey'],
                    'testPublishableKey' => $this->getSetting('payments', 'stripe')['testPublishableKey']

                ],
                'wc'                    => [
                    'enabled'   => $this->getSetting('payments', 'wc')['enabled'],
                    'productId' => $this->getSetting('payments', 'wc')['productId']
                ]
            ],
            'role'           => $userType,
            'weekSchedule'   => $this->getCategorySettings('weekSchedule'),
            'wordpress'      => [
                'dateFormat'  => $this->getSetting('wordpress', 'dateFormat'),
                'timeFormat'  => $this->getSetting('wordpress', 'timeFormat'),
                'startOfWeek' => (int)$this->getSetting('wordpress', 'startOfWeek')
            ],
            'labels'         => [
                'enabled' => $this->getSetting('labels', 'enabled')
            ],
            'roles'          => [
                'allowConfigureSchedule'      => $this->getSetting('roles', 'allowConfigureSchedule'),
                'allowConfigureDaysOff'       => $this->getSetting('roles', 'allowConfigureDaysOff'),
                'allowConfigureSpecialDays'   => $this->getSetting('roles', 'allowConfigureSpecialDays'),
                'allowWriteAppointments'      => $this->getSetting('roles', 'allowWriteAppointments'),
                'automaticallyCreateCustomer' => $this->getSetting('roles', 'automaticallyCreateCustomer'),
                'inspectCustomerInfo'         => $this->getSetting('roles', 'inspectCustomerInfo'),
                'allowCustomerReschedule'     => $this->getSetting('roles', 'allowCustomerReschedule'),
                'allowWriteEvents'            => $this->getSetting('roles', 'allowWriteEvents'),
            ],
            'customization'  => $this->getCategorySettings('customization'),
            'appointments'   => $this->getCategorySettings('appointments'),
            'slotDateConstraints' => [
                'minDate' => DateTimeService::getNowDateTimeObject()
                    ->modify("+{$this->getSetting('general', 'minimumTimeRequirementPriorToBooking')} seconds")
                    ->format('Y-m-d H:i:s'),
                'maxDate' => DateTimeService::getNowDateTimeObject()
                    ->modify("+{$this->getSetting('general', 'numberOfDaysAvailableForBooking')} day")
                    ->format('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * @param $settingCategoryKey
     * @param $settingKey
     * @param $settingValue
     *
     * @return mixed|void
     */
    public function setSetting($settingCategoryKey, $settingKey, $settingValue)
    {
        $this->settingsCache[$settingCategoryKey][$settingKey] = $settingValue;
        $settingsCopy = $this->settingsCache;
        unset($settingsCopy['wordpress']);
        update_option('amelia_settings', json_encode($settingsCopy));
    }

    /**
     * @param $settingCategoryKey
     * @param $settingValues
     *
     * @return mixed|void
     */
    public function setCategorySettings($settingCategoryKey, $settingValues)
    {
        $this->settingsCache[$settingCategoryKey] = $settingValues;
        $settingsCopy = $this->settingsCache;
        unset($settingsCopy['wordpress']);
        update_option('amelia_settings', json_encode($settingsCopy));
    }

    /**
     * @param array $settings
     *
     * @return mixed|void
     */
    public function setAllSettings($settings)
    {
        foreach ($settings as $settingCategoryKey => $settingValues) {
            $this->settingsCache[$settingCategoryKey] = $settingValues;
        }
        $settingsCopy = $this->settingsCache;
        unset($settingsCopy['wordpress']);
        update_option('amelia_settings', json_encode($settingsCopy));
    }
}
