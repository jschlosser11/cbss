<?php
/**
 * Assembling infrastructure services:
 * Instantiating infrastructure services
 */

use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Services\Notification\MailerFactory;
use AmeliaBooking\Infrastructure\Services\Notification\MailgunService;
use AmeliaBooking\Infrastructure\Services\Notification\PHPMailService;
use AmeliaBooking\Infrastructure\Services\Notification\SMTPService;
use AmeliaBooking\Infrastructure\Services\Notification\WpMailService;

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Mailer Service
 *
 * @param Container $c
 *
 * @return MailgunService|PHPMailService|SMTPService|WpMailService
 */
$entries['infrastructure.mail.service'] = function ($c) {
    return MailerFactory::create($c->get('domain.settings.service'));
};

/**
 * Report Service
 *
 * @return AmeliaBooking\Infrastructure\Services\Report\Spout\CsvService
 */
$entries['infrastructure.report.csv.service'] = function () {
    return new AmeliaBooking\Infrastructure\Services\Report\Spout\CsvService();
};

/**
 * PayPal Payment Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Payment\PayPalService
 * @throws \Interop\Container\Exception\ContainerException
 */
$entries['infrastructure.payment.payPal.service'] = function ($c) {
    return new AmeliaBooking\Infrastructure\Services\Payment\PayPalService(
        $c->get('domain.settings.service')
    );
};

/**
 * Stripe Payment Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Payment\StripeService
 * @throws \Interop\Container\Exception\ContainerException
 */
$entries['infrastructure.payment.stripe.service'] = function ($c) {
    return new AmeliaBooking\Infrastructure\Services\Payment\StripeService(
        $c->get('domain.settings.service')
    );
};

/**
 * Currency Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Payment\CurrencyService
 * @throws \Interop\Container\Exception\ContainerException
 */
$entries['infrastructure.payment.currency.service'] = function ($c) {
    return new AmeliaBooking\Infrastructure\Services\Payment\CurrencyService(
        $c->get('domain.settings.service')
    );
};

/**
 * Less Parser Service
 *
 * @return AmeliaBooking\Infrastructure\Services\Frontend\LessParserService
 */
$entries['infrastructure.frontend.lessParser.service'] = function () {
    return new AmeliaBooking\Infrastructure\Services\Frontend\LessParserService(
        AMELIA_PATH . '/assets/less/frontend/amelia-booking.less',
        'amelia-booking.css',
        UPLOADS_PATH . '/amelia/css'
    );
};

/**
 * Google Calendar Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Infrastructure\Services\Google\GoogleCalendarService
 */
$entries['infrastructure.google.calendar.service'] = function ($c) {
    return new AmeliaBooking\Infrastructure\Services\Google\GoogleCalendarService($c);
};
