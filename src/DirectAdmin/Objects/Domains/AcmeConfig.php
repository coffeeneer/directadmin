<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Objects\Domains;

use Omines\DirectAdmin\Objects\Domain;

/**
 * ACME configuration of a domain, which controls how its TLS certificates are provisioned.
 *
 * Settings without accessors are kept as returned by DirectAdmin, so they are saved unchanged.
 */
class AcmeConfig
{
    /** @var array */
    private $data;

    /**
     * Construct the object.
     *
     * @param array $data The configuration as returned by the domain-tls REST API
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return bool Whether certificates are provisioned with ACME for the domain
     */
    public function isEnabled()
    {
        return !empty($this->data['enabled']);
    }

    /**
     * @param bool $enabled Whether certificates are provisioned with ACME for the domain
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->data['enabled'] = (bool) $enabled;
        return $this;
    }

    /**
     * @return bool Whether wildcard certificates, which require DNS validation, are preferred
     */
    public function isWildcardPreferred()
    {
        return !empty($this->data['preferWildcard']);
    }

    /**
     * @param bool $preferWildcard Whether wildcard certificates, which require DNS validation, are preferred
     * @return $this
     */
    public function setPreferWildcard($preferWildcard)
    {
        $this->data['preferWildcard'] = (bool) $preferWildcard;
        return $this;
    }

    /**
     * @return string The external DNS provider used for DNS validation, Domain::ACME_DNS_PROVIDER_NONE when none is used
     */
    public function getDnsProvider()
    {
        return isset($this->data['dnsProvider']) ? (string) $this->data['dnsProvider'] : Domain::ACME_DNS_PROVIDER_NONE;
    }

    /**
     * Sets the external DNS provider, replacing the variables (ie. credentials) of the previous provider.
     *
     * @param string $provider The ACME DNS provider name, or one of the Domain::ACME_DNS_PROVIDER_ constants
     * @param string[] $environment Variables required by the provider, ie. its API credentials
     * @return $this
     */
    public function setDnsProvider($provider, array $environment = [])
    {
        $this->data['dnsProvider'] = (string) $provider;
        $this->data['dnsEnvironment'] = $environment;
        return $this;
    }

    /**
     * @return array The configuration in the format expected by the domain-tls REST API
     */
    public function toArray()
    {
        $data = $this->data;

        // DirectAdmin expects a JSON object, also when there are no variables
        $data['dnsEnvironment'] = (object) (isset($data['dnsEnvironment']) ? $data['dnsEnvironment'] : []);

        return $data;
    }
}
