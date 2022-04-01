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
use Omines\DirectAdmin\Utility\Conversion;

/**
 * Encapsulates an autoresponder.
 */
class Autoresponder extends MailObject
{
    const CACHE_DATA = 'autoresponder';

    /**
     * Construct the object.
     *
     * @param string $prefix The part before the @ in the address
     * @param Domain $domain The containing domain
     * @param string|array|null $config URL encoded config string as returned by EMAIL_AUTORESPONDER_MODIFY
     */
    public function __construct($prefix, Domain $domain, $config = null)
    {
        parent::__construct($prefix, $domain);
        if (isset($config)) {
            $this->setCache(self::CACHE_DATA, is_string($config) ? \GuzzleHttp\Psr7\Query::parse($config) : $config);
        }
    }

    /**
     * Creates a new autoresponder.
     *
     * @param Domain $domain Domain to add the account to
     * @param string $prefix Prefix for the account
     * @param string $subject The text to be put before the original subject
     * @param string $text The message
     * @param bool $cc Whether to send cc
     * @param string|null $ccEmail The email address to use for the cc
     * @param string $replyOnceInterval The interval in which not to send another reply:
     *  '1m', '10m', '30m', '1h', '2h', '6h', '12h', '1d', '2d', '3d', '4d', '5d', '6d' or '7d'
     * @param string $contentType The content type to use for the reply:
     *  'text/plain' or 'text/html'
     * @return Autoresponder The created autoresponder
     */
    public static function create(
        Domain $domain,
        string $prefix,
        string $subject,
        string $text,
        string|null $ccEmail,
        string $replyOnceInterval,
        string $contentType)
    {
        $domain->invokePost('EMAIL_AUTORESPONDER', 'create', [
            'user' => $prefix,
            'subject' => $subject,
            'text' => $text,
            'cc' => Conversion::onOff(!!$ccEmail),
            'email' => $ccEmail,
            'reply_once_time' => $replyOnceInterval,
            'reply_content_type' => $contentType,
            'reply_encoding' => 'UTF-8',
            'create' => 'Create',
        ]);

        return new self($prefix, $domain);
    }

    /**
     * Modifies an existing autoresponder.
     *
     * @param string $subject The text to be put before the original subject
     * @param string $text The message
     * @param bool $cc Whether to send cc
     * @param string|null $ccEmail The email address to use for the cc
     * @param string $replyOnceInterval The interval in which not to send another reply:
     *  '1m', '10m', '30m', '1h', '2h', '6h', '12h', '1d', '2d', '3d', '4d', '5d', '6d' or '7d'
     * @param string $contentType The content type to use for the reply:
     *  'text/plain' or 'text/html'
     * @return Autoresponder The created autoresponder
     */
    public function modify(
        string $subject,
        string $text,
        string|null $ccEmail,
        string $replyOnceInterval,
        string $contentType)
    {
        $this->getDomain()->invokePost('EMAIL_AUTORESPONDER', 'modify', [
            'user' => $this->getPrefix(),
            'subject' => $subject,
            'text' => $text,
            'cc' => Conversion::onOff(!!$ccEmail),
            'email' => $ccEmail,
            'reply_once_time' => $replyOnceInterval,
            'reply_content_type' => $contentType,
            'reply_encoding' => 'UTF-8',
        ]);
    }

    /**
     * Deletes the vacation message.
     */
    public function delete()
    {
        $this->invokeDelete('EMAIL_AUTORESPONDER', 'select0');
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
     * Returns the cc email
     *
     * @return string The email address to use for CC
     */
    public function getCcEmail()
    {
        return $this->getData('email');
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
     * Cache wrapper to keep the autoresponder up to date.
     *
     * @param string $key
     * @return mixed
     */
    protected function getData($key)
    {
        return $this->getCacheItem(self::CACHE_DATA, $key, function () {
            $result = $this->getContext()->invokeApiGet('EMAIL_AUTORESPONDER_MODIFY', [
                'domain' => $this->getDomainName(),
                'user' => $this->getPrefix(),
                'apitype' => 'yes'
            ]);

            return $result;
        });
    }
}
