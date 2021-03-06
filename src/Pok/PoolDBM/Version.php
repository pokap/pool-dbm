<?php

namespace Pok\PoolDBM;

class Version
{
    /**
     * Current Bundle Version
     */
    const VERSION = '1.0.0-ALPHA3';

    /**
     * @param string $version
     *
     * @return integer
     */
    public static function compare($version)
    {
        $currentVersion = str_replace(' ', '', strtolower(self::VERSION));
        $version = str_replace(' ', '', $version);

        return version_compare($version, $currentVersion);
    }
}
