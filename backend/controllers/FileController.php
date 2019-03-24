<?php
namespace backend\controllers;

use common\models\LoginForm;
use frontend\models\ContactForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\Controller;
use yii\web\Response;

/**
 * File controller
 */
class FileController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors                      = parent::behaviors();
        $behaviors['authenticator']     = [
            'class' => HttpBearerAuth::className(),
            'only'  => ['upload'],
        ];
        $behaviors['contentNegotiator'] = [
            'class'   => ContentNegotiator::className(),
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        $behaviors['access']            = [
            'class' => AccessControl::className(),
            'only'  => ['upload'],
            'rules' => [
                [
                    'actions' => ['upload'],
                    'allow'   => true,
                    'roles'   => ['@'],
                ],
            ],
        ];

        return $behaviors;
    }

    public function actionUpload()
    {
        $model = new LoginForm();

        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->login()) {
            return ['access_token' => Yii::$app->user->identity->getAuthKey()];
        } else {
            $model->validate();

            return $model;
        }
    }
}
