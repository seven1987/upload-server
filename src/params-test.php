<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'appVersion' => '0.8.0',
    'appName' => 'DM',
    'homePage' => 'http://www.dmgame111.com',

    'uploadService' => 'http://123.206.175.60:8071/',

    'tokenSignerKey' => 'testing',            // token 签名使用的 key
    'tokenExpirationTime' => 60 * 60 * 24 * 30,    // token 过期时间，单位秒，30 天
    'platformAgentID' => 1,                        // 平台Agent

    /*'rabbitmq' => [
        'host' => '192.168.50.29',
        'port' => '5673',
        'login' => 'dmuser',
        'password' => 'BQLOJHH9lMzzKYKM6',
        'vhost'=>'/'
    ],
    'backendSwoole' => "ws://192.168.50.24:9504",
    'frontendSwoole' => "ws://192.168.50.24:9503",
    'sysserver'=>[
        "host"    =>  "192.168.50.24",
        "port"  =>  8002
    ],
    'backendmsg'=>[
        'host' => '192.168.50.24',
        'port' => '9504',
    ],
    'frontendmsg'=>[
        'host' => '192.168.50.24',
        'port' => '9503',
    ],
    //swoole 日志服务
    'logserver'=>[
        'host' => '192.168.50.24',
        'port' => '7002',
    ],
    'reckonserver'=>[
        "host"    =>  "192.168.50.24",
        "port"  => '9002'
    ],*/

	//系统支持的多语言
	'allow_lang' => [
		'zh-CN' => '简体',
		'zh-TW' => '繁体',
//		'en-US' => 'En(US)',
	],
];
