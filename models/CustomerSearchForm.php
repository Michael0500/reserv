<?php

namespace app\models;

use yii\base\Model;

class CustomerSearchForm extends Model
{
    public $customer;

    public function rules()
    {
        return [
            ['customer', 'filter', 'filter' => 'trim'],
            ['customer', 'required'],
            ['customer', 'match', 'pattern' => '/^\d{8}$/', 'message' => 'Customer ID должен состоять из 8 цифр.'],
        ];
    }

    public function attributeLabels()
    {
        return ['customer' => 'Customer ID'];
    }
}
