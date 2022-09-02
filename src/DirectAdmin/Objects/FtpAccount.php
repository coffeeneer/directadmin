<?php

namespace Omines\DirectAdmin\Objects;

use Omines\DirectAdmin\Objects\DomainObject;

/**
 * Encapsulates an FTP account.
 */
class FtpAccount extends DomainObject
{
    private const CACHE_DATA = 'account';
    private const CACHE_DETAILED_DATA = 'detailed_account';

    /**
     * Construct the object.
     *
     * @param string $path The redirect path
     * @param Domain $domain The containing domain
     * @param string|array|null $config URL encoded config string as returned by CMD_API_REDIRECT
     */
    public function __construct($user, Domain $domain, $config = null)
    {
        parent::__construct($user, $domain);
        if (isset($config)) {
            $this->setCache(self::CACHE_DATA, is_string($config) ? \GuzzleHttp\Psr7\Query::parse($config) : $config);
        }
    }

    /**
     * Creates a new redirect.
     *
     * @param string $type The type of forwarder:
     *  '301', '302', '303'
     * @param string $to The url to forward to.
     * @param string $from The path to forward.
     * @return Redirect The newly created redirect
     */
    public static function create(Domain $domain, string $from, string $to, string $type) {
        $config = [
            'from' => $from,
            'to' => $to,
            'type' => $type,
        ];
        $domain->invokePost('REDIRECT', 'add', $config);

        return new self($from, $domain, $config);
    }

    /**
     * Deletes the forwarder.
     */
    public function delete()
    {
        $this->invokePost('REDIRECT', 'delete', [
            'select0' => $this->getUser()
        ]);
    }

    /**
     * Returns the path where to redirect from.
     *
     * @return string
     */
    public function getUser() {
        return $this->getName();
    }

    /**
     * Returns the url where to redirect to.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getData('account.path');
    }

    /**
     * Returns the redirect type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->getData('type');
    }

    /**
     * Cache wrapper to keep mailbox stats up to date.
     *
     * @param string $key
     * @return mixed
     */
    protected function getDetailedData($key)
    {
        return $this->getCacheItem(self::CACHE_DETAILED_DATA, $key, function () {
            return $this->getContext()->invokeApiGet('FTP_SHOW', [
                'domain' => $this->getDomainName(),
                'user' => $this->getUser(),
            ]);
        });
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
            $result = $this->getContext()->invokeApiGet('FTP', [
                'domain' => $this->getDomainName(),
                'extended' => 'yes'
            ]);

            return \GuzzleHttp\Psr7\Query::parse($result[$this->getUser()]);
        });
    }
}
