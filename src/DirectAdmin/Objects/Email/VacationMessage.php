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
use DateTime;

/**
 * Encapsulates a full mailbox with POP/IMAP/webmail access.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class VacationMessage extends MailObject
{
    const CACHE_DATA = 'vacationmessage';

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
     * Creates a new vacationmessage.
     *
     * @param Domain $domain Domain to add the account to
     * @param string $prefix Prefix for the account
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
    public static function create(
        Domain $domain,
        string $prefix,
        string $startTime,
        string $startDate,
        string $endTime,
        string $endDate,
        string $subject,
        string $text,
        string $replyOnceInterval,
        string $contentType = 'text/plain')
    {
        $startDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);

        $domain->invokePost('EMAIL_VACATION', 'create', [
            'user' => $prefix,
            'starttime' => $startTime,
            'startday' => $startDate->format('d'),
            'startmonth' => $startDate->format('m'),
            'startyear' => $startDate->format('Y'),
            'endtime' => $endTime,
            'endday' => $endDate->format('d'),
            'endmonth' => $endDate->format('m'),
            'endyear' => $endDate->format('Y'),
            'subject' => $subject,
            'text' => $text,
            'reply_once_time' => $replyOnceInterval,
            'reply_content_type' => $contentType,
            'reply_encoding' => 'UTF-8',
            'create' => 'Create',
        ]);

        return new self($prefix, $domain);
    }

    /**
     * Modifies an existing vacationmessage.
     *
     * @param Domain $domain Domain to add the account to
     * @param string $prefix Prefix for the account
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
     */
    public function modify(
        string $startTime,
        string $startDate,
        string $endTime,
        string $endDate,
        string $subject,
        string $text,
        string $replyOnceInterval,
        string $contentType)
    {
        $startDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);

        $this->getDomain()->invokePost('EMAIL_VACATION', 'modify', [
            'user' => $this->getPrefix(),
            'starttime' => $startTime,
            'startday' => $startDate->format('d'),
            'startmonth' => $startDate->format('m'),
            'startyear' => $startDate->format('Y'),
            'endtime' => $endTime,
            'endday' => $endDate->format('d'),
            'endmonth' => $endDate->format('m'),
            'endyear' => $endDate->format('Y'),
            'subject' => $subject,
            'text' => $text,
            'reply_once_time' => $replyOnceInterval,
            'reply_content_type' => $contentType,
            'reply_encoding' => 'UTF-8'
        ]);
    }

    /**
     * Deletes the vacation message.
     */
    public function delete()
    {
        $this->invokeDelete('EMAIL_VACATION', 'select0');
    }

    /**
     * Returns the start time
     *
     * @return string 'morning', 'afternoon' or 'evening'
     */
    public function getStartTime()
    {
        return $this->getData('starttime');
    }

    /**
     * Returns the start date
     *
     * @return \DateTime The date
     */
    public function getStartDate()
    {
        return $this->getData('startyear') . '-' .
            $this->getData('startmonth') . '-' .
            $this->getData('startday');
    }

    /**
     * Returns the end time
     *
     * @return string 'morning', 'afternoon' or 'evening'
     */
    public function getEndTime()
    {
        return $this->getData('endtime');
    }

    /**
     * Returns the end date
     *
     * @return \DateTime The date
     */
    public function getEndDate()
    {
        return $this->getData('endyear') . '-' .
            $this->getData('endmonth') . '-' .
            $this->getData('endday');
    }

    /**
     * Returns the subject
     *
     * @return string The subject
     */
    public function getSubject()
    {
        return $this->getData('reply_subject');
    }

    /**
     * Returns the content
     *
     * @return string The content text
     */
    public function getText()
    {
        return $this->getData('text');
    }

    /**
     * Returns the content type
     *
     * @return string The contehnt
     */
    public function getContentType()
    {
        return $this->getValueFromSelectBoxes($this->getData('reply_content_types'));
    }

    /**
     * Returns the reply once interval
     *
     * @return string The interval in which to send a reply
     */
    public function getReplyOnceInterval()
    {
        return $this->getValueFromSelectBoxes($this->getData('reply_once_select'));
    }

    /**
     * Get a value from the provided select boxes array
     *
     * @return string The value
     */
    private static function getValueFromSelectBoxes(string $selectBoxes)
    {
        preg_match_all('/selected value="(.+?)"/', $selectBoxes, $matches);
        return $matches[1][0];
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
            $result = $this->getContext()->invokeApiGet('EMAIL_VACATION_MODIFY', [
                'domain' => $this->getDomainName(),
                'user' => $this->getPrefix(),
                'apitype' => 'yes'
            ]);

            return $result;
        });
    }
}
