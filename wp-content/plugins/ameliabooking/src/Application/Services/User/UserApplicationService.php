<?php

namespace AmeliaBooking\Application\Services\User;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\UserService\CreateWPUser;

/**
 * Class UserApplicationService
 *
 * @package AmeliaBooking\Application\Services\User
 */
class UserApplicationService
{
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
     *
     * @param int $userId
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getAppointmentsCountForUser($userId)
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var AbstractUser $user */
        $user = $userRepository->getById($userId);

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var Collection $appointments */
        $appointments = null;

        switch ($user->getType()) {
            case (AbstractUser::USER_ROLE_PROVIDER):
                $appointments = $appointmentRepo->getFiltered(['providerId' => $userId]);

                break;
            case (AbstractUser::USER_ROLE_CUSTOMER):
                $appointments = $appointmentRepo->getFiltered(['customerId' => $userId]);

                break;
        }

        $now = DateTimeService::getNowDateTimeObject();

        $futureAppointments = 0;
        $pastAppointments = 0;

        foreach ((array)$appointments->keys() as $appointmentKey) {
            if ($appointments->getItem($appointmentKey)->getBookingStart()->getValue() >= $now) {
                $futureAppointments++;
            } else {
                $pastAppointments++;
            }
        }

        return [
            'futureAppointments' => $futureAppointments,
            'pastAppointments'   => $pastAppointments
        ];
    }

    /**
     * @param int          $userId
     * @param AbstractUser $user
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function setWpUserIdForNewUser($userId, $user)
    {
        /** @var CreateWPUser $createWPUserService */
        $createWPUserService = $this->container->get('user.create.wp.user');

        $externalId = $createWPUserService->create(
            $user->getEmail()->getValue(),
            'wpamelia-' . $user->getType()
        );

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        if ($externalId && !$userRepository->findByExternalId($externalId)) {
            $user->setExternalId(new Id($externalId));
            $userRepository->update($userId, $user);
        }
    }

    /**
     * @param int          $userId
     * @param AbstractUser $user
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function setWpUserIdForExistingUser($userId, $user)
    {
        /** @var CreateWPUser $createWPUserService */
        $createWPUserService = $this->container->get('user.create.wp.user');

        $externalId = $user->getExternalId()->getValue();

        $createWPUserService->update(
            $externalId,
            'wpamelia-' . $user->getType()
        );

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        if ($externalId && !$userRepository->findByExternalId($externalId)) {
            $user->setExternalId(new Id($externalId));
            $userRepository->update($userId, $user);
        }
    }
}
