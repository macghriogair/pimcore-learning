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

use Pimcore\Cache;
use Pimcore\Tool;
use Pimcore\Config;
use Pimcore\Model\Metadata;
use Pimcore\Model\Property;
use Pimcore\Model\Asset;
use Pimcore\Model\WebsiteSetting;
use Pimcore\Model\Document;
use Pimcore\Model\Glossary;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Redirect;
use Pimcore\Model\Element;
use Pimcore\Model;
use Pimcore\Model\Tool\Tag;
use Pimcore\File;

class Admin_SettingsController extends \Pimcore\Controller\Action\Admin
{
    public function metadataAction()
    {
        if ($this->getParam("data")) {
            $this->checkPermission("asset_metadata");

            if ($this->getParam("xaction") == "destroy") {
                $data = \Zend_Json::decode($this->getParam("data"));
                if (\Pimcore\Tool\Admin::isExtJS6()) {
                    $id = $data["id"];
                } else {
                    $id = $data;
                }

                $metadata = Metadata\Predefined::getById($id);
                $metadata->delete();

                $this->_helper->json(["success" => true, "data" => []]);
            } elseif ($this->getParam("xaction") == "update") {
                $data = \Zend_Json::decode($this->getParam("data"));

                // save type
                $metadata = Metadata\Predefined::getById($data["id"]);

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage(), $metadata->getTargetSubtype());
                if ($existingItem && $existingItem->getId() != $metadata->getId()) {
                    $this->_helper->json(["message" => "rule_violation", "success" => false]);
                }

                $metadata->minimize();
                $metadata->save();
                $metadata->expand();

                $this->_helper->json(["data" => $metadata, "success" => true]);
            } elseif ($this->getParam("xaction") == "create") {
                $data = \Zend_Json::decode($this->getParam("data"));
                unset($data["id"]);

                // save type
                $metadata = Metadata\Predefined::create();

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage(), $metadata->getTargetSubtype());
                if ($existingItem) {
                    $this->_helper->json(["message" => "rule_violation", "success" => false]);
                }

                $metadata->save();

                $this->_helper->json(["data" => $metadata, "success" => true]);
            }
        } else {
            // get list of types

            $list = new Metadata\Predefined\Listing();

            if ($this->getParam("filter")) {
                $filter = $this->getParam("filter");
                $list->setFilter(function ($row) use ($filter) {
                    foreach ($row as $value) {
                        if (strpos($value, $filter) !== false) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            $list->load();

            $properties = [];
            if (is_array($list->getDefinitions())) {
                foreach ($list->getDefinitions() as $metadata) {
                    $metadata->expand();
                    $properties[] = $metadata;
                }
            }

            $this->_helper->json(["data" => $properties, "success" => true, "total" => $list->getTotalCount()]);
        }
    }

    public function getPredefinedMetadataAction()
    {
        $type = $this->getParam("type");
        $subType = $this->getParam("subType");
        $list = Metadata\Predefined\Listing::getByTargetType($type, [$subType]);
        $result = [];
        foreach ($list as $item) {
            /** @var $item Metadata\Predefined */
            $item->expand();
            $result[] = $item;
        }


        $this->_helper->json(["data" => $result, "success" => true]);
    }

    public function propertiesAction()
    {
        if ($this->getParam("data")) {
            $this->checkPermission("predefined_properties");

            if ($this->getParam("xaction") == "destroy") {
                $data = \Zend_Json::decode($this->getParam("data"));
                if (\Pimcore\Tool\Admin::isExtJS6()) {
                    $id = $data["id"];
                } else {
                    $id = $data;
                }

                $property = Property\Predefined::getById($id);
                $property->delete();

                $this->_helper->json(["success" => true, "data" => []]);
            } elseif ($this->getParam("xaction") == "update") {
                $data = \Zend_Json::decode($this->getParam("data"));

                // save type
                $property = Property\Predefined::getById($data["id"]);
                $property->setValues($data);

                $property->save();

                $this->_helper->json(["data" => $property, "success" => true]);
            } elseif ($this->getParam("xaction") == "create") {
                $data = \Zend_Json::decode($this->getParam("data"));
                unset($data["id"]);

                // save type
                $property = Property\Predefined::create();
                $property->setValues($data);

                $property->save();

                $this->_helper->json(["data" => $property, "success" => true]);
            }
        } else {
            // get list of types
            $list = new Property\Predefined\Listing();

            if ($this->getParam("filter")) {
                $filter = $this->getParam("filter");
                $list->setFilter(function ($row) use ($filter) {
                    foreach ($row as $value) {
                        if ($value) {
                            $values = is_array($value) ? $value : [$value];

                            foreach ($values as $value) {
                                if (strpos($value, $filter) !== false) {
                                    return true;
                                }
                            }
                        }
                    }

                    return false;
                });
            }

            $list->load();

            $properties = [];
            if (is_array($list->getProperties())) {
                foreach ($list->getProperties() as $property) {
                    $properties[] = $property;
                }
            }

            $this->_helper->json(["data" => $properties, "success" => true, "total" => $list->getTotalCount()]);
        }
    }

    /**
     * @param $root
     * @param $thumbnailName
     */
    private function deleteThumbnailFolders($root, $thumbnailName)
    {
        // delete all thumbnails which are using this config
        /**
         * @param $dir
         * @param $thumbnail
         * @param array $matches
         * @return array
         */
        function delete($dir, $thumbnail, &$matches = [])
        {
            $dirs = glob($dir . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                if (
                    preg_match('@/thumb__' . $thumbnail . '$@', $dir) ||
                    preg_match('@/thumb__' . $thumbnail . '_auto@', $dir) ||
                    preg_match('@/thumb__document_' . $thumbnail . '\-[\d]+$@', $dir) ||
                    preg_match('@/thumb__document_' . $thumbnail . '\-[\d]+_auto@', $dir)
                ) {
                    recursiveDelete($dir);
                }
                delete($dir, $thumbnail, $matches);
            }

            return $matches;
        };

        delete($root, $thumbnailName);
    }

    /**
     * @param Asset\Image\Thumbnail\Config $thumbnail
     */
    private function deleteThumbnailTmpFiles(Asset\Image\Thumbnail\Config $thumbnail)
    {
        $this->deleteThumbnailFolders(PIMCORE_TEMPORARY_DIRECTORY . "/image-thumbnails", $thumbnail->getName());
    }

    /**
     * @param Asset\Video\Thumbnail\Config $thumbnail
     */
    private function deleteVideoThumbnailTmpFiles(Asset\Video\Thumbnail\Config $thumbnail)
    {
        $this->deleteThumbnailFolders(PIMCORE_TEMPORARY_DIRECTORY . "/video-thumbnails", $thumbnail->getName());
    }

    public function getSystemAction()
    {
        $this->checkPermission("system_settings");

        $values = Config::getSystemConfig();

        if (($handle = fopen(PIMCORE_PATH . "/config/data/timezones.csv", "r")) !== false) {
            while (($rowData = fgetcsv($handle, 10000, ",", '"')) !== false) {
                $timezones[] = $rowData[0];
            }
            fclose($handle);
        }

        $locales = Tool::getSupportedLocales();
        $languageOptions = [];
        foreach ($locales as $short => $translation) {
            if (!empty($short)) {
                $languageOptions[] = [
                    "language" => $short,
                    "display" => $translation . " ($short)"
                ];
                $validLanguages[] = $short;
            }
        }

        $valueArray = $values->toArray();
        $valueArray['general']['validLanguage'] = explode(",", $valueArray['general']['validLanguages']);

        //for "wrong" legacy values
        if (is_array($valueArray['general']['validLanguage'])) {
            foreach ($valueArray['general']['validLanguage'] as $existingValue) {
                if (!in_array($existingValue, $validLanguages)) {
                    $languageOptions[] = [
                        "language" => $existingValue,
                        "display" => $existingValue
                    ];
                }
            }
        }

        //cache exclude patterns - add as array
        if (!empty($valueArray['cache']['excludePatterns'])) {
            $patterns = explode(",", $valueArray['cache']['excludePatterns']);
            if (is_array($patterns)) {
                foreach ($patterns as $pattern) {
                    $valueArray['cache']['excludePatternsArray'][] = ["value" => $pattern];
                }
            }
        }

        //remove password from values sent to frontend
        $valueArray['database']["params"]['password'] = "##SECRET_PASS##";

        //admin users as array
        $adminUsers = [];
        $userList = new Model\User\Listing();
        $userList->setCondition("admin = 1 and email is not null and email != ''");
        $users = $userList->load();
        if (is_array($users)) {
            foreach ($users as $user) {
                $adminUsers[] = ["id" => $user->getId(), "username" => $user->getName()];
            }
        }
        $adminUsers[] = ["id" => "", "username" => "-"];

        $response = [
            "values" => $valueArray,
            "adminUsers" => $adminUsers,
            "config" => [
                "timezones" => $timezones,
                "languages" => $languageOptions,
                "client_ip" => Tool::getClientIp(),
                "google_private_key_exists" => file_exists(\Pimcore\Google\Api::getPrivateKeyPath()),
                "google_private_key_path" => \Pimcore\Google\Api::getPrivateKeyPath(),
                "path_separator" => PATH_SEPARATOR
            ]
        ];

        $this->_helper->json($response);
    }

    public function setSystemAction()
    {
        $this->checkPermission("system_settings");

        $values = \Zend_Json::decode($this->getParam("data"));

        // email settings
        $existingConfig = Config::getSystemConfig();
        $existingValues = $existingConfig->toArray();

        // fallback languages
        $fallbackLanguages = [];
        $languages = explode(",", $values["general.validLanguages"]);
        $filteredLanguages = [];
        foreach ($languages as $language) {
            if (isset($values["general.fallbackLanguages." . $language])) {
                $fallbackLanguages[$language] = str_replace(" ", "", $values["general.fallbackLanguages." . $language]);
            }

            if (\Zend_Locale::isLocale($language)) {
                $filteredLanguages[] = $language;
            }
        }

        // check if there's a fallback language endless loop
        foreach ($fallbackLanguages as $sourceLang => $targetLang) {
            $this->checkFallbackLanguageLoop($sourceLang, $fallbackLanguages);
        }

        // delete views if fallback languages has changed or the language is no more available
        if (isset($existingValues['general']['fallbackLanguages']) && is_array($existingValues['general']['fallbackLanguages'])) {
            $fallbackLanguagesChanged = array_diff_assoc($existingValues['general']['fallbackLanguages'],
                $fallbackLanguages);
            $dbName = $existingValues["database"]["params"]["dbname"];
            foreach ($fallbackLanguagesChanged as $language => $dummy) {
                $this->deleteViews($language, $dbName);
            }
        }

        $cacheExcludePatterns = $values["cache.excludePatterns"];
        if (is_array($cacheExcludePatterns)) {
            $cacheExcludePatterns = implode(',', $cacheExcludePatterns);
        }

        $settings = [
            "general" => [
                "timezone" => $values["general.timezone"],
                "path_variable" => $values["general.path_variable"],
                "domain" => $values["general.domain"],
                "redirect_to_maindomain" => $values["general.redirect_to_maindomain"],
                "language" => $values["general.language"],
                "validLanguages" => implode(",", $filteredLanguages),
                "fallbackLanguages" => $fallbackLanguages,
                "defaultLanguage" => $values["general.defaultLanguage"],
                "extjs6" => $values["general.extjs6"],
                "loginscreencustomimage" => $values["general.loginscreencustomimage"],
                "disableusagestatistics" => $values["general.disableusagestatistics"],
                "debug" => $values["general.debug"],
                "debug_ip" => $values["general.debug_ip"],
                "http_auth" => [
                    "username" => $values["general.http_auth.username"],
                    "password" => $values["general.http_auth.password"]
                ],
                "custom_php_logfile" => $values["general.custom_php_logfile"],
                "debugloglevel" => $values["general.debugloglevel"],
                "disable_whoops" => $values["general.disable_whoops"],
                "debug_admin_translations" => $values["general.debug_admin_translations"],
                "devmode" => $values["general.devmode"],
                "logrecipient" => $values["general.logrecipient"],
                "viewSuffix" => $values["general.viewSuffix"],
                "instanceIdentifier" => $values["general.instanceIdentifier"],
                "show_cookie_notice" => $values["general.show_cookie_notice"],
            ],
            "documents" => [
                "versions" => [
                    "days" => $values["documents.versions.days"],
                    "steps" => $values["documents.versions.steps"]
                ],
                "default_controller" => $values["documents.default_controller"],
                "default_action" => $values["documents.default_action"],
                "error_pages" => [
                    "default" => $values["documents.error_pages.default"]
                ],
                "createredirectwhenmoved" => $values["documents.createredirectwhenmoved"],
                "allowtrailingslash" => $values["documents.allowtrailingslash"],
                "generatepreview" => $values["documents.generatepreview"]
            ],
            "objects" => [
                "versions" => [
                    "days" => $values["objects.versions.days"],
                    "steps" => $values["objects.versions.steps"]
                ]
            ],
            "assets" => [
                "versions" => [
                    "days" => $values["assets.versions.days"],
                    "steps" => $values["assets.versions.steps"]
                ],
                "icc_rgb_profile" => $values["assets.icc_rgb_profile"],
                "icc_cmyk_profile" => $values["assets.icc_cmyk_profile"],
                "hide_edit_image" => $values["assets.hide_edit_image"],
                "disable_tree_preview" => $values["assets.disable_tree_preview"]
            ],
            "services" => [
                "google" => [
                    "client_id" => $values["services.google.client_id"],
                    "email" => $values["services.google.email"],
                    "simpleapikey" => $values["services.google.simpleapikey"],
                    "browserapikey" => $values["services.google.browserapikey"]
                ]
            ],
            "cache" => [
                "enabled" => $values["cache.enabled"],
                "lifetime" => $values["cache.lifetime"],
                "excludePatterns" => $cacheExcludePatterns,
                "excludeCookie" => $values["cache.excludeCookie"]
            ],
            "outputfilters" => [
                "less" => $values["outputfilters.less"],
                "lesscpath" => $values["outputfilters.lesscpath"]
            ],
            "webservice" => [
                "enabled" => $values["webservice.enabled"]
            ],
            "httpclient" => [
                "adapter" => $values["httpclient.adapter"],
                "proxy_host" => $values["httpclient.proxy_host"],
                "proxy_port" => $values["httpclient.proxy_port"],
                "proxy_user" => $values["httpclient.proxy_user"],
                "proxy_pass" => $values["httpclient.proxy_pass"],
            ],
            "applicationlog" => [
                "mail_notification" => [
                    "send_log_summary" => $values['applicationlog.mail_notification.send_log_summary'],
                    "filter_priority" => $values['applicationlog.mail_notification.filter_priority'],
                    "mail_receiver" => $values['applicationlog.mail_notification.mail_receiver'],
                ],
                "archive_treshold" => $values['applicationlog.archive_treshold'],
                "archive_alternative_database" => $values['applicationlog.archive_alternative_database'],
            ]
        ];

        // email & newsletter
        foreach (["email", "newsletter"] as $type) {
            $settings[$type] = [
                "sender" => [
                    "name" => $values[$type . ".sender.name"],
                    "email" => $values[$type . ".sender.email"]],
                "return" => [
                    "name" => $values[$type . ".return.name"],
                    "email" => $values[$type . ".return.email"]],
                "method" => $values[$type . ".method"],
                "smtp" => [
                    "host" => $values[$type . ".smtp.host"],
                    "port" => $values[$type . ".smtp.port"],
                    "ssl" => $values[$type . ".smtp.ssl"],
                    "name" => $values[$type . ".smtp.name"],
                    "auth" => [
                        "method" => $values[$type . ".smtp.auth.method"],
                        "username" => $values[$type . ".smtp.auth.username"],
                    ]
                ]
            ];

            $smtpPassword = $values[$type . ".smtp.auth.password"];
            if (!empty($smtpPassword)) {
                $settings[$type]['smtp']['auth']['password'] = $smtpPassword;
            }

            if (array_key_exists($type . ".debug.emailAddresses", $values)) {
                $settings[$type]["debug"] = ["emailaddresses" => $values[$type . ".debug.emailAddresses"]];
            }

            if (array_key_exists($type . ".bounce.type", $values)) {
                $settings[$type]["bounce"] = [
                    "type" => $values[$type . ".bounce.type"],
                    "maildir" => $values[$type . ".bounce.maildir"],
                    "mbox" => $values[$type . ".bounce.mbox"],
                    "imap" => [
                        "host" => $values[$type . ".bounce.imap.host"],
                        "port" => $values[$type . ".bounce.imap.port"],
                        "username" => $values[$type . ".bounce.imap.username"],
                        "password" => $values[$type . ".bounce.imap.password"],
                        "ssl" => $values[$type . ".bounce.imap.ssl"]
                    ]
                ];
            }
        }
        $settings["newsletter"]["usespecific"] = $values["newsletter.usespecific"];


        $settings = array_merge($existingValues, $settings);

        $configFile = \Pimcore\Config::locateConfigFile("system.php");
        File::putPhpFile($configFile, to_php_data_file_format($settings));

        $this->_helper->json(["success" => true]);
    }

    /**
     * @param $source
     * @param $definitions
     * @param array $fallbacks
     * @throws Exception
     */
    protected function checkFallbackLanguageLoop($source, $definitions, $fallbacks = [])
    {
        if (isset($definitions[$source])) {
            $targets = explode(",", $definitions[$source]);
            foreach ($targets as $l) {
                $target = trim($l);
                if ($target) {
                    if (in_array($target, $fallbacks)) {
                        throw new \Exception("Language `$source` | `$target` causes an infinte loop.");
                    }
                    $fallbacks[] = $target;

                    $this->checkFallbackLanguageLoop($target, $definitions, $fallbacks);
                }
            }
        } else {
            throw new \Exception("Language `$source` doesn't exist");
        }
    }

    public function getWeb2printAction()
    {
        $this->checkPermission("web2print_settings");

        $values = Config::getWeb2PrintConfig();
        $valueArray = $values->toArray();

        $optionsString = [];
        if ($valueArray['wkhtml2pdfOptions']) {
            foreach ($valueArray['wkhtml2pdfOptions'] as $key => $value) {
                $tmpStr = "--".$key;
                if ($value !== null && $value !== "") {
                    $tmpStr .= " ".$value;
                }
                $optionsString[] = $tmpStr;
            }
        }
        $valueArray['wkhtml2pdfOptions'] = implode("\n", $optionsString);

        $response = [
            "values" => $valueArray
        ];

        $this->_helper->json($response);
    }

    public function setWeb2printAction()
    {
        $this->checkPermission("web2print_settings");

        $values = \Zend_Json::decode($this->getParam("data"));

        if ($values['wkhtml2pdfOptions']) {
            $optionArray = [];
            $lines = explode("\n", $values['wkhtml2pdfOptions']);
            foreach ($lines as $line) {
                $parts = explode(" ", substr($line, 2));
                $key = trim($parts[0]);
                if ($key) {
                    $value = trim($parts[1]);
                    $optionArray[$key] = $value;
                }
            }
            $values['wkhtml2pdfOptions'] = $optionArray;
        }


        $configFile = \Pimcore\Config::locateConfigFile("web2print.php");
        File::putPhpFile($configFile, to_php_data_file_format($values));

        $this->_helper->json(["success" => true]);
    }

    public function clearCacheAction()
    {
        $this->checkPermission("clear_cache");

        // empty document cache
        Cache::clearAll();

        $db = \Pimcore\Db::get();
        $db->query("truncate table cache_tags");
        $db->query("truncate table cache");

        // empty cache directory
        recursiveDelete(PIMCORE_CACHE_DIRECTORY, false);
        // PIMCORE-1854 - recreate .dummy file => should remain
        \Pimcore\File::put(PIMCORE_CACHE_DIRECTORY . "/.dummy", "");

        \Pimcore::getEventManager()->trigger("system.cache.clear", $this);

        $this->_helper->json(["success" => true]);
    }

    public function clearOutputCacheAction()
    {
        $this->checkPermission("clear_cache");

        // remove "output" out of the ignored tags, if a cache lifetime is specified
        Cache::removeIgnoredTagOnClear("output");

        // empty document cache
        Cache::clearTags(["output", "output_lifetime"]);

        \Pimcore::getEventManager()->trigger("system.cache.clearOutputCache", $this);

        $this->_helper->json(["success" => true]);
    }

    public function clearTemporaryFilesAction()
    {
        $this->checkPermission("clear_temp_files");

        // public files
        recursiveDelete(PIMCORE_TEMPORARY_DIRECTORY, false);

        // system files
        recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY, false);

        // recreate .dummy files # PIMCORE-2629
        \Pimcore\File::put(PIMCORE_TEMPORARY_DIRECTORY . "/.dummy", "");
        \Pimcore\File::put(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/.dummy", "");

        \Pimcore::getEventManager()->trigger("system.cache.clearTemporaryFiles", $this);

        $this->_helper->json(["success" => true]);
    }


    public function staticroutesAction()
    {
        if ($this->getParam("data")) {
            $this->checkPermission("routes");

            $data = \Zend_Json::decode($this->getParam("data"));

            if (is_array($data)) {
                foreach ($data as &$value) {
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                }
            }

            if ($this->getParam("xaction") == "destroy") {
                $data = \Zend_Json::decode($this->getParam("data"));
                if (\Pimcore\Tool\Admin::isExtJS6()) {
                    $id = $data["id"];
                } else {
                    $id = $data;
                }

                $route = Staticroute::getById($id);
                $route->delete();

                $this->_helper->json(["success" => true, "data" => []]);
            } elseif ($this->getParam("xaction") == "update") {
                // save routes
                $route = Staticroute::getById($data["id"]);
                $route->setValues($data);

                $route->save();

                $this->_helper->json(["data" => $route, "success" => true]);
            } elseif ($this->getParam("xaction") == "create") {
                unset($data["id"]);

                // save route
                $route = new Staticroute();
                $route->setValues($data);

                $route->save();

                $this->_helper->json(["data" => $route, "success" => true]);
            }
        } else {
            // get list of routes

            $list = new Staticroute\Listing();

            if ($this->getParam("filter")) {
                $filter = $this->getParam("filter");
                $list->setFilter(function ($row) use ($filter) {
                    foreach ($row as $value) {
                        if (! is_scalar($value)) {
                            continue;
                        }
                        if (strpos((string)$value, $filter) !== false) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            $list->load();

            $routes = [];
            /** @var  $route Staticroute */
            foreach ($list->getRoutes() as $route) {
                if (is_array($route->getSiteId())) {
                    $route = json_encode($route);
                    $route = json_decode($route, true);
                    $route["siteId"] = implode(",", $route["siteId"]);
                }
                $routes[] = $route;
            }

            $this->_helper->json(["data" => $routes, "success" => true, "total" => $list->getTotalCount()]);
        }

        $this->_helper->json(false);
    }

    public function getAvailableLanguagesAction()
    {
        if ($languages = Tool::getValidLanguages()) {
            $this->_helper->json($languages);
        }

        $t = new Model\Translation\Website();
        $this->_helper->json($t->getAvailableLanguages());
    }

    public function getAvailableAdminLanguagesAction()
    {
        $langs = [];
        $availableLanguages = Tool\Admin::getLanguages();
        $locales = Tool::getSupportedLocales();

        foreach ($availableLanguages as $lang) {
            if (array_key_exists($lang, $locales)) {
                $langs[] = [
                    "language" => $lang,
                    "display" => $locales[$lang]
                ];
            }
        }

        $this->_helper->json($langs);
    }

    public function redirectsAction()
    {
        if ($this->getParam("data")) {
            $this->checkPermission("redirects");

            if ($this->getParam("xaction") == "destroy") {
                $data = \Zend_Json::decode($this->getParam("data"));
                if (\Pimcore\Tool\Admin::isExtJS6()) {
                    $id = $data["id"];
                } else {
                    $id = $data;
                }

                $redirect = Redirect::getById($id);
                $redirect->delete();

                $this->_helper->json(["success" => true, "data" => []]);
            } elseif ($this->getParam("xaction") == "update") {
                $data = \Zend_Json::decode($this->getParam("data"));

                // save redirect
                $redirect = Redirect::getById($data["id"]);

                if ($data["target"]) {
                    if ($doc = Document::getByPath($data["target"])) {
                        $data["target"] = $doc->getId();
                    }
                }

                $redirect->setValues($data);

                $redirect->save();

                $redirectTarget = $redirect->getTarget();
                if (is_numeric($redirectTarget)) {
                    if ($doc = Document::getById(intval($redirectTarget))) {
                        $redirect->setTarget($doc->getRealFullPath());
                    }
                }
                $this->_helper->json(["data" => $redirect, "success" => true]);
            } elseif ($this->getParam("xaction") == "create") {
                $data = \Zend_Json::decode($this->getParam("data"));
                unset($data["id"]);

                // save route
                $redirect = new Redirect();

                if ($data["target"]) {
                    if ($doc = Document::getByPath($data["target"])) {
                        $data["target"] = $doc->getId();
                    }
                }

                $redirect->setValues($data);

                $redirect->save();

                $redirectTarget = $redirect->getTarget();
                if (is_numeric($redirectTarget)) {
                    if ($doc = Document::getById(intval($redirectTarget))) {
                        $redirect->setTarget($doc->getRealFullPath());
                    }
                }
                $this->_helper->json(["data" => $redirect, "success" => true]);
            }
        } else {
            // get list of routes

            $list = new Redirect\Listing();
            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($this->getParam("filter")) {
                $list->setCondition("`source` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `target` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
            }


            $list->load();

            $redirects = [];
            foreach ($list->getRedirects() as $redirect) {
                if ($link = $redirect->getTarget()) {
                    if (is_numeric($link)) {
                        if ($doc = Document::getById(intval($link))) {
                            $redirect->setTarget($doc->getRealFullPath());
                        }
                    }
                }

                $redirects[] = $redirect;
            }

            $this->_helper->json(["data" => $redirects, "success" => true, "total" => $list->getTotalCount()]);
        }

        $this->_helper->json(false);
    }


    public function glossaryAction()
    {
        if ($this->getParam("data")) {
            $this->checkPermission("glossary");

            Cache::clearTag("glossary");

            if ($this->getParam("xaction") == "destroy") {
                $data = \Zend_Json::decode($this->getParam("data"));
                if (\Pimcore\Tool\Admin::isExtJS6()) {
                    $id = $data["id"];
                } else {
                    $id = $data;
                }

                $glossary = Glossary::getById($id);
                $glossary->delete();

                $this->_helper->json(["success" => true, "data" => []]);
            } elseif ($this->getParam("xaction") == "update") {
                $data = \Zend_Json::decode($this->getParam("data"));

                // save glossary
                $glossary = Glossary::getById($data["id"]);

                if ($data["link"]) {
                    if ($doc = Document::getByPath($data["link"])) {
                        $tmpLink = $data["link"];
                        $data["link"] = $doc->getId();
                    }
                }


                $glossary->setValues($data);

                $glossary->save();

                if ($link = $glossary->getLink()) {
                    if (intval($link) > 0) {
                        if ($doc = Document::getById(intval($link))) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                $this->_helper->json(["data" => $glossary, "success" => true]);
            } elseif ($this->getParam("xaction") == "create") {
                $data = \Zend_Json::decode($this->getParam("data"));
                unset($data["id"]);

                // save glossary
                $glossary = new Glossary();

                if ($data["link"]) {
                    if ($doc = Document::getByPath($data["link"])) {
                        $tmpLink = $data["link"];
                        $data["link"] = $doc->getId();
                    }
                }

                $glossary->setValues($data);

                $glossary->save();

                if ($link = $glossary->getLink()) {
                    if (intval($link) > 0) {
                        if ($doc = Document::getById(intval($link))) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                $this->_helper->json(["data" => $glossary, "success" => true]);
            }
        } else {
            // get list of glossaries

            $list = new Glossary\Listing();
            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($this->getParam("filter")) {
                $list->setCondition("`text` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
            }

            $list->load();

            $glossaries = [];
            foreach ($list->getGlossary() as $glossary) {
                if ($link = $glossary->getLink()) {
                    if (intval($link) > 0) {
                        if ($doc = Document::getById(intval($link))) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                $glossaries[] = $glossary;
            }

            $this->_helper->json(["data" => $glossaries, "success" => true, "total" => $list->getTotalCount()]);
        }

        $this->_helper->json(false);
    }

    public function getAvailableSitesAction()
    {
        $sitesList = new Model\Site\Listing();
        $sitesObjects = $sitesList->load();
        $sites = [[
            "id" => \Pimcore\Tool\Admin::isExtJS6() ? "default" : "",
            "rootId" => 1,
            "domains" => "",
            "rootPath" => "/",
            "domain" => $this->view->translate("main_site")
        ]];

        foreach ($sitesObjects as $site) {
            if ($site->getRootDocument()) {
                if ($site->getMainDomain()) {
                    $sites[] = [
                        "id" => $site->getId(),
                        "rootId" => $site->getRootId(),
                        "domains" => implode(",", $site->getDomains()),
                        "rootPath" => $site->getRootPath(),
                        "domain" => $site->getMainDomain()
                    ];
                }
            } else {
                // site is useless, parent doesn't exist anymore
                $site->delete();
            }
        }

        $this->_helper->json($sites);
    }

    public function getAvailableCountriesAction()
    {
        $countries = \Zend_Locale::getTranslationList('territory');
        asort($countries);

        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    "key" => $translation . " (" . $short . ")" ,
                    "value" => $short
                ];
            }
        }

        $result = ["data" => $options, "success" => true, "total" => count($options)];

        $this->_helper->json($result);
    }


    public function thumbnailAdapterCheckAction()
    {
        $instance = \Pimcore\Image::getInstance();
        if ($instance instanceof \Pimcore\Image\Adapter\GD) {
            echo '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->view->translate("important_use_imagick_pecl_extensions_for_best_results_gd_is_just_a_fallback_with_less_quality") .
                '</span>';
        }

        exit;
    }


    public function thumbnailTreeAction()
    {
        $this->checkPermission("thumbnails");

        $thumbnails = [];

        $list = new Asset\Image\Thumbnail\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $thumbnails[] = [
                "id" => $item->getName(),
                "text" => $item->getName()
            ];
        }

        $this->_helper->json($thumbnails);
    }

    public function thumbnailAddAction()
    {
        $this->checkPermission("thumbnails");

        $success = false;

        $pipe = Asset\Image\Thumbnail\Config::getByName($this->getParam("name"));

        if (!$pipe) {
            $pipe = new Asset\Image\Thumbnail\Config();
            $pipe->setName($this->getParam("name"));
            $pipe->save();

            $success = true;
        }

        $this->_helper->json(["success" => $success, "id" => $pipe->getName()]);
    }

    public function thumbnailDeleteAction()
    {
        $this->checkPermission("thumbnails");

        $pipe = Asset\Image\Thumbnail\Config::getByName($this->getParam("name"));
        $pipe->delete();

        $this->_helper->json(["success" => true]);
    }


    public function thumbnailGetAction()
    {
        $this->checkPermission("thumbnails");

        $pipe = Asset\Image\Thumbnail\Config::getByName($this->getParam("name"));

        $this->_helper->json($pipe);
    }


    public function thumbnailUpdateAction()
    {
        $this->checkPermission("thumbnails");

        $pipe = Asset\Image\Thumbnail\Config::getByName($this->getParam("name"));
        $settingsData = \Zend_Json::decode($this->getParam("settings"));
        $mediaData = \Zend_Json::decode($this->getParam("medias"));

        foreach ($settingsData as $key => $value) {
            $setter = "set" . ucfirst($key);
            if (method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }
        }

        $pipe->resetItems();

        foreach ($mediaData as $mediaName => $items) {
            foreach ($items as $item) {
                $type = $item["type"];
                unset($item["type"]);

                $pipe->addItem($type, $item, $mediaName);
            }
        }

        $pipe->save();

        $this->deleteThumbnailTmpFiles($pipe);

        $this->_helper->json(["success" => true]);
    }


    public function videoThumbnailAdapterCheckAction()
    {
        if (!\Pimcore\Video::isAvailable()) {
            echo '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->view->translate("php_cli_binary_and_or_ffmpeg_binary_setting_is_missing") .
                '</span>';
        }

        exit;
    }


    public function videoThumbnailTreeAction()
    {
        $this->checkPermission("thumbnails");

        $thumbnails = [];

        $list = new Asset\Video\Thumbnail\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $thumbnails[] = [
                "id" => $item->getName(),
                "text" => $item->getName()
            ];
        }

        $this->_helper->json($thumbnails);
    }

    public function videoThumbnailAddAction()
    {
        $this->checkPermission("thumbnails");

        $success = false;

        $pipe = Asset\Video\Thumbnail\Config::getByName($this->getParam("name"));

        if (!$pipe) {
            $pipe = new Asset\Video\Thumbnail\Config();
            $pipe->setName($this->getParam("name"));
            $pipe->save();

            $success = true;
        }

        $this->_helper->json(["success" => $success, "id" => $pipe->getName()]);
    }

    public function videoThumbnailDeleteAction()
    {
        $this->checkPermission("thumbnails");

        $pipe = Asset\Video\Thumbnail\Config::getByName($this->getParam("name"));
        $pipe->delete();

        $this->_helper->json(["success" => true]);
    }


    public function videoThumbnailGetAction()
    {
        $this->checkPermission("thumbnails");

        $pipe = Asset\Video\Thumbnail\Config::getByName($this->getParam("name"));
        $this->_helper->json($pipe);
    }


    public function videoThumbnailUpdateAction()
    {
        $this->checkPermission("thumbnails");

        $pipe = Asset\Video\Thumbnail\Config::getByName($this->getParam("name"));
        $data = \Zend_Json::decode($this->getParam("configuration"));

        $items = [];
        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if (method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }

            if (strpos($key, "item.") === 0) {
                $cleanKeyParts = explode(".", $key);
                $items[$cleanKeyParts[1]][$cleanKeyParts[2]] = $value;
            }
        }

        $pipe->resetItems();
        foreach ($items as $item) {
            $type = $item["type"];
            unset($item["type"]);

            $pipe->addItem($type, $item);
        }

        $pipe->save();

        $this->deleteVideoThumbnailTmpFiles($pipe);

        $this->_helper->json(["success" => true]);
    }

    public function robotsTxtAction()
    {
        $this->checkPermission("robots.txt");

        $siteSuffix = "";
        if ($this->getParam("site")) {
            $siteSuffix = "-" . $this->getParam("site");
        }

        $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . "/robots" . $siteSuffix . ".txt";

        if ($this->getParam("data") !== null) {
            // save data
            \Pimcore\File::put($robotsPath, $this->getParam("data"));

            $this->_helper->json([
                "success" => true
            ]);
        } else {
            // get data
            $data = "";
            if (is_file($robotsPath)) {
                $data = file_get_contents($robotsPath);
            }

            $this->_helper->json([
                "success" => true,
                "data" => $data,
                "onFileSystem" => file_exists(PIMCORE_DOCUMENT_ROOT . "/robots.txt")
            ]);
        }
    }



    public function tagManagementTreeAction()
    {
        $this->checkPermission("tag_snippet_management");

        $tags = [];

        $list = new Tag\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $tags[] = [
                "id" => $item->getName(),
                "text" => $item->getName()
            ];
        }

        $this->_helper->json($tags);
    }

    public function tagManagementAddAction()
    {
        $this->checkPermission("tag_snippet_management");

        $success = false;

        $tag = Model\Tool\Tag\Config::getByName($this->getParam("name"));

        if (!$tag) {
            $tag = new Model\Tool\Tag\Config();
            $tag->setName($this->getParam("name"));
            $tag->save();

            $success = true;
        }

        $this->_helper->json(["success" => $success, "id" => $tag->getName()]);
    }

    public function tagManagementDeleteAction()
    {
        $this->checkPermission("tag_snippet_management");

        $tag = Model\Tool\Tag\Config::getByName($this->getParam("name"));
        $tag->delete();

        $this->_helper->json(["success" => true]);
    }


    public function tagManagementGetAction()
    {
        $this->checkPermission("tag_snippet_management");

        $tag = Model\Tool\Tag\Config::getByName($this->getParam("name"));
        $this->_helper->json($tag);
    }


    public function tagManagementUpdateAction()
    {
        $this->checkPermission("tag_snippet_management");

        $tag = Model\Tool\Tag\Config::getByName($this->getParam("name"));
        $data = \Zend_Json::decode($this->getParam("configuration"));

        $items = [];
        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if (method_exists($tag, $setter)) {
                $tag->$setter($value);
            }

            if (strpos($key, "item.") === 0) {
                $cleanKeyParts = explode(".", $key);
                $items[$cleanKeyParts[1]][$cleanKeyParts[2]] = $value;
            }
        }

        $tag->resetItems();
        foreach ($items as $item) {
            $tag->addItem($item);
        }

        // parameters get/post
        $params = [];
        for ($i=0; $i<5; $i++) {
            $params[] = [
                "name" => $data["params.name" . $i],
                "value" => $data["params.value" . $i]
            ];
        }
        $tag->setParams($params);

        if ($this->getParam("name") != $data["name"]) {
            $tag->setName($this->getParam("name")); // set the old name again, so that the old file get's deleted
            $tag->delete(); // delete the old config / file
            $tag->setName($data["name"]);
        }

        $tag->save();

        $this->_helper->json(["success" => true]);
    }

    public function websiteSettingsAction()
    {
        try {
            if ($this->getParam("data")) {
                $this->checkPermission("website_settings");

                $data = \Zend_Json::decode($this->getParam("data"));

                if (is_array($data)) {
                    foreach ($data as &$value) {
                        $value = trim($value);
                    }
                }

                if ($this->getParam("xaction") == "destroy") {
                    if (\Pimcore\Tool\Admin::isExtJS6()) {
                        $id = $data["id"];
                    } else {
                        $id = $data;
                    }

                    $setting = WebsiteSetting::getById($id);
                    $setting->delete();

                    $this->_helper->json(["success" => true, "data" => []]);
                } elseif ($this->getParam("xaction") == "update") {
                    // save routes
                    $setting = WebsiteSetting::getById($data["id"]);

                    switch ($setting->getType()) {
                        case "document":
                        case "asset":
                        case "object":
                            if (isset($data["data"])) {
                                $path = $data["data"];
                                $element = Element\Service::getElementByPath($setting->getType(), $path);
                                $data["data"] = $element ? $element->getId() : null;
                            }
                            break;
                    }

                    $setting->setValues($data);

                    $setting->save();

                    $data = $this->getWebsiteSettingForEditMode($setting);

                    $this->_helper->json(["data" => $data, "success" => true]);
                } elseif ($this->getParam("xaction") == "create") {
                    unset($data["id"]);

                    // save route
                    $setting = new WebsiteSetting();
                    $setting->setValues($data);

                    $setting->save();

                    $this->_helper->json(["data" => $setting, "success" => true]);
                }
            } else {
                // get list of routes

                $list = new WebsiteSetting\Listing();

                $list->setLimit($this->getParam("limit"));
                $list->setOffset($this->getParam("start"));

                $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
                if ($sortingSettings['orderKey']) {
                    $list->setOrderKey($sortingSettings['orderKey']);
                    $list->setOrder($sortingSettings['order']);
                } else {
                    $list->setOrderKey("name");
                    $list->setOrder("asc");
                }

                if ($this->getParam("filter")) {
                    $list->setCondition("`name` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
                }

                $totalCount = $list->getTotalCount();
                $list = $list->load();

                $settings = [];
                foreach ($list as $item) {
                    $resultItem = $this->getWebsiteSettingForEditMode($item);
                    $settings[] = $resultItem;
                }

                $this->_helper->json(["data" => $settings, "success" => true, "total" => $totalCount]);
            }
        } catch (\Exception $e) {
            throw $e;
            $this->_helper->json(false);
        }

        $this->_helper->json(false);
    }

    /**
     * @param $item
     * @return array
     */
    private function getWebsiteSettingForEditMode($item)
    {
        $resultItem = [
            "id" => $item->getId(),
            "name" => $item->getName(),
            "type" => $item->getType(),
            "data" => null,
            "siteId" => $item->getSiteId(),
            "creationDate" => $item->getCreationDate(),
            "modificationDate" => $item->getModificationDate()
        ];


        switch ($item->getType()) {
            case "document":
            case "asset":
            case "object":
                $element = Element\Service::getElementById($item->getType(), $item->getData());
                if ($element) {
                    $resultItem["data"] = $element->getRealFullPath();
                }
                break;
            default:
                $resultItem["data"] = $item->getData("data");
                break;
        }

        return $resultItem;
    }

    public function getAvailableAlgorithmsAction()
    {
        $options = [
            [
                'key'   => 'password_hash',
                'value' => 'password_hash',
            ]
        ];

        $algorithms = hash_algos();
        foreach ($algorithms as $algorithm) {
            $options[] = [
                "key" => $algorithm,
                "value" => $algorithm
            ];
        }

        $result = ["data" => $options, "success" => true, "total" => count($options)];

        $this->_helper->json($result);
    }

    /**
     * deleteViews
     * delete views for localized fields when languages are removed to
     * prevent mysql errors
     * @param $language
     * @param $dbName
     */
    protected function deleteViews($language, $dbName)
    {
        $db = \Pimcore\Db::get();
        $views = $db->fetchAll("SHOW FULL TABLES IN " . $db->quoteIdentifier($dbName) . " WHERE TABLE_TYPE LIKE 'VIEW'");

        foreach ($views as $view) {
            if (preg_match("/^object_localized_[0-9]+_" . $language . "$/", $view["Tables_in_" . $dbName])) {
                $sql = "DROP VIEW " . $db->quoteIdentifier($view["Tables_in_" . $dbName]);
                $db->query($sql);
            }
        }
    }
}
