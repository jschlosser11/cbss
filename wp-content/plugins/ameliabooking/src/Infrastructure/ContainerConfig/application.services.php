<?php
/**
 * Assembling application services:
 * Instantiating application services and injecting the Infrastructure layer implementations
 */

use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\CustomField\CustomFieldApplicationService;
use AmeliaBooking\Application\Services\Gallery\GalleryApplicationService;
use AmeliaBooking\Application\Services\Location\LocationApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\Reservation\AppointmentReservationService;
use AmeliaBooking\Application\Services\Reservation\EventReservationService;
use AmeliaBooking\Application\Services\Reservation\ReservationService;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Infrastructure\Common\Container;

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Customer service
 *
 * @param Container $c
 *
 * @return UserApplicationService
 */
$entries['application.user.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\User\UserApplicationService($c);
};

/**
 * Provider service
 *
 * @param Container $c
 *
 * @return ProviderApplicationService
 */
$entries['application.user.provider.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\User\ProviderApplicationService($c);
};

/**
 * Customer service
 *
 * @param Container $c
 *
 * @return CustomerApplicationService
 */
$entries['application.user.customer.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\User\CustomerApplicationService($c);
};

/**
 * Location service
 *
 * @return \AmeliaBooking\Application\Services\Location\CurrentLocation
 */
$entries['application.currentLocation.service'] = function () {
    return new AmeliaBooking\Application\Services\Location\CurrentLocation();
};

/**
 * Appointment service
 *
 * @param Container $c
 *
 * @return AppointmentApplicationService
 */
$entries['application.booking.appointment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Booking\AppointmentApplicationService($c);
};

/**
 * Event service
 *
 * @param Container $c
 *
 * @return EventApplicationService
 */
$entries['application.booking.event.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Booking\EventApplicationService($c);
};

/**
 * Reservation service
 *
 * @param Container $c
 *
 * @return ReservationService
 */
$entries['application.reservation.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Reservation\ReservationService($c);
};

/**
 * Appointment Reservation service
 *
 * @param Container $c
 *
 * @return AppointmentReservationService
 */
$entries['application.reservation.appointment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Reservation\AppointmentReservationService($c);
};

/**
 * Event Reservation service
 *
 * @param Container $c
 *
 * @return EventReservationService
 */
$entries['application.reservation.event.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Reservation\EventReservationService($c);
};

/**
 * Booking service
 *
 * @param Container $c
 *
 * @return BookingApplicationService
 */
$entries['application.booking.booking.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Booking\BookingApplicationService($c);
};

/**
 * Bookable service
 *
 * @param Container $c
 *
 * @return BookableApplicationService
 */
$entries['application.bookable.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Bookable\BookableApplicationService($c);
};

/**
 * Gallery service
 *
 * @param Container $c
 *
 * @return GalleryApplicationService
 */
$entries['application.gallery.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Gallery\GalleryApplicationService($c);
};

/**
 * Calendar service
 *
 * @param Container $c
 *
 * @return TimeSlotService
 */
$entries['application.timeSlot.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\TimeSlot\TimeSlotService($c);
};

/**
 * Coupon service
 *
 * @param Container $c
 *
 * @return CouponApplicationService
 */
$entries['application.coupon.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Coupon\CouponApplicationService($c);
};

/**
 * Location service
 *
 * @param Container $c
 *
 * @return LocationApplicationService
 */
$entries['application.location.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Location\LocationApplicationService($c);
};

/**
 * Email Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Notification\EmailNotificationService
 */
$entries['application.emailNotification.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\EmailNotificationService($c, 'email');
};

/**
 * Email Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Notification\SMSNotificationService
 */
$entries['application.smsNotification.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\SMSNotificationService($c, 'sms');
};

/**
 * Appointment Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Placeholder\AppointmentPlaceholderService
 */
$entries['application.placeholder.appointment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Placeholder\AppointmentPlaceholderService($c);
};

/**
 * Event Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Placeholder\EventPlaceholderService
 */
$entries['application.placeholder.event.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Placeholder\EventPlaceholderService($c);
};

/**
 * Stats Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Stats\StatsService
 */
$entries['application.stats.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Stats\StatsService($c);
};

/**
 * Helper Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Helper\HelperService
 */
$entries['application.helper.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Helper\HelperService($c);
};

/**
 * Settings Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Settings\SettingsService
 */
$entries['application.settings.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Settings\SettingsService($c);
};

/**
 * SMS API Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Notification\SMSAPIService
 */
$entries['application.smsApi.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\SMSAPIService($c);
};

/**
 * Payment service
 *
 * @param Container $c
 *
 * @return PaymentApplicationService
 */
$entries['application.payment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Payment\PaymentApplicationService($c);
};

/**
 * Upload File Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\CustomField\CustomFieldApplicationService
 */
$entries['application.customField.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\CustomField\CustomFieldApplicationService($c);
};

/**
 * Web Hook Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\WebHook\WebHookApplicationService
 */
$entries['application.webHook.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\WebHook\WebHookApplicationService($c);
};
