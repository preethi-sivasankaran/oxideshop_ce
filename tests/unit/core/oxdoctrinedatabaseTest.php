<?php
/**
 * #PHPHEADER_OXID_LICENSE_INFORMATION#
 */

use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use OxidEsales\Eshop\Core\Db\oxDoctrineDatabase;
use OxidEsales\Eshop\Core\LegacyDatabase;

/**
 * @group dbal
 */
class Unit_Core_oxDoctrineDatabaseTest extends OxidTestCase
{

    /**
     * @var oxDoctrineDatabase
     */
    protected $connection = null;

    public function setUp()
    {
        $this->connection = new oxDoctrineDatabase();
    }

    /**
     * Test, that the connection is set correctly.
     */
    public function testConnection()
    {
        $connection = 'our connection mock';
        $this->connection->setConnection($connection);

        $this->assertEquals($connection, $this->connection->getDb());
    }

    /**
     * Test, that the method 'query' results in an empty result, if we select from an empty table.
     */
    public function testQueryWithSelectFromEmptyTable()
    {
        $sql = 'SELECT * FROM oxaddress;';
        $parameters = array();
        $statement = $this->connection->query($sql, $parameters);

        $result = array();
        while ($row = $statement->fetchRow()) {
            $result[] = $row;
        }

        $this->assertEmpty($result);
    }

    /**
     * Test, that the method 'query' results in an empty result, if we select from an empty table.
     */
    public function testQueryWithSelectFromFilledTable()
    {
        $sql = 'SELECT * FROM oxwrapping;';
        $parameters = array();
        $statement = $this->connection->query($sql, $parameters);

        $result = array();
        while ($row = $statement->fetchRow()) {
            $result[] = $row;
        }

        $rowCount = 4;

        $this->assertNotEmpty($result, 'We got no result from the database.');
        $this->assertEquals($rowCount, count($result));
        $this->assertDbResultHasOxIds(
            $result,
            array(
                'a6840cc0ec80b3991.74884864',
                '81b40cf076351c229.14252649',
                '81b40cf0cd383d3a9.70988998',
                '81b40cf210343d625.49755120'
            )
        );
    }

    /**
     * Test, that the method 'query' results in an empty result, if we select from an empty table.
     */
    public function testQueryWithSelectWithParameters()
    {
        $sql = "SELECT oxid,oxtitle FROM oxarticles WHERE oxid = ?;";
        $oxid = '6b6099c305f591cb39d4314e9a823fc1';
        $parameters = array($oxid);
        $statement = $this->connection->query($sql, $parameters);

        $result = array();
        while ($row = $statement->fetchRow()) {
            $result[] = $row;
        }

        $rowCount = 1;

        $this->assertEquals($rowCount, count($result));
        $this->assertEquals($oxid, $result[0]['oxid']);
        $this->assertEquals('Stewart+Brown Shirt Kisser Fish', $result[0]['oxtitle']);
    }

    public function testTransactionRollbacked()
    {
        $this->assertOriginalVendorIds();

        $this->connection->startTransaction();
        $this->connection->execute("INSERT INTO oxorderfiles (OXID) VALUES ('123');", array());

        // assure, that the changes are made in this transaction
        $this->assertChangedOrderFilesIds();

        $this->connection->rollbackTransaction();

        // assure, that the changes are reverted
        $this->assertOriginalVendorIds();
    }

    public function testTransactionCommitted()
    {
        $this->deleteAddedOrderFiles();

        $this->assertOriginalVendorIds();

        $this->connection->startTransaction();
        $this->connection->execute("INSERT INTO oxorderfiles (OXID) VALUES ('123');", array());

        // assure, that the changes are made in this transaction
        $this->assertChangedOrderFilesIds();

        $this->connection->commitTransaction();

        // assure, that the changes persist the transaction
        $this->assertChangedOrderFilesIds();

        // clean up
        $this->deleteAddedOrderFiles();
    }

    /**
     * Extensive test to go at the edges of mysql. Put as many inserts into an transaction - and look what happens.
     *
     * Doctrine\DBAL\Exception\DriverException:
     *  with Doctrine\DBAL\Driver\PDOException
     *  with SQLSTATE[HY000]: General error: 1114 The table 'oxorderfiles' is full"
     *
     * And Fatal error: Out of memory (allocated 6399721472) (tried to allocate 536870912 bytes)
     *
     * with nearly 36 million inserts.
     *
     * But: mysql cares for the rollback and doctrine doesn't destroys the rollback.
     */
    public function testTransactionBulkRollbackedCorrect()
    {
        $this->markTestSkipped('It runs really long and ')
        $this->deleteAddedOrderFiles();

        $this->assertOriginalVendorIds();

        $this->connection->startTransaction();
        $i = 0;
        try {
            for ($i = 0; $i < 100000000; $i++) {
                $this->connection->execute("INSERT INTO oxorderfiles(OXID) VALUES ('$i');", array());
            }
        } catch (Exception $e) {
            var_dump($e,$i);
        }

        // assure, that the changes in the transaction aren't persisting - not called, but nice to stay here
        $this->assertOriginalVendorIds();
    }

    /**
     * Test the setup of a doctrine master/slave connection.
     *
     * This test only runs, if you setup a master database on 33.33.33.30 and the fitting slave on 33.33.33.34
     * --> it is not intended to end in the production test code :)
     */
    public function testMasterSlaveSetUp()
    {
        $this->markTestSkipped('You need to set up a mysql master/slave environment!');

        $connection = $this->createMasterSlaveConnection();

        // we haven't connected to any of the mysql servers, the master shouldn't be connected yet
        $this->assertFalse($connection->isConnectedToMaster());

        $successSlave = $connection->connect('slave'); // worked without 'slave', but i found it unintuitive, so i let it here
        $successMaster = $connection->connect('master');

        $this->assertTrue($successMaster);
        $this->assertTrue($successSlave);

        // we've connected, the master should be connected now
        $this->assertTrue($connection->isConnectedToMaster());

        return $connection;
    }

    /**
     * Test the writing to a master/slave and reading from the slave, if things went well.
     *
     * This test only runs, if you setup a master database on 33.33.33.30 and the fitting slave on 33.33.33.34
     * --> it is not intended to end in the production test code :)
     */
    public function testMasterSlaveWriteToConnectionAndCheckSlave()
    {
        $this->markTestSkipped('You need to set up a mysql master/slave environment!');

        $connection = $this->testMasterSlaveSetUp();

        $slaveConnection = DriverManager::getConnection(
            array(
                'dbname'   => 'oxid',
                'user'     => 'oxid',
                'password' => 'oxid',
                'host'     => '33.33.33.30',
                'driver'   => 'pdo_mysql',
            )
        );

        $orderFilesIds = $this->fetchOrderFilesIdsFromSlave($slaveConnection);
        $this->assertEmpty($orderFilesIds);

        $this->assertOriginalVendorIds($slaveConnection);
        $connection->exec("INSERT INTO oxorderfiles (OXID) VALUES ('123');", array());

        $orderFilesIds = $this->fetchOrderFilesIdsFromSlave($slaveConnection);
        $this->assertNotEmpty($orderFilesIds);
        $this->assertEquals($orderFilesIds[0]['OXID'], '123');

        $this->assertChangedOrderFilesIds($slaveConnection);
        $this->deleteAddedOrderFiles();

        $this->assertOriginalVendorIds($slaveConnection);

        $orderFilesIds = $this->fetchOrderFilesIdsFromSlave($slaveConnection);
        $this->assertEmpty($orderFilesIds);
    }

    public function testDoctrineException()
    {
        try {
            $this->connection->query('SELECT;', null);
        } catch (SyntaxErrorException $e) {
        }
    }

    public function testLegacyException()
    {
        try {

            $db = oxDb::getDb();
            $db->execute('SELECT;');
        } catch (oxAdoDbException $e) {
        }
    }

    /**
     * Assure, that the given database results have the given oxIds.
     *
     * @todo: clarify the correct type of first parameter!
     *
     * @param object $databaseResults The database results we assume to have the given oxIds.
     * @param array  $expectedOxIds   The oxIds the database results should have.
     */
    protected function assertDbResultHasOxIds($databaseResults, $expectedOxIds)
    {
        $oxIds = $this->extractOxIds($databaseResults);

        $this->assertEquals($expectedOxIds, $oxIds, "The fetched rows don't have the expected oxId's!");
    }

    /**
     * @param $databaseResults
     *
     * @return array
     */
    protected function extractOxIds($databaseResults)
    {
        $oxIds = array();

        foreach ($databaseResults as $row) {
            $oxIds[] = $row['OXID'];
        }

        return $oxIds;
    }

    /**
     * Fetch all the oxId's of a statement.
     *
     * @param $statement
     *
     * @return array
     */
    private function fetchOxIds($statement)
    {
        $result = array();
        while ($row = $statement->fetchRow()) {
            $result[] = $row['OXID'];
        }

        return $result;
    }

    private function assertOriginalVendorIds()
    {
        $sql = "SELECT OXID FROM oxorderfiles;";
        $statement = $this->connection->query($sql, null);

        $result = $this->fetchOxIds($statement);
        $expected = array();

        $this->assertEquals($expected, $result);
    }

    private function assertChangedOrderFilesIds()
    {
        $sql = "SELECT OXID FROM oxorderfiles;";
        $statement = $this->connection->query($sql, null);

        $result = $this->fetchOxIds($statement);
        $expected = array('123');

        $this->assertEquals($expected, $result);
    }

    private function deleteAddedOrderFiles()
    {
        $this->connection->execute("DELETE FROM oxorderfiles WHERE OXID = '123';", array('a' => 'a'));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return MasterSlaveConnection
     */
    private function createMasterSlaveConnection()
    {
        $dbUser = 'oxid';
        $dbPassword = 'oxid';
        $dbName = 'oxid';

        $masterIP = '33.33.33.30';
        $slaveIP = '33.33.33.34';

        /**
         * @var MasterSlaveConnection $connection
         */
        $connection = DriverManager::getConnection(
            array(
                'wrapperClass' => 'Doctrine\DBAL\Connections\MasterSlaveConnection',
                'driver'       => 'pdo_mysql',
                'master'       => array(
                    'user'     => $dbUser,
                    'password' => $dbPassword,
                    'host'     => $masterIP,
                    'dbname'   => $dbName
                ),
                'slaves'       => array(
                    array(
                        'user'     => $dbUser,
                        'password' => $dbPassword,
                        'host'     => $slaveIP,
                        'dbname'   => $dbName
                    )
                )
            )
        );

        return $connection;
    }

    /**
     * @param $slaveConnection
     */
    private function fetchOrderFilesIdsFromSlave($slaveConnection)
    {
        $statement = $slaveConnection->query('SELECT OXID FROM oxorderfiles;');

        return $statement->fetchAll();
    }
}
