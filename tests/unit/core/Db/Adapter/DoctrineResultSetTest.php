<?php
/**
 * #PHPHEADER_OXID_LICENSE_INFORMATION#
 */

use OxidEsales\Eshop\Core\Db\Adapter\DoctrineResultSet;


/**
 * @group dbal
 */
class Unit_Core_Db_Adapter_DoctrineResultSetTest extends OxidTestCase
{

    /**
     * Test, that the method fetchRow is delegating to fetch of the adapted object.
     */
    public function testConnection()
    {
        $adapted = $this->getMockBuilder('stdClass')
            ->setMethods(array('fetch'))
            ->getMock();

        $adapted->expects($this->once())->method('fetch');

        $adapter = new DoctrineResultSet($adapted);
        $adapter->fetchRow();
    }

}
