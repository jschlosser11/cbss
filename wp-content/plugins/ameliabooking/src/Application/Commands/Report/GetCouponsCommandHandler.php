<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Report;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Report\AbstractReportService;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class GetCouponsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Report
 */
class GetCouponsCommandHandler extends CommandHandler
{
    /**
     * @param GetCouponsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetCouponsCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::COUPONS)) {
            throw new AccessDeniedException('You are not allowed to read coupons.');
        }

        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');
        /** @var AbstractReportService $reportService */
        $reportService = $this->container->get('infrastructure.report.csv.service');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $coupons = $couponRepository->getFiltered($command->getField('params'), 0);

        if ($coupons->length()) {
            $ids = [];

            foreach ((array)$coupons->keys() as $couponKey) {
                $ids[] = $coupons->getItem($couponKey)->getId()->getValue();
            }

            $coupons = $couponRepository->getAllByCriteria(['couponIds' => $ids]);
        }

        if (!$coupons instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get coupon coupons.');

            return $result;
        }

        $rows = [];

        $fields = $command->getField('params')['fields'];
        $delimiter = $command->getField('params')['delimiter'];

        foreach ((array)$coupons->toArray() as $coupon) {
            $row = [];

            if (in_array('code', $fields, true)) {
                $row[BackendStrings::getFinanceStrings()['code']] = $coupon['code'];
            }

            if (in_array('discount', $fields, true)) {
                $row[BackendStrings::getPaymentStrings()['discount']] = $coupon['discount'];
            }

            if (in_array('deduction', $fields, true)) {
                $row[BackendStrings::getPaymentStrings()['deduction']] = $coupon['deduction'];
            }

            if (in_array('services', $fields, true)) {
                $row[BackendStrings::getCommonStrings()['services']] =
                    $coupon['serviceList'] ? $coupon['serviceList'][0]['name'] .
                        (sizeof($coupon['serviceList']) > 1 ?
                            BackendStrings::getFinanceStrings()['coupons_multiple_services_text'] : '') : '';
            }

            if (in_array('events', $fields, true)) {
                $row[BackendStrings::getCommonStrings()['events']] =
                    $coupon['eventList'] ? $coupon['eventList'][0]['name'] .
                        (sizeof($coupon['eventList']) > 1 ?
                            BackendStrings::getFinanceStrings()['coupons_multiple_events_text'] : '') : '';
            }

            if (in_array('limit', $fields, true)) {
                $row[BackendStrings::getFinanceStrings()['limit']] = $coupon['limit'];
            }

            if (in_array('used', $fields, true)) {
                $row[BackendStrings::getFinanceStrings()['used']] = $coupon['used'];
            }

            $rows[] = $row;
        }

        $reportService->generateReport($rows, Entities::COUPONS, $delimiter);

        $result->setAttachment(true);

        return $result;
    }
}
