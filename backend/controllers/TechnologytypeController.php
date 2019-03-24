<?php
namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Technologytype;
use backend\models\FileForm;

/**
 * Dispatcher controller
 */
class TechnologytypeController extends MainController
{
    public $modelClass = 'backend\models\Technologytype';

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
        
        
            $array = Technologytype::find()->orderBy('name ASC')->asArray()->all();


        return [
            'technologytypes' => $array,
            'technologytypeslink' => ArrayHelper::map($array, 'id', 'name')
        ];
        
        
    }

    public function actionCreate(){

        if (Yii::$app->user->can('techtypesadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = new Technologytype();
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->fortype = (empty($dataDriver['data']['fortype'])?'':$dataDriver['data']['fortype']);
            $newdriver->author = Yii::$app->user->identity->parenttree . Yii::$app->user->identity->id . "|";
            if ($newdriver->save()){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось создать тип техники');
                return $this->technologytypesModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('techtypesedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if ( Technologytype::updateAll(
                [
                    'name' => $dataDriver['data']['name'],
                    'description' => (empty($dataDriver['data']['description']) ? '' : $dataDriver['data']['description']),
                    'fortype' => (empty($dataDriver['data']['fortype']) ? '' : $dataDriver['data']['fortype'])

                ], ['id' => $id])
            ){
                return ['success' => true];
            }
            else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось изменить тип техники');
                return $this->technologytypesModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('techtypesdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( Technologytype::deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось удалить тип техники');
                return $this->technologytypesModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));