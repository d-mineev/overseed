<?php
namespace backend\controllers;

use backend\models\DevicesData;
use backend\models\FileForm;
use backend\models\ReportForm;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use yii\web\ForbiddenHttpException;

use backend\models\Objects;
use backend\models\Drivers;
use backend\models\Fields;
use backend\models\Treilers;
use backend\models\Crops;
use backend\models\Technologyoperation;
use backend\models\Cropsgroup;
use backend\models\Fieldsgroup;
use backend\models\Objectsgroup;
use backend\models\Driversgroup;
use backend\models\Treilersgroup;

/**
 * Area controller
 */
class ReportController extends MainController
{
    public $modelClass = 'backend\models\DevicesData';

    /**
     * @inheritdoc
     */
    public function actions ()
    {
        $actions = parent::actions();

        unset($actions['index']);
        unset($actions['view']);

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
                'only' => ['upload', 'generate', 'view'],
            ]
        );
        $behaviors['access']             = ArrayHelper::merge(
            $behaviors['access'],
            [
                'only' => ['upload', 'generate', 'view'],
            ]
        );
        $behaviors['access']['rules'][0] = ArrayHelper::merge(
            $behaviors['access']['rules'][0],
            [
                'actions' => ['upload', 'generate', 'view'],
            ]
        );

        return $behaviors;
    }

    /**
     * @return DevicesData
     */
    public function actionIndex ()
    {
        return new DevicesData();
    }

    protected $typesReports = [
        ['id' => 1,'name' => 'Геозоны'],
        ['id' => 2,'name' => 'Объекты'],
        ['id' => 3,'name' => 'Водители'],
        ['id' =>  4,'name' => 'Прицепные'],
        ['id' =>  5 ,'name' => 'Культуры'],
        ['id' =>  6 ,'name' => 'Техн операции'],

    ];
    protected $typesReportGroups = [
        ['id' => 0,'name' => 'по одному'],
        ['id' => 1,'name' => 'по группам'],
    ];

    public function actionView ()
    {
        if (Yii::$app->user->can('reportsview') OR Yii::$app->user->can('superadmin')){

            $drivers = new Drivers();
            $objects = new Objects();
            $fields = new Fields();
            $treilers = new Treilers();
            $crops = new Crops();
            $technOper = new Technologyoperation();
            if (Yii::$app->user->can('superadmin')) {
                $driversgroups = Driversgroup::find()->select(['id','name'])->all();
                $objectsgroups = Objectsgroup::find()->select(['id','name'])->all();
                $fieldsgroups = Fieldsgroup::find()->select(['id','name'])->all();
                $treilersgroups = Treilersgroup::find()->select(['id','name'])->all();
                $cropsgroups = Cropsgroup::find()->select(['id','name'])->all();
            } else {
                $driversgroups = Driversgroup::find()->select(['id','name'])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
                $objectsgroups = Objectsgroup::find()->select(['id','name'])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
                $fieldsgroups = Fieldsgroup::find()->select(['id','name'])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
                $treilersgroups = Treilersgroup::find()->select(['id','name'])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
                $cropsgroups = Cropsgroup::find()->select(['id','name'])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
            }
            return [
                'success' => true,
                'types' => $alltypes = $this->typesReports,
                'typelink' => ArrayHelper::map($alltypes, 'id', 'name'),
                'typesGroups' => $alltypesGroups = $this->typesReportGroups,
                'typeGroupslink' => ArrayHelper::map($alltypesGroups, 'id', 'name'),
                'drivers' => $alldrivers =$drivers->getAll(),
                'driversgroups' => $driversgroups,
            //    'driverlink' => ArrayHelper::map($alldrivers, 'id', 'name'),
                'objects' => $allobjects =$objects->getAll(),
                'objectsgroups' => $objectsgroups,
            //    'objectlink' => ArrayHelper::map($allobjects, 'id', 'name'),
                'fields' => $allfields =$fields->getAll(),
                'fieldsgroups' => $fieldsgroups,
             //   'fieldlink' => ArrayHelper::map($allfields, 'id', 'name'),
                'treilers' => $alltreilers =$treilers->getAll(),
                'treilersgroups' => $treilersgroups,
             //   'treilerlink' => ArrayHelper::map($alltreilers, 'id', 'name'),
                'crops' => $allcrops =$crops->getAll(),
                'cropsgroups' => $cropsgroups,
                //   'treilerlink' => ArrayHelper::map($alltreilers, 'id', 'name'),
                'technoper' => $alltechnOper = $technOper->getAll(),
            ];
        } else {
            throw new ForbiddenHttpException('Access denied');
        }
    }

    /**
     * @return array|ReportForm
     */
    public function actionUpload ()
    {

        if (Yii::$app->user->can('tracksupload')OR Yii::$app->user->can('superadmin')){
            ini_set('memory_limit', '-1');

            $reportForm = new ReportForm();
            $file       = UploadedFile::getInstance(new FileForm(), 'file');
            $file->saveAs(Yii::$app->params['files']['data'] . $file->name);
            if ($reportForm->parseWlnFile(Yii::$app->params['files']['data'] . $file->name)) {
                return [
                    'success' => true,
                ];
            } else {
                $reportForm->addError('error', 'Ошибка. Файл ' . $file->name . ' не удалось распарсить');

                return $reportForm;
            }

        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }

    /**
     * @return array
     */
    public function actionGenerate ()
    {
        if (Yii::$app->user->can('reportsadd')OR Yii::$app->user->can('superadmin')){
            ini_set('memory_limit', '-1');

            $reportForm = new ReportForm();

            $reportForm->object = Yii::$app->getRequest()->getBodyParam('objects');
            $reportForm->field = Yii::$app->getRequest()->getBodyParam('fields');
            $reportForm->typereport = Yii::$app->getRequest()->getBodyParam('typereport');
           // file_put_contents('/var/www/overseer_prod/test002.txt', print_r($reportForm->typereport, true ));
            switch($reportForm->typereport){
                case '6':
                    $arrTreiler = $reportForm->getTreilerTehop( $reportForm->field);
                    $reportForm->object = $reportForm->getObjectTreiler($arrTreiler);
                    //file_put_contents('/var/www/overseer_prod/test001.txt', print_r($reportForm->object, true ));
                    $reportForm->field = [];
                    $reportForm->object = Objects::getIDtoExternal($reportForm->object);
                    break;
                case '5':
                    if(Yii::$app->getRequest()->getBodyParam('groupfield')==1){
                        $reportForm->field = $reportForm->getGroupCrops($reportForm->field);
                    }
                    $reportForm->field = $reportForm->getCultureFields($reportForm->field);
                    $reportForm->object =$reportForm->getObjects();
                    $reportForm->object = Objects::getIDtoExternal($reportForm->object);
                    break;
                case '4':
                    if(Yii::$app->getRequest()->getBodyParam('groupfield')==1){
                        $reportForm->field = $reportForm->getGroupTreilers($reportForm->field);
                    }
                    $reportForm->object = $reportForm->getObjectTreiler($reportForm->field);
                    $reportForm->object = Objects::getIDtoExternal($reportForm->object);
                    break;
                case '3':
                    if(Yii::$app->getRequest()->getBodyParam('groupfield')==1){
                        $reportForm->field = $reportForm->getGroupDrivers($reportForm->field);
                    }
                    $reportForm->object = $reportForm->getDrivers($reportForm->field);
                    $reportForm->object = Objects::getIDtoExternal($reportForm->object);
                    break;
                case '2':
                    if(Yii::$app->getRequest()->getBodyParam('groupfield')==1){
                        $reportForm->object = $reportForm->getGroupObjects($reportForm->field);
                    }
                    $reportForm->object = $reportForm->field;
                    $reportForm->field = [];
                    $reportForm->object = Objects::getIDtoExternal($reportForm->object);
                    break;
                case '1':
                    if(Yii::$app->getRequest()->getBodyParam('groupfield')==1){
                        $reportForm->field = $reportForm->getGroupFields($reportForm->field);
                    }
                    break;
            }



            $data = $reportForm->generate();
            return [
                'success' => true,
                'reports'  => $data['reports'],
                'coordn'=>$data['coordn'],
                'fieldsC'=>$data['fieldsC'],
                'center'=>$data['center']

            ];
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }

    public function actionObjectonline ()
    {

            $reportForm = new ReportForm();
        if ($reportForm->objectonline()){
            return [
                'cordin'=> $reportForm->objectonline()
            ];
        }


        else {
            $reportForm->addError('error', 'Ошибка. Нет данных об объекте');
        return $reportForm;
    }


    }
    
    public function actionSavetemplate() 
    {
        $reportForm = new ReportForm();
		$nameTemplate = Yii::$app->getRequest()->getBodyParam('nameTemplate');
		$strParam = Yii::$app->getRequest()->getBodyParam('strParam');
		$username = Yii::$app->getRequest()->getBodyParam('username');
	    $idTemplate = Yii::$app->getRequest()->getBodyParam('idTemplate');
	    return [
            'message'=> $reportForm->savetemplate($idTemplate, $nameTemplate, $strParam, $username)
        ];	   
    }
	
    public function actionGettemplate() 
    {
	    $reportForm = new ReportForm();
	    $username = Yii::$app->getRequest()->getBodyParam('username');
	    return [
		    'lineTemplates' => $reportForm->gettemplate($username)
	    ];
    }
	
    public function actionGetdatafields() 
    {
	    $reportForm = new ReportForm();
	    $username = Yii::$app->getRequest()->getBodyParam('username');
	    $name = Yii::$app->getRequest()->getBodyParam('name');	   
	   
	    return [
		    'dataFields' => $reportForm->getdatafields($username, $name)
	    ];
	    
    }
	
    public function actionEdittemplate()
    {
        $reportForm = new ReportForm();
		$nameTemplate = Yii::$app->getRequest()->getBodyParam('nameTemplate');
		$strParam = Yii::$app->getRequest()->getBodyParam('strParam');
		$username = Yii::$app->getRequest()->getBodyParam('username');
	    $idTemplate = Yii::$app->getRequest()->getBodyParam('idTemplate');
	    return [
            'message'=> $reportForm->edittemplate($idTemplate, $nameTemplate, $strParam, $username)
        ];	   
    }
	
    public function actionDeletetemplate()
    {
	    $reportForm = new ReportForm();
	    $nameTemplate = Yii::$app->getRequest()->getBodyParam('nameTemplate');	    
	    $username = Yii::$app->getRequest()->getBodyParam('username');
	    $idTemplate = Yii::$app->getRequest()->getBodyParam('idTemplate');
	    return [
          	'result'=> $reportForm->deletetemplate($idTemplate, $nameTemplate, $username)
          ];	    
    }	
}




