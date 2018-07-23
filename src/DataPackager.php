<?php
class DataPackager
{
    // 全局性code:
    const OK = 0;       //no error
    const FRONTEND_SUCCESS = 0; //no error

    // 业务-用户错误码201xxx
    // 用户端，登录、注册错误号
    const FRONTEND_LOGIN_INVALID = 201001;                                //登录已失效

    // 全局-  图片上传错误 301xxx
    const UPLOAD_FAILED = 103001;                                         // 上传失败
    const INVALID_UPLOAD = 103002;                                        // 无效上传
    const CHINESE_FILE_NAME = 103003;                                     // 文件名不能包含中文
    const INVALID_UPLOAD_TYPE = 103004;                                   // 无效上传类型
    const FAILED_CREATE_DIR = 103005;                                     // 创建目录失败
    const FILE_SIZE_EXCEEDS_LIMIT = 103006;                              // 上传文件大小超过限制
    const NO_TEMPORARY_FILE = 103007;                                   //保存临时文件失败
    const FILE_EXIST = 103008;                                   //文件已存在



    //系统错误码信息
    static $MSGS = [
        DataPackager::OK => '操作成功',
        DataPackager::FRONTEND_SUCCESS => '操作成功',

        DataPackager::FRONTEND_LOGIN_INVALID => '登录已失效',

        DataPackager::UPLOAD_FAILED => '上传失败',
        DataPackager::INVALID_UPLOAD => '无效上传',
        DataPackager::CHINESE_FILE_NAME => '文件名不能包含中文',
        DataPackager::INVALID_UPLOAD_TYPE => '无效上传类型',
        DataPackager::FAILED_CREATE_DIR => '创建目录失败',
        DataPackager::FILE_SIZE_EXCEEDS_LIMIT => '文件大小超过限',
        DataPackager::NO_TEMPORARY_FILE => '保存临时文件失败',
    ];

    /**
     * 数据按格式封装后输出
     *
     * @param array $data 数据集合
     * @param integer $code 消息代号，在 SysCode 中定义
     * @param array|string $msg 错误提示
     * @return string json
     */
    public static function pack($data, $code = DataPackager::FRONTEND_SUCCESS, $msg = '')
    {
        return json_encode(static::rawPack($data, $code, $msg));
    }

    /**
     * 数据按格式封装后输出 (报错用，data = null)
     *
     * @param integer $code 消息代号，在 SysCode 中定义
     * @param array|string $msg 错误提示
     * @return string json
     */
    public static function error($code, $msg = '')
    {
        return json_encode(['data' => null, 'code' => $code, 'msg' => $msg == '' ? static::$MSGS[$code] : $msg]);
    }

    /**
     * 数据按格式封装后输出
     *
     * @param array $data 数据集合
     * @param integer $code 消息代号，在 SysCode 中定义
     * @param array|string $msg 错误提示
     * @return array
     */
    public static function rawPack($data, $code = DataPackager::FRONTEND_SUCCESS, $msg = '')
    {
        if ($data == []) {
            return ['data' => null, 'code' => $code, 'msg' => $msg == '' ? static::$MSGS[$code] : $msg];
        }
        return ['code' => $code, 'msg' => $msg == '' ? static::$MSGS[$code] : $msg, 'data' => $data];
    }

}