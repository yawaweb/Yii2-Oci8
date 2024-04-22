<?php
/**
 * The OCI8 connection class for Yii2
 *
 * @author Yawaweb (hello-github@yawaweb.com)
 */

namespace yawaweb\yii2oci8;

use Codeception\Exception\ConfigurationException;
use yii\base\NotSupportedException;
use yii\db\Connection;

use ReflectionClass;
use PDOException;
use Yajra\Pdo\Oci8;
use yii\db\oci\Schema;

/**
 * Class Oci8Connection
 * @package app\components
 */
class Oci8Connection extends Connection
{
    public $pdoClass = Oci8::class;

    /**
     * @var string Class name for oci schemaMap
     */
    public $schemaClass = Schema::class;

    /**
     * @var bool If true cached schema will be created using yawaweb\yii2oci8\CachedSchema
     */
    public $useCachedSchema = false;

    /**
     * @var CachedSchema Contains an instance of the [[yawaweb\yii2oci8\CachedSchema]] when $useCachedSchema is true
     */
    public $cachedSchema;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->schemaMap['oci'] = $this->useCachedSchema === true ? CachedSchema::class : $this->schemaClass;
    }

    /**
     * Creates the PDO instance from Yajra\Pdo\Oci8 component
     * @exception PDOException
     * @return \PDO the pdo instance
     */
    protected function createPdoInstance()
    {
        //Empty attributes property cases exception in Yajra\Pdo\Oci8::__construct() method
        if (!is_array($this->attributes))
            $this->attributes = [];

        try {
            return parent::createPdoInstance();
        } catch(PDOException $e) {
            throw $e;
        }
    }

    /**
     * Returns private database handler from the OCI8 PDO class instance
     * @return resource Oci8 resource handler
     * @throws \ReflectionException
     */
    public function getDbh() {
        $prop = (new ReflectionClass($this->pdoClass))->getProperty('dbh');
        $prop->setAccessible(true);
        return $prop->getValue($this->masterPdo);
    }

    /**
     * Returns the schema information for the database opened by this connection.
     * @return \yii\db\Schema|\yawaweb\yii2oci8\CachedSchema Schema the schema information for the database opened by this connection.
     * @throws NotSupportedException NotSupportedException if there is no support for the current driver type
     * @throws ConfigurationException
     */
    public function getSchema()
    {
        if ($this->cachedSchema === null) {
            return parent::getSchema();
        } else {
            if (is_object($this->cachedSchema))
                return $this->cachedSchema;
            else if (is_array($this->cachedSchema)) {
                $this->cachedSchema['db'] = $this;
                $this->cachedSchema = \Yii::createObject($this->cachedSchema);
                //$this->cachedSchema = new CachedSchema(['db' => $this]);
                return $this->cachedSchema;
            } else {
                throw new ConfigurationException('The "cachedSchema" property must be an configuration array');
            }
        }
    }
}