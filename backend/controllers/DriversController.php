<?php
namespace backend\controllers;

use backend\models\Drivers;
use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Objects;
use backend\models\FileForm;
use yii\web\UploadedFile;
use yii\web\ForbiddenHttpException;
use yii\db\Query;


/**
 * Driver controller
 */
class DriversController extends MainController
{
    public $modelClass = 'backend\models\Drivers';

    /** @var Fields */
    private $driversModel;

    public function init ()
    {
        parent::init();

        $this->driversModel = new $this->modelClass();
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
        if(($user->can('superadmin')) or ($user->can('driversview'))){
            $Objtest = $this->driversModel->getAll();
            return [
                'drivers' => $Objtest,
                'driverlink' => ArrayHelper::map($Objtest, 'id', 'name'),
            ];
        }else {
            throw new ForbiddenHttpException('Access denied');
        }
        
    }

    public function actionView ($id = null)
    {
        $Object = Drivers::find()->where(['id' => $id])->asArray()->one();

        return [
            'driver' => $Object,

        ];
    }

    public function actionCreate(){

        if (Yii::$app->user->can('driversadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $newdriver = $this->driversModel;
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->mobile = (empty($dataDriver['data']['mobile'])?'':$dataDriver['data']['mobile']);
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->fotosrc = (empty($dataDriver['data']['fotosrc'])?'':$dataDriver['data']['fotosrc']);
            $newdriver->object =  (empty($dataDriver['data']['object'])?0:$dataDriver['data']['object']);
            $newdriver->externalid =  (empty($dataDriver['data']['externalid'])?'':$dataDriver['data']['externalid']);
            if  ($newdriver->save()){
                $idobject = $this->driversModel->findlastid($dataDriver['data']['name']);
            }else {
                $this->driversModel->addError('error', 'Ошибка. Не удалось создать водителя');
                return $this->driversModel;
            }
/*
            $user = Yii::$app->user;
            if (!empty($user->identity->driversrules))  {
                $checkboxobject = json_decode($user->identity->driversrules,true);
            } else {
                $checkboxobject = [];
            }
            $checkboxobject[$idobject] = true;
            $checkboxobject = json_encode($checkboxobject);
            if (Yii::$app->db->createCommand()->update('user',
                ['driversrules' => $checkboxobject],
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
                            SET driversrules = (CASE WHEN driversrules = \'\' THEN \'|'.$idobject.'|\' ELSE CONCAT(driversrules ,\''.$idobject.'|\') END)
                            WHERE  id='.$user->identity->id.'
                            '
                )->execute())
                {
                    return [
                        'success' => true,
                    ];
                } else {
                    $this->driversModel->addError('error', 'Ошибка. Не удалось добавить текущему пользователю водителя');
                    return $this->driversModel;
                }
            } else {
                if($query->createCommand()->setSql('
                    UPDATE "user"
                    SET driversrules = (CASE WHEN driversrules = \'\' THEN \'|'.$idobject.'|\' ELSE CONCAT(driversrules ,\''.$idobject.'|\') END)
                    WHERE  parenttree LIKE \'%|'.$id[1].'|%\'
                    '
                    )->execute())
                {
                    return [
                        'success' => true,
                    ];
                } else {
                    $this->driversModel->addError('error', 'Ошибка. Не удалось добавить текущему пользователю водителя');
                    return $this->driversModel;
                }

}
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){


        if (Yii::$app->user->can('driversedit')OR Yii::$app->user->can('superadmin')){
		  
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);		
            $id= (int) $dataDriver['data']['id'];
            $this->driversModel->updateColum($id, $dataDriver);
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('driversdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            $lastfoto =   $this->driversModel->findlastfoto($id);
            if(!empty($lastfoto) && file_exists(Yii::$app->params['files']['upload'] . $lastfoto))
            {
                unlink(Yii::$app->params['files']['upload'] . $lastfoto);
            }

            $this->driversModel->deleteAll(['id' => (int) $id]);
        } else {
            throw new ForbiddenHttpException('Access denied');
        }
        
    }


    public function actionUpload() {
        ini_set('memory_limit', '-1');

        $file       = UploadedFile::getInstance(new FileForm(), 'file');
        //$nameImg = time()."_".$file->name;
        $nameImg = Yii::$app->getRequest()->getBodyParam('objectid');

        $file->saveAs(Yii::$app->params['files']['upload'] . $nameImg);
        return [
            'success' => true,
        ];
    }
	
    	




}
