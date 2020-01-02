<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\WebHook;

use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class WebHookApplicationService
 *
 * @package AmeliaBooking\Application\Services\WebHook
 */
class WebHookApplicationService
{
    /** @var Container $container */
    private $container;

    /**
     * AppointmentApplicationService constructor.
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
     * @param string   $action
     * @param array    $reservation
     * @param array    $bookings
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function process($action, $reservation, $bookings) {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $this->container->get('application.booking.booking.service');

        $webHooks = $settingsService->getCategorySettings('webHooks');

        $hasHooks = false;

        foreach ((array)$webHooks as $webHook) {
            if ($webHook['action'] === $action && $webHook['type'] === $reservation['type']) {
                $hasHooks = true;
                break;
            }
        }

        if ($hasHooks) {
            $reservationEntity = $bookingApplicationService->getReservationEntity($reservation);

            $affectedBookingEntities = new Collection();

            foreach ($bookings as $booking) {
                $affectedBookingEntities->addItem($bookingApplicationService->getBookingEntity($booking));
            }

            switch ($reservation['type']) {
                case Entities::APPOINTMENT:
                    if ($reservationEntity->getProvider()->getGoogleCalendar()) {
                        $reservationEntity->getProvider()->getGoogleCalendar()->setToken(new Token(''));
                    }

                    break;

                case Entities::EVENT:
                    break;
            }

            foreach ((array)$webHooks as $webHook) {
                if ($webHook['action'] === $action && $webHook['type'] === $reservation['type']) {
                    $ch = curl_init($webHook['url']);

                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        $reservationEntity->getType()->getValue() => $reservationEntity->toArray(),
                        Entities::BOOKINGS                        => $affectedBookingEntities->toArray()
                    ], JSON_FORCE_OBJECT));

                    curl_exec($ch);

                    curl_close($ch);
                }
            }
        }
    }
}
