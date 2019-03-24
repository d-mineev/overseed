<?php
namespace backend\controllers;


use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Technologyoperation;


/**
 * Dispatcher controller
 */
class TechnologyoperationController extends MainController
{
    public $modelClass = 'backend\models\Technologyoperation';

    /** @var Fields */
    private $technologytypesModel;

    public function init ()
    {
        parent::init();

        $this->technologytypesModel = new $this->modelClass();
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
        if (Yii::$app->user->can('superadmin')) {
            $array = Technologyoperation::find()->orderBy('name ASC')->asArray()->all();
        } else {
            $array = Technologyoperation::find()->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->orderBy('name ASC')->asArray()->all();
        }

        return [
            'technologyoperations' => $array,
            'technologyoperationlink' => ArrayHelper::map($array, 'id', 'name')
        ];
    }

    public function actionCreate(){

        if (Yii::$app->user->can('techopersadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = $this->technologytypesModel;
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->color = (empty($dataDriver['data']['color'])?'':$dataDriver['data']['color']);
            $newdriver->author = Yii::$app->user->identity->parenttree . Yii::$app->user->identity->id . "|";
            if ($newdriver->save()){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось создать технологическую операцию');
                return $this->technologytypesModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('techopersedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if($this->technologytypesModel->updateColum($id, $dataDriver)){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось изменить технологическую операцию');
                return $this->technologytypesModel;
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('techopersdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( $this->technologytypesModel->deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось удалить технологическую операцию');
                return $this->technologytypesModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));