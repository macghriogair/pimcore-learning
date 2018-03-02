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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;

class Numeric extends Model\Object\ClassDefinition\Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "numeric";

    /**
     * @var float
     */
    public $width;

    /**
     * @var float
     */
    public $defaultValue;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "double";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "double";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "float";

    /**
     * @var bool
     */
    public $integer = false;

    /**
     * @var bool
     */
    public $unsigned = false;

    /**
     * @var float
     */
    public $minValue;

    /**
     * @var float
     */
    public $maxValue;

    /**
     * @var int
     */
    public $decimalPrecision;

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @return integer
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return $this->toNumeric($this->defaultValue);
        }
    }

    /**
     * @param integer $defaultValue
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        if (strlen(strval($defaultValue)) > 0) {
            $this->defaultValue = $defaultValue;
        }

        return $this;
    }

    /**
     * @param boolean $integer
     */
    public function setInteger($integer)
    {
        $this->integer = $integer;
    }

    /**
     * @return boolean
     */
    public function getInteger()
    {
        return $this->integer;
    }

    /**
     * @param float $maxValue
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $maxValue;
    }

    /**
     * @return float
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @param float $minValue
     */
    public function setMinValue($minValue)
    {
        $this->minValue = $minValue;
    }

    /**
     * @return float
     */
    public function getMinValue()
    {
        return $this->minValue;
    }

    /**
     * @param boolean $unsigned
     */
    public function setUnsigned($unsigned)
    {
        $this->unsigned = $unsigned;
    }

    /**
     * @return boolean
     */
    public function getUnsigned()
    {
        return $this->unsigned;
    }

    /**
     * @param int $decimalPrecision
     */
    public function setDecimalPrecision($decimalPrecision)
    {
        $this->decimalPrecision = $decimalPrecision;
    }

    /**
     * @return int
     */
    public function getDecimalPrecision()
    {
        return $this->decimalPrecision;
    }

    /**
     * @return string
     */
    public function getColumnType()
    {
        if ($this->getInteger()) {
            return "bigint(20)";
        }

        if ($this->getDecimalPrecision()) {
            return "decimal(64, " . intval($this->getDecimalPrecision()) . ")";
        }

        return parent::getColumnType();
    }

    /**
     * @return string
     */
    public function getQueryColumnType()
    {
        if ($this->getInteger()) {
            return "bigint(20)";
        }

        if ($this->getDecimalPrecision()) {
            return "decimal(64, " . intval($this->getDecimalPrecision()) . ")";
        }

        return parent::getQueryColumnType();
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            return $data;
        }

        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            return $this->toNumeric($data);
        }

        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param float $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (!$this->isEmpty($data) && !is_numeric($data)) {
            throw new Model\Element\ValidationException("invalid numeric data [" . $data . "]");
        }

        if (!$this->isEmpty($data) && !$omitMandatoryCheck) {
            $data = $this->toNumeric($data);

            if ($data >= PHP_INT_MAX) {
                throw new Model\Element\ValidationException("Value exceeds PHP_INT_MAX please use an input data type instead of numeric!");
            }

            if ($this->getInteger() && strpos((string) $data, ".") !== false) {
                throw new Model\Element\ValidationException("Value in field [ ".$this->getName()." ] is not an integer");
            }

            if (strlen($this->getMinValue()) && $this->getMinValue() > $data) {
                throw new Model\Element\ValidationException("Value in field [ ".$this->getName()." ] is not at least " . $this->getMinValue());
            }

            if (strlen($this->getMaxValue()) && $data > $this->getMaxValue()) {
                throw new Model\Element\ValidationException("Value in field [ ".$this->getName()." ] is bigger than " . $this->getMaxValue());
            }

            if ($this->getUnsigned() && $data < 0) {
                throw new Model\Element\ValidationException("Value in field [ ".$this->getName()." ] is not unsigned (bigger than 0)");
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Model\Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return strval($data);
    }


    /**
     * fills object field data values from CSV Import String
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = $this->toNumeric(str_replace(",", ".", $importValue));

        return $value;
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @param $data
     * @return bool
     */
    public function isEmpty($data)
    {
        return (strlen($data) < 1);
    }

    /**
     * @param $value
     * @return float|int
     */
    protected function toNumeric($value)
    {
        if (strpos((string) $value, ".") === false) {
            return (int) $value;
        }

        return (float) $value;
    }
}
