<?php

namespace MPF\Db;

/**
 * Represents a row in the database
 */
class Entry implements \ArrayAccess, \Iterator, \Serializable
{

    /**
     * Contains all the values of the fields in this entry, with the field name as a key.
     * Limited to what fields are returned by the Sql Query.
     *
     * @var array
     */
    private $fieldValues = array();

    /**
     * Md5 of the data when it was instanciated
     *
     * @var string
     */
    private $md5 = 0;

    /**
     * Contains the old md5 right after and UPDATE to the db
     *
     * @var string
     */
    public $oldMd5 = null;

    /**
     * The array $fieldValues should contains the field name as the KEY
     * and the proper value for each field name.
     *
     * @param array $fieldValues
     */
    public function __construct($fieldValues, $oldMd5 = null)
    {
        $this->fieldValues = $fieldValues;
        ksort($fieldValues);
        $this->md5 = md5(implode('', $fieldValues));
        $this->oldMd5 = $oldMd5;
    }

    /**
     * md5 of all the data
     *
     * @return type
     */
    public function getMD5()
    {
        return $this->md5;
    }

    /**
     * Sets the value depending on a field
     *
     * @param string $fieldName
     * @param string $value
     */
    public function set($fieldName, $value)
    {
        $this->fieldValues[$fieldName] = $value;
    }

    /**
     * Verifies if the array key exists.
     *
     * @param Mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->fieldValues);
    }

    /**
     * Returns the value for the array key if it exists.
     *
     * @param Mixed $offset
     * @return Mixed
     */
    public function offsetGet($offset)
    {
        return $this->fieldValues[$offset];
    }

    /**
     * Set a value in the array.
     *
     * @param Mixed $offset
     * @param Mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->fieldValues[$offset] = $value;
    }

    /**
     * Unset a key in the array.
     *
     * @param Mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->fieldValues[$offset]);
    }

    /**
     * Rewinds the pointer to the firs record.
     *
     */
    public function rewind()
    {
        reset($this->fieldValues);
    }

    /**
     * Returns the current record.
     *
     * @return DdEntry
     */
    public function current()
    {
        return current($this->fieldValues);
    }

    /**
     * Returns the Key of the current record.
     *
     * @return Mixed
     */
    public function key()
    {
        return key($this->fieldValues);
    }

    /**
     * Moves the pointer to the next record and returns it.
     *
     * @return DdEntry
     */
    public function next()
    {
        return next($this->fieldValues);
    }

    /**
     * Verifies if the current record is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return (false !== $this->current());
    }

    public function serialize()
    {
        $data = array(
            'md5' => $this->md5,
            'fieldValues' => $this->fieldValues,
        );
        return serialize($data);
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->md5 = $data['md5'];
        $this->fieldValues = $data['fieldValues'];
    }

}
