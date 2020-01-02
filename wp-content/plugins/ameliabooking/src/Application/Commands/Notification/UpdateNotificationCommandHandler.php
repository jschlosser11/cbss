<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Factory\Notification\NotificationFactory;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;
use DOMDocument;

/**
 * Class UpdateNotificationCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class UpdateNotificationCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'subject',
        'content'
    ];

    /**
     * @param UpdateNotificationCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateNotificationCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to update notification');
        }

        $notificationId = (int)$command->getArg('id');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');

        $currentNotification = $notificationRepo->getById($notificationId);

        $content = $command->getField('content');

        $parsedContent = null;

        try {
            $parsedContent = $this->parseContent($content);
        } catch (\Exception $e) {
            $content = $command->getField('content');
        }

        $content = str_replace(
            [
                'class="ql-align-center"',
                'class="ql-align-right"',
                'class="ql-align-left"',
                'class="ql-align-justify"'
            ],
            [
                'style="text-align: center;"',
                'style="text-align: right;"',
                'style="text-align: left;"',
                'class="text-align: justify"'
            ],
            $parsedContent ?: $content
        );

        $notification = NotificationFactory::create([
            'name'       => $currentNotification->getName()->getValue(),
            'status'     => $currentNotification->getStatus()->getValue(),
            'type'       => $currentNotification->getType()->getValue(),
            'time'       => $command->getField('time'),
            'timeBefore' => $command->getField('timeBefore'),
            'timeAfter'  => $command->getField('timeAfter'),
            'sendTo'     => $currentNotification->getSendTo()->getValue(),
            'subject'    => $command->getField('subject'),
            'entity'     => $command->getField('entity'),
            'content'    => $content
        ]);

        if (!$notification instanceof Notification) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update notification entity.');

            return $result;
        }

        if ($notificationRepo->update($notificationId, $notification)) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated notification.');
            $result->setData([
                Entities::NOTIFICATION => $notification->toArray(),
                'update'               => $parsedContent !== null
            ]);
        }

        return $result;
    }

    /**
     * @param $content
     * @return string
     */
    private function parseContent($content)
    {
        $html = new DOMDocument();
        $html->loadHTML($content);

        $html->preserveWhiteSpace = false;

        $hasParsedContent = false;

        foreach ($html->getElementsByTagName('img') as $image) {
            $src = $image->getAttribute('src');

            if (strpos($src, 'data:image/') === 0) {
                $hasParsedContent = true;

                $parts = explode(',', substr($src, 5), 2);

                $mimeSplitWithoutBase64 = explode(';', $parts[0], 2);
                $mimeSplit = explode('/', $mimeSplitWithoutBase64[0], 2);

                $outputFile = '';

                if (count($mimeSplit) === 2) {
                    $token = new Token();

                    $outputFile = $token->getValue() . '.' . (($mimeSplit[1] === 'jpeg') ? 'jpg' : $mimeSplit[1]);
                }

                $outputPath = UPLOADS_PATH . '/amelia/mail/';

                !is_dir($outputPath) && !mkdir($outputPath, 0755, true) && !is_dir($outputPath);

                file_put_contents($outputPath . $outputFile, base64_decode($parts[1]));

                $content = preg_replace(
                    '/<img(.*?)src="data:image(.*?)"(.*?)>/',
                    '<IMG src="' . UPLOADS_URL . '/amelia/mail/' . $outputFile . '">',
                    $content,
                    1
                );
            }
        }

        if ($hasParsedContent) {
            return str_replace('<IMG src="', '<img src="', $content);
        }

        return null;
    }
}
