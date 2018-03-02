<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

class Version
{
    /**
     * @var string
     */
    public static $version = '5.2.0';

    /**
     * @var int
     */
    public static $revision = 213;

    /**
     * @var string
     */
    public static $buildDate = '2018-03-02T10:06:24+00:00';

    /**
     * @return string
     */
    public static function getVersion()
    {
        return self::$version;
    }

    /**
     * @return int
     */
    public static function getRevision()
    {
        return self::$revision;
    }

    /**
     * @return string
     */
    public static function getBuildDate(): string
    {
        return self::$buildDate;
    }
}