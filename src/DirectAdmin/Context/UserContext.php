<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Context;

use Omines\DirectAdmin\DirectAdmin;
use Omines\DirectAdmin\DirectAdminException;
use Omines\DirectAdmin\Objects\Domain;
use Omines\DirectAdmin\Objects\Domains\AcmeConfig;
use Omines\DirectAdmin\Objects\Users\User;

/**
 * Context for user functions.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class UserContext extends BaseContext
{
    /** @var User */
    private $user;

    /**
     * Constructs the object.
     *
     * @param DirectAdmin $connection A prepared connection
     * @param bool $validate Whether to check if the connection matches the context
     */
    public function __construct(DirectAdmin $connection, $validate = false)
    {
        parent::__construct($connection);
        if ($validate) {
            $classMap = [
                DirectAdmin::ACCOUNT_TYPE_ADMIN => AdminContext::class,
                DirectAdmin::ACCOUNT_TYPE_RESELLER => ResellerContext::class,
                DirectAdmin::ACCOUNT_TYPE_USER => self::class,
            ];
            if ($classMap[$this->getType()] != get_class($this)) {
                /* @codeCoverageIgnoreStart */
                throw new DirectAdminException('Validation mismatch on context construction');
                /* @codeCoverageIgnoreEnd */
            }
        }
    }

    /**
     * Returns the type of the account (user/reseller/admin).
     *
     * @return string One of the DirectAdmin::ACCOUNT_TYPE_ constants describing the type of underlying account
     */
    public function getType()
    {
        return $this->getContextUser()->getType();
    }

    /**
     * Returns the actual user object behind the context.
     *
     * @return User The user object behind the context
     */
    public function getContextUser()
    {
        if (!isset($this->user)) {
            $this->user = User::fromConfig($this->invokeApiGet('SHOW_USER_CONFIG'), $this);
        }

        return $this->user;
    }

    /**
     * Returns a domain managed by the current user.
     *
     * @param string $domainName The requested domain name
     * @return Domain|null The domain if found, or NULL if it does not exist
     */
    public function getDomain($domainName)
    {
        return $this->getContextUser()->getDomain($domainName);
    }

    /**
     * Returns a full list of the domains managed by the current user.
     *
     * @return Domain[]
     */
    public function getDomains()
    {
        return $this->getContextUser()->getDomains();
    }

    /**
     * Returns the username of the current context.
     *
     * @return string Username for the current context
     */
    public function getUsername()
    {
        return $this->getConnection()->getUsername();
    }

    /**
     * Creates a login key for the connected user.
     *
     * @param string $name The key name
     * @param string $key The key value
     * @param int $expiresAt Expiry timestamp (utc)
     *
     * @return void
     */
    public function createLoginKey($name, $key, $expiresAt)
    {
        $this->getConnection()->createLoginKey($name, $key, $expiresAt);
    }

    /**
     * Creates a login key for the connected user that is restricted to the given commands.
     *
     * @param string $name The key name
     * @param string $key The key value
     * @param int $expiresAt Expiry timestamp (utc), the key is removed afterwards
     * @param string[] $allowCommands Commands the key may execute, ie. `phpmyadmin-sso`
     * @param bool $allowLogin Whether the key may be used to log in to the DirectAdmin interface
     *
     * @return void
     */
    public function createRestrictedLoginKey($name, $key, $expiresAt, array $allowCommands, $allowLogin = false)
    {
        $this->getConnection()->createRestrictedLoginKey($name, $key, $expiresAt, $allowCommands, $allowLogin);
    }

    /**
     * Creates a phpMyAdmin single sign-on URL for the connected user.
     *
     * @return string The URL to redirect the browser to
     */
    public function createPhpMyAdminSsoUrl()
    {
        return $this->getConnection()->createPhpMyAdminSsoUrl();
    }

    /**
     * Gets the ACME configuration of a domain of the connected user.
     *
     * @param string $domainName The domain
     * @return AcmeConfig
     */
    public function getAcmeConfig($domainName)
    {
        return new AcmeConfig($this->getConnection()->invokeRestApi('GET', $this->getAcmeConfigPath($domainName)));
    }

    /**
     * Saves the ACME configuration of a domain of the connected user.
     *
     * @param string $domainName The domain
     * @param AcmeConfig $config The complete configuration, as retrieved with getAcmeConfig()
     */
    public function saveAcmeConfig($domainName, AcmeConfig $config)
    {
        $this->getConnection()->invokeRestApi('PUT', $this->getAcmeConfigPath($domainName), $config->toArray());
    }

    /**
     * @param string $domainName The domain
     * @return string Path of the domain's ACME configuration in the REST API
     */
    private function getAcmeConfigPath($domainName)
    {
        return 'domain-tls/domains/' . rawurlencode($domainName) . '/acme-config';
    }
}
