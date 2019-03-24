<?php
namespace backend\controllers;

use backend\models\UsersGroup;
use backend\models\SignupForm;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use common\models\User;
use yii\db\Query;
use backend\models\Objects;
use backend\models\Drivers;
use backend\models\Fields;
use backend\models\Treilers;

/**
 * Object controller
 */
class UsersgroupController extends MainController
{
    public $modelClass = 'backend\models\UsersGroup';

    /** @var Fields */
    private $usergroupsModel;

    public function init ()
    {
        parent::init();

        $this->usergroupsModel = new $this->modelClass();
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
        $array = $this->usergroupsModel->getAll();
        $array =  $this->ViewJsonFormat($array, array('objectsrules','fieldsrules','treilersrules','driversrules'));
        $result = ArrayHelper::map($array, 'id', 'name');
        $result2 = ArrayHelper::map($array, 'id', 'objectsrules');
        $result3 = ArrayHelper::map($array, 'id', 'fieldsrules');
        $result4 = ArrayHelper::map($array, 'id', 'treilersrules');
        $result5 = ArrayHelper::map($array, 'id', 'driversrules');
    //    $array = $this->usersModel->getAll();
        $drivers = new Drivers();
        $objects = new Objects();
        $fields = new Fields();
        $treilers = new Treilers();
        return [
            'usergroups' => $array,
            'usergroupslink' => $result,
            'groupidobjectsrules' => $result2,
            'groupidfieldsrules' => $result3,
            'groupidtreilersrules' => $result4,
            'groupiddriversrules' => $result5,
            'drivers' => $alldrivers =$drivers->getAll(),
            'driverlink' => ArrayHelper::map($alldrivers, 'id', 'name'),
            'objects' => $allobjects =$objects->getAll(),
            'objectlink' => ArrayHelper::map($allobjects, 'id', 'name'),
            'fields' => $allfields =$fields->getAll(),
            'fieldlink' => ArrayHelper::map($allfields, 'id', 'name'),
            'treilers' => $alltreilers =$treilers->getAll(),
            'treilerlink' => ArrayHelper::map($alltreilers, 'id', 'name'),
        ];
    }



    public function actionCreate(){




        if (Yii::$app->user->can('groupusersadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);

            $newdriver = $this->usergroupsModel;
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->objectsrules = $this->putJsonRole($dataDriver['data']['checkboxobject']);
            $newdriver->fieldsrules = $this->putJsonRole(['data']['checkboxfield']);
            $newdriver->treilersrules = $this->putJsonRole(['data']['checkboxtreiler']);
            $newdriver->driversrules = $this->putJsonRole(['data']['checkboxdriver']);
            $newdriver->author = Yii::$app->user->identity->parenttree . Yii::$app->user->identity->id . "|";

            if ($newdriver->save()){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->usergroupsModel->addError('error', 'Ошибка. Не удалось создать группу пользователей');
                return $this->usergroupsModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('groupusersedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];


            $dataDriver['data']['checkboxobject'] = $this->putJsonRole($dataDriver['data']['checkboxobject']);
            $dataDriver['data']['checkboxfield'] = $this->putJsonRole($dataDriver['data']['checkboxfield']);
            $dataDriver['data']['checkboxtreiler'] = $this->putJsonRole($dataDriver['data']['checkboxtreiler']);
            $dataDriver['data']['checkboxdriver'] = $this->putJsonRole($dataDriver['data']['checkboxdriver']);
            if($this->usergroupsModel->updateColum($id, $dataDriver)){
/*
                $usermodel = new SignupForm();
                if( $usermodel->upUsersInGroup($id, $dataDriver)){
                    return [
                        'success' => true,
                    ];

                } else {
                    $this->usergroupsModel->addError('error', 'Ошибка. Не удалось изменить фильтры доступа у пользователей');
                    return $this->usergroupsModel;
                }
*/
                return [
                    'success' => true,
                ];
            }
            else {
                $this->usergroupsModel->addError('error', 'Ошибка. Не удалось изменить группу пользователей');
                return $this->usergroupsModel;
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('groupusersdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( $this->usergroupsModel->deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->usergroupsModel->addError('error', 'Ошибка. Не удалось удалить группу пользователей');
                return $this->usergroupsModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }
    private function getJsonRole ($filter){
        if (!empty($filter)){
            $objectrules =  explode("|",$filter);
            foreach ($objectrules as $value){
                if (!empty($value)) $answer[$value]=true;
            }
            return json_encode($answer);
        } else {
            return $filter;
        }
    }
    private function putJsonRole ($filter){
        $checkboxobject = [];
        if (!empty($filter)) {
            foreach ($filter as $key => $value) {
                if ($value) $checkboxobject[] = $key;
            }
        }
        if (count($checkboxobject)>0) {
            $checkboxobject = '|'. implode("|", $checkboxobject) . '|';
        } else {
            $checkboxobject ='';
        }
        return $checkboxobject;
    }
    private function ViewJsonFormat($Array, $Parrametrs){
        foreach ($Array as $key => $value) {
            foreach ($Parrametrs as $Parrametr){
                if (!empty($value[$Parrametr])){
                    $forObj = explode("|",$value[$Parrametr]);
                    $forObjJson = [];
                    foreach ($forObj as $value2){
                        if (!empty($value2)) $forObjJson[$value2] = true;
                    }
                    $forObjJson = json_encode($forObjJson);
                    $Array[$key][$Parrametr] = $forObjJson;
                }
            }
        }
        return $Array;
    }
}