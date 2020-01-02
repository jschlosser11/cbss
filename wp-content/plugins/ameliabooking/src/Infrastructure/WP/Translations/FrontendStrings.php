<?php

namespace AmeliaBooking\Infrastructure\WP\Translations;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;

/**
 * Class FrontendStrings
 *
 * @package AmeliaBooking\Infrastructure\WP\Translations
 *
 * @phpcs:disable
 */
class FrontendStrings
{
    /** @var array */
    private static $settings;

    /**
     * Set Settings
     *
     * @return array|mixed
     */
    public static function getLabelsFromSettings()
    {
        if (!self::$settings) {
            self::$settings = new SettingsService(new SettingsStorage());
        }

        if (self::$settings->getSetting('labels', 'enabled') === true) {
            $labels = self::$settings->getCategorySettings('labels');
            unset($labels['enabled']);

            return $labels;
        }

        return [];
    }

    /**
     * Return all strings for frontend
     *
     * @return array
     */
    public static function getAllStrings()
    {
        return array_merge(
            self::getCommonStrings(),
            self::getBookingStrings(),
            self::getCatalogStrings(),
            self::getSearchStrings(),
            self::getLabelsFromSettings(),
            self::getEventStrings()
        );
    }

    /**
     * Returns the array of the common frontend strings
     *
     * @return array
     */
    public static function getCommonStrings()
    {
        return [
            'add_coupon'                   => __('Add Coupon', 'wpamelia'),
            'add_to_calendar'              => __('Add to Calendar', 'wpamelia'),
            'appointment_info'             => __('Appointment Info', 'wpamelia'),
            'back'                         => __('Back', 'wpamelia'),
            'base_price_colon'             => __('Base Price:', 'wpamelia'),
            'booking_completed_approved'   => __('Thank you! Your booking is completed.', 'wpamelia'),
            'booking_completed_email'      => __('An email with details of your booking has been sent to you.', 'wpamelia'),
            'booking_completed_pending'    => __('Thank you! Your booking is completed and now is pending confirmation.', 'wpamelia'),
            'cancel'                       => __('Cancel', 'wpamelia'),
            'canceled'                     => __('Canceled', 'wpamelia'),
            'capacity_colon'               => __('Capacity:', 'wpamelia'),
            'client_time_colon'            => __('Client Time:', 'wpamelia'),
            'closed'                       => __('Closed', 'wpamelia'),
            'confirm'                      => __('Confirm', 'wpamelia'),
            'congratulations'              => __('Congratulations', 'wpamelia'),
            'coupon_invalid'               => __('This coupon is not valid anymore', 'wpamelia'),
            'coupon_send_text'             => __('You can use this coupon for next booking: ', 'wpamelia'),
            'coupon_missing'               => __('Please enter coupon', 'wpamelia'),
            'coupon_unknown'               => __('The coupon you entered is not valid', 'wpamelia'),
            'coupon_used'                  => __('Used coupon', 'wpamelia'),
            'credit_card'                  => __('Credit Card', 'wpamelia'),
            'credit_or_debit_card_colon'   => __('Credit or debit card:', 'wpamelia'),
            'custom_fields'                => __('Custom Fields', 'wpamelia'),
            'customer_already_booked'      => __('You have already booked this appointment', 'wpamelia'),
            'date_colon'                   => __('Date:', 'wpamelia'),
            'discount_amount_colon'        => __('Discount:', 'wpamelia'),
            'duration_colon'               => __('Duration:', 'wpamelia'),
            'event_info'                   => __('Event Info', 'wpamelia'),
            'email_colon'                  => __('Email:', 'wpamelia'),
            'email_exist_error'            => __('Email already exists with different name. Please check your name.', 'wpamelia'),
            'email_not_sent_error'         => __('Unfortunately a server error occurred and your email was not sent.', 'wpamelia'),
            'email_placeholder'            => __('example@mail.com', 'wpamelia'),
            'employee'                     => __('employee', 'wpamelia'),
            'employees'                    => __('employees', 'wpamelia'),
            'enter_email_warning'          => __('Please enter email', 'wpamelia'),
            'enter_first_name_warning'     => __('Please enter first name', 'wpamelia'),
            'enter_last_name_warning'      => __('Please enter last name', 'wpamelia'),
            'enter_phone_warning'          => __('Please enter phone number', 'wpamelia'),
            'enter_valid_email_warning'    => __('Please enter a valid email address', 'wpamelia'),
            'enter_valid_phone_warning'    => __('Please enter a valid phone number', 'wpamelia'),
            'extras_costs_colon'           => __('Extras Cost:', 'wpamelia'),
            'finish_appointment'           => __('Finish', 'wpamelia'),
            'first_name_colon'             => __('First Name:', 'wpamelia'),
            'file_upload'                  => __('Drop file here or click to upload', 'wpamelia'),
            'file_upload_error'            => __('Please upload the file', 'wpamelia'),
            'forbidden_file_upload'        => __('This file is forbidden for upload', 'wpamelia'),
            'h'                            => __('h', 'wpamelia'),
            'incomplete_cvc'               => __('Your card\'s security code is incomplete', 'wpamelia'),
            'incomplete_expiry'            => __('Your card\'s expiration date is incomplete', 'wpamelia'),
            'incomplete_number'            => __('Your card number is incomplete', 'wpamelia'),
            'incomplete_zip'               => __('Your postal code is incomplete', 'wpamelia'),
            'invalid_expiry_year_past'     => __('Your card\'s expiration year is in the past', 'wpamelia'),
            'invalid_number'               => __('Your card number is invalid', 'wpamelia'),
            'last_name_colon'              => __('Last Name:', 'wpamelia'),
            'location_colon'               => __('Location:', 'wpamelia'),
            'maximum_capacity_reached'     => __('Maximum capacity is reached', 'wpamelia'),
            'min'                          => __('min', 'wpamelia'),
            'no'                           => __('No', 'wpamelia'),
            'number_of_additional_persons' => __('Number of Additional Persons:', 'wpamelia'),
            'on_site'                      => __('On-site', 'wpamelia'),
            'pay_pal'                      => __('PayPal', 'wpamelia'),
            'payment_error'                => __('Sorry, there was an error processing your payment. Please try again later.', 'wpamelia'),
            'payment_method_colon'         => __('Payment Method:', 'wpamelia'),
            'persons'                      => __('persons', 'wpamelia'),
            'phone_colon'                  => __('Phone:', 'wpamelia'),
            'please_wait'                  => __('Please Wait', 'wpamelia'),
            'price_colon'                  => __('Price:', 'wpamelia'),
            'required_field'               => __('This field is required', 'wpamelia'),
            'open'                         => __('Open', 'wpamelia'),
            'select_calendar'              => __('Select Calendar', 'wpamelia'),
            'service'                      => __('service', 'wpamelia'),
            'services'                     => __('services', 'wpamelia'),
            'stripe'                       => __('Stripe', 'wpamelia'),
            'subtotal_colon'               => __('Subtotal:', 'wpamelia'),
            'time_colon'                   => __('Local Time:', 'wpamelia'),
            'time_slot_unavailable'        => __('Time slot is unavailable', 'wpamelia'),
            'total_cost_colon'             => __('Total Cost:', 'wpamelia'),
            'total_number_of_persons'      => __('Total Number of Persons:', 'wpamelia'),
            'waiting_for_payment'          => __('Waiting for payment', 'wpamelia'),
            'wc'                           => __('On-line', 'wpamelia'),
            'wc_appointment_is_removed'    => __('Appointment is removed from the cart.', 'wpamelia'),
            'wc_appointment_remove'        => __('On-line', 'wpamelia'),
            'wc_error'                     => __('Sorry, there was an error while adding booking to WooCommerce cart.', 'wpamelia'),
            'wc_product_name'              => __('Appointment', 'wpamelia'),
        ];
    }

    /**
     * Returns the array of the frontend strings for the search shortcode
     *
     * @return array
     */
    public static function getSearchStrings()
    {
        return [
            'appointment_date_colon'  => __('Appointment Date:', 'wpamelia'),
            'book'                    => __('Book', 'wpamelia'),
            'bringing_anyone'         => __('Bringing anyone with you?', 'wpamelia'),
            'enter_appointment_date'  => __('Please enter appointment date...', 'wpamelia'),
            'from'                    => __('From', 'wpamelia'),
            'name_asc'                => __('Name Ascending', 'wpamelia'),
            'name_desc'               => __('Name Descending', 'wpamelia'),
            'next'                    => __('Next', 'wpamelia'),
            'no_results_found'        => __('No results found...', 'wpamelia'),
            'of'                      => __('of', 'wpamelia'),
            'price_asc'               => __('Price Ascending', 'wpamelia'),
            'price_desc'              => __('Price Descending', 'wpamelia'),
            'refine_search'           => __('Please refine your search criteria', 'wpamelia'),
            'results'                 => __('results', 'wpamelia'),
            'search'                  => __('Search...', 'wpamelia'),
            'search_filters'          => __('Search Filters', 'wpamelia'),
            'search_results'          => __('Search Results', 'wpamelia'),
            'select'                  => __('Select', 'wpamelia'),
            'select_appointment_time' => __('Select the Appointment Time', 'wpamelia'),
            'select_extras'           => __('Select the Extras you\'d like', 'wpamelia'),
            'select_location'         => __('Select Location', 'wpamelia'),
            'showing'                 => __('Showing', 'wpamelia'),
            'time_range_colon'        => __('Time Range:', 'wpamelia'),
            'to_lower'                => __('to', 'wpamelia'),
            'to_upper'                => __('To', 'wpamelia'),
        ];
    }

    /**
     * Returns the array of the frontend strings for the booking shortcode
     *
     * @return array
     */
    public static function getBookingStrings()
    {
        return [
            'add_extra'                => __('Add extra', 'wpamelia'),
            'any'                      => __('Any', 'wpamelia'),
            'any_employee'             => __('Any Employee', 'wpamelia'),
            'book_appointment'         => __('Book Appointment', 'wpamelia'),
            'bringing_anyone_with_you' => __('Bringing anyone with you?', 'wpamelia'),
            'continue'                 => __('Continue', 'wpamelia'),
            'extra_colon'              => __('Extra:', 'wpamelia'),
            'person_upper'             => __('Person', 'wpamelia'),
            'persons_upper'            => __('Persons', 'wpamelia'),
            'pick_date_and_time_colon' => __('Pick date & time:', 'wpamelia'),
            'please_select'            => __('Please select', 'wpamelia'),
            'qty_colon'                => __('Qty:', 'wpamelia'),
        ];
    }

    /**
     * Returns the array of the frontend strings for the event shortcode
     *
     * @return array
     */
    public static function getEventStrings()
    {
        return [
            'event'                        => __('event', 'wpamelia'),
            'events'                       => __('events', 'wpamelia'),
            'event_about'                  => __('About this Event', 'wpamelia'),
            'event_free'                   => __('Free', 'wpamelia'),
            'event_book'                   => __('Book this event', 'wpamelia'),
            'event_book_persons'           => __('Number of persons', 'wpamelia'),
            'event_pick_min_date'          => __('Show from date', 'wpamelia'),
            'event_type'                   => __('Event Type', 'wpamelia'),
            'event_capacity'               => __('Capacity:', 'wpamelia'),
        ];
    }

    /**
     * Returns the array of the frontend strings for the catalog shortcode
     *
     * @return array
     */
    public static function getCatalogStrings()
    {
        return [
            'booking_appointment'    => __('Booking Appointment', 'wpamelia'),
            'buffer_time'            => __('Buffer Time', 'wpamelia'),
            'categories'             => __('Categories', 'wpamelia'),
            'category_colon'         => __('Category:', 'wpamelia'),
            'description'            => __('Description', 'wpamelia'),
            'description_colon'      => __('Description:', 'wpamelia'),
            'extras'                 => __('Extras', 'wpamelia'),
            'info'                   => __('Info', 'wpamelia'),
            'maximum_quantity_colon' => __('Maximum Quantity:', 'wpamelia'),
            'view_more'              => __('View More', 'wpamelia'),
        ];
    }
}
