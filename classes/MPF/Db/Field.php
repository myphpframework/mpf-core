<?php

namespace MPF\Db;

use \MPF\PhpDoc;

/**
 * Object for all the result of database queries
 */
class Field {

    private $name;
    private $value;
    private $classPhpDoc;
    private $options = array();
    private $operator = null;
    private $linkFieldName = '';

    public function __construct($classPhpDoc, $name, $value, $options) {
        $this->classPhpDoc = $classPhpDoc;
        $this->name = $name;
        $this->value = $value;
        $this->options = $options;
    }

    /**
     * Returns the database table the field belongs to
     *
     * @return string
     */
    public function getTable() {
        // for foreign fields we have to give their table not the class one
        if (array_key_exists(PhpDoc::PROPERTY_TABLE, $this->options)) {
            return $this->options[PhpDoc::PROPERTY_TABLE];
        }
        return $this->classPhpDoc[PhpDoc::CLASS_TABLE];
    }

    /**
     * Returns the database the field belongs to
     *
     * @return string
     */
    public function getDatabase() {
        // for foreign fields we have to give their database not the class one
        if (array_key_exists(PhpDoc::PROPERTY_DATABASE, $this->options)) {
            return $this->options[PhpDoc::PROPERTY_DATABASE];
        }

        // if we have no database assigned we take the main framework one
        if (!array_key_exists(PhpDoc::CLASS_DATABASE, $this->classPhpDoc)) {
            $this->classPhpDoc[PhpDoc::CLASS_DATABASE] = \MPF\Db::getDefaultName();
        }
        return $this->classPhpDoc[PhpDoc::CLASS_DATABASE];
    }

    /**
     * Returns the name of the class the field belongs to
     *
     * @return string
     */
    public function getClass() {
        return $this->classPhpDoc[PhpDoc::CLASS_NAME];
    }

    /**
     * Returns the value of the field
     *
     * @return mixed
     */
    public function getDefaultValue() {
        if (array_key_exists(PhpDoc::PROPERTY_DEFAULT_VALUE, $this->options)) {
            $value = $this->options[PhpDoc::PROPERTY_DEFAULT_VALUE];

            // @default was specified with no value thus allow nulls
            if (!$value || $value == 'null' || $value === null) {
                return null;
            }

            $time = strtotime($value);
            $time = (!$time ? time() : $time);

            switch ($this->getType()) {
                case 'timestamp':
                case 'datetime':
                    return date('Y-m-d H:i:s', $time);
                    break;
                case 'date':
                    return date('Y-m-d', $time);
                    break;
            }
            return $value;
        }
        return null;
    }

    public function getOnUpdateValue() {
        if (array_key_exists(PhpDoc::PROPERTY_ON_UPDATE, $this->options)) {
            $value = $this->options[PhpDoc::PROPERTY_ON_UPDATE];

            // @default was specified with no value thus allow nulls
            if (!$value || $value == 'null' || $value === null) {
                return null;
            }

            $time = strtotime($value);
            $time = (!$time ? time() : $time);

            switch ($this->getType()) {
                case 'timestamp':
                case 'datetime':
                    return date('Y-m-d H:i:s', $time);
                    break;
                case 'date':
                    return date('Y-m-d', $time);
                    break;
            }
            return $value;
        }
        return null;
    }

    public function getRelationship() {
        return (array_key_exists(PhpDoc::PROPERTY_RELATION, $this->options) ? $this->options[PhpDoc::PROPERTY_RELATION] : '');
    }

    /**
     * Returns the value of the field
     *
     * @return mixed
     */
    public function getValue() {
        return (!$this->value ? $this->getDefaultValue() : $this->value);
    }

    /**
     * Used for linktables
     *
     * @return string
     */
    public function getLinkFieldName() {
        if (!$this->linkFieldName) {
            return (array_key_exists(PhpDoc::PROPERTY_LINKNAME, $this->options) ? $this->options[PhpDoc::PROPERTY_LINKNAME] : '');
        }
        return $this->linkFieldName;
    }

    /**
     * Used for linktables
     *
     * @param string $name
     */
    public function setLinkFieldName($name) {
        $this->linkFieldName = $name;
    }

    /**
     * Sets the value of the field
     */
    public function setValue($value) {
        if (array_key_exists(PhpDoc::PROPERTY_RELATION, $this->options)
         && in_array(strtolower($this->options[PhpDoc::PROPERTY_RELATION]), array('manytomany', 'onetomany', 'onetoone'))) {
            if (!is_array($this->value)) {
                $this->value = array();
            }

            $this->value[] = $value;
        } else {
            $this->value = $value;
        }
    }

    /**
     * Returns the name of the field
     *
     * @return type string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the type of field
     *
     * @return string
     */
    public function getType() {
        if (array_key_exists(PhpDoc::PROPERTY_TYPE, $this->options)) {
            $type = $this->options[PhpDoc::PROPERTY_TYPE];
            if (is_array($type)) {
                $type = $type[0];
            }
            return $type;
        }
        return '';
    }

    /**
     * Returns the length for type of field
     *
     * @return integer
     */
    public function getTypeLength() {
        if (array_key_exists(PhpDoc::PROPERTY_TYPE, $this->options) && is_array($this->options[PhpDoc::PROPERTY_TYPE])) {
            return (int) $this->options[PhpDoc::PROPERTY_TYPE][1];
        }
        return 0;
    }

    /**
     * Returns the operator the field should be using to assign
     *
     * @return string
     */
    public function getOperator() {
        if ($this->operator !== null) {
            return $this->operator;
        }

        $operator = '=';
        switch (strtolower($this->options[PhpDoc::PROPERTY_TYPE][0])) {
            case 'varchar':
                $operator = 'LIKE';
                break;
        }
        return $operator;
    }

    /**
     *
     * @param mixed $value
     * @return boolean
     */
    public function matches($value) {
        switch (strtoupper($this->getOperator())) {
            default:
            case 'LIKE':
            case '=': return $this->isEqual($value); break;
            case '<=':return $this->isLessThanOrEqual($value); break;
            case '<': return $this->isLessThan($value); break;
            case '>=':return $this->isGreaterThanOrEqual($value); break;
            case '>': return $this->isGreaterThan($value); break;
        }
        return false;
    }

    /**
     * Sets the operator for the query.
     * Posibilities: =, LIKE, <=, <, >=, >
     *
     * @param string $operator
     */
    public function setOperator($operator) {
        if (!in_array($operator, array('=', 'LIKE', '<=', '<', '>=', '>'))) {
            // TODO: need custom mutli lang exception
            throw new Exception('Invalid field operator');
        }

        $this->operator = $operator;
    }

    /**
     * Returns the values for comparison
     *
     * @return mixed
     */
    private function getComparisonValues($dbValue) {
        $fieldValue = $this->getvalue();
        switch ($this->getType()) {
            case 'timestamp':
            case 'datetime':
            case 'date':
                return array(strtotime($fieldValue), strtotime($dbValue));
                break;
        }
        return array($fieldValue, $dbValue);
    }

    public function isGreaterThan($value) {
        list($dbValue, $fieldValue) = $this->getComparisonValues($value);
        return ($dbValue > $fieldValue);
    }

    public function isGreaterThanOrEqual($value) {
        list($dbValue, $fieldValue) = $this->getComparisonValues($value);
        return ($dbValue >= $fieldValue);
    }

    public function isLessThan($value) {
        list($dbValue, $fieldValue) = $this->getComparisonValues($value);
        return ($dbValue < $fieldValue);
    }

    public function isLessThanOrEqual($value) {
        list($dbValue, $fieldValue) = $this->getComparisonValues($value);
        return ($dbValue <= $fieldValue);
    }

    public function isEqual($value) {
        list($dbValue, $fieldValue) = $this->getComparisonValues($value);
        return ($dbValue == $fieldValue);
    }

    /**
     * Verifies if an operator was set
     *
     * @return type
     */
    public function hasOperator() {
        return ($this->operator !== null);
    }

    /**
     * Returns if the field as encryption policy
     *
     * @return bool
     */
    public function hasEncryption() {
        return array_key_exists(PhpDoc::PROPERTY_ENCRYPTION, $this->options);
    }

    /**
     * Returns if the field is password
     *
     * @return bool
     */
    public function isPassword() {
        return array_key_exists(PhpDoc::PROPERTY_PASSWORD, $this->options);
    }

    /**
     * Returns if the field is private
     *
     * @return bool
     */
    public function isPrivate() {
        return array_key_exists(PhpDoc::PROPERTY_PRIVATE, $this->options);
    }

    public function isPrimaryKey() {
        return (array_key_exists(PhpDoc::PROPERTY_PRIMARY_KEY, $this->options) && $this->options[ PhpDoc::PROPERTY_PRIMARY_KEY ]);
    }

    /**
     * Returns if the field is froma foreign table
     */
    public function isForeign() {
        if (array_key_exists(PhpDoc::PROPERTY_TYPE, $this->options) && $this->options[ PhpDoc::PROPERTY_TYPE ] == 'foreign') {
            return true;
        }
        return false;;
    }

    /**
     * Returns the type of salt
     *
     * @return string
     */
    public function getPasswordType() {
        $type = '';
        if (array_key_exists(PhpDoc::PROPERTY_PASSWORD, $this->options)) {
            return $this->options[PhpDoc::PROPERTY_PASSWORD];
        }
        return $type;
    }

    /**
     * Returns if the field is password
     *
     * @return bool
     */
    public function isSalt() {
        return array_key_exists(PhpDoc::PROPERTY_SALT, $this->options);
    }

    /**
     * Returns the type of salt
     *
     * @return string
     */
    public function getSaltType() {
        $type = '';
        if (array_key_exists(PhpDoc::PROPERTY_SALT, $this->options)) {
            return $this->options[PhpDoc::PROPERTY_SALT];
        }
        return $type;
    }

    /**
     * @return bool
     */
    public function isReadonly() {
        if (array_key_exists(PhpDoc::PROPERTY_READONLY, $this->options) && $this->options[PhpDoc::PROPERTY_READONLY]) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isNullable() {
        if (!array_key_exists(PhpDoc::PROPERTY_DEFAULT_VALUE, $this->options)) {
            return false;
        }

        $value = $this->getDefaultValue();
        if ($value === null) {
            return true;
        }
        return false;
    }

}