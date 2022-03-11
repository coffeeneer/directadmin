<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Objects\Email;

use Omines\DirectAdmin\Objects\Domain;

/**
 * Encapsulates a full mailbox with POP/IMAP/webmail access.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class Mailbox extends MailObject
{
    const CACHE_DATA = 'mailbox';

    /**
     * Construct the object.
     *
     * @param string $prefix The part before the @ in the address
     * @param Domain $domain The containing domain
     * @param string|array|null $config URL encoded config string as returned by CMD_API_POP
     */
    public function __construct($prefix, Domain $domain, $config = null)
    {
        parent::__construct($prefix, $domain);
        if (isset($config)) {
            $this->setCache(self::CACHE_DATA, is_string($config) ? \GuzzleHttp\Psr7\Query::parse($config) : $config);
        }
    }

    /**
     * Creates a new mailbox.
     *
     * @param Domain $domain Domain to add the account to
     * @param string $prefix Prefix for the account
     * @param string $password Password for the account
     * @param int|null $quota Quota in megabytes, or zero/null for unlimited
     * @param int|null $sendLimit Send limit, or 0 for unlimited, or null for system default
     * @return Mailbox The created mailbox
     */
    public static function create(Domain $domain, $prefix, $password, $quota = null, $sendLimit = null)
    {
        $domain->invokePost('POP', 'create', [
            'user' => $prefix,
            'passwd' => $password,
            'passwd2' => $password,
            'quota' => intval($quota) ?: 0,
            'limit' => isset($sendLimit) ? (intval($sendLimit) ?: 0) : null,
        ]);
        return new self($prefix, $domain);
    }

    /**
     * Deletes the mailbox.
     */
    public function delete()
    {
        $this->invokeDelete('POP', 'user');
    }

    /**
     * Gets a vacation message
     *
     * @return VacationMessage|null The vacation message, or null if not found
     */
    public function getVacationMessage()
    {
        $result = $this->getContext()->invokeApiGet('EMAIL_VACATION_MODIFY', [
            'domain' => $this->getDomainName(),
            'user' => $this->getPrefix(),
            'apitype' => 'yes'
        ]);

        if (!array_key_exists('starttime', $result)) {
            return null;
        }

        return new VacationMessage($this->getPrefix(), $this->getDomain(), $result);
    }

    /**
     * Gets an autoresponder
     *
     * @return Autoresponder|null The autoresponder, or null if not found
     */
    public function getAutoresponder()
    {
        $result = $this->getContext()->invokeApiGet('EMAIL_AUTORESPONDER_MODIFY', [
            'domain' => $this->getDomainName(),
            'user' => $this->getPrefix(),
            'apitype' => 'yes'
        ]);

        if (!array_key_exists('text', $result)) {
            return null;
        }

        return new Autoresponder($this->getPrefix(), $this->getDomain(), $result);
    }

    /**
     * Creates a new vacationmessage.
     *
     * @param string $startTime Start time:
     *  'morning', 'afternoon' or 'evening'
     * @param string $startDate Start date in Y-m-d
     * @param string $endTime End time:
     *  'morning', 'afternoon' or 'evening'
     * @param string $endDate End date in Y-m-d
     * @param string $subject The text to be put before the original subject
     * @param string $text The contents of the vacationmessage
     * @param string $replyOnceInterval The interval in which not to send another reply:
     *  '1m', '10m', '30m', '1h', '2h', '6h', '12h', '1d', '2d', '3d', '4d', '5d', '6d' or '7d'
     * @param string $contentType The content type to use for the reply:
     *  'text/plain' or 'text/html'
     * @return VacationMessage The created vacation message
     */
    public function createVacationMessage(
        string $startTime,
        string $startDate,
        string $endTime,
        string $endDate,
        string $subject,
        string $text,
        string $replyOnceInterval,
        string $contentType = 'text/plain')
    {
        return VacationMessage::create(
            $this->getDomain(),
            $this->getPrefix(),
            $startTime,
            $startDate,
            $endTime,
            $endDate,
            $subject,
            $text,
            $replyOnceInterval,
            $contentType);
    }

    /**
     * Creates a new autoresponder.
     *
     * @param string $subject The text to be put before the original subject
     * @param string $text The message
     * @param string|null $ccEmail The email address to use for the cc
     * @param string $replyOnceInterval The interval in which not to send another reply:
     *  '1m', '10m', '30m', '1h', '2h', '6h', '12h', '1d', '2d', '3d', '4d', '5d', '6d' or '7d'
     * @param string $contentType The content type to use for the reply:
     *  'text/plain' or 'text/html'
     * @return Autoresponder The created autoresponder
     */
    public function createAutoresponder(
        string $subject,
        string $text,
        string|null $ccEmail,
        string $replyOnceInterval,
        string $contentType = 'text/plain')
    {
        return Autoresponder::create(
            $this->getDomain(),
            $this->getPrefix(),
            $subject,
            $text,
            $ccEmail,
            $replyOnceInterval,
            $contentType);
    }

    /**
     * Reset the password for this mailbox.
     *
     * @param string $newPassword
     */
    public function setPassword($newPassword)
    {
        $this->invokePost('POP', 'modify', [
            'user' => $this->getPrefix(),
            'passwd' => $newPassword,
            'passwd2' => $newPassword,
        ], false);
    }

    /**
     * Returns the disk quota in megabytes.
     *
     * @return float|null
     */
    public function getDiskLimit()
    {
        return floatval($this->getData('quota')) ?: null;
    }

    /**
     * Returns the disk usage in megabytes.
     *
     * @return float
     */
    public function getDiskUsage()
    {
        return floatval($this->getData('usage'));
    }

    /**
     * Return the amount of mails sent in the current period.
     *
     * @return int
     */
    public function getMailsSent()
    {
        return intval($this->getData('sent'));
    }

    /**
     * Return the maximum number of mails that can be send each day
     *
     * @return int
     */
    public function getMailLimit()
    {
        return intval($this->getData('limit'));
    }

    /**
     * Returns if the mailbox is suspended or not
     *
     * @return bool
     */
    public function getMailSuspended()
    {
        return( strcasecmp($this->getData('suspended'),"yes" ) == 0 );
    }

    /**
     * Cache wrapper to keep mailbox stats up to date.
     *
     * @param string $key
     * @return mixed
     */
    protected function getData($key)
    {
        return $this->getCacheItem(self::CACHE_DATA, $key, function () {
            $result = $this->getContext()->invokeApiGet('POP', [
                'domain' => $this->getDomainName(),
                'action' => 'full_list',
            ]);

            return \GuzzleHttp\Psr7\Query::parse($result[$this->getPrefix()]);
        });
    }
}
