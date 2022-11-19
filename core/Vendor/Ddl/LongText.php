<?php namespace Core\Vendor\Ddl;

use Zend\Db\Sql\Ddl\Column\Column;

class LongText extends Column
{
    /**
     * @var string
     */
    protected $type = 'LONGTEXT';
}