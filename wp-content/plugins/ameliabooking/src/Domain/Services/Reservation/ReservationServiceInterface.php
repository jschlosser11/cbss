<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Services\Reservation;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\AbstractBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Booking\Validator;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Interface ReservationServiceInterface
 *
 * @package AmeliaBooking\Domain\Services\Reservation
 */
interface ReservationServiceInterface
{
    /**
     * @param int    $bookingId
     * @param string $requestedStatus
     * @param string $token
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws BookingCancellationException
     */
    public function updateStatus($bookingId, $requestedStatus, $token);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param int       $bookingId
     * @param array     $paymentData
     * @param float     $amount
     * @param \DateTime $dateTime
     *
     * @return boolean
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function addPayment($bookingId, $paymentData, $amount, $dateTime);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array     $data
     * @param Validator $validator
     * @param bool      $save
     *
     * @return CommandResult
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function process($data, $validator, $save);

    /**
     * @param array $appointmentData
     * @param bool  $inspectTimeSlot
     * @param bool  $save
     *
     * @return Reservation
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function book($appointmentData, $inspectTimeSlot, $save);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param CommandResult $result
     * @param array         $appointmentData
     * @param Validator     $validator
     * @param bool          $save
     *
     * @return Reservation|null
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function processBooking($result, $appointmentData, $validator, $save);

    /**
     * @param CommandResult $result
     * @param Reservation   $reservation
     * @param BookingType   $bookingType
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function finalize($result, $reservation, $bookingType);

    /**
     * @param CustomerBooking  $booking
     * @param AbstractBookable $bookable
     *
     * @return float
     *
     * @throws InvalidArgumentException
     */
    public function getPaymentAmount($booking, $bookable);

    /**
     * @param AbstractBooking  $reservation
     * @param CustomerBooking  $booking
     * @param AbstractBookable $bookable
     *
     * @return array
     */
    public function getBookingPeriods($reservation, $booking, $bookable);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param array $data
     *
     * @return AbstractBookable
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getBookableEntity($data);

    /**
     * @param Service|Event $bookable
     *
     * @return boolean
     */
    public function isAggregatedPrice($bookable);

    /**
     * @param BooleanValueObject $bookableAggregatedPrice
     * @param BooleanValueObject $extraAggregatedPrice
     *
     * @return boolean
     */
    public function isExtraAggregatedPrice($extraAggregatedPrice, $bookableAggregatedPrice);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param CustomerBooking  $booking
     * @param AbstractBookable $bookable
     * @param AbstractBooking  $reservation
     * @param string           $paymentGateway
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getInfo($bookable, $booking, $reservation, $paymentGateway);

    /**
     * @param int $id
     *
     * @return Appointment|Event
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function getReservationById($id);

    /**
     * @param int $id
     *
     * @return Appointment|Event
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function getReservationByBookingId($id);

    /**
     * @param Appointment|Event $reservation
     * @param Service|Event $bookable
     * @param \DateTime $dateTime
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function isBookable($reservation, $bookable, $dateTime);

    /**
     * @param CustomerBooking $booking
     * @param string          $token
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws AccessDeniedException
     */
    function inspectToken($booking, $token);

    /**
     * @param \DateTime $bookingStart
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws BookingCancellationException
     */
    function inspectMinimumCancellationTime($bookingStart);
}
