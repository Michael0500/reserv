<?php

namespace tests\unit\models;

use app\models\ReserveReportForm;

class ReserveReportFormTest extends \Codeception\Test\Unit
{
    public function testValidPeriod()
    {
        $model = new ReserveReportForm([
            'dateFrom' => '2026-01-01',
            'dateTo' => '2026-01-31',
        ]);

        verify($model->validate())->true();
    }

    public function testEndDateCannotPrecedeStartDate()
    {
        $model = new ReserveReportForm([
            'dateFrom' => '2026-02-01',
            'dateTo' => '2026-01-31',
        ]);

        verify($model->validate())->false();
        verify($model->getErrors('dateTo'))->notEmpty();
    }
}
