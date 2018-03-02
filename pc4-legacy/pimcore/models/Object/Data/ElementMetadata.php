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
 * @method \Pimcore\Model\Object\Data\ElementMetadata\Dao getDao()
 */
class ElementMetadata extends Model\AbstractModel
{

    /**
     * @var Model\Element\ElementInterface
     */
    protected $element;

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var array
     */
    public $data = [];

    /**
     * @param $fieldname
     * @param array $columns
     * @param null $element
     * @throws \Exception
     */
    public function __construct($fieldname, $columns = [], $element = null)
    {
        $this->fieldname = $fieldname;
        $this->element = $element;
        $this->columns = $columns;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed|void
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) == "get") {
            $key = strtolower(substr($name, 3, strlen($name)-3));

            if (in_array($key, $this->columns)) {
                return $this->data[$key];
            }

            throw new \Exception("Requested data $key not available");
        }

        if (substr($name, 0, 3) == "set") {
            $key = strtolower(substr($name, 3, strlen($name)-3));
            if (in_array($key, $this->columns)) {
                $this->data[$key] = $arguments[0];
            } else {
                throw new \Exception("Requested data $key not available");
            }
        }
    }

    /**
     * @param $object
     * @param string $ownertype
     * @param $ownername
     * @param $position
     */
    public function save($object, $ownertype = "object", $ownername, $position)
    {
        $element = $this->getElement();
        $type = Model\Element\Service::getElementType($element);
        $this->getDao()->save($object, $ownertype, $ownername, $position, $type);
    }

    /**
     * @param Object\Concrete $source
     * @param $destination
     * @param $fieldname
     * @param $ownertype
     * @param $ownername
     * @param $position
     * @param $type
     * @return mixed
     */
    public function load(Object\Concrete $source, $destination, $fieldname, $ownertype, $ownername, $position, $type)
    {
        return $this->getDao()->load($source, $destination, $fieldname, $ownertype, $ownername, $position, $type);
    }

    /**
     * @param $fieldname
     * @return $this
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @param $element
     * @return $this
     */
    public function setElement($element)
    {
        $this->element = $element;

        return $this;
    }

    /**
     * @return Object\Concrete
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param $columns
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->getElement()->__toString();
    }
}
