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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

/**
 * @deprecated will be removed entirely in Pimcore 5
 * @method \Pimcore\Model\Object\Data\KeyValue\Dao getDao()
 */
class KeyValue extends Model\AbstractModel
{

    /**
     * @var Object\ClassDefinition
     */
    public $class;

    /**
     * @var int
     */
    public $objectId;

    /**
     * @var array
     */
    public $arr = [];

    /** Whether multivalent values are allowed.
     * @var
     */
    protected $multivalent;

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @param Object\ClassDefinition $class
     * @return $this
     */
    public function setClass(Object\ClassDefinition $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return Object\ClassDefinition
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $str = "Object\\Data\\KeyValue oid=" . $this->objectId . "\n";
        $props = $this->getInternalProperties();

        if (is_array($props)) {
            foreach ($props as $prop) {
                $str .= "    " . $prop["key"] . "=>" . $prop["value"] . "\n";
            }
        }

        return $str;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param $objectId
     * @return $this
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * @param $arr
     * @return $this
     */
    public function setProperties($arr)
    {
        $newProperties = [];
        foreach ($arr as $key => $pair) {
            if (!$pair["inherited"]) {
                $newProperties[] = $pair;
            }
        }

        $this->arr = $newProperties;

        return $this;
    }

    /**
     * @return array
     */
    public function getInternalProperties()
    {
        return $this->arr;
    }

    /**
     * @param $groupName
     * @return array
     */
    public function getKeyvaluepairsByGroup($groupName)
    {
        $data = [];
        $group = Object\KeyValue\GroupConfig::getByName($groupName);
        if (!empty($group)) {
            $properties = $this->getProperties();
            foreach ((array)$properties as $property) {
                if ($property['groupId'] == $group->getId()) {
                    $data[] = $property;
                }
            }
        }

        return $data;
    }

    /**
     * @param bool $forEditMode
     * @return array
     */
    public function getProperties($forEditMode = false)
    {
        $result = [];
        $object = Object::getById($this->objectId);
        if (!$object) {
            throw new \Exception('Object with Id '. $this->objectId .' not found');
        }
        $objectName = $object->getKey();

        $internalKeys = [];
        foreach ($this->arr as $pair) {
            $pair["inherited"] = false;
            $pair["source"] = $object->getId();
            $pair["groupId"] = Object\KeyValue\KeyConfig::getById($pair['key'])->getGroup();
            $result[] = $pair;
            $internalKeys[] = $pair["key"];
        }

        $blacklist = $internalKeys;

        $parent = Object\Service::hasInheritableParentObject($object);
        while ($parent) {
            $kv = $parent->getKeyvaluepairs();
            $parentProperties = $kv ? $kv->getInternalProperties() : [];

            $addedKeys = [];

            foreach ($parentProperties as $parentPair) {
                $parentKeyId = $parentPair["key"];
                $parentValue = $parentPair["value"];

                if (in_array($parentKeyId, $blacklist)) {
                    continue;
                }

                if ($this->multivalent && !$forEditMode && in_array($parentKeyId, $internalKeys)) {
                    continue;
                }

                $add = true;

                for ($i = 0; $i < count($result); ++$i) {
                    $resultPair = $result[$i];

                    $resultKey = $resultPair["key"];

                    $existingPair = null;
                    if ($resultKey == $parentKeyId) {
                        if ($this->multivalent && !in_array($resultKey, $blacklist)) {
                        } else {
                            $add = false;
                        }
                        // if the parent's key is already in the (internal) result list then
                        // we don't add it => not inherited.
                        if (!$this->multivalent) {
                            $add = false;
                            if (empty($resultPair["altSource"])) {
                                $resultPair["altSource"] = $parent->getId();
                                $resultPair["altValue"] = $parentPair["value"];
                            }
                        }

                        $result[$i] = $resultPair;
                    }

                    if (!$this->multivalent) {
                        break;
                    }
                }

                $addedKeys[] = $parentPair["key"];
                if ($add) {
                    $parentPair["inherited"] = true;
                    $parentPair["source"] = $parent->getId();
                    $parentPair["altSource"] = $parent->getId();
                    $parentPair["altValue"] = $parentPair["value"];
                    $parentPair["groupId"] = Object\KeyValue\KeyConfig::getById($parentPair['key'])->getGroup();
                    $result[] = $parentPair;
                }
            }

            foreach ($parentProperties as $parentPair) {
                $parentKeyId = $parentPair["key"];
                $blacklist[] = $parentKeyId;
            }

            $parent = Object\Service::hasInheritableParentObject($parent);
        }

        return $result;
    }

    /**
     * @param $propName
     * @param null $groupId
     * @return int
     * @throws \Exception
     */
    public function getKeyId($propName, $groupId = null)
    {
        $keyConfig = Object\KeyValue\KeyConfig::getByName($propName, $groupId);

        if (!$keyConfig) {
            throw new \Exception("key does not exist");
        }
        $keyId =  $keyConfig->getId();

        return $keyId;
    }


    /**
     * @param $propName
     * @param null $groupId
     * @return array|null
     * @throws \Exception
     */
    public function getProperty($propName, $groupId = null)
    {
        $keyId =  $this->getKeyId($propName, $groupId);

        $result = [];
        // the key name is valid, now iterate over the object's pairs
        $propsWithInheritance = $this->getProperties();
        foreach ($propsWithInheritance as $pair) {
            if ($pair["key"] == $keyId) {
                $result[] = new Object\Data\KeyValue\Entry($pair["value"], $pair["translated"], $pair["metadata"]);
            }
        }
        $count = count($result);
        if ($count == 0) {
            return null;
        } elseif ($count == 1) {
            return $result[0];
        } else {
            return $result;
        }
    }

    /** Sets the value of the property with the given id
     * @param $keyId the id of the key
     * @param $value the value
     * @param bool $fromGrid if true then the data is coming from the grid, we have to check if the value needs
     *                  to be translated
     * @return Object\Data\KeyValue the resulting object
     */
    public function setPropertyWithId($keyId, $value, $fromGrid = false)
    {
        // the key name is valid, now iterate over the object's pairs
        for ($i = 0; $i < count($this->arr); $i++) {
            $pair = $this->arr[$i];
            if ($pair["key"] == $keyId) {
                if ($fromGrid) {
                    $translatedValue = $this->getTranslatedValue($keyId, $value);
                }

                $pair["value"] = $value;
                $pair["translated"] = $translatedValue;
                $this->arr[$i] = $pair;

                return;
            }
        }

        $pair = [];
        $pair["key"] = $keyId;
        $pair["value"] = $value;
        $this->arr[] = $pair;

        return $this;
    }

    /**
     * @param $keyId
     * @param $value
     * @return string
     */
    private function getTranslatedValue($keyId, $value)
    {
        $translatedValue = "";
        $keyConfig = Object\KeyValue\KeyConfig::getById($keyId);
        $translatorID = $keyConfig->getTranslator();
        $translatorConfig = Object\KeyValue\TranslatorConfig::getById($translatorID);
        $className = $translatorConfig->getTranslator();
        if (\Pimcore\Tool::classExists($className)) {
            $translator = new $className();
            $translatedValue = $translator->translate($value);
            if (!$translatedValue) {
                $translatedValue = $value;
            }
        }

        return $translatedValue;
    }

    /**
     * @param $propName
     * @param $value
     * @throws \Exception
     */
    public function setProperty($propName, $value)
    {
        $keyId =  $this->getKeyId($propName);
        $this->setPropertyWithId($keyId, $value);
    }

    /**
     * @param $name
     * @param $arguments
     * @return array|mixed|null|void
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $sub = substr($name, 0, 14);
        if (substr($name, 0, 16) == "getWithGroupName") {
            $key = substr($name, 16, strlen($name)-16);
            $groupConfig = Object\KeyValue\GroupConfig::getByName($arguments[0]);

            return $this->getProperty($key, $groupConfig->getId());
        } elseif (substr($name, 0, 14) == "getWithGroupId") {
            $key = substr($name, 14, strlen($name)-14);
            $groupConfig = Object\KeyValue\GroupConfig::getById($arguments[0]);

            return $this->getProperty($key, $groupConfig->getId());
        } elseif (substr($name, 0, 3) == "get") {
            $key = substr($name, 3, strlen($name)-3);

            return $this->getProperty($key);
        } elseif (substr($name, 0, 3) == "set") {
            $key = substr($name, 3, strlen($name)-3);

            return $this->setProperty($key, $arguments[0]);
        }

        return parent::__call($name, $arguments);
    }

    /**
     * @param $keyId
     * @return array|null
     */
    public function getEntryByKeyId($keyId)
    {
        $result = [];
        foreach ($this->getProperties() as $property) {
            if ($property['key'] == $keyId) {
                $result[] = new Object\Data\KeyValue\Entry($property["value"], $property["translated"], $property["metadata"]);
            }
        }

        $count = count($result);
        if ($count == 0) {
            return null;
        } elseif ($count == 1) {
            return $result[0];
        } else {
            return $result;
        }
    }

    /**
     * deletes an entry with the given keyId if the entry exists
     *
     * @param $keyId
     */
    public function deleteEntryByKeyId($keyId)
    {
        foreach ($this->arr as $i => $entry) {
            if ($entry['key'] == $keyId) {
                unset($this->arr[$i]);
                break;
            }
        }
    }

    /**
     * @param $keyId
     * @param $value
     */
    public function setValueWithKeyId($keyId, $value)
    {
        $cleanedUpValues = [];
        foreach ($this->arr as $entry) {
            if ($entry['key'] != $keyId) {
                $cleanedUpValues[] = $entry;
            }
        }
        $this->arr = $cleanedUpValues;

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $v) {
            $pair = [];
            $pair["key"] = $keyId;
            $pair["value"] = $v;
            $pair["translated"] = $this->getTranslatedValue($keyId, $v);
            $this->arr[] = $pair;
        }
    }

    /**
     * @param  $multivalent
     */
    public function setMultivalent($multivalent)
    {
        $this->multivalent = $multivalent;
    }

    /**
     * @return
     */
    public function getMultivalent()
    {
        return $this->multivalent;
    }
}
