<?php
require_once __DIR__ . "/vendor/autoload.php";

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;

class TokenService
{
    public static $key = '';
    public static $tokenArray = [];

    public function __construct()
    {
        $params = require(__DIR__ . '/params.php');
        static::$key = $params['tokenSignerKey'];
    }

    /**
     * 验证token
     * @param $token
     * @return array
     */
    public static function authToken($token)
    {
        do {
            try {
                $token = (new Parser())->parse($token);
            } catch (\Exception $e) {
                // token 内容错误，解析失败
                return ['errno' => 1, 'msg' => '非法操作, 认证信息解析错误'];
                break;
            }

            $data = new ValidationData(time()+60);

            // token 校验失败，或已过期
            if (!$token->validate($data)) {
                return ['errno' => 1, 'msg' => '非法操作, 认证信息失效'];
                break;
            }

            $signer = new Sha256();

            // token 签名验证失败，数据被篡改
            if (!$token->verify($signer, static::$key)) {
                return ['errno' => 1, 'msg' => '非法操作, 数据被篡改'];
                break;
            }
            return ['errno' => 0, 'msg' => '认证用户', 'data' => []];

        } while (true);
    }

    /**
     * 王者厅主后台token验证
     * @param $token
     * @return array
     */
    public static function authBackToken($token)
    {
        list($header, $payload, $signature) = explode('.', $token, 3);
        $_payload = json_decode(base64_decode($payload, true), true);
        if (hash_hmac('sha256', $header.'.'.$payload, static::$key, false) != $signature) {
            return ['errno' => 1, 'msg' => '非法操作, 数据被篡改'];
        }
        // 是否过期
        if ($_payload['exp'] <= time()) {
            return ['errno' => 1, 'msg' => '非法操作, 认证信息失效'];
        }

        return ['errno' => 0, 'msg' => '认证用户', 'data' => []];
    }
}

?>