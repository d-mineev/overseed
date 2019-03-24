<?php
namespace backend\controllers;

use backend\models\Rolesbook;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use backend\models\SignupForm;


/**
 * Object controller
 */
class RolesbookController extends MainController
{
    public $modelClass = 'backend\models\Rolesbook';

    /** @var Fields */
    private $rolesbookModel;

    public function init ()
    {
        parent::init();

        $this->rolesbookModel = new $this->modelClass();
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
        $array = $this->rolesbookModel->getAll();
        $result = ArrayHelper::map($array, 'id', 'name');
        $result2 = ArrayHelper::map($array, 'id', 'roles');
        if (Yii::$app->user->can('superadmin')) {
            $allrolles = Yii::$app->authManager->roles;
        } else {
            $allrolles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
        }

        $answer = [];
        foreach ($allrolles as $key => $value){
            $answer[$key]['name'] = $value->name;
            $answer[$key]['description'] = $value->description;
        }

     //   file_put_contents('/var/www/over.loc/user3roles.txt', print_r($roles, true ));
        return [
            'rolesbook' => $array,
            'rolesbooklink' => $result,
            'rolesidlink' => $result2,
            'allroles' => $answer
        ];
    }

    public function actionCreate(){

        if (Yii::$app->user->can('rolesadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = $this->rolesbookModel;
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->roles = (empty($dataDriver['data']['roles'])?'':json_encode($dataDriver['data']['roles']));
            $userOnline = Yii::$app->user;
            if (empty($userOnline->identity->parenttree)){
                $newdriver->author = "|".$userOnline->identity->id."|";
            } else {
                $newdriver->author = $userOnline->identity->parenttree . $userOnline->identity->id."|";
            }
            if ($newdriver->save()){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->rolesbookModel->addError('error', 'Ошибка. Не удалось создать роль');
                return $this->rolesbookModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('rolesedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if($this->rolesbookModel->updateColum($id, $dataDriver)){
                $auth = Yii::$app->authManager;
                $usermodel = new SignupForm();
                $findusers = $usermodel->getallbyroles($dataDriver['data']['id']);
                foreach ($findusers as $idarray){
                    //надо удалить старую связь  и саму роль-право ( редактирование)
                    $auth->revokeAll($idarray['id']);
                    foreach ($dataDriver['data']['roles'] as $key => $value) {
                        if ($value) {
                            //привязываем роль пользователю
                            $userRole = $auth->getRole($key);
                            $auth->assign($userRole, $idarray['id']);
                        }
                    }
                }
                return [
                    'success' => true,
                ];
            }
            else {
                $this->rolesbookModel->addError('error', 'Ошибка. Не удалось изменить роль');
                return $this->rolesbookModel;
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('rolesdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( $this->rolesbookModel->deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->rolesbookModel->addError('error', 'Ошибка. Не удалось удалить роль');
                return $this->rolesbookModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }

}
