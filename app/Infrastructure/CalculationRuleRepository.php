<?php


namespace archive\app\Infrastructure;


use archive\coreapp\CalculationRuleConnection;
use archive\coreapp\PDOLogger;
use archive\coreapp\ProdBaseConnection;

class CalculationRuleRepository
{
    private $connToCalcRule;
    private $connToProdBase;
    private $connToArcData;

    /**
     * CalculationRuleRepository constructor.
     */
    public function __construct()
    {
        $this->connToCalcRule = CalculationRuleConnection::getConnectionToCalculationRuleDB();
        $this->connToProdBase = ProdBaseConnection::getConnectionToProdBase();
    }

    public function deleteCalculationRule(int $id)
    {
        $resultQuery = false;

        $sql = 'UPDATE calculation_rule SET calculation_rule_del = 1 WHERE calculation_rule_id = :id';
        $statement = $this->connToCalcRule->prepare($sql);
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);

        try {
            $resultQuery = $statement->execute();
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultQuery;
    }

    public function findTypeSignal($type_signal)
    {
        $resultQuery = false;

        $sql = 'SELECT * FROM universal_types WHERE universal_name = :type_signal';
        $statement = $this->connToProdBase->prepare($sql);
        $statement->bindValue(':type_signal', $type_signal);

        try {
            $statement->execute();
            $resultQuery = $statement->fetch(\PDO::FETCH_OBJ);
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultQuery;
    }

    public function findTagsDevice(string $tagNameList)
    {
        $resultQuery = false;

        $sql = "SELECT tag_name FROM device_tags WHERE tag_name in ($tagNameList)";
        $statement = $this->connToProdBase->prepare($sql);

        try {
            $statement->execute();
            $resultQuery = $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultQuery;
    }

    public function findTagsSignal(string $tagNameList)
    {
        $resultQuery = false;

        $sql = "SELECT tag_name FROM signal_tags WHERE tag_name in ($tagNameList)";
        $statement = $this->connToProdBase->prepare($sql);

        try {
            $statement->execute();
            $resultQuery = $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultQuery;
    }

    public function saveCalculationRule($dataForSave)
    {
        $resultQuery = false;

        $sql = 'INSERT INTO calculation_rule (' . $dataForSave['listColumnTable'] . ') VALUES (' . $dataForSave['aliasColumnTable'] . ')';
        $statement = $this->connToCalcRule->prepare($sql);

        foreach ($dataForSave['bindValue'] as $key => $value) {
            $statement->bindValue($key, $value, (gettype($value) == 'integer') ? \PDO::PARAM_INT:\PDO::PARAM_STR);
        }


        try {
            $isSuccess = $statement->execute();
            if ($isSuccess) {
                $resultQuery['lastInsertId'] = (integer) $this->connToCalcRule->lastInsertId();
            }
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultQuery;
    }

    public function findCalculationRule(array $dataForSearch)
    {
        $resultQuery = false;
        $sql = 'SELECT * FROM calculation_rule WHERE calculation_rule_del = 0 AND object_type = :object_type AND object_id = :object_id';
        $statement = $this->connToCalcRule->prepare($sql);

        foreach ($dataForSearch['bindValue'] as $key => $value) {
            $statement->bindValue($key, $value, (gettype($value) == 'integer') ? \PDO::PARAM_INT:\PDO::PARAM_STR);
        }

        try {
            $statement->execute();
            $resultQuery = $statement->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultQuery;
    }

}
