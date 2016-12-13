<?php

namespace MLocati\TranslationsUpdater;

class ResourceStats
{
    /**
     * Resource handle (empty string for concrete5 core).
     *
     * @var string
     */
    protected $handle = '';

    /**
     * Resource name (empty string for concrete5 core).
     *
     * @var string
     */
    protected $name = '';

    /**
     * Resource version (empty string for packages).
     *
     * @var string
     */
    protected $version = '';
    /**
     * List of localeID => progress.
     *
     * @var array
     */
    protected $localeProgress = array();

    /**
     * URL of the .po file.
     *
     * @var string
     */
    protected $poUrl = '';

    /**
     * URL of the .mo file.
     *
     * @var string
     */
    protected $moUrl = '';

    /**
     * Set the resource handle (empty string for concrete5 core).
     *
     * @param string $value
     *
     * @return static
     */
    public function setHandle($value)
    {
        $this->handle = (string) $value;

        return $this;
    }

    /**
     * Get the resource handle (empty string for concrete5 core).
     *
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Set the resource name (empty string for concrete5 core).
     *
     * @param string $value
     *
     * @return static
     */
    public function setName($value)
    {
        $this->name = (string) $value;

        return $this;
    }

    /**
     * Get the resource name (empty string for concrete5 core).
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $includeVersion
     *
     * @return string
     */
    public function getDisplayName($includeVersion = false)
    {
        if ($this->name === '') {
            $result = ($this->handle === '') ? t('concrete5') : $this->handle;
        } else {
            $result = $this->name;
        }
        if ($includeVersion) {
            $v = $this->getDisplayVersion();
            if ($v !== '') {
                $result .= ' v'.$v;
            }
        }

        return $result;
    }

    /**
     * Set the resource version (empty string for packages).
     *
     * @param string $value
     *
     * @return static
     */
    public function setVersion($value)
    {
        $this->version = (string) $value;

        return $this;
    }

    /**
     * Get the resource version (empty string for packages).
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getDisplayVersion()
    {
        $result = '';
        if ($this->version !== '') {
            if ($this->handle === '' && preg_match('/^core-(?:dev-)?(\d+)$/', $this->version, $m)) {
                $numbers = str_split($m[1], 1);
                if (isset($numbers[1]) && (int) $numbers[1] < 5) {
                    $numbers[1] = $numbers[0].$numbers[1];
                    array_shift($numbers);
                }
                $numbers = array_pad($numbers, 2, '0');
                if (count($numbers) === 5) {
                    $numbers[3] .= array_pop($numbers);
                }
                $result = implode('.', $numbers);
            } else {
                $result = $this->version;
            }
        }

        return $result;
    }

    /**
     * Set the progress for a specific locale.
     *
     * @param string $localeID
     * @param int $progress
     *
     * @return static
     */
    public function setLocaleProgress($localeID, $progress)
    {
        $this->localeProgress[(string) $localeID] = (int) $progress;
    }

    /**
     * Return the list of defined locale IDs.
     *
     * @return string[]
     */
    public function getLocaleIDs()
    {
        return array_keys($this->localeProgress);
    }

    /**
     * Set .po file URL.
     *
     * @param string $value
     *
     * @return static
     */
    public function setPOUrl($value)
    {
        $this->poUrl = (string) $value;

        return $this;
    }

    /**
     * Get .po file URL.
     *
     * @return string
     */
    public function getMOUrl()
    {
        return $this->moUrl;
    }
    /**
     * Set .mo file URL.
     *
     * @param string $value
     *
     * @return static
     */
    public function setMOUrl($value)
    {
        $this->moUrl = (string) $value;

        return $this;
    }

    /**
     * Get .mo file URL.
     *
     * @return string
     */
    public function getPOUrl()
    {
        return $this->poUrl;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = array(
            'handle' => $this->handle,
            'version' => $this->getVersion(),
            'locales' => $this->localeProgress,
        );

        return $result;
    }
}
