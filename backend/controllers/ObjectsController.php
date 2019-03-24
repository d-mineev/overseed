<?php
namespace backend\controllers;

use backend\models\Drivers;
use backend\models\Objects;
use backend\models\Treilers;
use backend\models\ObjectTreiler;
use backend\models\ObjectDriver;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\db\Query;
use backend\models\Technologytype;


/**
 * Object controller
 */
class ObjectsController extends MainController
{
    public $modelClass = 'backend\models\Objects';

    /** @var Fields */
    private $objectsModel;

    public function init ()
    {
        parent::init();

        $this->objectsModel = new $this->modelClass();
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
        if(($user->can('superadmin')) or ($user->can('objectsview'))){
            //$Objtest = $this->objectsModel->getAll();
            $Objtest = Objects::find()
                ->with([
                    'treiler' => function ($query){
                        $query->where(['stack'=> true])
                            ->andWhere(['lastinfo' => true])
                            ->one();
                    },
                    'drivers'=> function ($query){
                        $query->select(['id','name'])->all();
                    }])->asArray()->all();
           // $Objtest['treiler'];
           // Treilers::find()->select(['id','name'])->where(['stack' => false])->all()
            return [
                'objects' => $Objtest,
                'objectlink' => ArrayHelper::map($Objtest, 'id', 'name'),
                'objecttype' => ArrayHelper::map($Objtest, 'id', 'type'),
                'objecttreiler' => ArrayHelper::map($Objtest, 'id', 'treilerid'),
                'objectdevice' => ArrayHelper::map($Objtest, 'externalid', 'id'),
                'objectextern' => ArrayHelper::map($Objtest, 'id','externalid'),
                'objectcolor' => ArrayHelper::map($Objtest, 'id', 'color'),
                'objectdriver' => ArrayHelper::map($Objtest, 'id', 'drivers')
            ];
        }else {
            throw new ForbiddenHttpException('Access denied');
        }

/*
        if ($user->can('superadmin')){
            if (!empty($user->identity->treilersrules)) {
                $treilers = explode("|", $user->identity->treilersrules);
                $ob = [];
                foreach ($treilers as $value) {
                    if ($value) {
                        $ob[] = (int)$value;
                    }
                }
            }
            $Treilers = Treilers::find()->indexBy('id')->where(['id' => $ob])->all();
        } else {
            $Treilers = Treilers::find()->indexBy('id')->all();
        }

            $subQuery = ObjectTreiler::find()
                ->select(['id_object', 'id_treiler', 'stack'])
                ->where(['lastinfo' => true])
                ->andWhere(['id_treiler' => $ob])
                ->andWhere(['stack' => true])
                ->all();


         //   ->having(['stack' => true]);
        //$subQuery = ObjectTreiler::find()->select('id_treiler')->where(['stack' => true]);


        $TreilersSelect = Treilers::find()
                            ->indexBy('id')
                            ->where(['id' => ArrayHelper::getColumn($Treilers, 'id')])
                            ->andWhere(['not in', 'id', $subQuery])
                            ->asArray()
                            ->all();

*/


/*
        $array = $this->objectsModel->getAll();

        $result = ArrayHelper::map($array, 'id', 'name');
        $result2 = ArrayHelper::map($array, 'id', 'type');
        $result3 = ArrayHelper::map($array, 'id', 'treilerid');
        $result4 = ArrayHelper::map($array, 'externalid', 'id');
        $result5 = ArrayHelper::map($array, 'id', 'color');
        $result6 = ArrayHelper::map($Objecttype, 'id', 'name');



        return [
            'objects' => $array,
            'objectlink' => $result,
            'objecttype' => $result2,
            'objecttreiler' => $result3,
            'objectdevice' => $result4,
            'objectcolor' => $result5,
            'types' => $Objecttype,
            'typelink' => $result6,
            'objtest' => $Objtest,
            'objtestlink' => ArrayHelper::map($Objtest, 'id', 'color')
        ];
*/
    }

    public function actionView ($id = null)
    {
        $user = Yii::$app->user;
        $Object = Objects::find()->where(['id' => $id])
        ->with([
        'treiler' => function ($query){
            $query->where(['stack'=> true])
            ->andWhere(['lastinfo' => true])
            ->one();
        },
        'drivers'=> function ($query){
            $query->select(['id','name'])->all();
        }])->asArray()->one();
        //проверка есть ли прицеп
        if (!empty($Object['treiler'])){
            $Object['treilerid'] = $Object['treiler'][0]['id_treiler'];
        } else {
            $Object['treilerid'] = 0;
        }
        unset($Object['treiler']);
//свободные прицеппы
        if (!$user->can('superadmin')){
            if (!empty($user->identity->treilersrules)) {
                $treilers = explode("|", $user->identity->treilersrules);
                $ob = [];
                foreach ($treilers as $value) {
                    if ($value) {
                        $ob[] = (int)$value;
                    }
                }
                $freeTreilers = Treilers::find()->select(['id','name'])->where(['stack' => false])->andWhere(['id'=>$ob])->asArray()->all();

            } else {

            }
            
        } else {

            $freeTreilers = Treilers::find()->select(['id','name'])->where(['stack' => false])->all();
        }

        $linkTreilers = Treilers::find()->select(['id','name'])->all();
        $Objecttype = Technologytype::find()->indexBy('id')->where(['fortype' => 1])->asArray()->all();
        $linkTreilers[] = array('id'=>0, 'name' => 'без прицепного');
        $freeTreilers[] = array('id'=>0, 'name' => 'без прицепного');
        $allDrivers = new Drivers();
        return [
            'object' => $Object,
            'types' =>  $Objecttype,
            'typelink' => ArrayHelper::map($Objecttype, 'id', 'name'),
            'selecttreilers' => $freeTreilers,
            'treilerlink' => ArrayHelper::map($linkTreilers, 'id', 'name'),
            'drivers' => $allDrivers->getAll()
            
        ];
    }


    public function actionCreate(){
        if (Yii::$app->user->can('objectsadd')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataObject = json_decode($postData, true);
            file_put_contents('/var/www/over.loc/answer.txt', print_r($dataObject , true ));
            $newobject = $this->objectsModel;
            $newobject->name =  $dataObject['data']['name'];
            $newobject->type =  (int)$dataObject['data']['type'];
            $newobject->odometer =  $dataObject['data']['odometer'];
            $newobject->fuel =  $dataObject['data']['fuel'];

            $newobject->externalid =  (empty($dataObject['data']['externalid'])?'':$dataObject['data']['externalid']);
            $newobject->color =  (empty($dataObject['data']['color'])?'':$dataObject['data']['color']);

            if  ($newobject->save()){
                $idobject = $this->objectsModel->findlastid($dataObject['data']['name']);
            }else {
                $this->objectsModel->addError('error', 'Ошибка. Не удалось создать объект');
                return $this->objectsModel;
            }

            if (!empty($dataObject['data']['treilerid'])){
                $this->addStacktreiler($idobject, $dataObject['data']['treilerid']);
               
            }
            if (!empty($dataObject['data']['drivers'])){
                $this->addDriver($idobject, $dataObject['data']['drivers']);
            }
            $user = Yii::$app->user;
            if (!empty($user->identity->parenttree)) {
                $id = explode("|", $user->identity->parenttree);
                $id = array_reverse($id);
            }
            $query  = new Query();

            if (($id[1] == 1)or ($id[1] == 0)){
                if($query->createCommand()->setSql('
                UPDATE "user"
                SET objectsrules = (CASE WHEN objectsrules = \'\' THEN \'|'.$idobject.'|\' ELSE CONCAT(objectsrules ,\''.$idobject.'|\') END)
                WHERE  id='.$user->identity->id.'
                '
                )->execute())
                {
                    return [
                        'success' => true,
                    ];
                } else {
                    $this->objectsModel->addError('error', 'Ошибка. Не удалось создать объект');
                    return $this->objectsModel;
                }
            } else {
                if($query->createCommand()->setSql('
                UPDATE "user"
                SET objectsrules = (CASE WHEN objectsrules = \'\' THEN \'|'.$idobject.'|\' ELSE CONCAT(objectsrules ,\''.$idobject.'|\') END)
                WHERE  parenttree LIKE \'%|'.$id[1].'|%\'
                '
                )->execute())
                {
                    return [
                        'success' => true,
                    ];
                } else {
                    $this->objectsModel->addError('error', 'Ошибка. Не удалось создать объект');
                    return $this->objectsModel;
                }
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('objectsedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataObject = json_decode($postData, true);
            $id= (int) $dataObject['data']['id'];
            $Object = Objects::find()->with([
                'treiler' => function ($query){
                    $query->where(['stack'=> true])->andWhere(['lastinfo' => true])->one();
                }])->where(['id'=> $id])->one();
            $Object->name =  $dataObject['data']['name'];
            $Object->type =  (int)$dataObject['data']['type'];
            $Object->odometer =  $dataObject['data']['odometer'];
            $Object->fuel =  $dataObject['data']['fuel'];
            $Object->externalid =  (empty($dataObject['data']['externalid'])?'':$dataObject['data']['externalid']);
            $Object->color =  (empty($dataObject['data']['color'])?'':$dataObject['data']['color']);
            if  ($Object->save()){
            }else {
                $this->objectsModel->addError('error', 'Ошибка. Не удалось изменить объект');
                return $this->objectsModel;
            }
            //был ли прицеп до редактирования
            if (!empty($Object->treiler)){
                $lastTreiler = $Object['treiler'][0]['id_treiler'];
            } else {
                $lastTreiler = 0;
            }
            //проверка совпадает ли прицеппы
            if ($dataObject['data']['treilerid'] == $lastTreiler){

            } else if (($dataObject['data']['treilerid']>0) and ($lastTreiler==0)){
                $this->addStacktreiler($id, $dataObject['data']['treilerid']);
            } else if (($dataObject['data']['treilerid']==0) and ($lastTreiler>0)){
                $this->dismissStacktreiler($id, $lastTreiler);
            } else if (($dataObject['data']['treilerid']>0) and ($lastTreiler>0)){
                $this->dismissStacktreiler($id, $lastTreiler);
                $this->addStacktreiler($id, $dataObject['data']['treilerid']);

            }

            $this->dellDriver($id);
            if (!empty($dataObject['data']['drivers'])){
                $this->addDriver($id, $dataObject['data']['drivers']);
            }

           // unset($Object['treiler']);

           // file_put_contents('/var/www/over.loc/answer.txt', print_r($alert, true ));

        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }

    public function actionDelete(){
        if (Yii::$app->user->can('objectsdelite') OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataObject = json_decode($postData, true);
            $id= (int) $dataObject['id'];
            $this->objectsModel->deleteAll(['id' => (int) $id]);
            $this->dellDriver($id);
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionExternal()
    {
        $id = Yii::$app->getRequest()->getBodyParam('id');
        return [
            'object' => $this->objectsModel->getExternal($id),
        ];
    }

    public function actionOneid()
    {
        $id = Yii::$app->getRequest()->getBodyParam('id');
        return [
            'object' => $this->objectsModel->getOne($id),
        ];
    }
    private function addStacktreiler($id_obj, $id_treiler){
        ObjectTreiler::updateAll(['lastinfo' => false], ['id_treiler' => $id_treiler]);
        $ObjectTreiler = new ObjectTreiler();
        $ObjectTreiler->id_object = $id_obj;
        $ObjectTreiler->id_treiler = $id_treiler;
        $ObjectTreiler->stack = true;
        //"2016-05-14 11:25:01"
        $ObjectTreiler->receiving_date = date ("Y-m-d G:i:s");
        $ObjectTreiler->lastinfo = true;


        $Treiler = Treilers::find()->where(['id'=> $id_treiler])->one();
        $Treiler->stack = true;

        if ($ObjectTreiler->save() and $Treiler->save()){
            return true;
        }
    }
    private function dismissStacktreiler($id_obj, $id_treiler){
        ObjectTreiler::updateAll(['lastinfo' => false], ['id_treiler' => $id_treiler]);

        $fields = Objects::find()->where(['id' => $id_obj])
            ->with([
                'devicesdata' => function ($query){
                    $query->orderBy('receiving_date DESC')
                        ->select(['device_id','lat1','lon1','receiving_date'])->limit(1)->one();
                }
            ])->asArray()->one();


        
        $ObjectTreiler = new ObjectTreiler();
        $ObjectTreiler->id_object = $id_obj;
        $ObjectTreiler->id_treiler = $id_treiler;
        $ObjectTreiler->stack = false;
        //"2016-05-14 11:25:01"
        $ObjectTreiler->receiving_date = date ("Y-m-d G:i:s");
        $ObjectTreiler->lastinfo = true;
        if (!empty($fields['devicesdata']) and $points = $fields['devicesdata']){
            $ObjectTreiler->lat1 = $points[0]['lat1'];
            $ObjectTreiler->lon1 = $points[0]['lon1'];
        }

        $Treiler = Treilers::find()->where(['id'=> $id_treiler])->one();
        $Treiler->stack = false;

        if ($ObjectTreiler->save() and $Treiler->save()){
            return true;
        }
    }
    private function addDriver($id_object, $id_drivers){
        

            $Model = new ObjectDriver();
            foreach ($id_drivers as $field){
                $rows[] = [$id_object,$field['id']];
            }
            if (Yii::$app->db->createCommand()->batchInsert(ObjectDriver::tableName(), $Model->attributes(), $rows)->execute()){
                return true;
            } else {
                return false;
            }


    }
    private function dellDriver($id_object){
            $Model = new ObjectDriver();
            $modelatr = $Model->attributes();
            ObjectDriver::deleteAll([$modelatr[0] => (int)$id_object]);
    }
}


