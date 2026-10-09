<?php

namespace app\models;

use yii\base\Model;

class ReserveReportForm extends Model
{
    public $dateFrom;
    public $dateTo;

    public function rules()
    {
        return [
            [['dateFrom', 'dateTo'], 'required'],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            ['dateTo', 'validateDateRange'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'dateFrom' => 'Дата с',
            'dateTo' => 'Дата по',
        ];
    }

    public function validateDateRange($attribute)
    {
        if (!$this->hasErrors() && $this->dateTo < $this->dateFrom) {
            $this->addError($attribute, 'Дата окончания должна быть не раньше даты начала.');
        }
    }
}
