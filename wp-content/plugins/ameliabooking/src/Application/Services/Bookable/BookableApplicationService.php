<?php

namespace AmeliaBooking\Application\Services\Bookable;

use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Gallery\GalleryApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Schedule\Period;
use AmeliaBooking\Domain\Entity\Schedule\PeriodService;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDay;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriod;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriodService;
use AmeliaBooking\Domain\Entity\Schedule\WeekDay;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ProviderServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponServiceRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\PeriodServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayPeriodServiceRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;

/**
 * Class BookableApplicationService
 *
 * @package AmeliaBooking\Application\Services\Booking
 */
class BookableApplicationService
{

    private $container;

    /**
     * BookableApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param int $serviceId
     * @param int $providerId
     *
     * @return Service
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    public function getAppointmentService($serviceId, $providerId)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var Collection $providerServices */
        $providerServices = $serviceRepository->getProviderServicesWithExtras($serviceId, $providerId);

        return $providerServices->keyExists($serviceId) ?
            $providerServices->getItem($serviceId) : $serviceRepository->getById($serviceId);
    }

    /**
     * @param Collection $categories
     * @param Collection $services
     *
     * @throws InvalidArgumentException
     */
    public function addServicesToCategories($categories, $services)
    {
        /** @var Category $category */
        foreach ($categories->getItems() as $category) {
            $category->setServiceList(new Collection());
        }

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            $categoryId = $service->getCategoryId()->getValue();

            $categories
                ->getItem($categoryId)
                ->getServiceList()
                ->addItem($service, $service->getId()->getValue());
        }
    }

    /**
     * @param Service    $service
     * @param Collection $providers
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageProvidersForServiceAdd($service, $providers)
    {
        /** @var ProviderServiceRepository $providerServiceRepo */
        $providerServiceRepo = $this->container->get('domain.bookable.service.providerService.repository');

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            $providerServiceRepo->add($service, $provider->getId()->getValue());
        }
    }

    /**
     * @param Service $service
     * @param array   $serviceProvidersIds
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function manageProvidersForServiceUpdate($service, $serviceProvidersIds)
    {
        /** @var ProviderRepository $providerRepo */
        $providerRepo = $this->container->get('domain.users.providers.repository');
        /** @var ProviderServiceRepository $providerServiceRepo */
        $providerServiceRepo = $this->container->get('domain.bookable.service.providerService.repository');
        /** @var PeriodServiceRepository $periodServiceRepo */
        $periodServiceRepo = $this->container->get('domain.schedule.period.service.repository');
        /** @var SpecialDayPeriodServiceRepository $specialDayPeriodServiceRepo */
        $specialDayPeriodServiceRepo = $this->container->get('domain.schedule.specialDay.period.service.repository');

        /** @var Collection $providers */
        $serviceProviders = $providerRepo->getByCriteria(['services' => [$service->getId()->getValue()]]);

        $serviceId = $service->getId()->getValue();

        /** @var Provider $provider */
        foreach ($serviceProviders->getItems() as $provider) {
            $isServiceProvider = in_array($provider->getId()->getValue(), $serviceProvidersIds, false);

            if (!$isServiceProvider) {
                /** @var WeekDay $weekDay */
                foreach ($provider->getWeekDayList()->getItems() as $weekDay) {
                    /** @var Period $period */
                    foreach ($weekDay->getPeriodList()->getItems() as $period) {
                        /** @var PeriodService $periodService */
                        foreach ($period->getPeriodServiceList()->getItems() as $periodService) {
                            if ($periodService->getServiceId()->getValue() === $serviceId) {
                                $periodServiceRepo->delete($periodService->getId()->getValue());
                            }
                        }
                    }
                }

                /** @var SpecialDay $specialDay */
                foreach ($provider->getSpecialDayList()->getItems() as $specialDay) {
                    /** @var SpecialDayPeriod $period */
                    foreach ($specialDay->getPeriodList()->getItems() as $period) {
                        /** @var SpecialDayPeriodService $periodService */
                        foreach ($period->getPeriodServiceList()->getItems() as $periodService) {
                            if ($periodService->getServiceId()->getValue() === $serviceId) {
                                $specialDayPeriodServiceRepo->delete($periodService->getId()->getValue());
                            }
                        }
                    }
                }
            }
        }

        $providerServiceRepo->deleteAllNotInProvidersArrayForService($serviceProvidersIds, $serviceId);

        foreach ($serviceProvidersIds as $providerId) {
            if (!in_array($providerId, $serviceProviders->keys(), false)) {
                $providerServiceRepo->add($service, (int)$providerId);
            }
        }
    }

    /**
     * @param Service $service
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageExtrasForServiceAdd($service)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        if ($service->getExtras() !== null) {
            $extras = $service->getExtras();
            foreach ($extras->getItems() as $extra) {
                /** @var Extra $extra */
                $extra->setServiceId(new Id($service->getId()->getValue()));

                if (!($extraId = $extraRepository->add($extra))) {
                    $serviceRepository->rollback();
                }

                $extra->setId(new Id($extraId));
            }
        }
    }

    /**
     * @param Service $service
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageExtrasForServiceUpdate($service)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        if ($service->getExtras() !== null) {
            $extras = $service->getExtras();
            foreach ($extras->getItems() as $extra) {
                /** @var Extra $extra */
                $extra->setServiceId(new Id($service->getId()->getValue()));
                if ($extra->getId() === null) {
                    if (!($extraId = $extraRepository->add($extra))) {
                        $serviceRepository->rollback();
                    }

                    $extra->setId(new Id($extraId));
                } else {
                    if (!$extraRepository->update($extra->getId()->getValue(), $extra)) {
                        $serviceRepository->rollback();
                    }
                }
            }
        }
    }

    /**
     * Accept two collection: services and providers
     * For each service function will add providers that are working on this service
     *
     * @param Service    $service
     * @param Collection $providers
     *
     * @return Collection
     *
     * @throws InvalidArgumentException
     */
    public function getServiceProviders($service, $providers)
    {
        $serviceProviders = new Collection();

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            /** @var Service $providerService */
            foreach ($provider->getServiceList()->getItems() as $providerService) {
                if ($providerService->getId()->getValue() === $service->getId()->getValue()) {
                    $serviceProviders->addItem($provider, $provider->getId()->getValue());
                }
            }
        }

        return $serviceProviders;
    }

    /**
     * Add 0 as duration for service time before or time after if it is null
     *
     * @param Service $service
     *
     * @throws InvalidArgumentException
     */
    public function checkServiceTimes($service)
    {
        if (!$service->getTimeBefore()) {
            $service->setTimeBefore(new Duration(0));
        }

        if (!$service->getTimeAfter()) {
            $service->setTimeAfter(new Duration(0));
        }
    }

    /**
     * Return collection of extras that are passed in $extraIds array for provided service
     *
     * @param array   $extraIds
     * @param Service $service
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function filterServiceExtras($extraIds, $service)
    {
        $extras = new Collection();

        foreach ((array)$service->getExtras()->keys() as $extraKey) {
            /** @var Extra $extra */
            $extra = $service->getExtras()->getItem($extraKey);

            if (in_array($extra->getId()->getValue(), $extraIds, false)) {
                if (!$extra->getDuration()) {
                    $extra->setDuration(new Duration(0));
                }

                $extras->addItem($extra, $extraKey);
            }
        }

        return $extras;
    }

    /**
     *
     * @param array $services
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getAppointmentsCountForServices($services)
    {
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var Collection $appointments */
        $appointments = $appointmentRepo->getFiltered(['services' => $services]);

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
     *
     * @param Category $category
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function deleteCategory($category)
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get('domain.bookable.category.repository');

        /** @var Service $service */
        foreach ($category->getServiceList()->getItems() as $service) {
            if (!$this->deleteService($service)) {
                return false;
            }
        }

        return $categoryRepository->delete($category->getId()->getValue());
    }

    /**
     *
     * @param Service $service
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function deleteService($service)
    {
        /** @var GalleryApplicationService $galleryService */
        $galleryService = $this->container->get('application.gallery.service');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var CouponServiceRepository $couponServiceRepository */
        $couponServiceRepository = $this->container->get('domain.coupon.service.repository');

        /** @var ProviderServiceRepository $providerServiceRepository */
        $providerServiceRepository = $this->container->get('domain.bookable.service.providerService.repository');

        /** @var PeriodServiceRepository $periodServiceRepository */
        $periodServiceRepository = $this->container->get('domain.schedule.period.service.repository');

        /** @var SpecialDayPeriodServiceRepository $specialDayPeriodServiceRepository */
        $specialDayPeriodServiceRepository = $this->container->get('domain.schedule.specialDay.period.service.repository');

        /** @var CustomFieldServiceRepository $customFieldServiceRepository */
        $customFieldServiceRepository = $this->container->get('domain.customFieldService.repository');

        /** @var AppointmentApplicationService $appointmentApplicationService */
        $appointmentApplicationService = $this->container->get('application.booking.appointment.service');

        /** @var Collection $appointments */
        $appointments = $appointmentRepository->getFiltered([
            'services' => [$service->getId()->getValue()]
        ]);

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            if (!$appointmentApplicationService->delete($appointment)) {
                return false;
            }
        }

        /** @var Extra $extra */
        foreach ($service->getExtras()->getItems() as $extra) {
            if (!$this->deleteExtra($extra)) {
                return false;
            }
        }

        return
            $galleryService->manageGalleryForEntityDelete($service->getGallery()) &&
            $customFieldServiceRepository->deleteByEntityId($service->getId()->getValue(), 'serviceId') &&
            $specialDayPeriodServiceRepository->deleteByEntityId($service->getId()->getValue(), 'serviceId') &&
            $periodServiceRepository->deleteByEntityId($service->getId()->getValue(), 'serviceId') &&
            $providerServiceRepository->deleteByEntityId($service->getId()->getValue(), 'serviceId') &&
            $couponServiceRepository->deleteByEntityId($service->getId()->getValue(), 'serviceId') &&
            $serviceRepository->deleteViewStats($service->getId()->getValue()) &&
            $serviceRepository->delete($service->getId()->getValue());
    }

    /**
     *
     * @param Extra $extra
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function deleteExtra($extra)
    {
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        /** @var CustomerBookingExtraRepository $customerBookingExtraRepository */
        $customerBookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');

        return
            $customerBookingExtraRepository->deleteByEntityId($extra->getId()->getValue(), 'extraId') &&
            $extraRepository->delete($extra->getId()->getValue());
    }
}
