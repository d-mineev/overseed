<?php
namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Technologytype;
use backend\models\Technologyproc;
use backend\models\Technologyoperation;


/**
 * Dispatcher controller
 */
class TechnologyprocController extends MainController
{
    public $modelClass = 'backend\models\Technologyproc';

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
        if (Yii::$app->user->can('superadmin')) {
            $array = Technologyproc::find()->asArray()->orderBy('name ASC')->all();
            $treilertype = Technologytype::find()->indexBy('id')->where(['fortype' => 2])->asArray()->all();
            $opers = Technologyoperation::find()->indexBy('id')->asArray()->all();
        } else {
            $array = Technologyproc::find()->asArray()->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->orderBy('name ASC')->all();
            $treilertype = Technologytype::find()->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->indexBy('id')->where(['fortype' => 2])->asArray()->all();
            $opers = Technologyoperation::find()->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->indexBy('id')->asArray()->all();
        }
        

        return [
            'technologyprocs' => $array,
            'technologyprocslink' => ArrayHelper::map($array, 'id', 'name'),
            'technologyoperations' => $opers,
            'technoperlink' => ArrayHelper::map($opers, 'id', 'name'),
            'technologytypes' => $treilertype,
            'techntypelink' => ArrayHelper::map($treilertype, 'id', 'name')

        ];
    }

    public function actionCreate(){
        $user = Yii::$app->user;
        if (Yii::$app->user->can('techprocadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = new Technologyproc();
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->techntype = (empty($dataDriver['data']['techntype'])?'':$dataDriver['data']['techntype']);
            $newdriver->technoper = (empty($dataDriver['data']['technoper'])?'':$dataDriver['data']['technoper']);
            $newdriver->author = $user->identity->parenttree . $user->identity->id . "|";
            if ($newdriver->save()){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось создать техн. процесс');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('techprocedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if ( Technologyproc::updateAll(
                [
                    'name' => $dataDriver['data']['name'],
                    'description' => (empty($dataDriver['data']['description']) ? '' : $dataDriver['data']['description']),
                    'techntype' => (empty($dataDriver['data']['techntype'])?'':$dataDriver['data']['techntype']),
                    'technoper' => (empty($dataDriver['data']['technoper'])?'':$dataDriver['data']['technoper'])

                ], ['id' => $id])
            ){
                return ['success' => true];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось изменить техн. процесс');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('techprocdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( Technologyproc::deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось удалить техн. процесс');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));