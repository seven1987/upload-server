<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'appVersion' => '0.8.0',
    'appName' => 'DM',
    'homePage' => 'http://www.dmgame111.com',

    'uploadService' => 'https://upload.hc-esports-api.com/',

    'tokenSignerKey' => 'SEUtLpjiup8y1ocJIaXL5weQ5h6eRQjc',            // token 签名使用的 key
    'tokenExpirationTime' => 60 * 60 * 24 * 30,    // token 过期时间，单位秒，30 天
    'platformAgentID' => 1,                        // 平台Agent

    'rabbitmq' => [
        'host' => '10.200.45.46',
        'port' => '5675',
        'login' => 'dmuser',
        'password' => 'BQLOJHH9lMzzKYKM6',
        'vhost'=>'/'
    ],
    'backendSwoole' => "wss://bmsg.hc-esports-api.com:9508",
    'frontendSwoole' => "wss://fmsg.hc-esports-api.com:9507",
    'sysserver'=>[
        "host"    =>  "10.200.45.46",
        "port"  =>  8004
    ],
    'backendmsg'=>[
        'host' => '103.196.125.46',
        'port' => '9508',
    ],
    'frontendmsg'=>[
        'host' => '103.196.125.46',
        'port' => '9507',
    ],
    //swoole 日志服务
    'logserver'=>[
        'host' => '10.200.45.46',
        'port' => '7004',
    ],
    'reckonserver'=>[
        "host"    =>  "10.200.45.46",
        "port"  =>  9004
    ],

	//系统支持的多语言
	'allow_lang' => [
		'zh-CN' => '简体',
		'zh-TW' => '繁体',
//		'en-US' => 'En(US)',
	],
];
