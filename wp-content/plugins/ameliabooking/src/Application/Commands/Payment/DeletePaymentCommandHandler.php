<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Payment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;

/**
 * Class DeletePaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class DeletePaymentCommandHandler extends CommandHandler
{
    /**
     * @param DeletePaymentCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(DeletePaymentCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanDelete(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to delete payments.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        $payment = $paymentRepository->getById($command->getArg('id'));

        if (!$payment instanceof Payment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to delete payment.');

            return $result;
        }

        if ($paymentRepository->delete($command->getArg('id'))) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Payment successfully deleted.');
            $result->setData(
                [
                    Entities::PAYMENT => $payment->toArray()
                ]
            );
        }

        return $result;
    }
}
