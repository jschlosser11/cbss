<?php

/** @noinspection PhpUnusedParameterInspection */

/**
 * Database hook for activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use WP_Error;
use WP_Upgrader;

/**
 * Class AutoUpdateHook
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class AutoUpdateHook
{
    /**
     * Add our self-hosted auto update plugin to the filter transient
     *
     * @param $transient
     *
     * @return object $ transient
     */
    public static function checkUpdate($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        /** @var string $purchaseCode */
        $purchaseCode = $settingsService->getSetting('activation', 'purchaseCodeStore');

        /** @var string $envatoTokenEmail */
        $envatoTokenEmail = $settingsService->getSetting('activation', 'envatoTokenEmail');

        // Get the remote info
        $remoteInformation = self::getRemoteInformation($purchaseCode, $envatoTokenEmail);

        // If a newer version is available, add the update
        if ($remoteInformation) {
            if (isset($remoteInformation->force_deactivate) && $remoteInformation->force_deactivate === true) {
                $settingsService->setSetting('activation', 'active', false);
                $settingsService->setSetting('activation', 'purchaseCodeStore', '');
                $settingsService->setSetting('activation', 'envatoTokenEmail', '');
            }

            if ($remoteInformation && version_compare(AMELIA_VERSION, $remoteInformation->new_version, '<')) {
                $transient->response[AMELIA_PLUGIN_SLUG] = $remoteInformation;
            }
        }

        return $transient;
    }

    /**
     * Add our self-hosted description to the filter
     *
     * @param bool  $response
     * @param array $action
     * @param       $args
     *
     * @return bool|object
     */
    public static function checkInfo($response, $action, $args)
    {
        if ('plugin_information' !== $action) {
            return $response;
        }

        if (empty($args->slug)) {
            return $response;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        /** @var string $purchaseCode */
        $purchaseCode = $settingsService->getSetting('activation', 'purchaseCodeStore');

        /** @var string $envatoTokenEmail */
        $envatoTokenEmail = $settingsService->getSetting('activation', 'envatoTokenEmail');

        if ($args->slug === AMELIA_PLUGIN_SLUG) {
            return self::getRemoteInformation($purchaseCode, $envatoTokenEmail);
        }

        return $response;
    }

    /**
     * Add a message for unavailable auto update on plugins page if plugin is not activated
     */
    public static function addMessageOnPluginsPage()
    {
        /** @var SettingsService $settingsService */
        $settingsService = new SettingsService(new SettingsStorage());

        /** @var bool $activated */
        $activated = $settingsService->getSetting('activation', 'active');

        /** @var array $settingsStrings */
        $settingsStrings = BackendStrings::getSettingsStrings();

        /** @var string $url */
        $url = AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-settings&activeSetting=activation';

        /** @var string $redirect */
        $redirect = '<a href="' . $url . '" target="_blank">' . $settingsStrings['settings_lower'] . '</a>';

        if (!$activated) {
            echo sprintf(' ' . $settingsStrings['plugin_not_activated'], $redirect);
        }
    }

    /**
     * Add error message on plugin update if plugin is not activated
     *
     * @param bool        $reply
     * @param string      $package
     * @param WP_Upgrader $updater
     *
     * @return WP_Error|string|bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function addMessageOnUpdate($reply, $package, $updater)
    {
        /** @var array $settingsStrings */
        $settingsStrings = BackendStrings::getSettingsStrings();

        /** @var string $url */
        $url = AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-settings&activeSetting=activation';

        /** @var string $redirect */
        $redirect = '<a href="' . $url . '" target="_blank">' . $settingsStrings['settings_lower'] . '</a>';

        if (!$package) {
            return new WP_Error(
                'amelia_not_activated',
                sprintf(' ' . $settingsStrings['plugin_not_activated'], $redirect)
            );
        }

        return $reply;
    }

    /**
     * Get information about the remote version
     *
     * @param string $purchaseCode
     * @param string $envatoTokenEmail
     *
     * @return bool|object
     */
    private static function getRemoteInformation($purchaseCode, $envatoTokenEmail)
    {
        $request = wp_remote_post(
            AMELIA_STORE_API_URL . 'autoupdate/info',
            [
                'body' => [
                    'slug'             => 'ameliabooking',
                    'purchaseCode'     => $purchaseCode,
                    'envatoTokenEmail' => $envatoTokenEmail,
                    'domain'           => self::extractDomain(
                        $_SERVER['SERVER_NAME']
                    ),
                    'subdomain'        => self::extractSubdomain(
                        $_SERVER['SERVER_NAME']
                    )
                ]
            ]
        );

        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return unserialize(json_decode($request['body'])->info);
        }

        return false;
    }

    /**
     * @param $domain
     *
     * @return mixed
     */
    private static function extractDomain($domain)
    {
        if (preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches)) {
            return $matches['domain'];
        }

        return $domain;
    }

    /**
     * @param $domain
     *
     * @return string
     */
    private static function extractSubdomain($domain)
    {
        $subdomains = $domain;
        $domain = self::extractDomain($subdomains);
        $subdomains = rtrim(strstr($subdomains, $domain, true), '.');

        return $subdomains;
    }
}
