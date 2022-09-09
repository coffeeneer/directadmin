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
     * Creates a new FTP account.
     *
     * @param string $user The username (without domain).
     * @param string $type The type of account:
     *  'user', 'domain', 'ftp', 'custom'
     * @param string $password The password to use.
     * @param string $customPath A custom path, to be used with 'custom' type
     * @return FtpAccount The newly created account
     */
    public static function create(Domain $domain, string $user, string $type, string $password, string $customPath = null) {
        $config = [
            'user' => $user,
            'type' => $type,
            'passwd' => $password,
            'passwd2' => $password,
        ];

        if($type === 'custom') {
            $config['custom_val'] = $customPath;
        }

        $domain->invokePost('FTP', 'create', $config);

        return new self($user . '@' . $domain->getDomainName(), $domain);
    }

    /**
     * Modifies an FTP account.
     *
     * @param string $type The type of account:
     *  'user', 'domain', 'ftp', 'custom'
     * @param string $password The password to use.
     * @param string $customPath A custom path, to be used with 'custom' type
     */
    public function modify(string $type, string $password, string $customPath = null) {
        $config = [
            'user' => $this->getUserWithoutDomain(),
            'type' => $type,
            'passwd' => $password,
            'passwd2' => $password,
        ];

        if($customPath) {
            $config['custom_val'] = $customPath;
        }

        $this->invokePost('FTP', 'modify', $config);
    }

    /**
     * Deletes the account.
     */
    public function delete()
    {
        $this->invokePost('FTP', 'delete', [
            'select0' => $this->getUserWithoutDomain()
        ]);
    }

    /**
     * Returns the full user name.
     *
     * @return string
     */
    public function getUser() {
        return $this->getName();
    }

    /**
     * Returns user without the domain part.
     *
     * @return string
     */
    public function getUserWithoutDomain() {
        return strtok($this->getName(), '@');
    }

    /**
     * Returns wether the account is a system user.
     *
     * @return bool
     */
    public function isSystemUser() {
        return $this->getUser() === $this->getUserWithoutDomain();
    }

    /**
     * Returns the account path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getData('path');
    }

    /**
     * Returns the account type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->getDetailedData('type');
    }

    /**
     * Cache wrapper to keep account stats up to date.
     *
     * @param string $key
     * @return mixed
     */
    protected function getDetailedData($key)
    {
        return $this->getCacheItem(self::CACHE_DETAILED_DATA, $key, function () {
            return $this->getContext()->invokeApiGet('FTP_SHOW', [
                'domain' => $this->getDomainName(),
                'user' => $this->getUserWithoutDomain(),
            ]);
        });
    }

    /**
     * Cache wrapper to keep account stats up to date.
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

            return \GuzzleHttp\Psr7\Query::parse($result[$this->getUser()]['account']);
        });
    }
}
