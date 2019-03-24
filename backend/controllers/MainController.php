<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\Response;

/**
 * Main api controller
 */
class MainController extends ActiveController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors                      = parent::behaviors();
        $behaviors['authenticator']     = [
            'class' => HttpBearerAuth::className(),
            'only' => ['index', 'view', 'create', 'update', 'delete', 'edit', 'options' , 'newuser', 'edituser' , 'deleteuser'],
        ];
        $behaviors['contentNegotiator'] = [
            'class'   => ContentNegotiator::className(),
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        $behaviors['access']            = [
            'class' => AccessControl::className(),
            'only'  => ['index', 'view', 'create', 'update', 'delete', 'edit', 'options', 'newuser', 'edituser'],
            'rules' => [
                [
                    'actions' => ['index', 'view', 'create', 'update', 'delete', 'edit', 'options', 'newuser', 'edituser'],
                    'allow'   => true,
                    'roles'   => ['@'],
                ],
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'view' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['POST', 'DELETE'],
		'edit' =>   ['POST', 'EDIT'],		
        ];
    }
}
