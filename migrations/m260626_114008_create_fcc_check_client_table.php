<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%fcc_check_client}}`.
 */
class m260626_114008_create_fcc_check_client_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%fcc_check_client}}', [
            'id' => $this->primaryKey(),
            'customer' => $this->string(8)->comment('Номер клиента (FCC_ID)'),
            'notif_cust_dt' => $this->date()->comment('Дата отправки уведомления'),
            'notif_channel' => $this->string(50)->comment('Канал уведомления'),
            'system_ac_no' => $this->string(20)->comment('Номер счета по ЦБ'),
            'calc_ac_close_date' => $this->date()->comment('Плановая дата закрытия'),
            'cus_type_balance' => $this->string(8)->comment('Есть или нет остаток на счету'),
            'ac_close_date' => $this->date()->comment('Дата закрытия счета'),
            'account_status' => $this->string(6)->comment('Статус счета'),
            'processed_status' => $this->string(1)->comment('U - запись не обработана, P - запись успешно обработана, E - бизнес-ошибка'),
            'remarks' => $this->string(300)->comment('Детализация бизнес-ошибки'),
            'amount_transfer' => $this->double()->comment('Сумма списания'),
            'currency' => $this->string(6)->comment('Валюта счета'),
            'application_date' => $this->date()->comment('Дата фактической обработки'),
            'processing_time' => $this->dateTime()->comment('Время списания'),
            'employee_sum' => $this->double()->comment('Сумма возврата из резерва'),
            'employee_date_sum' => $this->date()->comment('Дата возврата из резерва'),
            'employee_comment' => $this->string(500)->comment('Комментарий'),
            'employee_name' => $this->string()->comment('Кто редактировал (сотрудник)'),
            'employee_created_at' => $this->timestamp()->comment('Дата редактирования')->defaultExpression('NOW()'),
        ]);

        $this->createIndex('idx-customer-fcc_check_client', '{{%fcc_check_client}}', 'customer');
        $this->createIndex('idx-system_ac_no-fcc_check_client', '{{%fcc_check_client}}', 'system_ac_no');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%fcc_check_client}}');
    }
}