<?php
namespace OxidEsales\Eshop\Tests\integration\core\Database;

/**
 * This file is part of OXID eShop Community Edition.
 *
 * OXID eShop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eShop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2016
 * @version       OXID eShop CE
 */

use Doctrine\DBAL\DBALException;
use OxidEsales\Eshop\Core\Database\DatabaseInterface;
use OxidEsales\Eshop\Core\Database\Doctrine;

/**
 * Tests for our database object.
 *
 * @group doctrine
 */
class DoctrineTest extends DatabaseInterfaceImplementationTest
{

    /**
     * @var string The database exception class to be thrown
     */
    const DATABASE_EXCEPTION_CLASS = 'OxidEsales\Eshop\Core\exception\DatabaseException';

    /**
     * @var string The result set class class
     */
    const RESULT_SET_CLASS = 'OxidEsales\Eshop\Core\Database\Adapter\DoctrineResultSet';

    /**
     * @var string The empty result set class class
     */
    const EMPTY_RESULT_SET_CLASS = 'OxidEsales\Eshop\Core\Database\DoctrineEmptyResultSet';

    /**
     * @var bool Use the legacy database adapter.
     *
     * @todo get rid of this
     */
    const USE_LEGACY_DATABASE = false;

    /**
     * Set up before beginning with tests
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::createDatabaseTable();
    }

    /**
     * Tear down after all tests are done
     */
    public static function tearDownAfterClass()
    {
        self::removeDatabaseTable();

        parent::tearDownAfterClass();
    }

    /**
     * Create a table in the database especially for this test.
     */
    protected static function createDatabaseTable()
    {
        $db = self::createDatabaseStatic();

        $db->execute('CREATE TABLE IF NOT EXISTS ' . self::TABLE_NAME . ' (oxid CHAR(32), oxuserid CHAR(32)) ENGINE innoDb;');
    }

    /**
     * Drop the test database table.
     */
    protected static function removeDatabaseTable()
    {
        $db = self::createDatabaseStatic();

        $db->execute('DROP TABLE ' . self::TABLE_NAME . ';');
    }

    /**
     * Create the database object under test.
     *
     * @return Doctrine The database object under test.
     */
    protected function createDatabase()
    {
        return new Doctrine();
    }

    /**
     * Close the database connection.
     */
    protected function closeConnection()
    {
        $this->database->closeConnection();
    }
    
    /**
     * Create the database object under test - the static pendant to use in the setUpBeforeClass and tearDownAfterClass.
     *
     * @return Doctrine The database object under test.
     */
    protected static function createDatabaseStatic()
    {
        return new Doctrine();
    }

    /**
     * @return string The name of the database exception class
     */
    protected function getDatabaseExceptionClassName()
    {
        return self::DATABASE_EXCEPTION_CLASS;
    }

    /**
     * @return string The name of the result set class
     */
    protected function getResultSetClassName()
    {
        return self::RESULT_SET_CLASS;
    }

    /**
     * @return string The name of the empty result set class
     */
    protected function getEmptyResultSetClassName()
    {
        return self::EMPTY_RESULT_SET_CLASS;
    }

    /**
     * Test that the expected exception is thrown for an invalid function parameter.
     * See the data provider for arguments considered invalid.
     *
     * @dataProvider dataProviderTestGetArrayThrowsDatabaseExceptionOnInvalidArguments
     *
     * @param mixed $invalidParameter A parameter, which is considered invalid and will trigger an exception
     */
    public function testGetArrayThrowsDatabaseExceptionOnInvalidArguments($invalidParameter)
    {
        $expectedExceptionClass = '\InvalidArgumentException';
        $this->setExpectedException($expectedExceptionClass);

        $this->database->getArray(
            "SELECT OXID FROM " . self::TABLE_NAME . " WHERE OXID = '" . self::FIXTURE_OXID_1 . "'",
            $invalidParameter
        );
    }

    /**
     * Test delegation of SELECT queries to Doctrine::select()
     */
    public function testExecuteDelegatesSelectQueriesToSelectMethod()
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' LIMIT 0,1';

        /** @var \OxidEsales\Eshop\Core\Database\Doctrine|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder('\OxidEsales\Eshop\Core\Database\Doctrine')
            ->setMethods(['select'])
            ->getMock();

        $databaseMock->expects($this->once())->method('select');

        $databaseMock->execute(
            $query,
            array()
        );
    }

    /**
     * As in ADOdb Lite GetAll is just an alias for getArray. Test that delegation works.
     *
     */
    public function testGetAllDelegatesToGetArray()
    {
        /** @var \OxidEsales\Eshop\Core\Database\Doctrine|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder('\OxidEsales\Eshop\Core\Database\Doctrine')
            ->setMethods(['getArray'])
            ->getMock();

        $databaseMock->expects($this->once())->method('getArray');

        $databaseMock->getAll(
            "SELECT OXID FROM " . self::TABLE_NAME . " WHERE OXID = '" . self::FIXTURE_OXID_1 . "'"
        );
    }

    /**
     * Test, that setFetchMode returns expected values.
     */
    public function testSetFetchModeReturnsExpectedValues()
    {
        /** Get a fresh database */
        $database = $this->createDatabase();

        $message = 'Setting the fetch mode on a fresh connection will return null';
        $expectedReturnValue = DatabaseInterface::FETCH_MODE_NUM;
        $fetchMode = DatabaseInterface::FETCH_MODE_ASSOC;

        $actualReturnValue = $database->setFetchMode($fetchMode);

        $this->assertSame($expectedReturnValue, $actualReturnValue, $message);


        $message = 'Fetch mode was set to FETCH_MODE_ASSOC and will returned now';
        $expectedReturnValue = DatabaseInterface::FETCH_MODE_ASSOC;
        $fetchMode = DatabaseInterface::FETCH_MODE_BOTH;

        $actualReturnValue = $database->setFetchMode($fetchMode);

        $this->assertSame($expectedReturnValue, $actualReturnValue, $message);


        $message = 'Fetch mode was set to FETCH_MODE_BOTH and will returned now';
        $expectedReturnValue = DatabaseInterface::FETCH_MODE_BOTH;
        $fetchMode = DatabaseInterface::FETCH_MODE_DEFAULT;

        $actualReturnValue = $database->setFetchMode($fetchMode);

        $this->assertSame($expectedReturnValue, $actualReturnValue, $message);


        $message = 'Fetch mode was set to FETCH_MODE_DEFAULT and will returned now';
        $expectedReturnValue = DatabaseInterface::FETCH_MODE_DEFAULT;
        $fetchMode = DatabaseInterface::FETCH_MODE_NUM;

        $actualReturnValue = $database->setFetchMode($fetchMode);

        $this->assertSame($expectedReturnValue, $actualReturnValue, $message);
    }

    /**
     * Test, that startTransaction() throws the expected Exception on failure.
     */
    public function testStartTransactionThrowsExpectedExceptionOnFailure()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION_CLASS);

        $connectionMock =  $this->getMockBuilder('\Doctrine')
            ->setMethods(['beginTransaction'])
            ->getMock();
        $connectionMock->expects($this->once())
            ->method('beginTransaction')
            ->willThrowException(new DBALException());

        /** @var \OxidEsales\Eshop\Core\Database\Doctrine|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder('\OxidEsales\Eshop\Core\Database\Doctrine')
            ->setMethods(['getConnection'])
            ->getMock();
        $databaseMock->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connectionMock));

        $databaseMock->startTransaction();
    }

    /**
     * Test, that commitTransaction() throws the expected Exception on failure.
     */
    public function testCommitTransactionThrowsExpectedExceptionOnFailure()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION_CLASS);

        $connectionMock =  $this->getMockBuilder('\Doctrine')
            ->setMethods(['commit'])
            ->getMock();
        $connectionMock->expects($this->once())
            ->method('commit')
            ->willThrowException(new DBALException());

        /** @var \OxidEsales\Eshop\Core\Database\Doctrine|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder('\OxidEsales\Eshop\Core\Database\Doctrine')
            ->setMethods(['getConnection'])
            ->getMock();
        $databaseMock->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connectionMock));

        $databaseMock->commitTransaction();
    }

    /**
     * Test, that rollbackTransaction() throws the expected Exception on failure.
     */
    public function testRollbackTransactionThrowsExpectedExceptionOnFailure()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION_CLASS);

        $connectionMock =  $this->getMockBuilder('\Doctrine')
            ->setMethods(['rollBack'])
            ->getMock();
        $connectionMock->expects($this->once())
            ->method('rollBack')
            ->willThrowException(new DBALException());

        /** @var \OxidEsales\Eshop\Core\Database\Doctrine|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder('\OxidEsales\Eshop\Core\Database\Doctrine')
            ->setMethods(['getConnection'])
            ->getMock();
        $databaseMock->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connectionMock));

        $databaseMock->rollbackTransaction();
    }

    /**
     * Test, that setTransactionIsolationLevel() throws the expected Exception on failure.
     */
    public function testSetTransactionIsolationLevelThrowsExpectedExceptionOnFailure()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION_CLASS);

        $connectionMock =  $this->getMockBuilder('\Doctrine')
            ->setMethods(['setTransactionIsolation'])
            ->getMock();
        $connectionMock->expects($this->once())
            ->method('setTransactionIsolation')
            ->willThrowException(new DBALException());

        /** @var \OxidEsales\Eshop\Core\Database\Doctrine|\PHPUnit_Framework_MockObject_MockObject $databaseMock */
        $databaseMock = $this->getMockBuilder('\OxidEsales\Eshop\Core\Database\Doctrine')
            ->setMethods(['getConnection'])
            ->getMock();
        $databaseMock->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connectionMock));

        $databaseMock->setTransactionIsolationLevel('READ COMMITTED');
    }

    /**
     * Test, that setTransactionIsolationLevel() throws the expected Exception on failure.
     */
    public function testSetTransactionIsolationLevelThrowsExpectedExceptionOnInvalidParameter()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $this->database->setTransactionIsolationLevel('INVALID TRANSACTION ISOLATION LEVEL');
    }
}
