<?php

/** @var yii\web\View $this */
/** @var app\models\CustomerSearchForm $searchModel */
/** @var app\models\ReserveReportForm $reportModel */
/** @var app\models\FccCheckClient[] $accounts */
/** @var bool $searched */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Резервы клиентов';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="reserve-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">Поиск клиента</h2>
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'action' => ['index'],
                'options' => ['class' => 'row g-3 align-items-end'],
            ]); ?>
            <div class="col-sm-6 col-lg-4">
                <?= $form->field($searchModel, 'customer')->textInput([
                    'maxlength' => 8,
                    'inputmode' => 'numeric',
                    'autocomplete' => 'off',
                    'placeholder' => '00000001',
                ]) ?>
            </div>
            <div class="col-auto mb-3">
                <?= Html::submitButton('Найти', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <?php if ($searched && !$accounts): ?>
        <div class="alert alert-info">У клиента не найдены счета.</div>
    <?php endif; ?>

    <?php if ($accounts): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 card-title">Счета клиента <?= Html::encode($searchModel->customer) ?></h2>
                <p class="text-muted">Чтобы удалить ранее внесённые сведения, очистите сумму, дату и комментарий и сохраните форму.</p>

                <?php $form = ActiveForm::begin([
                    'action' => ['save', 'customer' => $searchModel->customer],
                    'method' => 'post',
                ]); ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle reserve-table">
                        <thead>
                        <tr>
                            <th>Клиент</th>
                            <th>Счёт</th>
                            <th>Сумма списанного остатка</th>
                            <th>Валюта</th>
                            <th>Дата</th>
                            <th>Сумма возврата из резерва</th>
                            <th>Дата возврата</th>
                            <th>Комментарий</th>
                            <th>ФИО сотрудника</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($accounts as $id => $account): ?>
                            <tr>
                                <td><?= Html::encode($account->customer) ?></td>
                                <td><?= Html::encode($account->system_ac_no) ?></td>
                                <td><?= $account->amount_transfer === null ? '—' : Html::encode(number_format((float) $account->amount_transfer, 2, ',', ' ')) ?></td>
                                <td><?= Html::encode($account->currency) ?></td>
                                <td><?= Html::encode($account->application_date ?: '—') ?></td>
                                <td class="reserve-input">
                                    <?= $form->field($account, "[$id]employee_sum", ['template' => '{input}{error}'])
                                        ->textInput(['inputmode' => 'decimal', 'autocomplete' => 'off']) ?>
                                </td>
                                <td class="reserve-date">
                                    <?= $form->field($account, "[$id]employee_date_sum", ['template' => '{input}{error}'])
                                        ->input('date') ?>
                                </td>
                                <td class="reserve-comment">
                                    <?= $form->field($account, "[$id]employee_comment", ['template' => '{input}{error}'])
                                        ->textarea(['rows' => 2, 'maxlength' => 500]) ?>
                                </td>
                                <td><?= Html::encode($account->employee_name ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h2 class="h5 card-title">Отчёт по резервам</h2>
            <p class="text-muted mb-3">Выберите период. Выгрузка будет подключена после уточнения правил формирования отчёта.</p>
            <?php $form = ActiveForm::begin([
                'options' => ['class' => 'row g-3 align-items-end'],
            ]); ?>
            <div class="col-sm-4 col-lg-3"><?= $form->field($reportModel, 'dateFrom')->input('date') ?></div>
            <div class="col-sm-4 col-lg-3"><?= $form->field($reportModel, 'dateTo')->input('date') ?></div>
            <div class="col-auto mb-3">
                <?= Html::button('Выгрузить в Excel', ['class' => 'btn btn-outline-success', 'disabled' => true]) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
