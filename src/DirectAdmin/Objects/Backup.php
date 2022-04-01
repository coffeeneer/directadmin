<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Objects;

/**
 * Backup.
 *
 */
class Backup extends DomainObject
{
    public const CONTENT_OPTIONS = [
        'domain',
        'subdomain',
        'email',
        'email_data',
        'emailsettings',
        'forwarder',
        'autoresponder',
        'vacation',
        'list',
        'ftp',
        'ftpsettings',
        'database',
        'database_data',
        'trash'
    ];

    private const BACKUP_FOLDER = '/backups/';

    private const CACHE_CONTENTS = 'contents';

    /**
     * Construct the object.
     *
     * @param Domain $domain The containing domain
     */
    public function __construct($name, Domain $domain)
    {
        parent::__construct($name, $domain);
    }

    /**
     * Creates a new backup.
     *
     * @param Domain $domain Domain to create a backup for
     * @param array $contents The contents
     * @return void
     */
    public static function create(Domain $domain, array $contents)
    {
        $selection = [];
        foreach ($contents as $key => $value) {
            $selection['select' . $key] = $value;
        }

        $domain->invokePost('SITE_BACKUP', 'backup', $selection);
    }

    /**
     * Restores a backup.
     *
     * @param array $contents The contents to restore
     * @return void
     */
    public function restore(array $contents)
    {
        $arguments = [
            'file' => $this->getFileName()
        ];

        foreach ($contents as $key => $value) {
            $arguments['select' . $key] = $value;
        }

        $this->invokePost('SITE_BACKUP', 'restore', $arguments);
    }

    /**
     * Deletes the backup.
     */
    public function delete()
    {
        $this->invokePost('FILE_MANAGER', 'multiple', [
            'button' => 'delete',
            'select0' => $this->getFilePath()
        ]);
    }

    /**
     * Gets the backup filename
     *
     * @return string The filename of the backup
     */
    public function getFilename() {
        return $this->getName();
    }

    /**
     * Gets the backup file path
     *
     * @return string The filename of the backup
     */
    public function getFilePath() {
        return self::BACKUP_FOLDER . $this->getFilename();
    }

    /**
     * Gets a list of the contents saved in the backup file
     *
     * @return array A list of the contents backed up in the file
     */
    public function getContents() {
        return $this->getContentData('contents');
    }

    /**
     * Cache wrapper to keep content data up to date.
     *
     * @param string $key
     * @return mixed
     */
    protected function getContentData($key)
    {
        return $this->getCacheItem(self::CACHE_CONTENTS, $key, function () {
            $result = $this->getContext()->invokeApiGet('SITE_BACKUP', [
                'domain' => $this->getDomainName(),
                'action' => 'view',
                'file' => $this->getFileName()
            ]);

            return [
                'contents' => $result
            ];
        });
    }
}
