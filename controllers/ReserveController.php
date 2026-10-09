<?php

namespace app\controllers;

use app\models\CustomerSearchForm;
use app\models\FccCheckClient;
use app\models\ReserveReportForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class ReserveController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'save' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new CustomerSearchForm();
        $reportModel = new ReserveReportForm();
        $accounts = [];
        $searched = false;

        if ($searchModel->load(Yii::$app->request->get()) && $searchModel->validate()) {
            $searched = true;
            $accounts = $this->findAccounts($searchModel->customer);
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'reportModel' => $reportModel,
            'accounts' => $accounts,
            'searched' => $searched,
        ]);
    }

    public function actionSave($customer)
    {
        $searchModel = new CustomerSearchForm(['customer' => $customer]);
        if (!$searchModel->validate()) {
            throw new \yii\web\BadRequestHttpException('Некорректный Customer ID.');
        }

        $accounts = $this->findAccounts($searchModel->customer);
        if (!$accounts) {
            throw new \yii\web\NotFoundHttpException('Счета клиента не найдены.');
        }

        $loaded = FccCheckClient::loadMultiple($accounts, Yii::$app->request->post());
        $valid = $loaded && FccCheckClient::validateMultiple($accounts);

        if ($valid) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $employeeName = (string) Yii::$app->user->identity->username;
                foreach ($accounts as $account) {
                    $account->applyEmployee($employeeName);
                    if (!$account->save(false, [
                        'employee_sum',
                        'employee_date_sum',
                        'employee_comment',
                        'employee_name',
                        'employee_created_at',
                    ])) {
                        throw new \RuntimeException('Не удалось сохранить данные счёта.');
                    }
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Данные по резервам сохранены.');
                return $this->redirect(['index', 'CustomerSearchForm[customer]' => $searchModel->customer]);
            } catch (\Throwable $exception) {
                $transaction->rollBack();
                Yii::error($exception, __METHOD__);
                Yii::$app->session->setFlash('error', 'Не удалось сохранить данные. Повторите попытку.');
            }
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'reportModel' => new ReserveReportForm(),
            'accounts' => $accounts,
            'searched' => true,
        ]);
    }

    private function findAccounts(string $customer): array
    {
        return FccCheckClient::find()
            ->where(['customer' => $customer])
            ->orderBy(['system_ac_no' => SORT_ASC, 'id' => SORT_ASC])
            ->indexBy('id')
            ->all();
    }
}
