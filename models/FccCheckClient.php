<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * ActiveRecord for the FCC client check data and the reserve return fields.
 *
 * @property int $id
 * @property string|null $customer
 * @property string|null $system_ac_no
 * @property float|null $amount_transfer
 * @property string|null $currency
 * @property string|null $application_date
 * @property float|null $employee_sum
 * @property string|null $employee_date_sum
 * @property string|null $employee_comment
 * @property string|null $employee_name
 * @property string|null $employee_created_at
 */
class FccCheckClient extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%fcc_check_client}}';
    }

    public function rules()
    {
        return [
            [['employee_sum'], 'filter', 'filter' => static function ($value) {
                return $value === null || $value === ''
                    ? null
                    : str_replace(',', '.', trim((string) $value));
            }],
            [['employee_date_sum', 'employee_comment'], 'filter', 'filter' => static function ($value) {
                $value = trim((string) $value);
                return $value === '' ? null : $value;
            }],
            [['employee_sum'], 'number', 'min' => 0.01],
            [['employee_date_sum'], 'date', 'format' => 'php:Y-m-d'],
            [['employee_comment'], 'string', 'max' => 500],
            [['employee_sum', 'employee_date_sum'], 'validateReserveFields', 'skipOnEmpty' => false],
            [['employee_sum'], 'validateAvailableAmount'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'customer' => 'Клиент',
            'system_ac_no' => 'Счёт',
            'amount_transfer' => 'Сумма списанного остатка',
            'currency' => 'Валюта',
            'application_date' => 'Дата',
            'employee_sum' => 'Сумма возврата из резерва',
            'employee_date_sum' => 'Дата возврата',
            'employee_comment' => 'Комментарий',
            'employee_name' => 'ФИО сотрудника',
        ];
    }

    public function validateReserveFields($attribute)
    {
        $hasAnyValue = $this->employee_sum !== null
            || $this->employee_date_sum !== null
            || $this->employee_comment !== null;

        if ($hasAnyValue && $this->$attribute === null) {
            $this->addError($attribute, 'Заполните сумму и дату возврата.');
        }
    }

    public function validateAvailableAmount($attribute)
    {
        if ($this->$attribute === null || $this->hasErrors($attribute)) {
            return;
        }

        if ($this->amount_transfer === null) {
            $this->addError($attribute, 'Для счёта не указана сумма списанного остатка.');
            return;
        }

        if ((float) $this->$attribute > (float) $this->amount_transfer) {
            $this->addError(
                $attribute,
                'Сумма возврата не может превышать сумму списанного остатка.'
            );
        }
    }

    public function hasReserveData()
    {
        return $this->employee_sum !== null
            || $this->employee_date_sum !== null
            || $this->employee_comment !== null;
    }

    public function applyEmployee(string $employeeName)
    {
        if ($this->hasReserveData()) {
            $this->employee_name = $employeeName;
            $this->employee_created_at = date('Y-m-d H:i:s');
        } else {
            $this->employee_name = null;
            $this->employee_created_at = null;
        }
    }
}
