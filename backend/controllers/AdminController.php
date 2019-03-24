<?php

namespace backend\controllers;


use Yii;
use backend\models\SignupForm;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use common\models\User;
use yii\db\Query;
use backend\models\Objects;
use backend\models\Drivers;
use backend\models\Fields;
use backend\models\Treilers;
use yii\web\Response;

/**
 * Area controller
 */
class AdminController extends MainController
{

    public $modelClass = 'backend\models\SignupForm';

    private $usersModel;

    public function init ()
    {
        parent::init();

        $this->usersModel = new $this->modelClass();
    }

    public function actions ()
    {
        $actions = parent::actions();

        unset($actions['index']);
        unset($actions['view']);
        unset($actions['delete']);
        unset($actions['create']);

        return $actions;
    }

    public function behaviors ()
    {
        $behaviors                       = parent::behaviors();
        $behaviors['authenticator']      = ArrayHelper::merge(
            $behaviors['authenticator'],
            [
                'only' => ['upload', 'generate'],
            ]
        );
        $behaviors['access']             = ArrayHelper::merge(
            $behaviors['access'],
            [
                'only' => ['upload', 'generate'],
            ]
        );
        $behaviors['access']['rules'][0] = ArrayHelper::merge(
            $behaviors['access']['rules'][0],
            [
                'actions' => ['upload', 'generate'],
            ]
        );
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    public function actionIndex ()
    {
        if (Yii::$app->user->can('usersview') OR Yii::$app->user->can('superadmin')){
            $array = $this->usersModel->getAll();
            $drivers = new Drivers();
            $objects = new Objects(); 
            $fields = new Fields();
            $treilers = new Treilers();

            
            return [
                'success' => true,
                'users' => $array,
                'drivers' => $alldrivers =$drivers->getAll(),
                'driverlink' => ArrayHelper::map($alldrivers, 'id', 'name'),
                'objects' => $allobjects =$objects->getAll(),
                'objectlink' => ArrayHelper::map($allobjects, 'id', 'name'),
                'fields' => $allfields =$fields->getAll(),
                'fieldlink' => ArrayHelper::map($allfields, 'id', 'name'),
                'treilers' => $alltreilers =$treilers->getAll(),
                'treilerlink' => ArrayHelper::map($alltreilers, 'id', 'name'),
                
            ];
        } else {
            throw new ForbiddenHttpException('Access denied');
        }
    }

    public function actionView ($id = null)
    {
        if (Yii::$app->user->can('usersview') OR Yii::$app->user->can('superadmin')){
            $array = $this->usersModel->getOne($id);
            if (count($array) == 0){
                $this->usersModel->addError('error', 'Ошибка. Нет доступа к инфо пользователя');
                return $this->usersModel;}
            else {
                $array[0]['objectsrules'] = $this->getJsonRole($array[0]['objectsrules']);
                $array[0]['fieldsrules'] = $this->getJsonRole($array[0]['fieldsrules']);
                $array[0]['treilersrules'] = $this->getJsonRole($array[0]['treilersrules']);
                $array[0]['driversrules'] = $this->getJsonRole($array[0]['driversrules']);
                $roles = Yii::$app->authManager->getRolesByUser($id);

                $answer = [];
                foreach ($roles as $ar){
                    $answer[$ar->name] = true;
                }
                return [
                    'success' => true,
                    'user' => $array,
                    'roles' => $answer

                ];
            }
         //   file_put_contents('/var/www/over.loc/answer.txt', print_r($answer, true ));

        } else {
            throw new ForbiddenHttpException('Access denied');
        }
    }

    public function actionNewuser()
    {
        if (Yii::$app->user->can('usersadd') OR Yii::$app->user->can('superadmin')){
        $newuser = Yii::$app->getRequest()->getBodyParam('newuser');
        $checkbox = Yii::$app->getRequest()->getBodyParam('checkbox');

        $findusername = User::findOne(['username' => $newuser['username']]);
        $finduseremail = User::findOne(['email' => $newuser['email']]);
        if (!($newuser['password']===$newuser['password2'])){
            $this->usersModel->addError('error', 'Ошибка. Два разных пороля');
            return $this->usersModel;

        }
        elseif (!empty($findusername)) {

            if ($findusername->status == 1){
                $this->usersModel->addError('error', 'Ошибка. Пользователь с таким Логином существует. Но он заблокирован');
                return $this->usersModel;
            }
            else{
                $this->usersModel->addError('error', 'Ошибка. Пользователь с таким Логином существует');
                return $this->usersModel;
            }

                }
        elseif (!empty($finduseremail)) {
                    $this->usersModel->addError('error', 'Ошибка. Пользователь с такой электронной почтой существует');
                    return $this->usersModel;
                }
        else {
            $checkboxobject = $this->putJsonRole('checkboxobject');
            $checkboxfield = $this->putJsonRole('checkboxfield');
            $checkboxtreiler = $this->putJsonRole('checkboxtreiler');
            $checkboxdriver = $this->putJsonRole('checkboxdriver');

            $createuser = new SignupForm();
            $createuser->username = $newuser['username'];
            $createuser->name = $newuser['name'];
            $createuser->email = $newuser['email'];
            $createuser->password = $newuser['password'];
            $createuser->telefon = (empty($newuser['telefon'])?'':$newuser['telefon']);
            $createuser->skype = (empty($newuser['skype'])?'':$newuser['skype']);
            $createuser->position = (empty($newuser['position'])?0:$newuser['position']);
            $createuser->objectsrules = (empty($checkboxobject) ?'': '|'.$checkboxobject."|");
            $createuser->fieldsrules = (empty($checkboxfield)  ?'':'|'.$checkboxfield."|");
            $createuser->treilersrules = (empty($checkboxtreiler)  ?'':'|'.$checkboxtreiler."|");
            $createuser->driversrules = (empty($checkboxdriver)  ?'':'|'.$checkboxdriver."|");
            $createuser->role = (empty($newuser['role'])?0:$newuser['role']);
            $createuser->group = (empty($newuser['group'])?0:$newuser['group']);
            $user = $createuser->signup($newuser['status']);
            $findusername = User::findOne(['username' => $newuser['username']]);
            if (!empty($user)) {
                //добавляем к этой роли правила, проверяя чек бокс на true;
                foreach ($checkbox as $key => $value) {
                    if ($value) {
                        //привязываем роль пользователю
                        $userRole = Yii::$app->authManager->getRole($key);
                        Yii::$app->authManager->assign($userRole, $user->id);
                    }
                }
                return [
                    'success' => true,
                    'newuser' => $user,
                    'id' => $findusername->id
                ];
            }
            else {
                $this->usersModel->addError('error', 'Ошибка. Создать пользователя не получилось');
                return $this->usersModel;
            }
        }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }




/*
        $createuser->role = Yii::$app->getRequest()->getBodyParam('role');
        $createuser->rule = Yii::$app->getRequest()->getBodyParam('rule');
 */
    }

    public function actionEdituser()
    {
        if (Yii::$app->user->can('usersedit') OR Yii::$app->user->can('superadmin')){
        $id = Yii::$app->getRequest()->getBodyParam('id');
        $newuser = Yii::$app->getRequest()->getBodyParam('newuser');
        $checkbox = Yii::$app->getRequest()->getBodyParam('checkbox');
        $finduseremail = User::findOne(['email' => $newuser['email']]);
        $findusername = User::findOne(['username' => $newuser['username']]);
        if(!empty($finduseremail) and !($finduseremail->id == $id) ){
            $this->usersModel->addError('error', 'Ошибка. Пользователь с такой электронной почтой существует');
            return $this->usersModel;
        }

        elseif (!empty($findusername) and !($findusername->id == $id)) {
            $this->usersModel->addError('error', 'Ошибка. Пользователь с таким Логином существует');
            return $this->usersModel;
        }

        else {
            $checkboxobject = $this->putJsonRole('checkboxobject');
            $checkboxfield = $this->putJsonRole('checkboxfield');
            $checkboxtreiler = $this->putJsonRole('checkboxtreiler');
            $checkboxdriver = $this->putJsonRole('checkboxdriver');

            $createuser = new SignupForm();

            $createuser->username = $newuser['username'];
            $createuser->name = $newuser['name'];
            $createuser->email = $newuser['email'];
            $createuser->telefon = (empty($newuser['telefon'])?'':$newuser['telefon']);
            $createuser->skype = (empty($newuser['skype'])?'':$newuser['skype']);
            $createuser->position = (empty($newuser['position'])?0:$newuser['position']);
            $createuser->objectsrules = (empty($checkboxobject) ?'': '|'.$checkboxobject."|");
            $createuser->fieldsrules = (empty($checkboxfield)  ?'':'|'.$checkboxfield."|");
            $createuser->treilersrules = (empty($checkboxtreiler)  ?'':'|'.$checkboxtreiler."|");
            $createuser->driversrules = (empty($checkboxdriver)  ?'':'|'.$checkboxdriver."|");
            $createuser->role = (empty($newuser['role'])?0:$newuser['role']);
            $createuser->group = (empty($newuser['group'])?0:$newuser['group']);

            $answer = $createuser->userupdate($id , $newuser['status']);
            if ($answer) {
                $auth = Yii::$app->authManager;
                //надо удалить старую связь  и саму роль ( редактирование)
                $auth->revokeAll($id);
                //добавляем к этой роли правила, проверяя чек бокс на true;
                 //  file_put_contents('/var/www/over.loc/user.txt', print_r($checkbox, true ));
                foreach ($checkbox as $key => $value) {

                    if ($value) {
                        //привязываем роль пользователю
                        $userRole = $auth->getRole($key);
                        $auth->assign($userRole, $id);
                    }
                }

                return [
                    'success' => true,
                ];
            }
            else {
                $this->usersModel->addError('error', 'Ошибка. Не удалось изменить пользователя');
                return $this->usersModel;
            }
        }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDeleteuser(){
        if (Yii::$app->user->can('usersdelite') OR Yii::$app->user->can('superadmin') ){
            $id = Yii::$app->getRequest()->getBodyParam('id');
           if($this->usersModel->delete($id)){
               Yii::$app->authManager->revokeAll($id);
               return [
                   'success' => true,
               ];
           }
           else {
               $this->usersModel->addError('error', 'Ошибка. Не удалось удалить пользователя');
               return $this->usersModel;
           }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }
    }
    public function actionSetpassword(){
        $id = Yii::$app->getRequest()->getBodyParam('id');
        $password = Yii::$app->getRequest()->getBodyParam('password');
        $password2 = Yii::$app->getRequest()->getBodyParam('password2');
       if ($password === $password2){
           if( $this->usersModel->usersetpassword($id , $password)){
               return [
                   'success' => true,
               ];
           }
           else {
               $this->usersModel->addError('error', 'Ошибка. Не удалось сменить пороль');
               return $this->usersModel;
           }

       }
       else {
           $this->usersModel->addError('error', 'Ошибка. Два разных пороля');
           return $this->usersModel;
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
        $checkboxinput = Yii::$app->getRequest()->getBodyParam($filter);
        $checkboxobject = [];
        if (!empty($checkboxinput)) {
            foreach ($checkboxinput as $key => $value) {
                if ($value) $checkboxobject[] = $key;
            }
        }
        if (count($checkboxobject)>0) $checkboxobject = implode("|", $checkboxobject);
        return $checkboxobject;
    }
}
/*
if (Yii::$app->user->can('treilersadd')){
} else {
    throw new ForbiddenHttpException('Access denied');
}*/