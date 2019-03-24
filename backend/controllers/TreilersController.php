<?php
namespace backend\controllers;

use backend\models\Treilers;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\db\Query;
use backend\models\Technologytype;
use backend\models\Technologyoperation;
use backend\models\TreilerTechnoper;


/**
 * Treilers controller
 */
class TreilersController extends MainController
{
    public $modelClass = 'backend\models\Treilers';

    /** @var Fields */
    private $treilersModel;

    public function init ()
    {
        parent::init();

        $this->treilersModel = new $this->modelClass();
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
        if(($user->can('superadmin')) or ($user->can('treilersview'))){
            $Objtest = $this->treilersModel->getAll();
            return [
                'treilers' => $Objtest,
                'treilerlink' => ArrayHelper::map($Objtest, 'id', 'name'),
                'treilerwidth' => ArrayHelper::map($Objtest, 'id', 'width'),
            ];
        }else {
            throw new ForbiddenHttpException('Access denied');
        }
        

    }

    public function actionCreate(){
        if (Yii::$app->user->can('treilersadd')OR Yii::$app->user->can('superadmin')) {
            $postData = file_get_contents("php://input");
          //  file_put_contents('/var/www/overseer_prod/test0006.txt', print_r($postData, true ));
            $dataTreiler = json_decode($postData, true);
            $newtreiler = $this->treilersModel;
            $newtreiler->name = $dataTreiler['data']['name'];
            $newtreiler->width = $dataTreiler['data']['width'];
            $newtreiler->techntype = (empty($dataTreiler['data']['techntype']) ? '' : $dataTreiler['data']['techntype']);
        //    $newtreiler->technoper = (empty($dataTreiler['data']['technoper']) ? '' : $dataTreiler['data']['technoper']);
            $newtreiler->externalid = (empty($dataTreiler['data']['externalid']) ? '' : $dataTreiler['data']['externalid']);
            $newtreiler->save();

            if ($newtreiler->save()) {
                $idobject = $this->treilersModel->findlastid($dataTreiler['data']['name']);

                $Model = new TreilerTechnoper();
                $modelatr = $Model->attributes();
                TreilerTechnoper::deleteAll([$modelatr[0] => $idobject]);
                $arr_technoper = json_decode($dataTreiler['data']['technoper'], true);
                if(count($arr_technoper)>0) {
                    // file_put_contents('/var/www/over.loc/answer.txt', print_r( , true));
                    foreach ($arr_technoper as $technoper) {

                        $rows[] = [$idobject, $technoper];
                    }
                    if (Yii::$app->db->createCommand()->batchInsert(TreilerTechnoper::tableName(), $Model->attributes(), $rows)->execute()) {
                        return [
                            'success' => true,
                        ];
                    }else {
                        $this->treilersModel->addError('error', 'Ошибка. Не удалось добавить техн опер прицепное');
                        return $this->treilersModel;
                    }
                }

            } else {
                $this->treilersModel->addError('error', 'Ошибка. Не удалось создать прицеп');
                return $this->treilersModel;
            }




            $user = Yii::$app->user;
            if (!empty($user->identity->parenttree)) {
                $id = explode("|", $user->identity->parenttree);
                $id = array_reverse($id);
            }
            $query = new Query();
            if (($id[1] == 1)or ($id[1] == 0)){
                if ($query->createCommand()->setSql('
                UPDATE "user"
                SET treilersrules = (CASE WHEN treilersrules = \'\' THEN \'|' . $idobject . '|\' ELSE CONCAT(treilersrules ,\'' . $idobject . '|\') END)
                WHERE  id='.$user->identity->id.'
        '
                )->execute())
                {
                    return [
                        'success' => true,
                    ];
                } else {
                    $this->treilersModel->addError('error', 'Ошибка. Не удалось добавить текущему пользователю прицепное');
                    return $this->treilersModel;
                }
            }else {
                if ($query->createCommand()->setSql('
                    UPDATE "user"
                    SET treilersrules = (CASE WHEN treilersrules = \'\' THEN \'|' . $idobject . '|\' ELSE CONCAT(treilersrules ,\'' . $idobject . '|\') END)
                    WHERE  parenttree LIKE \'%|' . $id[1] . '|%\'
            '
                )->execute()
                ) {
                    return [
                        'success' => true,
                    ];
                } else {
                    $this->treilersModel->addError('error', 'Ошибка. Не удалось добавить текущему пользователю прицепное');
                    return $this->treilersModel;
                }
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }



    public function actionEdit(){
        if (Yii::$app->user->can('treilersedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
           // file_put_contents('/var/www/overseer_prod/test0005.txt', print_r($postData, true ));
            $dataTreiler = json_decode($postData, true);

            $id= (int) $dataTreiler['data']['id'];
            $this->treilersModel->updateColum($id, $dataTreiler);
            $Model = new TreilerTechnoper();
            $modelatr = $Model->attributes();
            TreilerTechnoper::deleteAll([$modelatr[0] => $id]);
            $arr_technoper = $dataTreiler['data']['technoper'];//json_decode($dataTreiler['data']['technoper'], true);
            if(count($arr_technoper)>0) {
               // file_put_contents('/var/www/over.loc/answer.txt', print_r( , true));
                foreach ($arr_technoper as $technoper) {

                    $rows[] = [$id, $technoper];
                }
                if (Yii::$app->db->createCommand()->batchInsert(TreilerTechnoper::tableName(), $Model->attributes(), $rows)->execute()) {
                    return [
                        'success' => true,
                    ];
                }else {
                    $this->treilersModel->addError('error', 'Ошибка. Не удалось добавить техн опер прицепное');
                    return $this->treilersModel;
                }
            }


        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }
    public function actionDefault(){
        $treilertype = Technologytype::find()->indexBy('id')->where(['fortype' => 2])->asArray()->all();
        return [
            'datatechnologytypes' => $treilertype
        ];
    }
    public function actionDelete(){
        if (Yii::$app->user->can('treilersdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataTreiler = json_decode($postData, true);
            $id= (int) $dataTreiler['id'];
            if($this->treilersModel->deleteAll(['id' => (int) $id])){
                $Model = new TreilerTechnoper();
                $modelatr = $Model->attributes();
                TreilerTechnoper::deleteAll([$modelatr[0] => $id]);
                return [
                    'success' => true,
                ];
            }
            else {
                $this->treilersModel->addError('error', 'Ошибка. Не удалось удалить прицепное');
                return $this->treilersModel;
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }

    public function actionView ($id = null)
    {
        $treilertype = Technologytype::find()->indexBy('id')->where(['fortype' => 2])->asArray()->all();

        $treileroper = Technologyoperation::find()->orderBy('name ASC')->asArray()->all();
        if($id>0){


        $Treiler = Treilers::find()->with([
            'technoper' => function ($query){
                $query->select(['id','name']);
            }
        ])->where(['id' => $id])->asArray()->one();
        $Treiler['technoper'] = ArrayHelper::getColumn($Treiler['technoper'], 'id');
        }else{
            $Treiler =[];
        }
        return [
            'treiler' => $Treiler,
          //  'treiler' => $this->treilersModel->getOne($id),
            'datatechnologytypes' => $treilertype,
            'techntypelink' => ArrayHelper::map($treilertype, 'id', 'name'),
            //'technologyoperations' => $treileroper,
            //'technologyoperationlink' => ArrayHelper::map($treileroper, 'id', 'name')




        ];
    }
}
