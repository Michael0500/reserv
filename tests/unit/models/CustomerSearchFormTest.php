<?php

namespace tests\unit\models;

use app\models\CustomerSearchForm;

class CustomerSearchFormTest extends \Codeception\Test\Unit
{
    public function testCustomerIdMustContainExactlyEightDigits()
    {
        verify((new CustomerSearchForm(['customer' => '00000001']))->validate())->true();
        verify((new CustomerSearchForm(['customer' => '123']))->validate())->false();
        verify((new CustomerSearchForm(['customer' => '1234567A']))->validate())->false();
    }
}
