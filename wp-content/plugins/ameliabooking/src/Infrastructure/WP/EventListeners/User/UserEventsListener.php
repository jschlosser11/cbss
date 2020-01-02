<?php
/**
 * Handle WP part of user-related events
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\User;

use League\Event\ListenerInterface;
use League\Event\EventInterface;

/**
 * Class UserEventsListener
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\User
 */
class UserEventsListener implements ListenerInterface
{
    /**
     * Check if provided argument is the listener
     *
     * @param mixed $listener
     *
     * @return bool
     */
    public function isListener($listener)
    {
        return $listener === $this;
    }

    /**
     * Handle events
     *
     * @param EventInterface $event
     * @param mixed          $param
     */
    public function handle(EventInterface $event, $param = null)
    {
        // Handling the event
        switch ($event->getName()) {
            case 'user.added':
                UserAddedEventHandler::handle($param);
                break;
        }
    }
}
