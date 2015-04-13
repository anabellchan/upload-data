<?php
/**
 * Created by PhpStorm.
 * User: Anabell
 * Date: 2015-04-10
 * Time: 5:01 PM
 */
class TriumfRow
{
    private $row = [];
    private $rowNum = null;

    public function __construct($newRow)
    {
        $this->row = $newRow;
        $this->setRowNum($newRow->getRowIndex());
        //        $rowNum = $this->getRowNum();
        //        echo "<p>$rowNum</p>";
    }

    public function getRowNum() {
        return $this->rowNum;
    }

    public function setRowNum($num) {
        $this->rowNum = $num;
    }

    public function getRow() {
        return $this->row;
    }
    public function toArray() {
        return $this->row->toarray();
    }

}