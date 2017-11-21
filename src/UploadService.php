<?php
require_once "DataPackager.php";
/**
 * 文件上传服务
 * Class UploadService
 */
class UploadService
{
    public static $all_config = [
        'upload_config' => [
            //图片上传
            'image' => [
                'file_data_field' => 'Filedata',//上传文件的文件域名称
                'max_size' => 5 * 1024,//最大上传大小 5M
                'ext' => 'gif,jpg,jpeg,png,bmp', //扩展名
                'upload_path' => './uploads/image/',//默认上传目录
                'rename' => 1,//是否重命名, 0-否 1-是  默认1
            ],
            //文件上传
            'file' => [
                'file_data_field' => 'Filedata',//上传文件的文件域名称
                'max_size' => 20 * 1024,//最大上传大小 20M
                'ext' => 'doc,docx,xls,xlsx,ppt,htm,html,txt,zip,rar,gz,bz2',
                'upload_path' => './uploads/file/',//默认上传目录
                'rename' => 1,//是否重命名, 0-否 1-是  默认1
            ],
            //媒体相关上传
            'media' => [
                'file_data_field' => 'Filedata',//上传文件的文件域名称
                'max_size' => 200 * 1024,//最大上传大小 200M
                'ext' => 'swf,flv,mp3,wav,wma,wmv,mid,avi,mpg,asf,rm,rmvb',//扩展名
                'upload_path' => './uploads/media/',//默认上传目录
                'rename' => 1,//是否重命名, 0-否 1-是  默认1
            ],
        ],
    ];

    public function __construct()
    {
        $params = require(__DIR__ . '/params.php');
        static::$all_config = array_merge(static::$all_config, $params);
    }

    /**
     *  表单图片上传
     * @param string act  form
     * @param integer image_type 1：默认路径  2 ：后台战队图片目录 3：用户端战队图片 4： 个人头像  其它： 公共目录
     * @param string file_data_field
     * @return array
     */
    public static function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['code' => DataPackager::INVALID_UPLOAD, 'msg' => '无效上传'];
        }
        //所有参数
        $imageType = isset($_POST['image_type']) && !empty($_POST['image_type']) ? $_POST['image_type'] : 1;
        $fileDataField = isset($_POST['file_data_field']) && !empty($_POST['file_data_field']) ? $_POST['file_data_field'] : 'Filedata';
        $rename = $imageType == 2 ? false : true;//不进行重命名的图片类型

        // 根据上传类型获得保存的路径
        $uploadPath = static::getImageSavePath($imageType);

        //不重命名的时候， 验证名称不能含中文
        if (!$rename && isset($_FILES[$fileDataField]['name'])) {
            if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $_FILES[$fileDataField]['name'])) {
                return ['code' => DataPackager::CHINESE_FILE_NAME, 'msg' => '文件名不能包含中文'];
            }
        }


        //执行上传参数 type=image
        $params = [
            'file_data_field' => $fileDataField,
            'type' => 'image',// ** 目前只支持图片上传 **
            'upload_path' => $uploadPath,
            'rename' => $rename,
        ];
        //验证上传类型
        $allowUploadTypes = array_keys(static::$all_config['upload_config']);//允许上传的类型
        $type = isset($params['type']) ? $params['type'] : 'image'; //上传类型  images|file  默认图片类型
        if (!in_array($type, $allowUploadTypes)) {
            return ['code' => DataPackager::INVALID_UPLOAD_TYPE, 'msg' => '无效上传类型'];
        }

        //参数赋值
        $typeConfig = static::$all_config['upload_config'][$type];
        $fileDataField = isset($params['file_data_field']) ? $params['file_data_field'] : $typeConfig['file_data_field']; //上传文件的文件域名称
        $rename = isset($params['rename']) ? $params['rename'] : $typeConfig['rename']; //是否重命名, 0-否 1-是  默认0
        $ext = isset($params['ext']) ? $params['ext'] : $typeConfig['ext']; //是允许上传的文件扩展名 默认图片扩展
        $maxSize = isset($params['max_size']) ? $params['max_size'] : $typeConfig['max_size']; //最大文件大小, 默认500K
        $uploadPath = isset($params['upload_path']) ? $params['upload_path'] : $typeConfig['upload_path']; //上传目录， 默认为全局图片上传目录

        $uploadPath .= '/' . date('Ymd');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        if (!is_dir($uploadPath)) {
            return ['code' => DataPackager::FAILED_CREATE_DIR, 'msg' => '创建目录失败'];
        }

        //方法1：使用ci上传类库上传 （更安全）
        $config = [
            'upload_path' => $uploadPath,
            'allowed_types' => implode("|", explode(",", $ext)),
            'max_size' => $maxSize,
            'encrypt_name' => (bool)$rename,
        ];

        require_once 'Upload.php';
        $uploadModel = new Upload($config);
        $ret = $uploadModel->do_upload($fileDataField);
        if (!$ret) {
            return ['code' => DataPackager::UPLOAD_FAILED, 'msg' => implode("|", $uploadModel->error_msg)];
        }
        $uploadData = $uploadModel->data();

        //获取文件完整url路径
        $fullPath = $uploadData['full_path'];
        $fullPath = str_replace("\\", "/", $fullPath);
        $documentRoot = str_replace("/", "\\/", str_replace("\\", "/", $_SERVER['DOCUMENT_ROOT']));
        $url = preg_replace('/^' . $documentRoot . '/', "", $fullPath);
        $url = static::_getHostName() . trim($url, '/');
        return ['code' => DataPackager::OK, 'msg' => '上传成功', 'url' => $url];
    }

    /**
     * 字节流上传
     * @param string act  encode
     * @param integer image_type 1：默认路径  2 ：后台战队图片目录 3：用户端战队图片 4： 个人头像  其它： 公共目录
     * @param string file_data_field
     * @param string name
     * @return array
     */
    public static function uploadImgByte()
    {
        $type = 'image';
        $typeConfig = static::$all_config['upload_config'][$type];
        $fileDataField = isset($_POST['file_data_field']) && !empty($_POST['file_data_field']) ? $_POST['file_data_field'] : $typeConfig['file_data_field'];

        //所有参数
        $imageType = isset($_POST['image_type']) && !empty($_POST['image_type']) ? $_POST['image_type'] : 1;

        $file = $_POST[$fileDataField];
        if (empty($file)) {
            return ['code' => DataPackager::INVALID_UPLOAD, 'msg' => '无效上传'];
        }

//		//方法1 ：直接返回上传图片内容
//		if(!preg_match("/data:image\/(png|jpg|jpeg)/i", $file))
//		{
//			return ['errno'=>1, 'msg'=>'无效图片上传'];
//		}
//		$url = '<img src=\''.$file.'\' alt=\'\'>';
//		return ['errno'=>0, 'msg'=>'上传成功','url'=>$url];


        //方法2：重写图片文件
        //参数赋值
        $maxSize = isset($params['max_size']) ? $params['max_size'] : $typeConfig['max_size']; //最大文件大小, 默认500K
        // 根据上传类型获得保存的路径
        $uploadPath = static::getImageSavePath($imageType);

        $uploadPath .= '/' . date('Ymd');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        //上传大小验证
        if (strlen($file) > ($maxSize * 1024)) {
            return ['code' => DataPackager::FILE_SIZE_EXCEEDS_LIMIT, 'msg' => '上传文件大小超过限制'];
        }

        if (strpos($file, 'data:image/png;base64,') === false && strpos($file, 'data:image/jpg;base64,') === false && strpos($file, 'data:image/jpeg;base64,') === false) {
            return ['code' =>  DataPackager::NO_TEMPORARY_FILE, 'msg' => 'empty upload'];
        }
        $fileString = '';
        $fileType = '';
        if (strpos($file, 'data:image/png') > -1 ) {
            $fileString = str_replace('data:image/png;base64,', '', trim($file));
            $fileType = '.png';
        } elseif (strpos($file, 'data:image/jpg') > -1 ) {
            $fileString = str_replace('data:image/jpg;base64,', '', trim($file));
            $fileType = '.jpg';
        } elseif (strpos($file, 'data:image/jpeg') > -1 ){
            $fileString = str_replace('data:image/jpeg;base64,', '', trim($file));
            $fileType = '.jpeg';
        }

        $file = base64_decode($fileString); //截图得到的只能是png格式图片，所以只要处理png就行了
        $name = md5(time() . rand(100000, 999999)) . $fileType; // 这里把文件名做了md5处理
        $tempName = md5(time() . rand(100000, 999999) . rand(1, 100000)) . $fileType; // 这里把文件名做了md5处理

        //获取文件完整url路径
        $targetFile = str_replace("\\", "/", rtrim($uploadPath, '/') . '/' . $name);
        $tempFile = str_replace("\\", "/", rtrim($uploadPath, '/') . '/' . $tempName);
        $documentRoot = str_replace("/", "\\/", str_replace("\\", "/", $_SERVER['DOCUMENT_ROOT']));
        $url = preg_replace('/^' . $documentRoot . '/', "", $targetFile);
        $url = static::_getHostName() . '/' . trim($url, '/');
        //1 保存临时文件
        if (file_put_contents($tempFile, $file) == false) {
            return ['code' => DataPackager::NO_TEMPORARY_FILE, 'msg' => '保存临时文件失败'];
        }

        $im = $fileType == '.png' ? imagecreatefrompng($tempFile) : imagecreatefromjpeg($tempFile);
        unlink($tempFile);//删除临时文件
        if ($im) {
            $sign = $fileType == '.png' ? imagepng($im, $targetFile) : imagejpeg($im, $targetFile);//写入图片
            if ($sign) {
                return ['code' => DataPackager::OK, 'msg' => '上传成功', 'url' => str_replace('./', '', $url)];
            }
        }
        return ['code' =>  DataPackager::UPLOAD_FAILED, 'msg' => '上传失败'];
    }


    // 根据上传类型获得保存的路径
    private static function getImageSavePath($imageType = 1)
    {
        switch ($imageType) {
            case 1://默认图片保存路径
                return './uploads/images';
            case 2://战队编辑图片保存路径
                return './uploads/team/img';
            case 3: //用户端战队图片保存路径
                return './uploads/team/frontend/img';
            case 4: //用户端个人头像
                return './uploads/personal';
            case 5: //数据录入
                return './uploads/dc';
            default:
                return './uploads/common/'.$imageType;
        }
    }

    private static function _getHostName()
    {
        if (isset(static::$all_config['uploadService'])) {
            return static::$all_config['uploadService'];
        } else {
            $http = static::getIsSecureConnection() ? 'https' : 'http';
            if (isset($_SERVER['HTTP_HOST'])) {
                if (strpos($_SERVER['HTTP_HOST'], ':') > 0) {
                    return strpos($_SERVER['HTTP_HOST'], ':80/') > 0 ? $http . '://' . str_replace(':80/', '/', $_SERVER['HTTP_HOST']) . '/' : $http . '://' . $_SERVER['HTTP_HOST'] . '/';
                } else {
                    $port = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] !== '80' ? ':' . $_SERVER['SERVER_PORT'] : '';
                    $port .= '/';
                    return $http . '://' . $_SERVER['HTTP_HOST'] . $port;
                }
            } elseif (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] != '-') {
                $port = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] !== '80' ? ':' . $_SERVER['SERVER_PORT'] : '';
                $port .= '/';
                return $http . '://' . $_SERVER['SERVER_NAME'] . $port;
            } elseif (isset($_SERVER['HTTP_HOST'])) {
                return $http . '://' . $_SERVER['HTTP_HOST'] . '/';
            }
        }
    }

    private static function getIsSecureConnection()
    {
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
            || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'],
                'https') === 0;
    }

    /**
     *  获取某类型上传的图片
     *
     * @param string act  get
     * @param integer image_type 1：默认路径  2 ：后台战队图片目录 3：用户端战队图片 4： 个人头像  其它： 公共目录
     * @return array
     */
    public static function getImg()
    {
        // 非post 返回空数组
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['code' => DataPackager::INVALID_UPLOAD, 'msg' => '无效操作'];
        }
        //获取图片的类型
        $imageType = isset($_POST['image_type']) && !empty($_POST['image_type']) ? $_POST['image_type'] : 1;
        $filePath = static::getImageSavePath($imageType);
        $url = static::_getHostName();
        $allTeamImg = static::scanFiles($filePath);
        foreach ($allTeamImg as & $img) {
            $img = str_replace('./', '',
                $url . $img); // str_replace(rtrim(str_replace('\\', '/', ROOT_PATH), '/'), '', $img);
        }
        return ['code' => DataPackager::OK, 'data' => $allTeamImg];
    }

    /**
     *  表单图片上传
     * @param string act  bi_form
     * @param integer image_type 1：默认路径  2 ：后台战队图片目录 3：用户端战队图片 4： 个人头像  其它： 公共目录
     * @return array
     */
    public static function bi_upload()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['code' => DataPackager::INVALID_UPLOAD, 'msg' => '无效上传'];
        }
        //所有参数
        $imageType = isset($_POST['image_type']) && !empty($_POST['image_type']) ? $_POST['image_type'] : 1;
        $fileDataField = 'Filedata';
        $rename = $imageType == 2 ? false : true;//不进行重命名的图片类型

        // 根据上传类型获得保存的路径
        $uploadPath = static::getImageSavePath($imageType);
        $isMulti = $_POST['multi'];

        //参数赋值
        $typeConfig = static::$all_config['upload_config']['image'];
        $uploadPath .= '/' . date('Ymd');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        if (!is_dir($uploadPath)) {
            return ['code' => DataPackager::FAILED_CREATE_DIR, 'msg' => '创建目录失败'];
        }
        //获取文件完整url路径
        $fullPath = $uploadPath;
        $fullPath = str_replace("\\", "/", $fullPath);
        $fileSizeLimit = empty($_POST['fileSizeLimit']) ? $typeConfig['max_size']*1024 : $_POST['fileSizeLimit'];
        if ($isMulti=="true") {
            $fileList = $_FILES[$fileDataField];
            $error_arr = [];
            $url_arr = [];
            foreach ($fileList['name'] as $key => $value) {
                //不重命名的时候， 验证名称不能含中文
                if (!$rename && isset($value)) {
                    if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $value)) {
                        $error_arr[] = $value . '文件名不能包含中文';
                        continue;
                    }
                }
                if ($fileList['size'][$key] > $fileSizeLimit) {
                    $error_arr[] = $value . '上传文件大小超过限制';
                    continue;
                }
                $fileName = $value;
                $fileArr = explode('.', $fileName);
                $fileType = $fileArr[count($fileArr)-1];
                if (strpos($fileType, $typeConfig['ext'])!== false) {
                    $error_arr[] = $value.'上传类型错误';
                    continue;
                }
                if ($rename) {
                    $fileName = md5(substr($fileName, strpos('.'.$fileType, $fileName)).time().rand(100,999)).'.'.$fileType;
                }
                if (@move_uploaded_file($fileList['tmp_name'][$key], "$fullPath/$fileName")){
                    @unlink($_FILES[$fileDataField]['tmp_name']);
                    $documentRoot = str_replace("/", "\\/", str_replace("\\", "/", $_SERVER['DOCUMENT_ROOT']));
                    $url = preg_replace('/^' . $documentRoot . '/', "", "$fullPath/$fileName");
                    $url = preg_replace('/^\.\//', "", $url);
                    $url = static::_getHostName() . trim($url, '/');
                    $url_arr[] = $url;
                } else {
                    $error_arr[] = $value.'上传失败';
                    continue;
                }
            }
            $msg = '上传成功';
            if (!empty($error_arr)) {
                $msg = '部分上传成功'.implode("|", $error_arr);
            }
            return ['code' => DataPackager::OK, 'msg' => $msg, 'url' => $url_arr];

        } else {
            //不重命名的时候， 验证名称不能含中文
            if (!$rename && isset($_FILES[$fileDataField]['name'])) {
                if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $_FILES[$fileDataField]['name'])) {
                    return ['code' => DataPackager::CHINESE_FILE_NAME, 'msg' => '文件名不能包含中文'];
                }
            }
            if ($_FILES[$fileDataField]['size'] > $fileSizeLimit) {
                return ['code' => DataPackager::FILE_SIZE_EXCEEDS_LIMIT, 'msg' => '上传文件大小超过限制'];
            }
            $fileName = $_FILES[$fileDataField]['name'];
            $fileArr = explode('.', $fileName);
            $fileType = $fileArr[count($fileArr)-1];
            if (strpos($fileType, $typeConfig['ext'])!== false) {
                return ['code' => DataPackager::FAILED_CREATE_DIR, 'msg' => '上传类型错误'];
            }
            if ($rename) {
                $fileName = md5(substr($fileName, strpos('.'.$fileType, $fileName)).time().rand(100,999)).'.'.$fileType;
            }
            if (@move_uploaded_file($_FILES[$fileDataField]['tmp_name'], "$fullPath/$fileName")){
                @unlink($_FILES[$fileDataField]['tmp_name']);
                $documentRoot = str_replace("/", "\\/", str_replace("\\", "/", $_SERVER['DOCUMENT_ROOT']));
                $url = preg_replace('/^' . $documentRoot . '/', "", "$fullPath/$fileName");
                $url = preg_replace('/^\.\//', "", $url);
                $url = static::_getHostName() . trim($url, '/');
                return ['code' => DataPackager::OK, 'msg' => '上传成功', 'url' => $url];
            } else {
                return ['code' => DataPackager::FAILED_CREATE_DIR, 'msg' => '上传失败'];
            }
        }
    }

    /**
     * 返回相对应的文件地址文件名
     * @param $dir
     * @return array
     */
    private static function scanFiles($dir)
    {
        if (!is_dir($dir)) {
            return [];
        }
        // 兼容各操作系统 DIRECTORY_SEPARATOR
        $dir = rtrim(str_replace('\\', '/', $dir), '/') . '/';
        // 栈，默认值为传入的目录
        $dirs = [$dir];
        // 放置所有文件的容器
        $rt = [];
        do {
            // 弹栈
            $dir = array_pop($dirs);
            // 扫描该目录
            $tmp = scandir($dir);
            foreach ($tmp as $f) {
                // 过滤. .. trait文件
                if ($f == '.' || $f == '..') {
                    continue;
                }
                // 组合当前绝对路径
                $path = $dir . $f;
                // 如果是目录，压栈。
                if (is_dir($path)) {
                    array_push($dirs, $path . '/');
                } else {
                    $rt[] = $path;
                }
            }
        } while ($dirs); // 直到栈中没有目录
        return $rt;
    }
}