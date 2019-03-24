<?php
namespace backend\controllers;

use backend\models\Fields;
use backend\models\FileForm;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use yii\web\ForbiddenHttpException;
use yii\db\Query;

/**
 * Fields controller
 */
class FieldsController extends MainController
{
    public $modelClass = 'backend\models\Fields';

    /** @var Fields */
    private $fieldsModel;

    public function init ()
    {
        parent::init();

        $this->fieldsModel = new $this->modelClass();
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
        $user = Yii::$app->user;
        if(($user->can('superadmin')) or ($user->can('fieldsview'))){
            $Objtest = $this->fieldsModel->getAll();
            return [
                'fields' => $Objtest,
                'fieldlink' => ArrayHelper::map($Objtest, 'id', 'name'),
                'fieldper' =>   ArrayHelper::map($Objtest, 'id', 'perimeter'),
            ];
        }else {
            throw new ForbiddenHttpException('Access denied');
        }
    }



    /**
     * @return array|Fields
     */
    public function actionUpload ()
    {
        $file = UploadedFile::getInstance(new FileForm(), 'file');
        $file->saveAs(Yii::$app->params['files']['data'] . $file->name);

        if ($this->fieldsModel->saveFields($file->name)) {
            return [
                'success' => true
            ];
        } else {
            $this->fieldsModel->addError('error', 'Ошибка. Не удалось сохранить файл');

            return $this->fieldsModel;
        }
    }

    /**
     * @param null $id
     *
     * @return null|static
     */
    public function actionView ($id = null)
    {
        if($this->fieldsModel->getOne($id)){
            return [
                'field' => $this->fieldsModel->getOne($id),
            ];
        } else {
            $this->fieldsModel->addError('error', 'Ошибка. Не удалось показать поле');
            return $this->fieldsModel;
        }

    }

    /**
     * @return array|Fields

     */

    public function actionCreate(){

        if (Yii::$app->user->can('fieldsadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
      //      $this->fieldsModel->createColum($dataDriver);


            if  ($this->fieldsModel->createColum($dataDriver)){
                $idfield = $this->fieldsModel->findlastid($dataDriver['data']['name']);
            }else {
                $this->fieldsModel->addError('error', 'Ошибка. Не удалось создать геозону');
                return $this->fieldsModel;
            }

/*
            $user = Yii::$app->user;
            if (!empty($user->identity->fieldsrules))  {
                $checkboxfield = json_decode($user->identity->fieldsrules,true);
            } else {
                $checkboxfield = [];
            }
            $checkboxfield[$idfield] = true;
            $checkboxfield = json_encode($checkboxfield);
            if (Yii::$app->db->createCommand()->update('user',
                ['fieldsrules' => $checkboxfield],
                'id ='.$user->identity->id)
                ->execute() )
*/
                $user = Yii::$app->user;
            if (!empty($user->identity->parenttree)) {
                $id = explode("|", $user->identity->parenttree);
                $id = array_reverse($id);
            } else {
                $id = [];
                $id[1] = 1;
            }
            $query  = new Query();
            if (($id[1] == 1)or ($id[1] == 0)){
                if($query->createCommand()->setSql('
                    UPDATE "user"
                    SET fieldsrules = (CASE WHEN fieldsrules = \'\' THEN \'|'.$idfield.'|\' ELSE CONCAT(fieldsrules ,\''.$idfield.'|\') END)
                    WHERE  id='.$user->identity->id.'
                    '
                )->execute())
                {
                    return [
                        'success' => true,
                    ];
                } else {
                    $this->fieldsModel->addError('error', 'Ошибка. Не удалось добавить пользователю геозону');
                    return $this->fieldsModel;
                }
            }else {
                if($query->createCommand()->setSql('
                    UPDATE "user"
                    SET fieldsrules = (CASE WHEN fieldsrules = \'\' THEN \'|'.$idfield.'|\' ELSE CONCAT(fieldsrules ,\''.$idfield.'|\') END)
                    WHERE  parenttree LIKE \'%|'.$id[1].'|%\'
                    '
                    )->execute())
                {
                    return [
                        'success' => true,
                    ];
                } else {
                    $this->fieldsModel->addError('error', 'Ошибка. Не удалось добавить пользователю геозону');
                    return $this->fieldsModel;
                }

            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }



    public function actionEdit(){
        if (Yii::$app->user->can('fieldsedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if($this->fieldsModel->updateColum($id, $dataDriver)){
                return [
                    'success' => true,
                ];
            } else {
                $this->fieldsModel->addError('error', 'Ошибка. Не удалось изменить геозону');
                return $this->fieldsModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionDelete(){
        if (Yii::$app->user->can('fieldsdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataObject = json_decode($postData, true);
            $id= (int) $dataObject['id'];
            if ($this->fieldsModel->deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            } else {
                $this->fieldsModel->addError('error', 'Ошибка. Не удалось изменить геозону');
                return $this->fieldsModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }

}

