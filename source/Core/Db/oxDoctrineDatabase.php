<?php
/**
 * #PHPHEADER_OXID_LICENSE_INFORMATION#
 */
namespace OxidEsales\Eshop\Core\Db;

use Doctrine\DBAL\DriverManager;
use OxidEsales\Eshop\Core\Db\Adapter\DoctrineResultSet;

class oxDoctrineDatabase extends \oxSuperCfg
{

    /**
     * @var \Doctrine\DBAL\Connection The database connection.
     */
    protected $connection = null;

    /**
     * @var int The last set fetch mode. We store the adodb fetch mode here.
     */
    protected $lastFetchMode = 0;

    /**
     * The standard constructor.
     */
    public function __construct()
    {
        $this->setConnection($this->createConnection());
    }

    /**
     * Set the database connection.
     *
     * @param \Doctrine\DBAL\Connection $oConnection The database connection we want to use.
     */
    public function setConnection($oConnection)
    {
        $this->connection = $oConnection;
    }

    /**
     * Execute the given query and return the corresponding result set.
     *
     * @param string     $query      The query we want to execute.
     * @param array|bool $parameters The parameters for the given query.
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return mixed|DoctrineResultSet
     */
    public function query($query, $parameters)
    {
        if (!empty($parameters) && is_array($parameters)) {
            $statement = $this->getConnection()->prepare($query);

            $index = 1;

            foreach ($parameters as $parameter) {
                $statement->bindValue($index, $parameter);
                $index++;
            }
            $statement->execute();

            return new DoctrineResultSet($statement);
        } else {
            return new DoctrineResultSet(
                $this->getConnection()->query($query)
            );
        }
    }

    /**
     * Set the fetch mode for future calls. Returns the old fetch mode.
     *
     * Hints:
     *  - we map the adodb fetch mode to the pdo (used by doctrine) fetch mode here
     *  - cause there is no getter in dbal or pdo we save the actual fetch mode in this object too
     *
     * @param int $fetchmode How do we want to get the results?
     *
     * @return int The previous fetch mode.
     */
    public function setFetchMode($fetchmode)
    {
        $lastFetchMode = $this->lastFetchMode;

        $newFetchmode = $this->mapFetchMode($fetchmode);
        $this->getConnection()->setFetchMode($newFetchmode);
        $this->lastFetchMode = $newFetchmode;

        return $lastFetchMode;
    }

    /**
     * Map the adodb fetch mode to the corresponding pdo fetch mode.
     *
     *  ADODB_FETCH_DEFAULT = 0
     *  ADODB_FETCH_NUM = 1
     *  ADODB_FETCH_ASSOC = 2
     *  ADODB_FETCH_BOTH = 3
     *
     *  FETCH_LAZY = 1
     *  FETCH_ASSOC = 2
     *  FETCH_NUM = 3
     *  FETCH_BOTH = 4
     *
     * @param int $fetchmode The adodb fetch mode.
     *
     * @return int The doctrine use pdo fetch mode.
     */
    private function mapFetchMode($fetchmode)
    {
        $result = $fetchmode + 1;

        switch ($fetchmode) {
            case 2:
                $fetchmode = 3;
                break;
            case 3:
                $fetchmode = 2;
                break;
        }

        return $result;
    }

    /* ---- START: not implemented or not, but needed methods: ---- */

    public function setConnectionForSlave()
    {
    }

    public function quote($value)
    {
        return $this->getDb()->quote($value);
    }

    public function quoteArray($arrayOfStrings)
    {
        foreach ($arrayOfStrings as $key => $string) {
            $arrayOfStrings[$key] = $this->quote($string);
        }

        return $arrayOfStrings;
    }

    public function getAll($query, $parameters = false, $type = true)
    {
        if (!$parameters) {
            $parameters = array();
        }
        if (!$type) {
            $type = array();
        }

        // @todo: look at fetch mode for the returning
        return new DoctrineResultSet($this->getDb($type)->fetchAll($query, $parameters));
    }

    public function getRow($query, $parameters = false, $type = true)
    {
        return $this->query($query, $parameters, $type);
    }

    public function select($query, $parameters = false, $type = true)
    {
        if (!$parameters) {
            $parameters = array();
        }
        if (!$type) {
            $type = array();
        }

        return new DoctrineResultSet($this->getDb($type)->executeQuery($query, $parameters));
    }

    public function getOne($query, $parameters = false, $type = true)
    {
        $q = $this->select($query, $parameters);

        return $q->fetchRow();
    }

    public function execute($query, $parameters = false)
    {
        return $this->query($query, $parameters);
    }

    public function getCol($query, $parameters = false, $type = true)
    {
        if (!$parameters) {
            $parameters = array();
        }
        if (!$type) {
            $type = array();
        }

        return new DoctrineResultSet($this->getDb($type)->fetchColumn($query, $parameters));
    }

    public function getAssoc($query, $parameters = false, $type = true)
    {
        $result = $this->query($query, $parameters, $type);

        return $result;
    }

    /**
     * Start mysql transaction.
     *
     * @return bool
     */
    public function startTransaction()
    {
//        return $this->getDb(false)->execute('START TRANSACTION');
    }

    /**
     * Commit mysql transaction.
     *
     * @return bool
     */
    public function commitTransaction()
    {
//        return $this->getDb(false)->execute('COMMIT');
    }

    /**
     * RollBack mysql transaction.
     *
     * @return bool
     */
    public function rollbackTransaction()
    {
//        return $this->getDb(false)->execute('ROLLBACK');
    }


    /**
     * return meta data
     *
     * @param string $table Table name
     *
     * @return array
     */
    public function metaColumns($table)
    {
//        return $this->getDb(false)->MetaColumns($table);
    }

    /* ---- END: not implemented or not, but needed methods ---- */


    /**
     * Return connection to db.
     *
     * Important: this method is for backwards compatibility with the oxLegacyDb here. It is only an alias for the getter.
     *
     * @param bool $type Connection type
     *
     * @return \Doctrine\DBAL\Connection The database connection.
     */
    public function getDb($type = true)
    {
        return $this->getConnection();
    }

    /**
     * Getter for the used database connection.
     *
     * @return \Doctrine\DBAL\Connection The database connection.
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Create the database connection.
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return \Doctrine\DBAL\Connection The dataabase connection.
     */
    protected function createConnection()
    {
        /**
         * @todo: exchange the deprecated configuration
         */
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = $this->getConnectionParameters();

        return DriverManager::getConnection($connectionParams, $config);
    }

    /**
     * Get the connection parameter array.
     *
     * @param bool $utf8 Set the utf8 flag for the database connection.
     *
     * @todo: load connection data from the config object
     *
     * @return array The connection settings parameters.
     */
    private function getConnectionParameters($utf8 = false)
    {
        $connectionParameters = array(
            'dbname'   => 'oxid',
            'user'     => 'oxid',
            'password' => 'oxid',
            'host'     => 'localhost',
            'driver'   => 'pdo_mysql',
        );

        if ($utf8) {
            $connectionParameters['driverOptions'] = array(
                1002 => 'SET NAMES utf8'
            );
        }

        return $connectionParameters;
    }
}
