<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2017/7/7
 * Time: 14:08
 */
//defined("ROOT_PATH") or define("ROOT_PATH", __DIR__);
// 支持跨域
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, X-Request-Uri, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET,POST");
//引入上传服务
require_once "../UploadService.php";
require_once "../TokenService.php";
require_once "../DataPackager.php";
// post
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo date('Y-m-d H:i:s') . '<br> 随机数==><i style="color: red"> ' . rand(10000, 70000) . '</i><br>' . '看到这个说明服务还在跑。。。';
    exit;
} else {
    //允许的操作类型
    $allowAct = [
        'form',//表单上传图片(默认) ci_upload
        'encode',//base64字节上传
        'get',// 获取已经上传的图片
        'bi_form',// 获取已经上传的图片
    ];

    //获取操作类型
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    if (empty($act) || !in_array($act, $allowAct)) {
        http_response_code(404);
        echo DataPackager::pack([], DataPackager::INVALID_UPLOAD);
        exit;
    }
    // token校验
    //$tokenService = new TokenService();
    //// 非获取token的操作都要验证token
    //if ($act != 'auth') {
    //    //$token = isset($_POST['tk']) ? $_POST['tk'] : '';
    //    if (empty($token)) {
    //        echo DataPackager::pack([], DataPackager::FRONTEND_LOGIN_INVALID);
    //        exit;
    //    }
    //    $res = (!empty(@$_POST['backend'])) ? $tokenService::authBackToken($token) : $tokenService::authToken($token);
    //    if ($res['errno']!=0) {
    //        http_response_code(401);
    //        echo DataPackager::pack([], DataPackager::FRONTEND_LOGIN_INVALID, $res['msg']);
    //        exit;
    //    }
    //}
    $uploadService = new UploadService();
    //根据操作类型执行上传
    switch ($act) {
        /**
         *  表单图片上传
         * @param string act  form
         * @param integer image_type 1：默认路径  2 ：后台战队图片目录 3：用户端战队图片 4： 个人头像  其它： 公共目录
         * @param string file_data_field
         * @param string name
         * @return array
         */
        case "form":
            $uploadRet = $uploadService::upload();
            if ($uploadRet['code']==DataPackager::OK) {
                $res['error'] = 0;
                $res['message'] = $uploadRet['msg'];
                $res['url'] = $uploadRet['url'];
            } else {
                $res['error'] = 1;
                $res['message'] = $uploadRet['msg'];
            }
            $res = json_encode($res);
            //kindeditor跨域上传专用: 上传后重定向到客户端设置的重定向地址
            if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                http_response_code(301);
                $redirect = $_POST['redirect'] . "?s=" . $res . "#" . $res;
                header("Location: " . $redirect);
                return;
            }
            if ($uploadRet['code']==DataPackager::OK) {
                echo DataPackager::pack(['url' => $uploadRet['url']], DataPackager::OK);
            } else {
                http_response_code(401);
                echo DataPackager::pack([], $uploadRet['code'], $uploadRet['msg']);
            }
            break;
        /**
         * 字节流上传
         * @param string actionFrom  backend  来源是后台 仅后台使用
         * @param string act  encode
         * @param integer image_type 1：默认路径  2 ：后台战队图片目录 3：用户端战队图片 4： 个人头像  其它： 公共目录
         * @param string file_data_field
         * @param string name
         * @return array
         */
        case "encode":
            $uploadRet = $uploadService::uploadImgByte();
            if ($uploadRet['code']==DataPackager::OK) {
                echo DataPackager::pack(['url' => $uploadRet['url']], DataPackager::OK);
            } else {
                http_response_code(401);
                echo DataPackager::pack([], $uploadRet['code'], $uploadRet['msg']);
            }
            break;
        /**
         *  获取某类型上传的图片
         *
         * @param string act  get
         * @param integer image_type 1：默认路径  2 ：后台战队图片目录 3：用户端战队图片 4： 个人头像  其它： 公共目录
         * @return array
         */
        case "get":
            $uploadRet = $uploadService::getImg();
            if ($uploadRet['code']==DataPackager::OK) {
                echo DataPackager::pack($uploadRet['data'], DataPackager::OK);
            } else {
                http_response_code(401);
                echo DataPackager::pack([], $uploadRet['code'], $uploadRet['msg']);
            }
            break;
    }
    exit;
}
