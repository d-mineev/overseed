<?php
namespace backend\controllers;

use backend\models\Dispatchers;
use Yii;
use yii\helpers\ArrayHelper;

use backend\models\FileForm;

/**
 * Dispatcher controller
 */
class DispatchersController extends MainController
{
    public $modelClass = 'backend\models\Dispatchers';

    /** @var Fields */
    private $dispatchersModel;

    public function init ()
    {
        parent::init();

        $this->dispatchersModel = new $this->modelClass();
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
        $array = $this->dispatchersModel->getAll();
        $result = ArrayHelper::map($array, 'id', 'name');


        return [
            'dispatchers' => $array,
            'dispatcherslink' => $result,
            'objectdispatcher' => $result

        ];
    }

    public function actionCreate(){

        if (Yii::$app->user->can('dispatchersadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = $this->dispatchersModel;
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->skype = (empty($dataDriver['data']['skype'])?'':$dataDriver['data']['skype']);
            if ($newdriver->save()){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->dispatchersModel->addError('error', 'Ошибка. Не удалось создать диспетчер');
                return $this->dispatchersModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('dispatchersedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if($this->dispatchersModel->updateColum($id, $dataDriver)){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->dispatchersModel->addError('error', 'Ошибка. Не удалось изменить диспетчер');
                return $this->dispatchersModel;
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('dispatchersdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( $this->dispatchersModel->deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->dispatchersModel->addError('error', 'Ошибка. Не удалось удалить диспетчер');
                return $this->dispatchersModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));