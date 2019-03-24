<?php
namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Fields;
use backend\models\Fieldsgroup;
use backend\models\FieldGroup;


/**
 * Dispatcher controller
 */
class FieldsgroupController extends MainController
{
    public $modelClass = 'backend\models\Fieldsgroup';

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
         //   $array = Fieldsgroup::find()->asArray()->orderBy('name ASC')->all();
            $Fieldsgroups = Fieldsgroup::find()->with([
                'fields' => function ($query){
                    $query->select(['id','name','perimeter','type']);
                }
            ])->all();
        } else {
            $Fieldsgroups = Fieldsgroup::find()->with([
                'fields' => function ($query){
                    $query->select(['id','name','perimeter','type']);
                }
            ])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
        }
        if(count($Fieldsgroups)>0){
            foreach ($Fieldsgroups as $Fieldsgroup) {
                $fields_in_groop[$Fieldsgroup->id] = $Fieldsgroup->fields;
            }
        } else {
            $fields_in_groop = [];
        }

        return [
           // 'fieldsgroup' => $array,
            'fieldsgroup' => $Fieldsgroups,
            'fieldsgrouplink' => ArrayHelper::map($Fieldsgroups, 'id', 'name'),
            "fields_in_groop" => $fields_in_groop
        ];
    }

    public function actionCreate(){
        $user = Yii::$app->user;
        if ($user->can('fieldsadd')OR $user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);

            $newdriver = new Fieldsgroup();
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->author = $user->identity->parenttree . $user->identity->id . "|";

          if($newdriver->save()) {
              if(count($dataDriver['fieldsingroup'])>0){
                  $lastID = Fieldsgroup::find()->where(['name' => $dataDriver['data']['name']])->orderBy(['id' => SORT_DESC])->one();
                  $Model = new FieldGroup();
                  $modelatr = $Model->attributes();
                  FieldGroup::deleteAll([$modelatr[0] => (int) $lastID->id]);
                  foreach ($dataDriver['fieldsingroup'] as $field){
                      $rows[] = [$lastID->id,$field['id']];
                  }
                  if (Yii::$app->db->createCommand()->batchInsert(FieldGroup::tableName(), $Model->attributes(), $rows)->execute()){
                      return [
                         'success' => true,
                      ];
                  } else {
                          $this->Model->addError('error', 'Ошибка. Не удалось добавить геозону в группу');
                          return $this->Model;
                  }
              } else {
                  return [
                      'success' => true,
                  ];
              }

          } else {
                $this->Model->addError('error', 'Ошибка. Не удалось создать группу геозон');
                return $this->Model;
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
            if ( Fieldsgroup::updateAll(
                [
                    'name' => $dataDriver['data']['name'],
                    'description' => (empty($dataDriver['data']['description']) ? '' : $dataDriver['data']['description']),
                    
                ], ['id' => $id])
            ){
                $Model = new FieldGroup();
                $modelatr = $Model->attributes();
                FieldGroup::deleteAll([$modelatr[0] => $id]);
                if(count($dataDriver['fieldsingroup'])>0){
                    foreach ($dataDriver['fieldsingroup'] as $field){
                        $rows[] = [$id,$field['id']];
                    }
                    if (Yii::$app->db->createCommand()->batchInsert(FieldGroup::tableName(), $Model->attributes(), $rows)->execute()){
                        return [
                            'success' => true,
                        ];
                    }
                    else {
                        $this->Model->addError('error', 'Ошибка. Не удалось добавить поля в группу');
                        return $this->Model;
                    }
                } else {
                    return [
                        'success' => true,
                    ];
                }

            } else {
                $this->Model->addError('error', 'Ошибка. Не удалось изменить группу геозон');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('fieldsdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( Fieldsgroup::deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось удалить группу геозон');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));