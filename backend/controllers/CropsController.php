<?php
namespace backend\controllers;


use Yii;
use yii\helpers\ArrayHelper;
use backend\models\FileForm;
use yii\web\UploadedFile;
use backend\models\Crops;

/**
 * Dispatcher controller
 */
class CropsController extends MainController
{
    public $modelClass = 'backend\models\Crops';

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
            $array = Crops::find()->orderBy('name ASC')->asArray()->all();
        } else {
            $array = Crops::find()->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->orderBy('name ASC')->asArray()->all();
        }

        return [
            'crops' => $array,
            'croplink' => ArrayHelper::map($array, 'id', 'name')
        ];
    }

    public function actionView ($id = null)
    {

        
        $Object = Crops::find()->where(['id' => $id])->one();

        $image = $Object->getImage();

        if($image) {
            $Object = ArrayHelper::toArray ($Object);
            $Object['image'] = '/backend/web/upload/store/'.$image->filePath;
      //
        }
        return [
            'crop' => $Object,

        ];
    }
    
    public function actionUpload() {
        ini_set('memory_limit', '-1');

        $file       = UploadedFile::getInstance(new FileForm(), 'file');
        //$nameImg = time()."_".$file->name;
        $nameImg = Yii::$app->getRequest()->getBodyParam('objectid');


        if ($file->saveAs(Yii::$app->params['files']['upload'] . $nameImg)){
            return [
                'success' => true,
            ];
        }
        else {
            $this->technologytypesModel->addError('error', 'Ошибка. Не удалось загрузить фото');
            return $this->technologytypesModel;
        }
    }

    public function actionCreate(){

        if (Yii::$app->user->can('cropsadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = $this->technologytypesModel;
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->color = (empty($dataDriver['data']['color'])?'':$dataDriver['data']['color']);

            $newdriver->author = Yii::$app->user->identity->parenttree . Yii::$app->user->identity->id . "|";

            if ($newdriver->save()){
                if(!empty($dataDriver['data']['fotosrc'])){
                     if (file_exists(Yii::$app->params['files']['upload'] .$dataDriver['data']['fotosrc'])){
                        if($newdriver->attachImage(Yii::$app->params['files']['upload'].$dataDriver['data']['fotosrc'], true)){
                            unlink(Yii::$app->params['files']['upload'] .$dataDriver['data']['fotosrc']);
                        }
                        return [
                            'success' => true,
                        ];
                    }

                }else {
                    return [
                        'success' => true,
                    ];
                }

            }
            else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось создать культуру');
                return $this->technologytypesModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('cropsedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];

            $newdriver = Crops::findOne($id);
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->color = (empty($dataDriver['data']['color'])?'':$dataDriver['data']['color']);
            if ($newdriver->save()) {
                $image = $newdriver->getImage();
                if(($image) and ($dataDriver['data']['delloldfoto']) and ($image->filePath != 'no.png')){
                    $newdriver->removeImage($image);
                }
                if ((!empty($dataDriver['data']['fotosrc'])) AND ($dataDriver['data']['fotosrc']!=='/backend/web/upload/store/no.png')) {
                    if (file_exists(Yii::$app->params['files']['upload'] . $dataDriver['data']['fotosrc'])) {
                        if ($newdriver->attachImage(Yii::$app->params['files']['upload'] . $dataDriver['data']['fotosrc'], true)) {
                            unlink(Yii::$app->params['files']['upload'] . $dataDriver['data']['fotosrc']);
                        }
                        return [
                            'success' => true,
                        ];
                    }

                }else {
                    return [
                        'success' => true,
                    ];
                }
            } else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось изменить культуру');
                return $this->technologytypesModel;
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('cropsdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            $newdriver = Crops::findOne($id);
            $image = $newdriver->getImage();
            if($image){
                $newdriver->removeImage($image);
            }
            if($newdriver->delete())
            {
                return [
                    'success' => true,
                ];
            }
            /*
            $lastfoto =   $this->technologytypesModel->findlastfoto($id);
            if(!empty($lastfoto) && file_exists(Yii::$app->params['files']['upload'] . $lastfoto))
            {
                unlink(Yii::$app->params['files']['upload'] . $lastfoto);
            }
            if( $this->technologytypesModel->deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }*/
            else {
                $this->technologytypesModel->addError('error', 'Ошибка. Не удалось удалить культуру');
                return $this->technologytypesModel;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));