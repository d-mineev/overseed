<?php
$configParamsLocal = file_exists(__DIR__ . '/../../common/config/params-local.php') ? require(__DIR__ . '/../../common/config/params-local.php') : [];
$paramsLocal = file_exists(__DIR__ . '/params-local.php') ? require(__DIR__ . '/params-local.php') : [];

$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    $configParamsLocal,
    require(__DIR__ . '/params.php'),
    $paramsLocal
);

return [
    'id'                  => 'app-backend',
    'basePath'            => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap'           => ['log'],
//    'modules'             => [],
	'modules' => [
		'yii2images' => [
			'class' => 'rico\yii2images\Module',
			//be sure, that permissions ok
			//if you cant avoid permission errors you have to create "images" folder in web root manually and set 777 permissions
			'imagesStorePath' => 'upload/store', //path to origin images
			'imagesCachePath' => 'upload/cache', //path to resized copies
			//'graphicsLibrary' => 'GD', //but really its better to use 'Imagick'
			'graphicsLibrary' => 'Imagick', //but really its better to use 'Imagick'
			'placeHolderPath' => '@webroot/upload/no.png', // if you want to get placeholder when image not exists, string will be processed by Yii::getAlias
		],
	],
    'components'          => [
        'user'       => [
            'identityClass' => 'common\models\User',
            'enableSession' => false,
            'loginUrl'      => null,

        ],
        'request'    => [
            'class'                  => '\yii\web\Request',
            'enableCookieValidation' => false,
            'enableCsrfValidation'   => false,
            'baseUrl'                => '/api',
            'parsers'                => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl'     => true,
            'enableStrictParsing' => true,
            'showScriptName'      => false,
            'rules'               => [
				/*
				'<module:\w+>/<controller:\w+>/<id:\d+>' => '<module>/<controller>/view',
				'<module:\w+>/<controller:\w+>/<action:\w+>/<id:\d+>' => '<module>/<controller>/<action>',
				'<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
				*/
                ['class' => 'yii\rest\UrlRule', 'controller' => ['site', 'report', 'tasks', 'fields', 'drivers',
																'treilers','treil', 'objects' ,'admin',
																'positions' , 'rolesbook',
																'technologytype', 'technologyoperation', 'technologyproc',
																	'crops', 'fuelinfo',
																	'usersgroup' ,'fieldsgroup', 'kml']],
                'OPTIONS login'                => 'site/login',
                'POST login'                   => 'site/login',
                'OPTIONS dashboard'            => 'site/dashboard',
                'GET dashboard'                => 'site/dashboard',

				'OPTIONS site/init'            => 'site/init',
				'GET site/init'                => 'site/init',
				'OPTIONS site/userinfo'            => 'site/userinfo',
				'GET site/userinfo'                => 'site/userinfo',
				'OPTIONS site/useredit'            => 'site/useredit',
				'POST site/useredit'                => 'site/useredit',
				'OPTIONS site/upload'            => 'site/upload',
				'POST site/upload'                => 'site/upload',

                'OPTIONS test'  => 'test/index',
                'GET test' => 'test/index',

				'OPTIONS kml'  => 'kml/index',
				'GET kml' => 'kml/index',
				'OPTIONS kml/track/<filename:>/<zum:>/<time:>'  => 'kml/track',
				'GET kml/track/<filename:>/<zum:>/<time:>' => 'kml/track',
				'OPTIONS kml/fields/<object:>/<type:>/<ids:>'  => 'kml/fields',
				'GET kml/fields/<object:>/<type:>/<ids:>' => 'kml/fields',
				'OPTIONS kml/points/<object:>/<type:>/<ids:>'  => 'kml/points',
				'GET kml/points/<object:>/<type:>/<ids:>' => 'kml/points',

                'OPTIONS contact'              => 'site/contact',
                'POST contact'                 => 'site/contact',
                'OPTIONS resetpassword'              => 'site/resetpassword',
                'POST resetpassword'                 => 'site/resetpassword',
                'OPTIONS resetuserpass'              => 'site/resetuserpass',
                'POST resetuserpass'                 => 'site/resetuserpass',

                'OPTIONS report'               => 'report/index',
                'GET report'                   => 'report/index',
                'OPTIONS report/upload'        => 'report/upload',
                'POST report/upload'           => 'report/upload',
                'OPTIONS report/generate'      => 'report/generate',
                'POST report/generate'         => 'report/generate',
                'OPTIONS reports/objectonline'      => 'report/objectonline',
                'POST reports/objectonline'         => 'report/objectonline',

				'OPTIONS reports/view'       => 'report/view',
				'GET reports/view' 			=> 'report/view',
                
                'OPTIONS reports/savetemplate'      => 'report/savetemplate',
    		    'POST reports/savetemplate'	        => 'report/savetemplate',
    		    'OPTIONS reports/edittemplate'      => 'report/edittemplate',
    		    'POST reports/edittemplate'	        => 'report/edittemplate',
		
		    'OPTIONS reports/deletetemplate'    => 'report/deletetemplate',
		    'POST reports/deletetemplate'	    => 'report/deletetemplate',	
			
    		    'OPTIONS reports/gettemplate'       => 'report/gettemplate',
    		    'POST reports/gettemplate'	        => 'report/gettemplate',	
    		    'OPTIONS reports/getdatafields'     => 'report/getdatafields',
    		    'POST reports/getdatafields'	    => 'report/getdatafields',



                'OPTIONS fields'               => 'fields/index',
                'GET fields'                   => 'fields/index',
                'OPTIONS fields/upload'        => 'fields/upload',
                'POST fields/upload'           => 'fields/upload',
                'OPTIONS fields/view/<id:\d+>' => 'fields/view',
                'GET fields/view/<id:\d+>'     => 'fields/view',
                'OPTIONS fields/delete'        => 'fields/delete',
                'POST fields/delete'           => 'fields/delete',
                'OPTIONS fields/create'    => 'fields/create',
                'POST fields/create'       => 'fields/create',
                'OPTIONS fields/edit'      => 'fields/edit',
                'POST fields/edit'         => 'fields/edit',


                'OPTIONS objects/create' => 'objects/create',
                'POST objects/create'     => 'objects/create',
                'OPTIONS objects/edit'  => 'objects/edit',
                'POST objects/edit'       => 'objects/edit',
                'OPTIONS objects/delete'    => 'objects/delete',
                'POST objects/delete'           => 'objects/delete',
                'OPTIONS objects/view/<id:\d+>'     => 'objects/view',
                'GET objects/view/<id:\d+>'             => 'objects/view',
                'OPTIONS objects/external'          => 'objects/external',
                'POST objects/external'                 => 'objects/external',


                'OPTIONS treilers/create' => 'treilers/create',
                'POST treilers/create'     => 'treilers/create',
                'OPTIONS treilers/edit' => 'treilers/edit',
                'POST treilers/edit'       => 'treilers/edit',
                'OPTIONS treilers/delete'      => 'treilers/delete',
                'POST treilers/delete'            => 'treilers/delete',
                'OPTIONS treilers/view/<id:\d+>'     => 'treilers/view',
                'GET treilers/view/<id:\d+>'             => 'treilers/view',



                'OPTIONS drivers/create' => 'drivers/create',
                'POST drivers/create'     => 'drivers/create',
                'OPTIONS drivers/edit' => 'drivers/edit',
                'POST drivers/edit'     => 'drivers/edit',
                'OPTIONS drivers/delete' => 'drivers/delete',
                'POST drivers/delete'     => 'drivers/delete',
				'OPTIONS drivers/view/<id:\d+>'     => 'drivers/view',
				'GET drivers/view/<id:\d+>'             => 'drivers/view',
			
		    'OPTIONS drivers/upload' => 'drivers/upload',
                'POST drivers/upload'     => 'drivers/upload',	

                'OPTIONS admin'            => 'admin/index',
                'GET admin'                => 'admin/index',

                'OPTIONS admin/newuser'            => 'admin/newuser',
                'POST admin/newuser'                => 'admin/newuser',
			
		    'OPTIONS tasks'               => 'tasks/index',
                'GET tasks'                   => 'tasks/index',
		    'OPTIONS tasks/create'        => 'tasks/create',
                'POST tasks/create'            => 'tasks/create',
		    'OPTIONS tasks/edit'        => 'tasks/edit',
                'POST tasks/edit'            => 'tasks/edit',
		    'OPTIONS tasks/delete'        => 'tasks/delete',
                'POST tasks/delete'            => 'tasks/delete',			    
			

				
			

                'OPTIONS admin/view/<id:\d+>'        => 'admin/view',
                'GET admin/view/<id:\d+>'             => 'admin/view',
                'OPTIONS admin/newuser'         => 'admin/newuser',
                'POST admin/newuser'              => 'admin/newuser',
                'OPTIONS admin/edituser'            => 'admin/edituser',
                'POST admin/edituser'                => 'admin/edituser',
                'OPTIONS admin/deleteuser'            => 'admin/deleteuser',
                'POST admin/deleteuser'                => 'admin/deleteuser',
                'OPTIONS admin/setpassword'                 => 'admin/setpassword',
                'POST admin/setpassword'                    => 'admin/setpassword',


				'OPTIONS positions/create' => 'positions/create',
				'POST positions/create'     => 'positions/create',
				'OPTIONS positions/edit' => 'positions/edit',
				'POST positions/edit'     => 'positions/edit',
				'OPTIONS positions/delete' => 'positions/delete',
				'POST positions/delete'     => 'positions/delete',
				'OPTIONS positions/dispatchers' => 'positions/dispatchers',
				'GET positions/dispatchers'     => 'positions/dispatchers',


				'OPTIONS technologytype' => 'technologytype/index',
				'GET technologytype'     => 'technologytype/index',
				'OPTIONS technologytype/create' => 'technologytype/create',
				'POST technologytype/create'     => 'technologytype/create',
				'OPTIONS technologytype/edit' => 'technologytype/edit',
				'POST technologytype/edit'     => 'technologytype/edit',
				'OPTIONS technologytype/delete' => 'technologytype/delete',
				'POST technologytype/delete'     => 'technologytype/delete',

				'OPTIONS techoperation' => 'technologyoperation/index',
				'GET techoperation'     => 'technologyoperation/index',
				'OPTIONS techoperation/create' => 'technologyoperation/create',
				'POST techoperation/create'     => 'technologyoperation/create',
				'OPTIONS techoperation/edit' => 'technologyoperation/edit',
				'POST techoperation/edit'     => 'technologyoperation/edit',
				'OPTIONS techoperation/delete' => 'technologyoperation/delete',
				'POST techoperation/delete'     => 'technologyoperation/delete',

				'OPTIONS techproc' => 'technologyproc/index',
				'GET techproc'     => 'technologyproc/index',
				'OPTIONS techproc/create' => 'technologyproc/create',
				'POST techproc/create'     => 'technologyproc/create',
				'OPTIONS techproc/edit' => 'technologyproc/edit',
				'POST techproc/edit'     => 'technologyproc/edit',
				'OPTIONS techproc/delete' => 'technologyproc/delete',
				'POST techproc/delete'     => 'technologyproc/delete',

				'OPTIONS fuelinfo' => 'fuelinfo/index',
				'GET fuelinfo'     => 'fuelinfo/index',
				'OPTIONS fuelinfo/create' => 'fuelinfo/create',
				'POST fuelinfo/create'     => 'fuelinfo/create',
				'OPTIONS fuelinfo/edit' => 'fuelinfo/edit',
				'POST fuelinfo/edit'     => 'fuelinfo/edit',
				'OPTIONS fuelinfo/delete' => 'fuelinfo/delete',
				'POST fuelinfo/delete'     => 'fuelinfo/delete',

				'OPTIONS crops' 			=> 		'crops/index',
				'GET crops'     			=> 		'crops/index',
				'OPTIONS crops/create' 		=> 		'crops/create',
				'POST crops/create'     	=> 		'crops/create',
				'OPTIONS crops/edit' 		=> 		'crops/edit',
				'POST crops/edit'     		=> 		'crops/edit',
				'OPTIONS crops/delete' 		=> 		'crops/delete',
				'POST crops/delete'     	=> 		'crops/delete',

				'OPTIONS crops/upload' => 'crops/upload',
				'POST crops/upload'     => 'crops/upload',
				'OPTIONS crops/view/<id:\d+>'     => 'crops/view',
				'GET crops/view/<id:\d+>'             => 'crops/view',

				'OPTIONS rolesbook' => 'rolesbook/index',
				'GET rolesbook'     => 'rolesbook/index',
				'OPTIONS rolesbook/create' => 'rolesbook/create',
				'POST rolesbook/create'     => 'rolesbook/create',
				'OPTIONS rolesbook/edit' => 'rolesbook/edit',
				'POST rolesbook/edit'     => 'rolesbook/edit',
				'OPTIONS rolesbook/delete' => 'rolesbook/delete',
				'POST rolesbook/delete'     => 'rolesbook/delete',


				'OPTIONS usersgroup' => 'usersgroup/index',
				'GET usersgroup'     => 'usersgroup/index',
				'OPTIONS usersgroup/create' => 'usersgroup/create',
				'POST usersgroup/create'     => 'usersgroup/create',
				'OPTIONS usersgroup/edit' => 'usersgroup/edit',
				'POST usersgroup/edit'     => 'usersgroup/edit',
				'OPTIONS usersgroup/delete' => 'usersgroup/delete',
				'POST usersgroup/delete'     => 'usersgroup/delete',

				'OPTIONS fieldsgroup' => 'fieldsgroup/index',
				'GET fieldsgroup'     => 'fieldsgroup/index',
				'OPTIONS fieldsgroup/create' => 'fieldsgroup/create',
				'POST fieldsgroup/create'     => 'fieldsgroup/create',
				'OPTIONS fieldsgroup/edit' => 'fieldsgroup/edit',
				'POST fieldsgroup/edit'     => 'fieldsgroup/edit',
				'OPTIONS fieldsgroup/delete' => 'fieldsgroup/delete',
				'POST fieldsgroup/delete'     => 'fieldsgroup/delete',

				'OPTIONS objectsgroup' => 'objectsgroup/index',
				'GET objectsgroup'     => 'objectsgroup/index',
				'OPTIONS objectsgroup/create' => 'objectsgroup/create',
				'POST objectsgroup/create'     => 'objectsgroup/create',
				'OPTIONS objectsgroup/edit' => 'objectsgroup/edit',
				'POST objectsgroup/edit'     => 'objectsgroup/edit',
				'OPTIONS objectsgroup/delete' => 'objectsgroup/delete',
				'POST objectsgroup/delete'     => 'objectsgroup/delete',

				'OPTIONS treilersgroup' => 'treilersgroup/index',
				'GET treilersgroup'     => 'treilersgroup/index',
				'OPTIONS treilersgroup/create' => 'treilersgroup/create',
				'POST treilersgroup/create'     => 'treilersgroup/create',
				'OPTIONS treilersgroup/edit' => 'treilersgroup/edit',
				'POST treilersgroup/edit'     => 'treilersgroup/edit',
				'OPTIONS treilersgroup/delete' => 'treilersgroup/delete',
				'POST treilersgroup/delete'     => 'treilersgroup/delete',

				'OPTIONS driversgroup' => 'driversgroup/index',
				'GET driversgroup'     => 'driversgroup/index',
				'OPTIONS driversgroup/create' => 'driversgroup/create',
				'POST driversgroup/create'     => 'driversgroup/create',
				'OPTIONS driversgroup/edit' => 'driversgroup/edit',
				'POST driversgroup/edit'     => 'driversgroup/edit',
				'OPTIONS driversgroup/delete' => 'driversgroup/delete',
				'POST driversgroup/delete'     => 'driversgroup/delete',

				'OPTIONS cropsgroup' => 'cropsgroup/index',
				'GET cropsgroup'     => 'cropsgroup/index',
				'OPTIONS cropsgroup/create' => 'cropsgroup/create',
				'POST cropsgroup/create'     => 'cropsgroup/create',
				'OPTIONS cropsgroup/edit' => 'cropsgroup/edit',
				'POST cropsgroup/edit'     => 'cropsgroup/edit',
				'OPTIONS cropsgroup/delete' => 'cropsgroup/delete',
				'POST cropsgroup/delete'     => 'cropsgroup/delete',

            ],
        ],
        'log'        => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
//        'errorHandler' => [
//            'errorAction' => 'site/error',
//        ],
    ],
    'params'              => $params,
];

