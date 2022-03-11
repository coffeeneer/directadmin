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
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class AutoResponder extends MailObject
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
     * @param bool $cc Whether to send cc
     * @param string $ccEmail The email address to use for the cc
     * @param string $text The message
     * @return Autoresponder The created vacation message
     */
    public static function create(
        Domain $domain,
        string $prefix,
        bool $cc,
        string $ccEmail,
        string $text)
    {
        $domain->invokePost('EMAIL_AUTORESPONDER', 'create', [
            'user' => $prefix,
            'text' => $text,
            'cc' => Conversion::onOff($cc),
            'email' => $cc ? $ccEmail : null,
            'create' => 'Create',
        ]);

        return new self($prefix, $domain);
    }

    /**
     * Modifies an existing autoresponder.
     *
     * @param string $prefix Prefix for the account
     * @param bool $cc Whether to send cc
     * @param string $ccEmail The email address to use for the cc
     * @param string $text The message
     */
    public function modify(string $prefix, bool $cc, string $ccEmail, string $text)
    {
        $this->getDomain()->invokePost('EMAIL_AUTORESPONDER', 'modify', [
            'user' => $prefix,
            'text' => $text,
            'cc' => Conversion::onOff($cc),
            'email' => $cc ? $ccEmail : null,
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
     * Returns the content
     *
     * @return string The content text
     */
    public function getText()
    {
        return $this->getData('text');
    }

    /**
     * Returns the content
     *
     * @return bool Whether a CC should be sent
     */
    public function getCc()
    {
        return Conversion::toBool($this->getData('cc'));
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
