<?php
/**
 * DirectAdmin
 * (c) Omines Internetbureau B.V.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Objects\Users;

use Omines\DirectAdmin\Context\ResellerContext;
use Omines\DirectAdmin\Context\UserContext;
use Omines\DirectAdmin\DirectAdmin;
use Omines\DirectAdmin\DirectAdminException;
use Omines\DirectAdmin\Objects\Domain;
use Omines\DirectAdmin\Objects\Object;

/**
 * User
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class User extends Object
{
    const CACHE_CONFIG          = 'config';
    const CACHE_USAGE           = 'usage';

    /** @var Domain[] **/
    private $domains;

    /**
     * Construct the object.
     *
     * @param string $name Username of the account.
     * @param UserContext $context The context managing this object.
     * @param mixed|null $config An optional preloaded configuration.
     */
    public function __construct($name, UserContext $context, $config = null)
    {
        parent::__construct($name, $context);
        if(isset($config))
            $this->setCache(self::CACHE_CONFIG, $config);
    }

    /**
     * @return string The username.
     */
    public function getUsername()
    {
        return $this->getName();
    }

    /**
     * Returns the bandwidth limit of the user.
     *
     * @return float|null Limit in megabytes, or null for unlimited.
     */
    public function getBandwidthLimit()
    {
        return floatval($this->getConfig('bandwidth')) ?: null;
    }

    /**
     * Returns the current period's bandwidth usage in megabytes.
     *
     * @return float
     */
    public function getBandwidthUsage()
    {
        return floatval($this->getUsage('bandwidth'));
    }

    /**
     * Returns the disk quota of the user.
     *
     * @return float|null Limit in megabytes, or null for unlimited.
     */
    public function getDiskLimit()
    {
        return floatval($this->getConfig('quota')) ?: null;
    }

    /**
     * Returns the current disk usage in megabytes.
     *
     * @return float
     */
    public function getDiskUsage()
    {
        return floatval($this->getUsage('quota'));
    }

    /**
     * @return Domain|null The default domain for the user, if any.
     */
    public function getDefaultDomain()
    {
        if(empty($name = $this->getConfig('domain')))
            return null;
        return $this->getDomain($name);
    }

    /**
     * Returns maximum number of domains allowed to this user, or NULL for unlimited.
     *
     * @return int|null
     */
    public function getDomainLimit()
    {
        return intval($this->getConfig('vdomains')) ?: null;
    }

    /**
     * Returns number of domains owned by this user.
     *
     * @return int
     */
    public function getDomainUsage()
    {
        return intval($this->getUsage('vdomains'));
    }

    /**
     * Returns whether the user is currently suspended.
     *
     * @return bool
     */
    public function isSuspended()
    {
        return ($this->getConfig('suspended') === 'ON');
    }

    /**
     * @param string $domainName
     * @return null|Domain
     */
    public function getDomain($domainName)
    {
        if(!isset($this->domains))
            $this->getDomains();
        return isset($this->domains[$domainName]) ? $this->domains[$domainName] : null;
    }

    /**
     * @return Domain[]
     */
    public function getDomains()
    {
        if(!isset($this->domains))
        {
            if(!$this->isSelfManaged())
                $this->domains = $this->impersonate()->getDomains();
            else
                $this->domains = Object::toRichObjectArray($this->getContext()->invokeGet('ADDITIONAL_DOMAINS'), Domain::class, $this->getContext());
        }
        return $this->domains;
    }

    /**
     * @return string The user type, as one of the ACCOUNT_TYPE_ constants in the DirectAdmin class.
     */
    public function getType()
    {
        return $this->getConfig('usertype');
    }

    /**
     * @return UserContext
     */
    public function impersonate()
    {
        /** @var ResellerContext $context */
        if(!($context = $this->getContext()) instanceof ResellerContext)
            throw new DirectAdminException('You need to be at least a reseller to impersonate');
        return $context->impersonateUser($this->getUsername());
    }

    /**
     * Modifies the configuration of the user. For available keys in the array check the documentation on
     * CMD_API_MODIFY_USER in the linked document.
     *
     * @param array $newConfig Associative array of values to be modified.
     * @url http://www.directadmin.com/api.html#modify
     */
    public function modifyConfig(array $newConfig)
    {
        // Merge old and new config
        $config = array_merge($this->loadConfig(), $newConfig, ['action' => 'customize', 'user' => $this->getUsername()]);
        foreach(['bandwidth', 'domainptr', 'ftp', 'mysql', 'nemailf', 'nemailml', 'nemailr', 'nemails',
                 'nsubdomains', 'quota', 'vdomains'] as $key)
        {
            $ukey = "u{$key}";
            if($config[$key] === 'unlimited' || (array_key_exists($key, $config) && !isset($config[$key])))
                $config[$ukey] = 'ON';
            else
                unset($config[$ukey]);
        }
        $this->getContext()->invokePost('MODIFY_USER', $config);
        $this->clearCache();
    }

    /**
     * @param float|null $newValue New value, or NULL for unlimited.
     */
    public function setBandwidthLimit($newValue)
    {
        $this->modifyConfig(['bandwidth' => isset($newValue) ? floatval($newValue) : null]);
    }

    /**
     * @param float|null $newValue New value, or NULL for unlimited.
     */
    public function setDiskLimit($newValue)
    {
        $this->modifyConfig(['quota' => isset($newValue) ? floatval($newValue) : null]);
    }

    /**
     * @param int|null $newValue New value, or NULL for unlimited.
     */
    public function setDomainLimit($newValue)
    {
        $this->modifyConfig(['vdomains' => isset($newValue) ? intval($newValue) : null]);
    }

    /**
     * Constructs the correct object from the given user config.
     *
     * @param array $config The raw config from DirectAdmin.
     * @param UserContext $context The context within which the config was retrieved.
     * @return Admin|Reseller|User The correct object.
     * @throws DirectAdminException If the user type could not be determined.
     */
    public static function fromConfig($config, UserContext $context)
    {
        $name = $config['username'];
        switch($config['usertype'])
        {
            case DirectAdmin::ACCOUNT_TYPE_USER:
                return new User($name, $context, $config);
            case DirectAdmin::ACCOUNT_TYPE_RESELLER:
                return new Reseller($name, $context, $config);
            case DirectAdmin::ACCOUNT_TYPE_ADMIN:
                return new Admin($name, $context, $config);
            default:
                throw new DirectAdminException("Unknown user type '$config[usertype]'");
        }
    }

    /**
     * Internal function to safe guard config changes and cache them.
     *
     * @param string $item Config item to retrieve.
     * @return mixed The value of the config item, or NULL.
     */
    private function getConfig($item)
    {
        return $this->getCacheItem(self::CACHE_CONFIG, $item, function() { return $this->loadConfig(); });
    }

    /**
     * Internal function to safe guard usage changes and cache them.
     *
     * @param string $item Usage item to retrieve.
     * @return mixed The value of the stats item, or NULL.
     */
    private function getUsage($item)
    {
        return $this->getCacheItem(self::CACHE_USAGE, $item, function() {
            return $this->getContext()->invokeGet('SHOW_USER_USAGE', ['user' => $this->getUsername()]);
        });
    }

    /**
     * @return bool Whether the account is managing itself.
     */
    protected function isSelfManaged()
    {
        return ($this->getUsername() === $this->getContext()->getUsername());
    }

    /**
     * Loads the current user configuration from the server.
     *
     * @return array
     */
    private function loadConfig()
    {
        return $this->getContext()->invokeGet('SHOW_USER_CONFIG', ['user' => $this->getUsername()]);
    }
}
