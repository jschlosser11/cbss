<?php

namespace AmeliaBooking\Application\Services\Helper;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class HelperService
 *
 * @package AmeliaBooking\Application\Services\Helper
 */
class HelperService
{
    private $container;

    /**
     * HelperService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Returns formatted price based on price plugin settings
     *
     * @param int|float $price
     *
     * @return string
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getFormattedPrice($price)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $paymentSettings = $settingsService->getCategorySettings('payments');

        // Price Separators
        $thousandSeparatorMap = [',', '.', ' ', ' '];
        $decimalSeparatorMap = ['.', ',', '.', ','];

        $thousandSeparator = $thousandSeparatorMap[$paymentSettings['priceSeparator'] - 1];
        $decimalSeparator = $decimalSeparatorMap[$paymentSettings['priceSeparator'] - 1];

        // Price Prefix
        $pricePrefix = '';
        if ($paymentSettings['priceSymbolPosition'] === 'before') {
            $pricePrefix = $paymentSettings['symbol'];
        } elseif ($paymentSettings['priceSymbolPosition'] === 'beforeWithSpace') {
            $pricePrefix = $paymentSettings['symbol'] . ' ';
        }

        // Price Suffix
        $priceSuffix = '';
        if ($paymentSettings['priceSymbolPosition'] === 'after') {
            $priceSuffix = $paymentSettings['symbol'];
        } elseif ($paymentSettings['priceSymbolPosition'] === 'afterWithSpace') {
            $priceSuffix = ' ' . $paymentSettings['symbol'];
        }

        $formattedNumber = number_format(
            $price,
            $paymentSettings['priceNumberOfDecimals'],
            $decimalSeparator,
            $thousandSeparator
        );

        return $pricePrefix . $formattedNumber . $priceSuffix;
    }

    /**
     * @param $seconds
     *
     * @return string
     */
    public function secondsToNiceDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = $seconds / 60 % 60;

        $duration =
            ($hours ? ($hours . 'h ') : '') . ($hours && $minutes ? ' ' : '') . ($minutes ? ($minutes . 'min') : '');

        return $duration;
    }
}
