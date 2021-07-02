<?php


namespace archive\app\Infrastructure;


use archive\coreapp\ArchiveConnection;
use archive\coreapp\CalculationRuleConnection;
use archive\coreapp\PDOLogger;

class CalculatedDataRepository
{
    private $connToArcData;
    private $connToCalcRule;

    public function __construct()
    {
        $this->connToArcData = ArchiveConnection::getConnectionToArchiveDB();
        $this->connToCalcRule = CalculationRuleConnection::getConnectionToCalculationRuleDB();
    }

    public function findCalculationRuleBy($rule_id)
    {
        $resultQuery = false;

        $sqlString = 'select * from calculation_rule where calculation_rule_del = 0 and calculation_rule_id = :id';
        $statement = $this->connToCalcRule->prepare($sqlString);

        try {
            $statement->execute([
                $rule_id
            ]);
            $resultQuery = $statement->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultQuery;
    }

    public function findLastAndPreviousValue($rule_id, $listArchiveTable)
    {
        if (WORK_MODE_APP == 'dev') {
//            $listArchiveTable = ['signal_archive_float_2021_6_9', 'signal_archive_float_2021_6_8', 'signal_archive_float_2021_6_7', 'signal_archive_float_2021_6_6'];
            $listArchiveTable = ['signal_archive_float_2020_1_1', 'signal_archive_float_2020_1_1', 'signal_archive_float_2021_6_7', 'signal_archive_float_2021_6_6'];
//            $signal_id = 43105577;
            $signal_id = -10;
        } else {
            $signal_id = -1 * (int) $rule_id;
        }

        $resultQuery = false;
        $tableNameLastValue = array_shift($listArchiveTable);

        $resultLastValue = $this->getLastValueForSignalId($signal_id, $tableNameLastValue);
        $resultPrevLastValue = $this->getPrevLastValueForSignalId($signal_id, $listArchiveTable);

        if (($resultLastValue !== false) && ($resultPrevLastValue !== false)) {
            $resultQuery = (float)($resultLastValue['signal_archive_float_value'] - $resultPrevLastValue['signal_archive_float_value']);
        }

        return $resultQuery;
    }

    private function getLastValueForSignalId($signal_id, ?string $tableNameLastValue)
    {
        $resultLastValue = false;

        $sqlQueryLastValue = 'select signal_archive_float_value from ' . $tableNameLastValue . ' where signal_id = :signal_id order by signal_archive_float_dt desc limit 1';
        $statement = $this->connToArcData->prepare($sqlQueryLastValue);
        $statement->bindValue(':signal_id', $signal_id, \PDO::PARAM_INT);

        try {
            $statement->execute();
            $resultLastValue = $statement->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultLastValue;
    }

    private function getPrevLastValueForSignalId($signal_id, array $listArchiveTable)
    {
        $resultPrevLastValue = false;

        $stringTemplate = 'select signal_archive_float_value, signal_archive_float_dt from ';
        $stringQueryAllTable = '';

        $countTable = count($listArchiveTable);
        foreach ($listArchiveTable as $tableName) {
            $stringQueryAllTable .= $stringTemplate . $tableName . ' where signal_id = :signal_id';
            if (--$countTable > 0) {
                $stringQueryAllTable .= ' union ';
            }
        }
        $sqlQueryPrevLastValue = '(' . $stringQueryAllTable . ') order by signal_archive_float_dt desc limit 1';

        $statement = $this->connToArcData->prepare($sqlQueryPrevLastValue);
        $statement->bindValue(':signal_id', $signal_id, \PDO::PARAM_INT);

        try {
            $statement->execute();
            $resultPrevLastValue = $statement->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
        }

        return $resultPrevLastValue;
    }

}
