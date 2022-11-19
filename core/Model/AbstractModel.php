<?php namespace Core\Model;

use Zend\Db\RowGateway\AbstractRowGateway;

abstract class AbstractModel extends AbstractRowGateway
{
    protected $primaryKeyColumn = 'id';

    protected $data;

    public function merge($data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function save()
    {
        return $this->executeSave($this->data);
    }

    protected function executeSave($data)
    {
        $this->initialize();

        if ($this->rowExistsInDatabase()) {
            // UPDATE
            $where = [];
            $isPkModified = false;

            // primary key is always an array even if its a single column
            foreach ($this->primaryKeyColumn as $pkColumn) {
                $where[$pkColumn] = $this->primaryKeyData[$pkColumn];
                if ($data[$pkColumn] == $this->primaryKeyData[$pkColumn]) {
                    unset($data[$pkColumn]);
                } else {
                    $isPkModified = true;
                }
            }

            if (!$data) return true;

            $statement = $this->sql->prepareStatementForSqlObject($this->sql->update()->set($data)->where($where));

            $result = $statement->execute();
            $rowsAffected = $result->getAffectedRows();
            unset($statement, $result); // cleanup

            // If one or more primary keys are modified, we update the where clause
            if ($isPkModified) {
                foreach ($this->primaryKeyColumn as $pkColumn) {
                    if ($data[$pkColumn] != $this->primaryKeyData[$pkColumn]) {
                        $where[$pkColumn] = $data[$pkColumn];
                    }
                }
            }
        } else {
            // INSERT
            $insert = $this->sql->insert();

            $insert->values($data);

            $statement = $this->sql->prepareStatementForSqlObject($insert);

            $result = $statement->execute();
            if (($primaryKeyValue = $result->getGeneratedValue()) && count($this->primaryKeyColumn) == 1) {
                $this->primaryKeyData = [$this->primaryKeyColumn[0] => $primaryKeyValue];
            } else {
                // make primary key data available so that $where can be complete
                $this->processPrimaryKeyData();
            }
            $rowsAffected = $result->getAffectedRows();
            unset($statement, $result); // cleanup

            $where = [];
            // primary key is always an array even if its a single column
            foreach ($this->primaryKeyColumn as $pkColumn) {
                $where[$pkColumn] = $this->primaryKeyData[$pkColumn];
            }
        }

        // refresh data
        $statement = $this->sql->prepareStatementForSqlObject($this->sql->select()->where($where));
        $result = $statement->execute();
        $rowData = $result->current();
        unset($statement, $result); // cleanup

        // make sure data and original data are in sync after save
        $this->populate($rowData, true);

        // return rows affected
        return $rowsAffected;
    }
}