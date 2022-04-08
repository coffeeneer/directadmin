<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Objects;

use Omines\DirectAdmin\Objects\DomainObject;

/**
 * Encapsulates an email forwarder.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class Redirect extends DomainObject
{
    private const CACHE_DATA = 'redirect';

    /**
     * Construct the object.
     *
     * @param string $path The redirect path
     * @param Domain $domain The containing domain
     * @param string|array|null $config URL encoded config string as returned by CMD_API_REDIRECT
     */
    public function __construct($path, Domain $domain, $config = null)
    {
        parent::__construct($path, $domain);
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
            'select0' => $this->getFrom()
        ]);
    }

    /**
     * Returns the path where to redirect from.
     *
     * @return string
     */
    public function getFrom() {
        return $this->getName();
    }

    /**
     * Returns the url where to redirect to.
     *
     * @return string
     */
    public function getTo()
    {
        return $this->getData('to');
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
    protected function getData($key)
    {
        return $this->getCacheItem(self::CACHE_DATA, $key, function () {
            $result = $this->getContext()->invokeApiGet('REDIRECT', [
                'domain' => $this->getDomainName(),
                'apitype' => 'yes',
            ]);

            return \GuzzleHttp\Psr7\Query::parse($result[$this->getFrom()]);
        });
    }
}
