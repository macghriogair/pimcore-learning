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
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;
use Pimcore\ExtensionManager;
use Pimcore\Tool;
use Pimcore\Model\Document;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Areablock extends Model\Document\Tag
{

    /**
     * Contains an array of indices, which represent the order of the elements in the block
     *
     * @var array
     */
    public $indices = [];

    /**
     * Current step of the block while iteration
     *
     * @var integer
     */
    public $current = 0;

    /**
     * @var array
     */
    public $currentIndex;


    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "areablock";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return $this->indices;
    }

    /**
     * @see Document\Tag\TagInterface::admin
     */
    public function admin()
    {
        $this->frontend();
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     */
    public function frontend()
    {
        if (!is_array($this->indices)) {
            $this->indices = [];
        }
        reset($this->indices); while ($this->loop());
    }

    /**
     * @param $index
     */
    public function renderIndex($index)
    {
        $this->start();

        $this->currentIndex = $this->indices[$index];
        $this->current = $index;

        $this->blockConstruct();
        $this->blockStart();

        $this->content();

        $this->blockDestruct();
        $this->blockEnd();
        $this->end();
    }

    /**
     *
     */
    public function loop()
    {
        $disabled = false;
        $options = $this->getOptions();
        $manual = false;
        if (is_array($options) && array_key_exists("manual", $options) && $options["manual"] == true) {
            $manual = true;
        }

        if ($this->current > 0) {
            if (!$manual && $this->blockStarted) {
                $this->blockDestruct();
                $this->blockEnd();

                $this->blockStarted = false;
            }
        } else {
            if (!$manual) {
                $this->start();
            }
        }

        if ($this->current < count($this->indices) && $this->current < $this->options["limit"]) {
            $index = current($this->indices);
            next($this->indices);

            $this->currentIndex = $index;
            if (!empty($options["allowed"]) && !in_array($index["type"], $options["allowed"])) {
                $disabled = true;
            }

            if (!$this->isBrickEnabled($index["type"]) && $options['dontCheckEnabled'] != true) {
                $disabled = true;
            }

            $this->blockStarted = false;

            if (!$manual && !$disabled) {
                $this->blockConstruct();
                $this->blockStart();

                $this->blockStarted = true;
                $this->content();
            } elseif (!$manual) {
                $this->current++;
            }

            return true;
        } else {
            if (!$manual) {
                $this->end();
            }

            return false;
        }
    }

    /**
     *
     */
    public function content()
    {

        // create info object and assign it to the view
        $info = new Area\Info();
        try {
            $info->setTag($this);
            $info->setName($this->getName());
            $info->setId($this->currentIndex["type"]);
            $info->setIndex($this->current);
            $info->setPath(str_replace(PIMCORE_DOCUMENT_ROOT, "", $this->getPathForBrick($this->currentIndex["type"])));
            $info->setConfig($this->getBrickConfig($this->currentIndex["type"]));
        } catch (\Exception $e) {
            Logger::err($e);
        }

        if ($this->getView() instanceof \Zend_View) {
            $this->getView()->brick = $info;
            $areas = $this->getAreaDirs();

            $view = $areas[$this->currentIndex["type"]] . "/view.php";
            $action = $areas[$this->currentIndex["type"]] . "/action.php";
            $edit = $areas[$this->currentIndex["type"]] . "/edit.php";
            $options = $this->getOptions();
            $params = [];
            if (isset($options["params"]) && is_array($options["params"]) && array_key_exists($this->currentIndex["type"], $options["params"])) {
                if (is_array($options["params"][$this->currentIndex["type"]])) {
                    $params = $options["params"][$this->currentIndex["type"]];
                }
            }

            // assign parameters to view
            foreach ($params as $key => $value) {
                $this->getView()->assign($key, $value);
            }

            // check for action file
            $actionObject = null;
            if (is_file($action)) {
                include_once($action);

                $actionClassFound = true;

                $actionClass = preg_replace_callback("/[\-_][a-z]/", function ($matches) {
                    $replacement = str_replace(["-", "_"], "", $matches[0]);

                    return strtoupper($replacement);
                }, ucfirst($this->currentIndex["type"]));

                $actionClassname = "\\Pimcore\\Model\\Document\\Tag\\Area\\" . $actionClass;

                if (!class_exists($actionClassname, false)) {
                    // also check the legacy prefixed class name, as this is used by some plugins
                    $actionClassname = "\\Document_Tag_Area_" . ucfirst($this->currentIndex["type"]);
                    if (!class_exists($actionClassname, false)) {
                        $actionClassFound = false;
                    }
                }

                if ($actionClassFound) {
                    $actionObject = new $actionClassname();

                    if ($actionObject instanceof Area\AbstractArea) {
                        $actionObject->setView($this->getView());

                        $areaConfig = new \Zend_Config_Xml($areas[$this->currentIndex["type"]] . "/area.xml");
                        $actionObject->setConfig($areaConfig);

                        // params
                        $params = array_merge($this->view->getAllParams(), $params);
                        $actionObject->setParams($params);

                        if ($info) {
                            $actionObject->setBrick($info);
                        }

                        if (method_exists($actionObject, "action")) {
                            $actionObject->action();
                        }

                        $this->getView()->assign('actionObject', $actionObject);
                    }
                } else {
                    $this->getView()->assign('actionObject', null);
                }
            }

            if (is_file($view)) {
                $editmode = $this->getView()->editmode;

                if ($actionObject && method_exists($actionObject, "getBrickHtmlTagOpen")) {
                    echo $actionObject->getBrickHtmlTagOpen($this);
                } else {
                    echo '<div class="pimcore_area_' . $this->currentIndex["type"] . ' pimcore_area_content">';
                }

                if (is_file($edit) && $editmode) {
                    echo '<div class="pimcore_area_edit_button_' . $this->getName() . ' pimcore_area_edit_button"></div>';

                    // forces the editmode in view.php independent if there's an edit.php or not
                    if (!array_key_exists("forceEditInView", $params) || !$params["forceEditInView"]) {
                        $this->getView()->editmode = false;
                    }
                }

                $this->getView()->template($view);

                if (is_file($edit) && $editmode) {
                    $this->getView()->editmode = true;

                    echo '<div class="pimcore_area_editmode_' . $this->getName() . ' pimcore_area_editmode pimcore_area_editmode_hidden">';
                    $this->getView()->template($edit);
                    echo '</div>';
                }

                if ($actionObject && method_exists($actionObject, "getBrickHtmlTagClose")) {
                    echo $actionObject->getBrickHtmlTagClose($this);
                } else {
                    echo '</div>';
                }

                if ($actionObject && method_exists($actionObject, "postRenderAction")) {
                    $actionObject->postRenderAction();
                }
            }
        }

        $this->current++;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->indices = Tool\Serialize::unserialize($data);
        if (!is_array($this->indices)) {
            $this->indices = [];
        }

        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $this->indices = $data;

        return $this;
    }

    /**
     *
     */
    public function blockConstruct()
    {
        // set the current block suffix for the child elements (0, 1, 3, ...) | this will be removed in Pimcore_View_Helper_Tag::tag
        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        $suffixes[] = $this->indices[$this->current]["key"];
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);
    }

    /**
     *
     */
    public function blockDestruct()
    {
        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        array_pop($suffixes);
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);
    }

    /**
     * @return array
     */
    protected function getToolBarDefaultConfig()
    {
        $buttonWidth = 168;

        // @extjs6
        if (!\Pimcore\Tool\Admin::isExtJS6()) {
            $buttonWidth = 154;
        }

        return [
            "areablock_toolbar" => [
                "title" => "",
                "width" => 172,
                "x" => 20,
                "y" => 50,
                "xAlign" => "left",
                "buttonWidth" => $buttonWidth,
                "buttonMaxCharacters" => 20
            ]
        ];
    }

    /**
     * Is executed at the beginning of the loop and setup some general settings
     *
     * @return $this
     */
    public function start()
    {
        reset($this->indices);
        $this->setupStaticEnvironment();

        // get configuration data for admin
        if (method_exists($this, "getDataEditmode")) {
            $data = $this->getDataEditmode();
        } else {
            $data = $this->getData();
        }

        $configOptions = array_merge($this->getToolBarDefaultConfig(), $this->getOptions());

        $options = [
            "options" => $configOptions,
            "data" => $data,
            "name" => $this->getName(),
            "id" => "pimcore_editable_" . $this->getName(),
            "type" => $this->getType(),
            "inherited" => $this->getInherited()
        ];
        $options = @\Zend_Json::encode($options);
        //$options = base64_encode($options);

        $this->outputEditmode('
            <script type="text/javascript">
                editableConfigurations.push('.$options.');
            </script>
        ');

        // set name suffix for the whole block element, this will be addet to all child elements of the block
        $suffixes = [];
        if (\Zend_Registry::isRegistered('pimcore_tag_block_current')) {
            $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
        }
        $suffixes[] = $this->getName();
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $class = "pimcore_editable pimcore_tag_" . $this->getType();
        if (array_key_exists("class", $this->getOptions())) {
            $class .= (" " . $this->getOptions()["class"]);
        }

        $this->outputEditmode('<div id="pimcore_editable_' . $this->getName() . '" name="' . $this->getName() . '" class="' . $class . '" type="' . $this->getType() . '">');

        return $this;
    }

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     */
    public function end()
    {
        $this->current = 0;

        // remove the suffix which was set by self::start()
        $suffixes = [];
        if (\Zend_Registry::isRegistered('pimcore_tag_block_current')) {
            $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
            array_pop($suffixes);
        }
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode("</div>");
    }

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     */
    public function blockStart()
    {
        $this->outputEditmode('<div class="pimcore_area_entry pimcore_block_entry ' . $this->getName() . '" key="' . $this->indices[$this->current]["key"] . '" type="' . $this->indices[$this->current]["type"] . '">');
        $this->outputEditmode('<div class="pimcore_block_buttons_' . $this->getName() . ' pimcore_block_buttons">');
        $this->outputEditmode('<div class="pimcore_block_plus_' . $this->getName() . ' pimcore_block_plus"></div>');
        $this->outputEditmode('<div class="pimcore_block_minus_' . $this->getName() . ' pimcore_block_minus"></div>');
        $this->outputEditmode('<div class="pimcore_block_up_' . $this->getName() . ' pimcore_block_up"></div>');
        $this->outputEditmode('<div class="pimcore_block_down_' . $this->getName() . ' pimcore_block_down"></div>');
        $this->outputEditmode('<div class="pimcore_block_type_' . $this->getName() . ' pimcore_block_type"></div>');
        $this->outputEditmode('<div class="pimcore_block_options_' . $this->getName() . ' pimcore_block_options"></div>');
        $this->outputEditmode('<div class="pimcore_block_clear_' . $this->getName() . ' pimcore_block_clear"></div>');
        $this->outputEditmode('</div>');
    }

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     */
    public function blockEnd()
    {
        $this->outputEditmode('</div>');
    }

    /**
     * Sends data to the output stream
     *
     * @param string $v
     */
    public function outputEditmode($v)
    {
        if ($this->getEditmode()) {
            echo $v . "\n";
        }
    }

    /**
     * Setup some settings that are needed for blocks
     */
    public function setupStaticEnvironment()
    {

        // setup static environment for blocks
        if (\Zend_Registry::isRegistered("pimcore_tag_block_current")) {
            $current = \Zend_Registry::get("pimcore_tag_block_current");
            if (!is_array($current)) {
                $current = [];
            }
        } else {
            $current = [];
        }

        if (\Zend_Registry::isRegistered("pimcore_tag_block_numeration")) {
            $numeration = \Zend_Registry::get("pimcore_tag_block_numeration");
            if (!is_array($numeration)) {
                $numeration = [];
            }
        } else {
            $numeration = [];
        }

        \Zend_Registry::set("pimcore_tag_block_numeration", $numeration);
        \Zend_Registry::set("pimcore_tag_block_current", $current);
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {

        // we need to set this here otherwise custom areaDir's won't work
        $this->options = $options;

        // read available types
        $areaConfigs = $this->getBrickConfigs();
        $availableAreas = ["name" => [], "index" => []];

        if (isset($options["sorting"]) && is_array($options["sorting"]) && count($options["sorting"])) {
            $availableAreasSort = $options["sorting"];
        } else {
            if (isset($options["allowed"]) && is_array($options["allowed"]) && count($options["allowed"])) {
                $availableAreasSort = $options["allowed"];
            } else {
                $availableAreasSort = false;
            }
        }

        if (!isset($options["allowed"]) || !is_array($options["allowed"])) {
            $options["allowed"] = [];
        }

        foreach ($areaConfigs as $areaName => $areaConfig) {

            // don't show disabled bricks
            if (!isset($options['dontCheckEnabled']) || !$options['dontCheckEnabled']) {
                if (!$this->isBrickEnabled($areaName)) {
                    continue;
                }
            }


            if (empty($options["allowed"]) || in_array($areaName, $options["allowed"])) {
                $n = (string) $areaConfig->name;
                $d = (string) $areaConfig->description;
                $icon = (string) $areaConfig->icon;

                if ($this->view->editmode) {
                    if (empty($icon)) {
                        $path = $this->getPathForBrick($areaName);
                        $iconPath = $path . "/icon.png";
                        if (file_exists($iconPath)) {
                            $icon = str_replace(PIMCORE_DOCUMENT_ROOT, "", $iconPath);
                        }
                    }

                    if ($this->view) {
                        $n = $this->view->translateAdmin((string) $areaConfig->name);
                        $d = $this->view->translateAdmin((string) $areaConfig->description);
                    }
                }

                $sortIndex = false;
                $sortKey = "name"; //allowed and sorting is not set || areaName is not in allowed
                if ($availableAreasSort) {
                    $sortIndex = array_search($areaName, $availableAreasSort);
                    $sortKey   = $sortIndex === false ? $sortKey : "index";
                }

                $availableAreas[$sortKey][] = [
                    "name" => $n,
                    "description" => $d,
                    "type" => $areaName,
                    "icon" => $icon,
                    "sortIndex" => $sortIndex
                ];
            }
        }

        if (count($availableAreas["name"])) {
            // sort with translated names
            usort($availableAreas["name"], function ($a, $b) {
                if ($a["name"] == $b["name"]) {
                    return 0;
                }

                return ($a["name"] < $b["name"]) ? -1 : 1;
            });
        }

        if (count($availableAreas["index"])) {
            // sort by allowed brick config order
            usort($availableAreas["index"], function ($a, $b) {
                return $a["sortIndex"] - $b["sortIndex"];
            });
        }

        $availableAreas = array_merge($availableAreas["index"], $availableAreas["name"]);
        $options["types"] = $availableAreas;

        if (isset($options["group"]) && is_array($options["group"])) {
            $groupingareas = [];
            foreach ($availableAreas as $area) {
                $groupingareas[$area["type"]] = $area["type"];
            }

            $groups = [];
            foreach ($options["group"] as $name => $areas) {
                $n = $name;
                if ($this->view && $this->editmode) {
                    $n = $this->view->translateAdmin($name);
                }
                $groups[$n] = $areas;

                foreach ($areas as $area) {
                    unset($groupingareas[$area]);
                }
            }

            if (count($groupingareas) > 0) {
                $uncatAreas = [];
                foreach ($groupingareas as $area) {
                    $uncatAreas[] = $area;
                }
                $n = "Uncategorized";
                if ($this->view && $this->editmode) {
                    $n = $this->view->translateAdmin($n);
                }
                $groups[$n] = $uncatAreas;
            }

            $options["group"] = $groups;
        }

        if (empty($options["limit"])) {
            $options["limit"] = 1000000;
        }


        $this->options = $options;

        return $this;
    }

    /**
     * Return the amount of block elements
     *
     * @return integer
     */
    public function getCount()
    {
        return count($this->indices);
    }

    /**
     * Return current iteration step
     *
     * @return integer
     */
    public function getCurrent()
    {
        return $this->current-1;
    }

    /**
     * Return current index
     *
     * @return integer
     */
    public function getCurrentIndex()
    {
        return $this->indices[$this->getCurrent()]["key"];
    }

    /**
     * If object was serialized, set the counter back to 0
     */
    public function __wakeup()
    {
        $this->current = 0;
        reset($this->indices);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !(bool) count($this->indices);
    }


    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param $document
     * @param mixed $params
     * @param null $idMapper
     * @throws \Exception
     *
     * @todo replace and with &&
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if (($data->indices === null or is_array($data->indices)) and ($data->current==null or is_numeric($data->current))
            and ($data->currentIndex==null or is_numeric($data->currentIndex))) {
            $indices = $data->indices;
            if ($indices instanceof \stdclass) {
                $indices = (array) $indices;
            }

            $this->indices = $indices;
            $this->current = $data->current;
            $this->currentIndex = $data->currentIndex;
        } else {
            throw new \Exception("cannot get  values from web service import - invalid data");
        }
    }

    /**
     * @return bool
     */
    public function isCustomAreaPath()
    {
        $options = $this->getOptions();

        return array_key_exists("areaDir", $options);
    }

    /**
     * @param $name
     * @return bool
     */
    public function isBrickEnabled($name)
    {
        if ($this->isCustomAreaPath()) {
            return true;
        }

        return ExtensionManager::isEnabled("brick", $name);
    }

    /**
     * @return string
     */
    public function getAreaDirectory()
    {
        $options = $this->getOptions();

        return PIMCORE_DOCUMENT_ROOT . "/" . trim($options["areaDir"], "/");
    }

    /**
     * @param $name
     * @return string
     */
    public function getPathForBrick($name)
    {
        if ($this->isCustomAreaPath()) {
            return $this->getAreaDirectory() . "/" . $name;
        }

        return ExtensionManager::getPathForExtension($name, "brick");
    }

    /**
     * @param $name
     * @throws \Exception
     */
    public function getBrickConfig($name)
    {
        if ($this->isCustomAreaPath()) {
            $path = $this->getAreaDirectory();

            return ExtensionManager::getBrickConfig($name, $path);
        }

        return ExtensionManager::getBrickConfig($name);
    }

    /**
     * @return array
     */
    public function getAreaDirs()
    {
        if ($this->isCustomAreaPath()) {
            return ExtensionManager::getBrickDirectories($this->getAreaDirectory());
        }

        return ExtensionManager::getBrickDirectories();
    }

    /**
     * @return array|mixed
     */
    public function getBrickConfigs()
    {
        if ($this->isCustomAreaPath()) {
            return ExtensionManager::getBrickConfigs($this->getAreaDirectory());
        }

        return ExtensionManager::getBrickConfigs();
    }

    /**
     * @param $name
     *
     * @return Areablock\Item[]
     */
    public function getElement($name)
    {
        // init
        $doc = Model\Document\Page::getById($this->getDocumentId());

        $list = [];
        foreach ($this->getData() as $index => $item) {
            if ($item['type'] == $name) {
                $list[ $index ] = new Areablock\Item($doc, $this->getName(), $item['key']);
            }
        }

        return $list;
    }
}
