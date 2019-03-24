<?php
namespace backend\controllers;

use backend\models\Positions;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;


/**
 * Object controller
 */
class PositionsController extends MainController
{
    public $modelClass = 'backend\models\Positions';

    /** @var Fields */
    private $positionsModel;

    public function init ()
    {
        parent::init();

        $this->positionsModel = new $this->modelClass();
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
        unset($actions['dispatchers']);

        return $actions;
    }


    /**
     * @inheritdoc
     */
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

    /**
     * @return array
     */
    public function actionIndex ()
    {
                $array = Positions::find()->orderBy('name ASC')->asArray()->all();
            return [
                'positions' => $array,
                'positionslink' => ArrayHelper::map($array, 'id', 'name')
            ];
        
    }

    public function actionCreate(){

        if (Yii::$app->user->can('positionsadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = $this->positionsModel;
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            if ($newdriver->save()){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->positionsModel->addError('error', 'Ошибка. Не удалось создать должность');
                return $this->positionsModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('positionsedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if($this->positionsModel->updateColum($id, $dataDriver)){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->positionsModel->addError('error', 'Ошибка. Не удалось изменить должность');
                return $this->positionsModel;
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('positionsdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
           if( $this->positionsModel->deleteAll(['id' => (int) $id])){
               return [
                   'success' => true,
               ];
           }
           else {
               $this->positionsModel->addError('error', 'Ошибка. Не удалось удалить должность');
               return $this->positionsModel;
           }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }

    public function actionDispatchers(){
        $array = $this->positionsModel->getDis();
        $result = ArrayHelper::map($array, 'id', 'name');


        return [
            'dispatchers' => $array,
            'dispatcherslink' => $result,
            'objectdispatcher' => $result

        ];

    }
}
