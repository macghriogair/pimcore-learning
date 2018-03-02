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

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Classificationstore\StoreConfig\Dao getDao()
 */
class StoreConfig extends Model\AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * The store name.
     *
     * @var string
     */
    public $name;

    /**
     * The store description.
     *
     * @var string
     */
    public $description;

    /**
     * @param integer $id
     * @return Model\Object\Classificationstore\StoreConfig
     */
    public static function getById($id)
    {
        try {
            $config = new self();
            $config->setId(intval($id));
            $config->getDao()->getById();

            return $config;
        } catch (\Exception $e) {
        }
    }

    /**
     * @param $name
     * @return StoreConfig
     */
    public static function getByName($name)
    {
        try {
            $config = new self();
            $config->setName($name);
            $config->getDao()->getByName();

            return $config;
        } catch (\Exception $e) {
        }
    }

    /**
     * @return Model\Object\Classificationstore\StoreConfig
     */
    public static function create()
    {
        $config = new self();
        $config->save();

        return $config;
    }



    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /** Returns the description.
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /** Sets the description.
     * @param $description
     * @return Model\Object\Classificationstore\StoreConfig
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Deletes the key value group configuration
     */
    public function delete()
    {
        \Pimcore::getEventManager()->trigger("object.Classificationstore.storeConfig.preDelete", $this);
        parent::delete();
        \Pimcore::getEventManager()->trigger("object.Classificationstore.storeConfig.postDelete", $this);
    }

    /**
     * Saves the store config
     */
    public function save()
    {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.Classificationstore.storeConfig.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.storeConfig.preAdd", $this);
        }

        $model = parent::save();

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.storeConfig.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.storeConfig.postAdd", $this);
        }

        return $model;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
