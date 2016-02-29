<?php
/**
 * #PHPHEADER_OXID_LICENSE_INFORMATION#
 */
namespace OxidEsales\Eshop\Core\Db\Adapter;

class DoctrineResultSet {
    protected $adapted = null;

    public $EOF = true;
    public function __construct($adapted)
    {
        $this->setAdapted($adapted);
    }

    public function fetchRow() {
        return $this->getAdapted()->fetch();
    }

    public function recordCount() {
        return $this->getAdapted()->rowCount();
    }

    public function moveNext() {
        // @todo: test implementation
        return $this->getAdapted()->nextRowset();
    }

    public function getAll() {
        // @todo: test implementation
        return $this->getAdapted()->fetchAll();
    }


    /**
     * @return null
     */
    protected function getAdapted()
    {
        return $this->adapted;
    }

    /**
     * @param null $adapted
     */
    protected function setAdapted($adapted)
    {
        $this->adapted = $adapted;
    }

}
