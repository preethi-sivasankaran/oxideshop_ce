<?php
/**
 * #PHPHEADER_OXID_LICENSE_INFORMATION#
 */

use OxidEsales\Eshop\Core\Db\oxDoctrineDatabase;

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
        $this->assertChangedVendorIds();

        $this->connection->rollbackTransaction();

        // assure, that the changes are reverted
        $this->assertOriginalVendorIds();
    }

    public function testTransactionCommitted()
    {
        $this->deleteAddedVendor();

        $this->assertOriginalVendorIds();

        $this->connection->startTransaction();
        $this->connection->execute("INSERT INTO oxorderfiles (OXID) VALUES ('123');", array());

        // assure, that the changes are made in this transaction
        $this->assertChangedVendorIds();

        $this->connection->commitTransaction();

        // assure, that the changes persist the transaction
        $this->assertChangedVendorIds();

        // clean up
        $this->deleteAddedVendor();
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

    /**
     * @param $doctrineDatabase
     *
     * @return array
     */
    private function assertOriginalVendorIds()
    {
        $sql = "SELECT OXID FROM oxorderfiles;";
        $statement = $this->connection->query($sql, null);

        $result = $this->fetchOxIds($statement);
        $expected = array();

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $doctrineDatabase
     *
     * @return array
     */
    private function assertChangedVendorIds()
    {
        $sql = "SELECT OXID FROM oxorderfiles;";
        $statement = $this->connection->query($sql, null);

        $result = $this->fetchOxIds($statement);
        $expected = array('123');

        $this->assertEquals($expected, $result);
    }

    private function deleteAddedVendor()
    {
        $this->connection->execute("DELETE FROM oxorderfiles WHERE OXID = '123';", array('a' => 'a'));
    }

}
