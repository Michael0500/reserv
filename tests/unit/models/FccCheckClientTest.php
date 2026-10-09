<?php

namespace tests\unit\models;

use app\models\FccCheckClient;

class FccCheckClientTest extends \Codeception\Test\Unit
{
    public function testReturnCannotExceedTransferredAmount()
    {
        $model = $this->createModel([
            'amount_transfer' => 100,
            'employee_sum' => '100,01',
            'employee_date_sum' => '2026-10-09',
        ]);

        verify($model->validate())->false();
        verify($model->getErrors('employee_sum'))->notEmpty();
    }

    public function testAmountEqualToTransferredAmountIsValid()
    {
        $model = $this->createModel([
            'amount_transfer' => 100,
            'employee_sum' => '100,00',
            'employee_date_sum' => '2026-10-09',
        ]);

        verify($model->validate())->true();
        verify($model->employee_sum)->equals('100.00');
    }

    public function testClearedReserveAlsoClearsEmployeeMetadata()
    {
        $model = $this->createModel([
            'employee_name' => 'Сотрудник',
            'employee_created_at' => '2026-10-09 12:00:00',
        ]);

        $model->applyEmployee('Другой сотрудник');

        verify($model->employee_name)->null();
        verify($model->employee_created_at)->null();
    }

    public function testDateAndAmountAreRequiredTogether()
    {
        $model = $this->createModel([
            'amount_transfer' => 100,
            'employee_comment' => 'Возврат согласован',
        ]);

        verify($model->validate())->false();
        $this->assertNotEmpty($model->getErrors('employee_sum'), print_r($model->getErrors(), true));
        $this->assertNotEmpty($model->getErrors('employee_date_sum'), print_r($model->getErrors(), true));
    }

    private function createModel(array $config)
    {
        return new class($config) extends FccCheckClient {
            public function attributes()
            {
                return [
                    'amount_transfer',
                    'employee_sum',
                    'employee_date_sum',
                    'employee_comment',
                    'employee_name',
                    'employee_created_at',
                ];
            }
        };
    }
}
