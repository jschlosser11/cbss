<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Notification;

use AmeliaBooking\Domain\Services\Notification\AbstractMailService;
use AmeliaBooking\Domain\Services\Notification\MailServiceInterface;
use Mailgun\Mailgun;

/**
 * Class MailgunService
 */
class MailgunService extends AbstractMailService implements MailServiceInterface
{
    /** @var string */
    private $apiKey;

    /** @var string */
    private $domain;

    /**
     * MailgunService constructor.
     *
     * @param string $from
     * @param string $fromName
     * @param string $apiKey
     * @param string $domain
     */
    public function __construct($from, $fromName, $apiKey, $domain)
    {
        parent::__construct($from, $fromName);
        $this->apiKey = $apiKey;
        $this->domain = $domain;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param       $to
     * @param       $subject
     * @param       $body
     * @param array $bccEmails
     *
     * @return mixed|void
     * @SuppressWarnings(PHPMD)
     */
    public function send($to, $subject, $body, $bccEmails = [])
    {
        $mgClient = Mailgun::create($this->apiKey);

        $mgArgs = [
            'from'    => "{$this->fromName} <{$this->from}>",
            'to'      => $to,
            'subject' => $subject,
            'html'    => $body
        ];

        if ($bccEmails){
            $mgArgs['bcc'] = implode(', ', $bccEmails);
        }

        $mgClient->messages()->send($this->domain, $mgArgs);
    }
}
