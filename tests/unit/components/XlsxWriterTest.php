<?php

namespace tests\unit\components;

use app\components\XlsxWriter;
use stdClass;
use ZipArchive;

class XlsxWriterTest extends \Codeception\Test\Unit
{
    public function testCreatesWorkbookWithExpectedReportData()
    {
        $filePath = tempnam(sys_get_temp_dir(), 'reserve_xlsx_test_');
        $account = new stdClass();
        $account->customer = '00000001';
        $account->system_ac_no = '40817810111111100000';
        $account->amount_transfer = 10.5;
        $account->currency = 'RUB';
        $account->application_date = '2026-01-04';
        $account->employee_sum = 2;
        $account->employee_date_sum = '2026-01-05';

        try {
            (new XlsxWriter())->writeReserveReport(
                $filePath,
                [$account],
                '2026-01-01',
                '2026-01-31',
                [
                    [
                        'currency' => 'RUB',
                        'opening_balance' => 5,
                        'debit' => 10.5,
                        'return' => 2,
                        'closing_balance' => 13.5,
                    ],
                    [
                        'currency' => 'USD',
                        'opening_balance' => 3,
                        'debit' => 0,
                        'return' => 1,
                        'closing_balance' => 2,
                    ],
                ]
            );

            $zip = new ZipArchive();
            verify($zip->open($filePath))->equals(true);
            $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            verify($worksheet)->stringContainsString('00000001');
            verify($worksheet)->stringContainsString('40817810111111100000');
            verify($worksheet)->stringContainsString('Расшифровка за период');
            verify($worksheet)->stringContainsString('Сводный отчёт');
            verify($worksheet)->stringContainsString('RUB');
            verify($worksheet)->stringContainsString('USD');
            verify($worksheet)->stringContainsString('<f>B8+E8-F8</f>');
            verify(simplexml_load_string($worksheet))->notEmpty();
            verify($zip->getFromName('xl/styles.xml'))->notEmpty();
            $zip->close();
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }
}
