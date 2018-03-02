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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\File;
use Pimcore\Model\User;
use Pimcore\Tool\Text\Csv;

class Admin
{
    /**
     * @var array
     */
    protected static $availableLanguages;

    /**
     * Finds the translation file for a given language
     *
     * @static
     * @param  string $language
     * @return string
     */
    public static function getLanguageFile($language)
    {

        //first try website languages dir, as fallback the core dir
        $languageFile = PIMCORE_CONFIGURATION_DIRECTORY . "/texts/" . $language . ".json";
        if (!is_file($languageFile)) {
            $languageFile =  PIMCORE_PATH . "/config/texts/" . $language . ".json";
        }

        return $languageFile;
    }

    /**
     * finds installed languages
     *
     * @static
     * @return array
     */
    public static function getLanguages()
    {
        if (!self::$availableLanguages) {
            $languages = [];
            $languageDirs = [PIMCORE_PATH . "/config/texts/", PIMCORE_CONFIGURATION_DIRECTORY . "/texts/"];
            foreach ($languageDirs as $filesDir) {
                if (is_dir($filesDir)) {
                    $files = scandir($filesDir);
                    foreach ($files as $file) {
                        if (is_file($filesDir . $file)) {
                            $parts = explode(".", $file);
                            if ($parts[1] == "json") {
                                if (\Zend_Locale::isLocale($parts[0])) {
                                    $languages[] = $parts[0];
                                }
                            }
                        }
                    }
                }
            }
            self::$availableLanguages = $languages;
        }

        return self::$availableLanguages;
    }

    /**
     * @static
     * @param  $scriptContent
     * @return mixed
     */
    public static function getMinimizedScriptPath($scriptContent)
    {
        $scriptPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/minified_javascript_core_".md5($scriptContent).".js";

        if (!is_file($scriptPath)) {
            File::put($scriptPath, $scriptContent);
        }

        $params = [
            "scripts" =>  basename($scriptPath),
            "_dc" => \Pimcore\Version::getRevision()
        ];

        return "/admin/misc/script-proxy?" . array_toquerystring($params);
    }


    /**
     * @param $file
     * @return \stdClass
     */
    public static function determineCsvDialect($file)
    {

        // minimum 10 lines, to be sure take more
        $sample = "";
        for ($i=0; $i<10; $i++) {
            $sample .= implode("", array_slice(file($file), 0, 11)); // grab 20 lines
        }

        try {
            $sniffer = new Csv();
            $dialect = $sniffer->detect($sample);
        } catch (\Exception $e) {
            // use default settings
            $dialect = new \stdClass();
        }

        // validity check
        if (!in_array($dialect->delimiter, [";", ",", "\t", "|", ":"])) {
            $dialect->delimiter = ";";
        }

        return $dialect;
    }


    /**
     * @static
     * @return string
     */
    public static function getMaintenanceModeFile()
    {
        return PIMCORE_CONFIGURATION_DIRECTORY . "/maintenance.php";
    }

    /**
     * @param null $sessionId
     * @throws \Exception
     * @throws \Zend_Config_Exception
     */
    public static function activateMaintenanceMode($sessionId = null)
    {
        if (empty($sessionId)) {
            $sessionId = session_id();
        }

        if (empty($sessionId)) {
            throw new \Exception("It's not possible to activate the maintenance mode without a session-id");
        }

        File::putPhpFile(self::getMaintenanceModeFile(), to_php_data_file_format([
            "sessionId" => $sessionId
        ]));

        @chmod(self::getMaintenanceModeFile(), 0777); // so it can be removed also via FTP, ...

        \Pimcore::getEventManager()->trigger("system.maintenance.activate");
    }

    /**
     * @static
     */
    public static function deactivateMaintenanceMode()
    {
        @unlink(self::getMaintenanceModeFile());

        \Pimcore::getEventManager()->trigger("system.maintenance.deactivate");
    }

    /**
     * @static
     * @return bool
     */
    public static function isInMaintenanceMode()
    {
        $file = self::getMaintenanceModeFile();

        if (is_file($file)) {
            $conf = include($file);
            if (isset($conf["sessionId"])) {
                return true;
            } else {
                @unlink($file);
            }
        }

        return false;
    }

    /**
     * @static
     * @return \Pimcore\Model\User
     */
    public static function getCurrentUser()
    {
        if (\Zend_Registry::isRegistered("pimcore_admin_user")) {
            $user = \Zend_Registry::get("pimcore_admin_user");

            return $user;
        }

        return null;
    }


    /**
     * @return true if in EXT JS5 mode
     */
    public static function isExtJS6()
    {
        if (isset($_SERVER["HTTP_X_PIMCORE_EXTJS_VERSION_MAJOR"]) && $_SERVER["HTTP_X_PIMCORE_EXTJS_VERSION_MAJOR"] == 6) {
            return true;
        }

        if (isset($_SERVER["HTTP_X_PIMCORE_EXTJS_VERSION_MAJOR"]) && $_SERVER["HTTP_X_PIMCORE_EXTJS_VERSION_MAJOR"] < 6) {
            return false;
        }

        if (isset($_REQUEST["extjs3"])) {
            return false;
        }

        if (isset($_REQUEST["extjs6"])) {
            return true;
        }

        $config = \Pimcore\Config::getSystemConfig();
        $mainSwitch = $config->general->extjs6;
        if ($mainSwitch) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param string|array $languages
     * @param bool $returnLanguageArray
     * @return string
     */
    public static function reorderWebsiteLanguages($user, $languages, $returnLanguageArray = false)
    {
        if (!is_array($languages)) {
            $languages = explode(",", $languages);
        }

        $contentLanguages = $user->getContentLanguages();
        if ($contentLanguages) {
            $contentLanguages = array_intersect($contentLanguages, $languages);
            $newLanguages = array_diff($languages, $contentLanguages);
            $languages = array_merge($contentLanguages, $newLanguages);
        }
        if ($returnLanguageArray) {
            return $languages;
        }

        return implode(",", $languages);
    }
}
