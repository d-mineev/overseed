<?php
namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Treilers;
use backend\models\Treilersgroup;
use backend\models\TreilerGroup;


/**
 * Dispatcher controller
 */
class TreilersgroupController extends MainController
{
    public $modelClass = 'backend\models\Treilersgroup';

    /** @var Treilers */
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
         //   $array = Treilersgroup::find()->asArray()->orderBy('name ASC')->all();
            $Treilersgroups = Treilersgroup::find()->with([
                'treilers' => function ($query){
                    $query->select(['id','name']);
                }
            ])->all();
        } else {
            $Treilersgroups = Treilersgroup::find()->with([
                'treilers' => function ($query){
                    $query->select(['id','name']);
                }
            ])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
        }
        if(count($Treilersgroups)>0){
            foreach ($Treilersgroups as $Treilersgroup) {
                $treilers_in_groop[$Treilersgroup->id] = $Treilersgroup->treilers;
            }
        } else {
            $treilers_in_groop = [];
        }

        return [
           // 'treilersgroup' => $array,
            'treilersgroup' => $Treilersgroups,
            'treilersgrouplink' => ArrayHelper::map($Treilersgroups, 'id', 'name'),
            "treilers_in_groop" => $treilers_in_groop
        ];
    }

    public function actionCreate(){
        $user = Yii::$app->user;
        if ($user->can('treilersadd')OR $user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);

            $newdriver = new Treilersgroup();
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->author = $user->identity->parenttree . $user->identity->id . "|";

          if($newdriver->save()) {
              if(count($dataDriver['treilersingroup'])>0){
                  $lastID = Treilersgroup::find()->where(['name' => $dataDriver['data']['name']])->orderBy(['id' => SORT_DESC])->one();
                  $Model = new TreilerGroup();
                  $modelatr = $Model->attributes();
                  TreilerGroup::deleteAll([$modelatr[0] => (int) $lastID->id]);
                  foreach ($dataDriver['treilersingroup'] as $treiler){
                      $rows[] = [$lastID->id,$treiler['id']];
                  }
                  if (Yii::$app->db->createCommand()->batchInsert(TreilerGroup::tableName(), $Model->attributes(), $rows)->execute()){
                      return [
                         'success' => true,
                      ];
                  } else {
                          $this->Model->addError('error', 'Ошибка. Не удалось добавить прицепныху в группу');
                          return $this->Model;
                  }
              } else {
                  return [
                      'success' => true,
                  ];
              }

          } else {
                $this->Model->addError('error', 'Ошибка. Не удалось создать группу прицепных');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('treilersedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if ( Treilersgroup::updateAll(
                [
                    'name' => $dataDriver['data']['name'],
                    'description' => (empty($dataDriver['data']['description']) ? '' : $dataDriver['data']['description']),
                    
                ], ['id' => $id])
            ){
                $Model = new TreilerGroup();
                $modelatr = $Model->attributes();
                TreilerGroup::deleteAll([$modelatr[0] => $id]);
                if(count($dataDriver['treilersingroup'])>0){
                    foreach ($dataDriver['treilersingroup'] as $treiler){
                        $rows[] = [$id,$treiler['id']];
                    }
                    if (Yii::$app->db->createCommand()->batchInsert(TreilerGroup::tableName(), $Model->attributes(), $rows)->execute()){
                        return [
                            'success' => true,
                        ];
                    }
                    else {
                        $this->Model->addError('error', 'Ошибка. Не удалось добавить прицепные в группу');
                        return $this->Model;
                    }
                } else {
                    return [
                        'success' => true,
                    ];
                }

            } else {
                $this->Model->addError('error', 'Ошибка. Не удалось изменить группу прицепных');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('treilersdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( Treilersgroup::deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось удалить группу прицепных');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));