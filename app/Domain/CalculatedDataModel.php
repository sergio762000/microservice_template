<?php


namespace microservice_template\app\Domain;


use microservice_template\app\Infrastructure\CalculatedDataRepository;

class CalculatedDataModel
{
    const MIN_PERIOD_SEARCH = '1d';
    const MIDDLE_PERIOD_SEARCH = '1w';
    const MAX_PERIOD_SEARCH = '3m';

    private $repository;

    public function __construct()
    {
        $this->repository = new CalculatedDataRepository();
    }

    public function searchCalculated($arrQueryString)
    {
        $resultSearch['status'] = false;
        $resultOneDaySearch = false;
        $resultOneWeekSearch = false;
        $resultThreeMonthsSearch = false;

        $dataCalculationRule = $this->repository->findCalculationRuleBy($arrQueryString['rule_id']);

        if ($dataCalculationRule['last_calculated_date'] != 0) {
            $dateTimeLastCalculated = getdate($dataCalculationRule['last_calculated_date']);
            //первый поиск - в день $dateTimeLastCalculated минус 1d
            $dateEarlyBorderRangeDay = $this->getDayLowerRange($dateTimeLastCalculated, self::MIN_PERIOD_SEARCH);
            $listArchiveTable = $this->getListArchiveTable($dateTimeLastCalculated, $dateEarlyBorderRangeDay, self::MIN_PERIOD_SEARCH);
            $resultOneDaySearch = $this->repository->findLastAndPreviousValue($arrQueryString['rule_id'], $listArchiveTable);

            //второй поиск - в диапазоне [$dateTimeLastCalculated-2d:$dateTimeLastCalculated-8d]
            if ($resultOneDaySearch === false) {
                $dateEarlyBorderRangeDay = $this->getDayLowerRange($dateTimeLastCalculated, self::MIDDLE_PERIOD_SEARCH);
                $listArchiveTable = $this->getListArchiveTable($dateTimeLastCalculated, $dateEarlyBorderRangeDay, self::MIDDLE_PERIOD_SEARCH);
                $resultOneWeekSearch = $this->repository->findLastAndPreviousValue($arrQueryString['rule_id'], $listArchiveTable);

                //третий поиск - в диапазоне [$dateTimeLastCalculated-9d:$dateTimeLastCalculated-3MON]
                if ($resultOneWeekSearch === false) {
                    $dateEarlyBorderRangeDay = $this->getDayLowerRange($dateTimeLastCalculated, self::MAX_PERIOD_SEARCH);
                    $listArchiveTable = $this->getListArchiveTable($dateTimeLastCalculated, $dateEarlyBorderRangeDay, self::MAX_PERIOD_SEARCH);
                    $resultThreeMonthsSearch = $this->repository->findLastAndPreviousValue($arrQueryString['rule_id'], $listArchiveTable);

                    if ($resultThreeMonthsSearch === false) {
                        $resultSearch['error'] = '1';
                        $resultSearch['message'] = 'Расчет изменения значения для данного правила не удался.';
                    } else {
                        $resultSearch['status'] = true;
                        $resultSearch['data'] = (float)$resultThreeMonthsSearch;
                    }
                } else {
                    $resultSearch['status'] = true;
                    $resultSearch['data'] = (float)$resultOneWeekSearch;
                }
            } else {
                $resultSearch['status'] = true;
                $resultSearch['data'] = (float)$resultOneDaySearch;
            }
        } else {
            $resultSearch['error'] = '1';
            $resultSearch['message'] = 'Для данного правила нет рассчитанного значения';
        }

        return $resultSearch;
    }

    private function getDayLowerRange(array $dateTimeLastCalculated, string $timePeriod)
    {
        $result = false;
        $resultSearchMatch = preg_match("#^(\d?\d)([d|w|m])$#", $timePeriod, $resultTimePeriod);

        if ($resultSearchMatch) {
            switch ($resultTimePeriod[2]) {
                case 'd':
                    if ($resultTimePeriod[1] > 92) { $resultTimePeriod[1] = 92; }
                    $quantityDay = $resultTimePeriod[1];
                    $dayMinusNDay = $dateTimeLastCalculated['mday'] - (int) $quantityDay;
                    $result = getdate(mktime($dateTimeLastCalculated['hours'],
                        $dateTimeLastCalculated['minutes'],
                        $dateTimeLastCalculated['seconds'],
                        $dateTimeLastCalculated['mon'],
                        $dayMinusNDay,
                        $dateTimeLastCalculated['year']));
                    break;
                case 'w':
                    if ($resultTimePeriod[1] > 13) { $resultTimePeriod[1] = 13; }
                    $quantityDay = $resultTimePeriod[1] * 7;
                    $dayMinusNDay = $dateTimeLastCalculated['mday'] - (int) $quantityDay;
                    $result = getdate(mktime($dateTimeLastCalculated['hours'],
                        $dateTimeLastCalculated['minutes'],
                        $dateTimeLastCalculated['seconds'],
                        $dateTimeLastCalculated['mon'],
                        $dayMinusNDay,
                        $dateTimeLastCalculated['year']));
                    break;
                case 'm':
                    if ($resultTimePeriod[1] > 3) { $resultTimePeriod[1] = 3; }
                    $result = getdate(mktime($dateTimeLastCalculated['hours'],
                        $dateTimeLastCalculated['minutes'],
                        $dateTimeLastCalculated['seconds'],
                        $dateTimeLastCalculated['mon'] - $resultTimePeriod[1],
                        $dateTimeLastCalculated['mday'],
                        $dateTimeLastCalculated['year']));
                    break;
                default:
                    $quantityDay = 1;
                    $dayMinusNDay = $dateTimeLastCalculated['mday'] - $quantityDay;
                    $result = getdate(mktime($dateTimeLastCalculated['hours'],
                        $dateTimeLastCalculated['minutes'],
                        $dateTimeLastCalculated['seconds'],
                        $dateTimeLastCalculated['mon'],
                        $dayMinusNDay,
                        $dateTimeLastCalculated['year']));
                    break;
            }
        }

        return $result;
    }

    private function getListArchiveTable($dateTimeLastCalculated, $dateTimePrevLastCalculated, $depthRangeDay): array
    {
        $deltaDay = 0;
        $offset = 1;
        $stringListArchiveTable = [];
        $stringListArchiveTableTempl = 'signal_archive_float_';

        $quantityDay = $dateTimeLastCalculated['yday'];
        if ((int) $dateTimeLastCalculated['year'] > (int)$dateTimePrevLastCalculated['year']) {
            if (date('L', mktime(0, 0, 0, $dateTimePrevLastCalculated['mon'], 0, $dateTimePrevLastCalculated['year']))) {
                $deltaDay = 366 - $dateTimePrevLastCalculated['yday'];
            } else {
                $deltaDay = 365 - $dateTimePrevLastCalculated['yday'];
            }
        } else {
            $quantityDay -= $dateTimePrevLastCalculated['yday'];
        }
        $quantityDay += $deltaDay;
        switch ($depthRangeDay) {
            case self::MIN_PERIOD_SEARCH:
            default:
                $offset = 1; break;
            case self::MIDDLE_PERIOD_SEARCH: $offset = 2; break;
            case self::MAX_PERIOD_SEARCH: $offset = 8; break;
        }

        //сначала добавляем в массив таблицу с последними рассчитанными данными
        $newDate = getdate(mktime($dateTimeLastCalculated['hours'], $dateTimeLastCalculated['minutes'],0, $dateTimeLastCalculated['mon'], $dateTimeLastCalculated['mday'], $dateTimeLastCalculated['year']));
        $stringListArchiveTable[] = $stringListArchiveTableTempl . $newDate['year'] . '_' . $newDate['mon'] . '_' . $newDate['mday'];

        for ($i = $offset; $i <= $quantityDay; ++$i) {
            $day = $dateTimeLastCalculated['mday'] - $i;

            $newDate = getdate(mktime($dateTimeLastCalculated['hours'], $dateTimeLastCalculated['minutes'],0,$dateTimeLastCalculated['mon'], $day, $dateTimeLastCalculated['year']));
            $stringListArchiveTable[] = $stringListArchiveTableTempl . $newDate['year'] . '_' . $newDate['mon'] . '_' . $newDate['mday'];
        }

        return $stringListArchiveTable;
    }

}
