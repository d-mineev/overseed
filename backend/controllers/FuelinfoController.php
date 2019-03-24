<?php
namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Technologyproc;
use backend\models\Fuelinfo;



/**
 * Dispatcher controller
 */
class FuelinfoController extends MainController
{
    public $modelClass = 'backend\models\Fuelinfo';

    /** @var Fields */
    private $Model;

    public function init ()
    {
        parent::init();

        $this->Model = new $this->modelClass();
    }

    /**
     * @inheritdoc
     */
    public function actions ()
    {
        $actions = parent::actions();

        unset($actions['index']);
        unset($actions['view']);
        unset($actions['delete']);
        unset($actions['create']);
	  unset($actions['edit']);

        return $actions;
    }


    public function behaviors ()
    {
        $behaviors                       = parent::behaviors();
        $behaviors['authenticator']      = ArrayHelper::merge(
            $behaviors['authenticator'],
            [
                'only' => ['upload'],
            ]
        );
        $behaviors['access']             = ArrayHelper::merge(
            $behaviors['access'],
            [
                'only' => ['upload'],
            ]
        );
        $behaviors['access']['rules'][0] = ArrayHelper::merge(
            $behaviors['access']['rules'][0],
            [
                'actions' => ['upload'],
            ]
        );

        return $behaviors;
    }

    public function actionIndex ()
    {
        if (Yii::$app->user->can('superadmin')){
            $array = Fuelinfo::find()->asArray()->orderBy('id ASC')->all();
            $technologyprocs = Technologyproc::find()->asArray()->orderBy('name ASC')->all();
        } else {
            $array = Fuelinfo::find()->asArray()->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->orderBy('id ASC')->all();
            $technologyprocs = Technologyproc::find()->asArray()->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->orderBy('name ASC')->all();
        }
        
        return [
            'fuelinfos' => $array,
            'technologyprocs' => $technologyprocs,
            'technologyprocslink' => ArrayHelper::map($technologyprocs, 'id', 'name'),
            

        ];
    }

    public function actionCreate(){
        $user = Yii::$app->user;
        if (Yii::$app->user->can('fuelinfoadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = new Fuelinfo();
            $newdriver->objectid =  (empty($dataDriver['data']['objectid'])?0:$dataDriver['data']['objectid']);
            $newdriver->techproc = (empty($dataDriver['data']['techproc'])?0:$dataDriver['data']['techproc']);
            $newdriver->fuel = (empty($dataDriver['data']['fuel'])?'':$dataDriver['data']['fuel']);
            $newdriver->author = $user->identity->parenttree . $user->identity->id . "|";
            if ($newdriver->save()){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось создать инфо о расходе');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('fuelinfoedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if ( Fuelinfo::updateAll(
                [
                    'objectid' =>  (empty($dataDriver['data']['objectid'])?0:$dataDriver['data']['objectid']),
                    'techproc' => (empty($dataDriver['data']['techproc'])?0:$dataDriver['data']['techproc']),
                    'fuel' => (empty($dataDriver['data']['fuel'])?'':$dataDriver['data']['fuel'])

                ], ['id' => $id])
            ){
                return ['success' => true];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось изменить инфо о расходе');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('fuelinfodelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( Fuelinfo::deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось удалить инфо о расходе');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));