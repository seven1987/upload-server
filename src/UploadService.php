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

    public static function checkValue($var,$default){
        return   (isset($var) && !empty($var))?$var:$default;
    }

    /**
     *  表单图片上传
     * @param string act  form          //行为
     * @param bool rename              //是否改名
     * @param bool enable_date_folder  //是否按日期分文件夹
     * @param string folder
     * @param string file_data_field
     * @return array
     */
    public static function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['code' => DataPackager::INVALID_UPLOAD, 'msg' => '无效上传'];
        }
        //所有参数
        $fileDataField = static::checkValue($_POST['file_data_field'] , 'Filedata');
        $rename = static::checkValue( $_POST['rename'], 0) ;//是否重命名
        $folder = static::checkValue( $_POST['folder'], 'common');
        $uploadPath = static::getUploadPath($folder);

        //是否启用分日期的文件夹
        $folderByDate = static::checkValue($_POST['enable_date_folder'],0); //最大文件大小, 默认500K
        if($folderByDate){
            $uploadPath .= '/' . date('Ymd');
        }

        if (!boolval($rename) && isset($_FILES[$fileDataField]['name'])) {
            //不重命名的时候， 验证名称不能含中文
            $filename =$_FILES[$fileDataField]['name'];
            if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $filename)) {
                return ['code' => DataPackager::CHINESE_FILE_NAME, 'msg' => '文件名不能包含中文'];
            }
            //不重命名的时候， 验证文件是否已存在
            $filepath = $uploadPath.'/'.$filename;
            if(file_exists($filepath)){
                return ['code' => DataPackager::FILE_EXIST, 'msg' => '文件已经存在'];
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

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        if (!is_dir($uploadPath)) {
            return ['code' => DataPackager::FAILED_CREATE_DIR, 'msg' => '创建目录失败'.$uploadPath];
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

    public static function getUploadPath($folder){
        $prefixFolder = './uploads/';
        $uploadPath = $prefixFolder.$folder;
        return $uploadPath;
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
        $fileDataField = static::checkValue($_POST['file_data_field'] , 'Filedata');
        $folder = static::checkValue( $_POST['folder'], 'common');
        $uploadPath = static::getUploadPath($folder);

        //是否启用分日期的文件夹
        $folderByDate =  static::checkValue($_POST['enable_date_folder'], false); //最大文件大小, 默认500K
        if(!$folderByDate){
            $uploadPath .= '/' . date('Ymd');
        }
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

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
        $maxSize = $typeConfig['max_size']; //最大文件大小, 默认500K

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
     *  获取某文件夹上传的图片
     *
     * @param string act  get
     * @param string folder
     * @return array
     */
    public static function getImg()
    {
        // 非post 返回空数组
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['code' => DataPackager::INVALID_UPLOAD, 'msg' => '无效操作'];
        }
        $folder = static::checkValue( $_POST['folder'], 'common');
        $filePath = static::getUploadPath($folder);
        $url = static::_getHostName();
        $allTeamImg = static::scanFiles($filePath);
        foreach ($allTeamImg as & $img) {
            $img = str_replace('./', '',
                $url . $img); // str_replace(rtrim(str_replace('\\', '/', ROOT_PATH), '/'), '', $img);
        }
        return ['code' => DataPackager::OK, 'data' => $allTeamImg];
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