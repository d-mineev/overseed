<?php
namespace backend\controllers;

use backend\models\Tasks;
use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Objects;

use backend\models\FileForm;
use yii\web\UploadedFile;
use yii\web\ForbiddenHttpException;

/**
 * Task controller
 */
class TasksController extends MainController
{
    public $modelClass = 'backend\models\Tasks';
    
    private $tasksModel;

    public function init ()
    {
        parent::init();

        $this->tasksModel = new $this->modelClass();
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
        $postData = file_get_contents("php://input");
        $dataTask = json_decode($postData, true);
        $id= (int) $dataTask['type'];

	  $array = $this->tasksModel->getAll($id);
	  $result = ArrayHelper::map($array, 'id', 'name');
        return [
            'tasks' => $array,
		'objecttask' => $result  
        ];
    }

    public function actionCreate() {  
	    
	if (Yii::$app->user->can('questsadd')OR Yii::$app->user->can('superadmin')){
		$postData = file_get_contents("php://input");
		$dataTask = json_decode($postData, true);		  	
		$this->tasksModel->createTask($dataTask);
		return [
			'message' => 'Задание добавлено!'
		];
	} else {
            throw new ForbiddenHttpException('Access denied');
	}		
    }


    public function actionEdit(){
	    
	if (Yii::$app->user->can('questsedit')OR Yii::$app->user->can('superadmin')){
		  $postData = file_get_contents("php://input");	  
		  $dataTask = json_decode($postData, true);	    
		  $id= (int) $dataTask['data']['id'];	    
		  $this->tasksModel->updateTask($id, $dataTask);
		  return [
			'message' => 'Задание отредактировано!'
		  ];
	} else {
            throw new ForbiddenHttpException('Access denied');
      }		
    }

    public function actionDelete(){
	    
	if (Yii::$app->user->can('questsdelite')OR Yii::$app->user->can('superadmin')){
		  $postData = file_get_contents("php://input");
		  $dataTask = json_decode($postData, true);
        $this->tasksModel->deleteAll(['id' => (int) $dataTask['data']]);
		 /* for($i = 0; $i < count($dataTask['data']); $i++) {
			  $id = (int) $dataTask['data'][$i];		  

			  $this->tasksModel->deleteAll(['id' => (int) $id]);
		  }	*/
		
		  return [
			  'message' => 'Удалено!'
		  ];
	} else {
            throw new ForbiddenHttpException('Access denied');
      }		
    }  	   
}