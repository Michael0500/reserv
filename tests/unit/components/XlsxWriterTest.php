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
                '2026-01-31'
            );

            $zip = new ZipArchive();
            verify($zip->open($filePath))->equals(true);
            $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            verify($worksheet)->stringContainsString('00000001');
            verify($worksheet)->stringContainsString('40817810111111100000');
            verify($worksheet)->stringContainsString('Расшифровка за период');
            verify($zip->getFromName('xl/styles.xml'))->notEmpty();
            $zip->close();
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }
}
