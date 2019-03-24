<?php
namespace backend\controllers;

use common\models\LoginForm;
use frontend\models\ContactForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\Controller;
use yii\web\Response;
use common\models\User;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use yii\db\Query;
use backend\models\FileForm;
use yii\web\UploadedFile;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors                      = parent::behaviors();
        $behaviors['authenticator']     = [
            'class' => HttpBearerAuth::className(),
            'only'  => ['dashboard','userinfo','useredit','upload'],
        ];
        $behaviors['contentNegotiator'] = [
            'class'   => ContentNegotiator::className(),
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        $behaviors['access']            = [
            'class' => AccessControl::className(),
            'only'  => ['dashboard','userinfo','useredit','upload'],
            'rules' => [
                [
                    'actions' => ['dashboard','userinfo','useredit','upload'],
                    'allow'   => true,
                    'roles'   => ['@'],
                ],
            ],
        ];

        return $behaviors;
    }

    public function actionLogin()
    {
        $model = new LoginForm();

        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->login()) {
            $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
            $answer = [];
            foreach ($roles as $ar){
                $answer[$ar->name] = true;
            }
            return ['access_token' => Yii::$app->user->identity->getAuthKey(),
                    'roles' => $answer,
                    'name' => Yii::$app->user->identity->username,
                    'id' => Yii::$app->user->identity->id    
            ];
        } else {
            $model->validate();

            return $model;
        }
    }

    public function actionDashboard()
    {
        $response = [
            'username'     => Yii::$app->user->identity->username,
            'access_token' => Yii::$app->user->identity->getAuthKey(),
        ];

        return $response;
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                $response = [
                    'flash' => [
                        'class'   => 'success',
                        'message' => 'Thank you for contacting us. We will respond to you as soon as possible.',
                    ]
                ];
            } else {
                $response = [
                    'flash' => [
                        'class'   => 'error',
                        'message' => 'There was an error sending email.',
                    ]
                ];
            }

            return $response;
        } else {
            $model->validate();

            return $model;
        }
    }

    public function actionResetpassword(){

        $model = new PasswordResetRequestForm();
           $model->email = Yii::$app->getRequest()->getBodyParam('mail');

        if ($model->validate()){
      //  if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                return [
                    'success' => true
                ];
            }
             else {
                    return [
                        'error' => true
                    ];
                }
        }
    }


    public function actionResetuserpass(){


        $token = (string) Yii::$app->getRequest()->getBodyParam('token');

        try {
            $model = new ResetPasswordForm($token);
            $model->password = Yii::$app->getRequest()->getBodyParam('password');
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ( $model->validate() && $model->resetPassword()) {
            return [
                'success' => true
            ];
        }
        else {
            return [
                'error' => true
            ];
        }

    }

    public function actionUserinfo()
    {
        $user = Yii::$app->user->identity;
        $userinfo =[];
        $userinfo["id"]=$user->id;
        $userinfo["username"]=$user->username;
        $userinfo["name"]=$user->name;
        $userinfo["email"]=$user->email;
        $userinfo["position"]=$user->position;
        $userinfo["skype"]=$user->skype;
        $userinfo["telefon"]=$user->telefon;
        $userinfo["foto"]=$user->foto;

        return [
            'success' => true,
            'user' => $userinfo
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
            return [
                'error' => true,
                'message' => 'Ошибка. Не удалось загрузить фото'
            ];
        }
    }


    public function actionUseredit(){

        $newuser = Yii::$app->getRequest()->getBodyParam('newuser');
        $finduseremail = User::findOne(['email' => $newuser['email']]);
        $findusername = User::findOne(['username' => $newuser['username']]);
        $id = Yii::$app->user->identity->id;
        $oldfoto =  Yii::$app->user->identity->foto;
        if(!empty($finduseremail) and !($finduseremail->id == $id) ){
            return [
                'error' => true,
                'message' => 'Ошибка. Пользователь с такой электронной почтой существует'
            ];
        }

        elseif (!empty($findusername) and !($findusername->id == $id)) {
            return [
                'error' => true,
                'message' => 'Ошибка. Пользователь с таким Логином существует'
            ];
        } elseif (Yii::$app->db->createCommand()->update('user',
                [
                    'username'        =>  $newuser['username'],
                    'name'        =>  $newuser['name'],
                    'email'      => $newuser['email'],
                    'position' =>  $newuser['position'],
                    'telefon'      =>  $newuser['telefon'],
                    'foto'      =>  $newuser['foto'],
                    'skype'     =>  $newuser['skype']

                ], 'id ='.$id)->execute()
            )   {

                if ($newuser['delloldfoto'] and !empty($oldfoto)){
                    if(file_exists(Yii::$app->params['files']['upload'] . Yii::$app->user->identity->foto))
                    {
                        unlink(Yii::$app->params['files']['upload'] . Yii::$app->user->identity->foto);
                    }
                }
            return [
                'success' => true
            ];

            }
            else {
                return [
                    'error' => true,
                    'message' => 'Ошибка. Пользователь с таким Логином существует'
                ];
            }



    }

    public function actionInit()
    {
	 
	    
//вначале создаем пустую таблицу {{user}}

        $user = new User();
        $user->username = 'demo123';
        $user->email = 'demo@example.com';
        $user->parenttree = '|0|';
        $user->generateAuthKey();
        $user->setPassword('demo123');

        $user->save(false);

        $auth = Yii::$app->authManager;
        $auth->removeAll(); //удаляем старые данные
        /// добавлять пользователя

        $userrole = $auth->createRole('superadmin');
        $userrole->description = 'СуперАдмин';
        $auth->add($userrole);


        //заготовка правил и ролей
        $userRole = $auth->getRole('superadmin');
        $auth->assign($userRole, 1);

/// просматривать объекты
        $userrole = $auth->createRole('dispatchersview');
        $userrole->description = 'Право просматривать диспетчеров';
        $auth->add($userrole);

        /// добавлять объекты
        $userrole = $auth->createRole('dispatchersadd');
        $userrole->description = 'Право добавлять  диспетчеров';
        $auth->add($userrole);


        /// редактировать объекты
        $userrole = $auth->createRole('dispatchersedit');
        $userrole->description = 'Право редактировать  диспетчеров';
        $auth->add($userrole);


        /// удалять объекты
        $userrole = $auth->createRole('dispatchersdelite');
        $userrole->description = 'Право удалять  диспетчеров';
        $auth->add($userrole);


        /// просматривать пользователя
        $userrole = $auth->createRole('usersview');
        $userrole->description = 'Право просматривать пользователей';
        $auth->add($userrole);

        /// добавлять пользователя
        $userrole = $auth->createRole('usersadd');
        $userrole->description = 'Право добавлять пользователей';
        $auth->add($userrole);



        /// редактировать пользователя
        $userrole = $auth->createRole('usersedit');
        $userrole->description = 'Право редактировать пользователей';
        $auth->add($userrole);


        /// удалять пользователя
        $userrole = $auth->createRole('usersdelite');
        $userrole->description = 'Право удалять пользователей';
        $auth->add($userrole);


        /// видеть группы пользователей
        $userrole = $auth->createRole('groupusersview');
        $userrole->description = 'Право видеть группы пользователей';
        $auth->add($userrole);

         /// добавлять группы пользователей
        $userrole = $auth->createRole('groupusersadd');
        $userrole->description = 'Право добавлять группы пользователей';
        $auth->add($userrole);


        /// редактировать группы пользователей
        $userrole = $auth->createRole('groupusersedit');
        $userrole->description = 'Право редактировать группы пользователей';
        $auth->add($userrole);


        /// удалять группы пользователей
        $userrole = $auth->createRole('groupusersdelite');
        $userrole->description = 'Право удалять группы пользователей';
        $auth->add($userrole);

        /// видеть роли
        $userrole = $auth->createRole('rolesview');
        $userrole->description = 'Право видеть роли';
        $auth->add($userrole);

         /// добавлять роли
        $userrole = $auth->createRole('rolesadd');
        $userrole->description = 'Право добавлять роли';
        $auth->add($userrole);


        /// редактировать роли
        $userrole = $auth->createRole('rolesedit');
        $userrole->description = 'Право редактировать роли';
        $auth->add($userrole);


        /// удалять роли
        $userrole = $auth->createRole('rolesdelite');
        $userrole->description = 'Право удалять роли';
        $auth->add($userrole);

        /// просматривать объекты
        $userrole = $auth->createRole('objectsview');
        $userrole->description = 'Право просматривать объекты';
        $auth->add($userrole);

        /// добавлять объекты
        $userrole = $auth->createRole('objectsadd');
        $userrole->description = 'Право добавлять объекты';
        $auth->add($userrole);


        /// редактировать объекты
        $userrole = $auth->createRole('objectsedit');
        $userrole->description = 'Право редактировать объекты';
        $auth->add($userrole);


        /// удалять объекты
        $userrole = $auth->createRole('objectsdelite');
        $userrole->description = 'Право удалять объекты';
        $auth->add($userrole);


        /// просматривать прицепы
        $userrole = $auth->createRole('treilersview');
        $userrole->description = 'Право просматривать прицепы';
        $auth->add($userrole);


        /// добавлять прицепы
        $userrole = $auth->createRole('treilersadd');
        $userrole->description = 'Право добавлять прицепы';
        $auth->add($userrole);


        /// редактировать прицепы
        $userrole = $auth->createRole('treilersedit');
        $userrole->description = 'Право редактировать прицепы';
        $auth->add($userrole);


        /// удалять прицепы
        $userrole = $auth->createRole('treilersdelite');
        $userrole->description = 'Право удалять прицепы';
        $auth->add($userrole);


        /// просматривать водителей
        $userrole = $auth->createRole('driversview');
        $userrole->description = 'Право просматривать водителей';
        $auth->add($userrole);



        /// добавлять водителей
        $userrole = $auth->createRole('driversadd');
        $userrole->description = 'Право добавлять водителей';
        $auth->add($userrole);


        /// редактировать водителей
        $userrole = $auth->createRole('driversedit');
        $userrole->description = 'Право редактировать водителей';
        $auth->add($userrole);


        /// удалять водителей
        $userrole = $auth->createRole('driversdelite');
        $userrole->description = 'Право удалять водителей';
        $auth->add($userrole);
        ;

        /// просматривать поля
        $userrole = $auth->createRole('fieldsview');
        $userrole->description = 'Право просматривать поля';
        $auth->add($userrole);


        /// добавлять поля
        $userrole = $auth->createRole('fieldsadd');
        $userrole->description = 'Право добавлять поля';
        $auth->add($userrole);



        /// редактировать поля
        $userrole = $auth->createRole('fieldsedit');
        $userrole->description = 'Право редактировать поля';
        $auth->add($userrole);


        /// удалять поля
        $userrole = $auth->createRole('fieldsdelite');
        $userrole->description = 'Право удалять поля';
        $auth->add($userrole);


        /// просматривать задания
        $userrole = $auth->createRole('questsview');
        $userrole->description = 'Право просматривать задания';
        $auth->add($userrole);


        /// добавлять задания
        $userrole = $auth->createRole('questsadd');
        $userrole->description = 'Право добавлять задания';
        $auth->add($userrole);


        /// редактировать задания
        $userrole = $auth->createRole('questsedit');
        $userrole->description = 'Право редактировать задания';
        $auth->add($userrole);


        /// удалять задания
        $userrole = $auth->createRole('questsdelite');
        $userrole->description = 'Право удалять задания';
        $auth->add($userrole);


        /// просматривать отчеты
        $userrole = $auth->createRole('reportsview');
        $userrole->description = 'Право просматривать отчеты';
        $auth->add($userrole);


        /// создавать шаблоны

        /// формировать отчеты
        $userrole = $auth->createRole('reportsadd');
        $userrole->description = 'Право формировать отчеты';
        $auth->add($userrole);

        /// видеть трэки
        $userrole = $auth->createRole('tracksview');
        $userrole->description = 'Право видеть трэки';
        $auth->add($userrole);


        /// загружать трэки
        $userrole = $auth->createRole('tracksupload');
        $userrole->description = 'Право загружать трэки';
        $auth->add($userrole);


        /// загружать поля
        $userrole = $auth->createRole('fieldsupload');
        $userrole->description = 'Право загружать поля';
        $auth->add($userrole);


        /// Объект онлайн
        $userrole = $auth->createRole('objectonline');
        $userrole->description = 'Право просматривать Объект онлайн';
        $auth->add($userrole);



        /// видеть должность
        $userrole = $auth->createRole('positionsview');
        $userrole->description = 'Право видеть должность';
        $auth->add($userrole);

         /// добавлять должность
        $userrole = $auth->createRole('positionsadd');
        $userrole->description = 'Право добавлять должность';
        $auth->add($userrole);


        /// редактировать должность
        $userrole = $auth->createRole('positionsedit');
        $userrole->description = 'Право редактировать должность';
        $auth->add($userrole);


        /// удалять должность
        $userrole = $auth->createRole('positionsdelite');
        $userrole->description = 'Право удалять должность';
        $auth->add($userrole);




        $userRole = $auth->getRole('usersview');
        $auth->assign($userRole, 1);
        $userRole = $auth->getRole('usersadd');
        $auth->assign($userRole, 1);
        $userRole = $auth->getRole('usersedit');
        $auth->assign($userRole, 1);
        $userRole = $auth->getRole('usersdelite');
        $auth->assign($userRole, 1);

        /// видеть  тип техники
        $userrole = $auth->createRole('techtypesview');
        $userrole->description = 'Право видеть тип техники';
        $auth->add($userrole);

        /// добавлять тип техники
        $userrole = $auth->createRole('techtypesadd');
        $userrole->description = 'Право добавлять тип техники';
        $auth->add($userrole);


        /// редактировать тип техники
        $userrole = $auth->createRole('techtypesedit');
        $userrole->description = 'Право редактировать тип техники';
        $auth->add($userrole);


        /// удалять тип техники
        $userrole = $auth->createRole('techtypesdelite');
        $userrole->description = 'Право удалять тип техники';
        $auth->add($userrole);

        /// видеть  техн. операции
        $userrole = $auth->createRole('techopersview');
        $userrole->description = 'Право видеть техн. операции';
        $auth->add($userrole);

        /// добавлять техн. операции
        $userrole = $auth->createRole('techopersadd');
        $userrole->description = 'Право добавлять техн. операции';
        $auth->add($userrole);


        /// редактировать техн. операции
        $userrole = $auth->createRole('techopersedit');
        $userrole->description = 'Право редактировать техн. операции';
        $auth->add($userrole);


        /// удалять техн. операции
        $userrole = $auth->createRole('techopersdelite');
        $userrole->description = 'Право удалять техн. операции';
        $auth->add($userrole);

/// видеть  культуры
        $userrole = $auth->createRole('cropsview');
        $userrole->description = 'Право видеть культуры';
        $auth->add($userrole);

        /// добавлять культуры
        $userrole = $auth->createRole('cropsadd');
        $userrole->description = 'Право добавлять культуры';
        $auth->add($userrole);


        /// редактировать культуры
        $userrole = $auth->createRole('cropsedit');
        $userrole->description = 'Право редактировать культуры';
        $auth->add($userrole);


        /// удалять культуры
        $userrole = $auth->createRole('cropsdelite');
        $userrole->description = 'Право удалять культуры';
        $auth->add($userrole);

        /// видеть  техн процессы
        $userrole = $auth->createRole('techprocview');
        $userrole->description = 'Право видеть техн процессы';
        $auth->add($userrole);

        /// добавлять техн процессы
        $userrole = $auth->createRole('techprocadd');
        $userrole->description = 'Право добавлять техн процессы';
        $auth->add($userrole);


        /// редактировать техн процессы
        $userrole = $auth->createRole('techprocedit');
        $userrole->description = 'Право редактировать техн процессы';
        $auth->add($userrole);


        /// удалять техн процессы
        $userrole = $auth->createRole('techprocdelite');
        $userrole->description = 'Право удалять техн процессы';
        $auth->add($userrole);

        /// видеть  расходы топлива
        $userrole = $auth->createRole('fuelinfoview');
        $userrole->description = 'Право видеть расходы топлива';
        $auth->add($userrole);

        /// добавлять расходы топлива
        $userrole = $auth->createRole('fuelinfoadd');
        $userrole->description = 'Право добавлять расходы топлива';
        $auth->add($userrole);


        /// редактировать расходы топлива
        $userrole = $auth->createRole('fuelinfoedit');
        $userrole->description = 'Право редактировать расходы топлива';
        $auth->add($userrole);


        /// удалять расходы топлива
        $userrole = $auth->createRole('fuelinfodelite');
        $userrole->description = 'Право удалять расходы топлива';
        $auth->add($userrole);

        return [
            'success' => true
        ];
    }

}
