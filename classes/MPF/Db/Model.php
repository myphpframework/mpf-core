<?php

namespace MPF\Db;

use \MPF\PhpDoc;
use \MPF\Config;
use MPF\Log\Category;

\MPF\ENV::bootstrap(\MPF\ENV::DATABASE);

/**
 *
 */
abstract class Model extends \MPF\PhpDoc
{

    private $md5 = null;

    public static function fromJson($json)
    {
        $properties = @json_decode($json);
        if (null === $properties) {
            // TODO: Custom exception fromJson error
            $exception = new \Exception('Bad json, cannot instantiate model');

            $logger = new \MPF\Log\Logger();
            $logger->emergency($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::DATABASE, 
                'className' => 'Db/Model',
                'exception' => $exception
            ));
            throw $exception;
        }

        $class = get_called_class();
        $model = new $class();
        foreach ($properties as $name => $value) {
            $model->$name = $value;
        }

        $model->generateMD5();
        return $model;
    }

    /**
     *
     * @return integer
     */
    public static function getTotalEntries()
    {
        $className = get_called_class();
        self::generatePhpDoc($className);

        $dbLayer = \MPF\Db::byName(self::getDb($className));
        return $dbLayer->getTotal(self::$phpdoc[$className]['class'][PhpDoc::CLASS_TABLE]);
    }

    /*
     * builds a model from a Db\Entry
     *
     * return Model
     */

    public static function fromDbEntry(Entry $entry)
    {
        $class = get_called_class();
        $model = new $class();
        foreach ($entry as $name => $value) {
            $model->$name = $value;
        }

        $model->generateMD5();
        return $model;
    }

    /**
     *
     * @param Field[] $fields
     * @return \MPF\Db\ModelResult
     */
    public static function byFields($fields, \MPF\Db\Page $page = null)
    {
        self::generatePhpDoc(get_called_class());
        $fields = (!is_array($fields) ? array($fields) : $fields);

        $dbLayer = \MPF\Db::byName($fields[0]->getDatabase());

        // no need to call generateMD5 because it ends up calling "fromDbEntry"
        return $dbLayer->queryModelFields($fields, $page);
    }

    /**
     *
     * @param \MPF\Db\ModelLinkTable $linkTable
     * @return \MPF\Db\ModelResult
     */
    public static function byLinkTable(\MPF\Db\ModelLinkTable $linkTable, \MPF\Db\Page $page = null)
    {
        $className = get_called_class();
        self::generatePhpDoc($className);

        if (!array_key_exists(PhpDoc::CLASS_DATABASE, self::$phpdoc[$className]['class'])) {
            $exception = new Exception\ModelMissingPhpDoc($className, PhpDoc::CLASS_DATABASE);

            $logger = new \MPF\Log\Logger();
            $logger->emergency($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::DATABASE, 
                'className' => 'Db/Model',
                'exception' => $exception
            ));
            throw $exception;
        }

        $dbLayer = \MPF\Db::byName($linkTable->database);
        return $dbLayer->queryModelLinkTable($linkTable, $page);
    }

    /**
     * Returns the default field properties according to the phpdoc
     *
     * @param string $className
     * @return \MPF\Db\Field
     */
    public static function generateFields($className=null)
    {
        if (!$className) {
            $className = get_called_class();
        }
        self::generatePhpDoc($className);

        $fields = array();
        foreach (self::$phpdoc[$className]['properties'] as $fieldName => $info) {
            if (array_key_exists('type', $info)) {
                $fields[] = new \MPF\Db\Field(self::$phpdoc[$className]['class'], $fieldName, null, self::$phpdoc[$className]['properties'][$fieldName]);
            }
        }

        return $fields;
    }

    /**
     * Returns the default field properties according to the phpdoc
     *
     * @param string $fieldName
     * @return \MPF\Db\Field
     */
    public static function generateField($fieldName, $value = null, $phpdoc = array())
    {
        $className = get_called_class();
        self::generatePhpDoc($className);

        if (!array_key_exists($fieldName, self::$phpdoc[$className]['properties'])) {
            $exception = new Exception\InvalidFieldName($fieldName, $className);

            $logger = new \MPF\Log\Logger();
            $logger->warning($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::DATABASE, 
                'className' => 'Db/Model',
                'exception' => $exception
            ));
            throw $exception;
        }

        return new \MPF\Db\Field(array_merge(self::$phpdoc[$className]['class'], $phpdoc), $fieldName, $value, self::$phpdoc[$className]['properties'][$fieldName]);
    }

    public static function getDb($className)
    {
        if (!array_key_exists(PhpDoc::CLASS_DATABASE, self::$phpdoc[$className]['class'])) {
            self::$phpdoc[$className]['class'][PhpDoc::CLASS_DATABASE] = \MPF\Db::getDefaultName();
        }

        return self::$phpdoc[$className]['class'][PhpDoc::CLASS_DATABASE];
    }

    final public function __construct()
    {
        parent::__construct();
    }

    final public function updatefromDbEntry(Entry $entry)
    {
        foreach ($entry as $name => $value) {
            $this->$name = $value;
        }
        $this->generateMD5();
    }

    /**
     *
     * @return integer
     */
    final public function getId()
    {
        $primaryFields = $this->getPrimaryFields();
        if (count($primaryFields) > 1) {
            $exception = new MPF\Db\Exception\TooManyPrimaryKeys();

            $this->getLogger()->emergency($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::DATABASE, 
                'className' => 'Db/Model',
                'exception' => $exception
            ));
            throw $exception;
        }

        return $primaryFields[0]->getValue();
    }

    public function delete()
    {
        $dbLayer = \MPF\Db::byName($this->getDatabase());
        $dbLayer->deleteModel($this);
    }

    public function save()
    {
        $dbLayer = \MPF\Db::byName($this->getDatabase());
        $dbLayer->saveModel($this);
    }

    /*
     * @param string $fieldName
     * @return \MPF\Db\Field
     */

    public function getField($fieldName)
    {
        if (!array_key_exists($fieldName, self::$phpdoc[$this->className]['properties'])) {
            $exception = new Exception\InvalidFieldName($fieldName, $this->className);

            $this->getLogger()->warning($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::DATABASE, 
                'className' => 'Db/Model',
                'exception' => $exception
            ));
            throw $exception;
        }

        $field = null;
        if (property_exists($this, $fieldName)) {
            $field = new \MPF\Db\Field(self::$phpdoc[$this->className]['class'], $fieldName, $this->$fieldName, self::$phpdoc[$this->className]['properties'][$fieldName]);
        }

        return $field;
    }

    /**
     * Returns the fields and their values
     *
     * @return \MPF\Db\Field
     */
    public function getFields()
    {
        $fields = array();
        foreach (self::$phpdoc[$this->className]['properties'] as $name => $property) {
            if (empty($property) || !array_key_exists(PhpDoc::PROPERTY_TYPE, $property)) {
                continue;
            }

            if (property_exists($this, $name) && $property['declaringClass'] == $this->className) {
                $fields[] = new \MPF\Db\Field(self::$phpdoc[$this->className]['class'], $name, $this->$name, $property);
            }
        }
        return $fields;
    }

    /**
     * Returns the table name the model belongs to
     *
     * @return string
     */
    public function getDatabase()
    {
        if (property_exists($this, 'database')) {
            return $this->database;
        }

        if (!array_key_exists(PhpDoc::CLASS_DATABASE, self::$phpdoc[$this->className]['class'])) {
            self::$phpdoc[$this->className]['class'][PhpDoc::CLASS_DATABASE] = \MPF\Db::getDefaultName();
        }

        return self::$phpdoc[$this->className]['class'][PhpDoc::CLASS_DATABASE];
    }

    /**
     * Returns the table name the model belongs to
     *
     * @return string
     */
    public function getTable()
    {
        if (property_exists($this, 'table')) {
            return $this->table;
        }

        return self::$phpdoc[$this->className]['class'][PhpDoc::CLASS_TABLE];
    }

    /**
     *  Returns the primary keys of the model
     *
     * @return \MPF\Db\Field[]
     */
    public function getPrimaryFields()
    {
        $primaryKeys = array();
        foreach (self::$phpdoc[$this->className]['properties'] as $name => $property) {
            if (array_key_exists(PhpDoc::PROPERTY_PRIMARY_KEY, $property) && $property[PhpDoc::PROPERTY_PRIMARY_KEY]) {
                $primaryKeys[] = $this->getField($name);
            }
        }
        return $primaryKeys;
    }

    /**
     *
     * @param string $fieldName
     * @param mixed $fieldValue
     */
    public function setField($fieldName, $fieldValue = null, $returnValue = false)
    {

        $field = $this->getField($fieldName);
        if ($field->isReadonly()) {
            $exception = new Exception\FieldReadonly($fieldName, $this->className);

            $this->getLogger()->warning($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::DATABASE, 
                'className' => 'Db/Model',
                'exception' => $exception
            ));
            throw $exception;
        }

        if (is_null($fieldValue) && !$field->isNullable()) {
            $exception = new Exception\FieldNotNull($fieldName, $this->className);

            $this->getLogger()->warning($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::DATABASE, 
                'className' => 'Db/Model',
                'exception' => $exception
            ));
            throw $exception;
        }

        $type = $field->getType();
        if ($fieldValue && $type) {
            $length = $field->getTypeLength();
            if (is_object($fieldValue)) {
                $exception = new Exception\InvalidFieldType($fieldName, $this->className);

                $this->getLogger()->warning($exception->getMessage(), array(
                    'category' => Category::FRAMEWORK | Category::DATABASE, 
                    'className' => 'Db/Model',
                    'exception' => $exception
                ));
                throw $exception;
            }

            switch (strtolower($type)) {
                case 'enum':
                    break;
                case 'datetime':
                case 'date':
                    if (!strtotime($fieldValue)) {
                        $exception = new Exception\InvalidFieldValue($fieldName, $this->className);

                        $this->getLogger()->warning($exception->getMessage(), array(
                            'category' => Category::FRAMEWORK | Category::DATABASE, 
                            'className' => 'Db/Model',
                            'exception' => $exception
                        ));
                        throw $exception;
                    }
                    break;
                case 'timestamp':
                case 'int':
                case 'integer':
                    if (!is_numeric($fieldValue)) {
                        $exception = new Exception\InvalidFieldValue($fieldName, $this->className);

                        $this->getLogger()->warning($exception->getMessage(), array(
                            'category' => Category::FRAMEWORK | Category::DATABASE, 
                            'className' => 'Db/Model',
                            'exception' => $exception
                        ));
                        throw $exception;
                    }
                    break;
                case 'text':
                    if (!is_string($fieldValue)) {
                        $exception = new Exception\InvalidFieldValue($fieldName, $this->className);

                        $this->getLogger()->warning($exception->getMessage(), array(
                            'category' => Category::FRAMEWORK | Category::DATABASE, 
                            'className' => 'Db/Model',
                            'exception' => $exception
                        ));
                        throw $exception;
                    }
                    break;
                case 'string':
                case 'varchar':
                    if ($length < strlen($fieldValue)) {
                        $exception = new Exception\InvalidFieldLength($fieldName, $this->className);

                        $this->getLogger()->warning($exception->getMessage(), array(
                            'category' => Category::FRAMEWORK | Category::DATABASE, 
                            'className' => 'Db/Model',
                            'exception' => $exception
                        ));
                        throw $exception;
                    }
                    break;
            }
        }

        if ($field->isPassword()) {
            // if we are setting it to null we dont generate a password
            if (!$fieldValue) {
                return;
            }

            // search for a salt
            $salt = '';
            foreach ($this->getFields() as $property) {
                if ($property->isSalt()) {
                    $salt = $property->getValue();
                    if (!$salt) {
                        $salt = $this->generateSalt($property->getTypeLength(), $property->getSaltType());
                        $this->{$property->getName()} = $salt;
                    }
                    break;
                }
            }

            if (!in_array($field->getPasswordType(), hash_algos())) {
                $exception = new Exception\InvalidHashAlgo($field->getPasswordType());

                $this->getLogger()->warning($exception->getMessage(), array(
                    'category' => Category::FRAMEWORK | Category::DATABASE, 
                    'className' => 'Db/Model',
                    'exception' => $exception
                ));
                throw $exception;
            }

            // put the generated salt in the middle of the password
            $strlen = strlen($fieldValue);
            $string = substr($fieldValue, 0, ($strlen / 2)) . $salt . substr($fieldValue, ($strlen / 2), $strlen);

            // put the framework salt in the middle of the password
            $framworkSalt = Config::get('settings')->framework->salt;
            $strlen = strlen($string);
            $string = substr($string, 0, ($strlen / 2)) . $framworkSalt . substr($string, ($strlen / 2), $strlen);

            $fieldValue = hash($field->getPasswordType(), $string);
        }

        if ($field->hasEncryption()) {
            
        }

        if ($returnValue) {
            return $fieldValue;
        }

        $this->$fieldName = $fieldValue;
    }

    /**
     * Exactly like setField but instead of setting the value to the
     * field it return its value
     *
     * @param string $fieldName
     * @param mixed $fieldValue
     * @return mixed
     */
    public function verifyField($fieldName, $fieldValue = null, $returnValue = false)
    {
        return $this->setField($fieldName, $fieldValue, true);
    }

    private function generateSalt($length, $hashType = 'sha512')
    {
        $salt = '';
        for ($i = 0; $i < $length; $i++) {
            $salt .= hash($hashType, Config::get('settings')->framework->salt . time() . uniqid(true));
        }
        $salt = base64_encode($salt);
        $salt = strlen($salt) > $length ? substr($salt, 0, $length) : $salt;
        return trim(strtr($salt, '/+=', '   '));
    }

    /**
     * Returns true if the model has not been saved yet
     *
     * @return boolean
     */
    public function isNew()
    {
        $isNew = true;
        foreach ($this->getPrimaryFields() as $field) {
            if ($field->getValue()) {
                $isNew = false;
            }
        }
        return $isNew;
    }

    /**
     * Generates the db entry for the model
     *
     * @return \MPF\Db\Entry
     */
    public function getDbEntry()
    {
        $fieldValues = array();
        foreach ($this->getFields() as $field) {
            if ($field->isForeign()) {
                continue;
            }

            $fieldValues[$field->getName()] = $field->getValue();
        }

        ksort($fieldValues);
        return new \MPF\Db\Entry($fieldValues, $this->getMD5());
    }

    private function generateMD5()
    {
        if ($this->md5 === null) {
            $fieldValues = array();
            foreach ($this->getFields() as $field) {
                if ($field->isForeign()) {
                    continue;
                }

                $fieldValues[$field->getName()] = $field->getValue();
            }

            ksort($fieldValues);
            $this->md5 = md5(implode('', $fieldValues));
        }
    }

    public function getMD5()
    {
        return $this->md5;
    }

    /**
     * Takes all the properties and their values and makes a json array
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Takes all the properties and their values and makes an array
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        foreach ($this->getFields() as $field) {
            if ($field->isPrivate()) {
                continue;
            }

            $array[$field->getName()] = $field->getValue();
        }

        return $array;
    }

}
