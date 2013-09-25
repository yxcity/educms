<?php

namespace module\Application\src\Model;

use Zend\Log\Writer\Stream;
use Zend\Log\Logger;
use Zend\Session\Config\StandardConfig;
use Zend\Session\SessionManager;
use Zend\Session\Container;
use Zend\Filter\StringTrim;
use Zend\Filter\HtmlEntities;
use Zend\Filter\StripTags;
use Zend\Validator\File\Size;
use Zend\Validator\File\IsImage;
use Zend\Validator\File\Extension;
use Zend\File\Transfer\Adapter\Http;
use Admin\Model\Msg;

final class Tool {

    private static $_instance = null;

    private function __construct() {
        
    }

    private function __clone() {
        
    }

    public static function getInstance() {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self ();
        }
    }

    /**
     *
     * @todo 验证网址
     * @param unknown $url        	
     * @return number
     */
    static function checkUrl($url) {
        return preg_match('/^http(s)*:\/\/[_a-zA-Z0-9-]+(.[_a-zA-Z0-9-]+)*$/', $url);
    }

    /**
     *
     * @todo 过滤非汉字
     * @param String $str        	
     * @return string
     */
    static function hanzi($str) {
        // $str = mb_convert_encoding($str, 'UTF-8', 'GB2312');
        preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $str, $matches);
        $str = join('', $matches [0]);
        // $str = mb_convert_encoding($str, 'GB2312', 'UTF-8');
        return $str;
    }

    /**
     *
     * @todo 设置 SESSION
     * @param unknown $data        	
     * @return boolean
     */
    static function setSession($name, $data, $expire = NULL, $_cookieId = NULL) {
        if (!$data || !is_array($data))
            return false;

        $expire = is_null($expire) ? 7200 : $expire;
        $name = self::_getIdentify($name, $_cookieId);
        self::setCache($name, $data, $expire);
        /*
         * $config = new StandardConfig (); $config->setOptions ( array ( 'remember_me_seconds' => 1800, 'name' => 'zf2' ) ); $manager = new SessionManager ( $config ); Container::getDefaultManager ( $manager ); $container = new Container ( $name ); foreach ( $data as $key => $val ) { $container->$key = $val; }
         */

        return true;
    }

    /**
     *
     * @todo 取得SESSION
     * @param String $key        	
     * @return Ambigous <boolean, unknown>
     */
    static function getSession($name, $key, $_cookieId = NULL) {
        $name = self::_getIdentify($name, $_cookieId);
        return self::getCache($name, $key);
        /*
         * $container = new Container ( $name ); $data = $container->$key; return $data ? $data : false;
         */
    }

    /**
     * 获取当前登录账户的Cookie唯一标识 _identify
     */
    static function _getIdentify($key = NULL, $_cookieId = NULL) {
        if (!is_null($_cookieId)) {
            $_identify = $_cookieId;
        } else {
            $_identify = isset($_COOKIE ["_identify"]) ? $_COOKIE ["_identify"] : NULL;
        }
        if (!$_identify) {
            return FALSE;
        }
        $_identify = is_null($key) ? $_identify : $_identify . "_" . $key; // 跨域数据唯一标识
        return $_identify;
    }

    /**
     * @文本过滤
     *
     * @param unknown $string        	
     * @param string $tag        	
     * @return string
     */
    static function filter($string, $tag = false) {
        $trim = new StringTrim ();
        $html = new HtmlEntities ();
        $string = $trim->filter($string);
        $string = $html->filter($string);
        if ($tag) {
            $tags = new StripTags ();
            $string = $tags->filter($string);
        }
        return $string;
    }

    /**
     *
     * @todo 写入日志信息
     * @param string $string        	
     * @param string $filename        	
     * @throws \Exception
     */
    public static function log($string, $filename = null) {
        $filename = $filename ? $filename : date('Y-m-d', time()) . '_' . rand(10000, 99999) . '.log';
        self::mkdir('./data/log/');
        $stream = @fopen("./data/log/{$filename}", 'w', false);
        if (!$stream) {
            throw new \Exception('文件文件不存在');
        }
        $writer = new Stream($stream);
        $logger = new Logger ();
        $logger->addWriter($writer);
        $logger->info(PHP_EOL . $string);
    }

    /**
     *
     * @todo 设置 COOKIE
     * @param String $name        	
     * @param String|Array $value        	
     * @param string $time        	
     */
    public static function setCookie($name, $value, $time = null, $all = false) {
        if ($all) {
            $domain = self::getDomain();
        } else {
            $domain = isset($_SERVER ['SERVER_NAME']) ? $_SERVER ['SERVER_NAME'] : 'weixin.youtitle.com'; // cookie 
        }
        $time = $time ? $time : time() + 7200; // expires for cookie

        if (is_array($value)) {
            $value = json_encode($value);
        }
        setcookie($name, $value, $time, '/', $domain);
    }

    /**
     *
     * @todo 取得COOKIE
     * @param unknown $key        	
     * @return Ambigous <boolean, unknown>
     */
    public static function getCookie($key) {
        $data = isset($_COOKIE [$key]) ? $_COOKIE [$key] : false;
        return $data;
    }

    /**
     *
     * @todo 生成目录
     * @param string $string        	
     * @return boolean
     */
    static public function mkdir($string) {
        $pattern = '/^([\S]+\/)+/';
        if (preg_match($pattern, $string)) {
            $fullPath = "";
            $dirArray = explode("/", $string);
            foreach ($dirArray as $each_d) {
                $fullPath .= $each_d . "/";
                if (!is_dir($fullPath)) {
                    @mkdir($fullPath, 511);
                    @fclose(@fopen($string . '/index.html', 'w'));
                }
            }
            return true;
        }
        return false;
    }

    static public function uploadfile($field, $dirname = null, $filename = null, $valid_exts = null) {
        if (!isset($field ['fileField'] ['name']))
            return array(
                'res' => 0,
                'msg' => '文件未上传'
            );
        $size = new Size(array(
            'size' => 20
                ));

        $adapter = new Http ();
        $valid_rules = array(
            $size
        );
        if (is_array($valid_exts)) {
            $exts = new Extension($valid_exts);
            array_push($valid_rules, $exts);
        }
        $adapter->setValidators($valid_rules, $field ['fileField'] ['name']);
        if (!$adapter->isValid()) {
            $dataError = $adapter->getMessages();
            $error = array();
            foreach ($dataError as $key => $row) {
                $error [] = $row;
            }
            return array(
                'res' => 0,
                'msg' => $error
            );
        } else {
            $srcDirname = $dirname ? $dirname : '/uploads/temp/';
            self::mkdir(BASE_PATH . $srcDirname);
            $adapter->setDestination(BASE_PATH . $srcDirname);
            $srcFilename = $field ['fileField'] ['name'];
            if ($adapter->receive($srcFilename)) {
                $filename = self::mvFile($srcDirname . $srcFilename, true, $dirname, $filename);
                return array(
                    'res' => 1,
                    'file' => $filename
                );
            }
        }
    }

    /**
     * 取得随机数
     *
     * @param int $length        	
     * @param boolean $isNum        	
     * @return string
     */
    static public function random($length, $isNum = FALSE) {
        $random = '';
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $num = '0123456789';
        if ($isNum) {
            $sequece = 'num';
        } else {
            $sequece = 'str';
        }
        $max = strlen($$sequece) - 1;
        for ($i = 0; $i < $length; $i++) {
            $random .= ${
                    $sequece} {mt_rand(0, $max)};
        }
        return $random;
    }

    /**
     * 截取指定字符串
     *
     * @param string $string        	
     * @param int $sublen        	
     * @param string $add        	
     * @param int $start        	
     * @param string $code        	
     * @return string
     */
    static function cutStr($string, $sublen, $add = '&#8230;', $start = 0, $code = 'UTF-8') {
        if ($code == 'UTF-8') {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);

            if (count($t_string [0]) - $start > $sublen)
                return join('', array_slice($t_string [0], $start, $sublen)) . $add;
            return join('', array_slice($t_string [0], $start, $sublen));
        } else {
            $start = $start * 2;
            $sublen = $sublen * 2;
            $strlen = strlen($string);
            $tmpstr = '';

            for ($i = 0; $i < $strlen; $i++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    if (ord(substr($string, $i, 1)) > 129) {
                        $tmpstr .= substr($string, $i, 2);
                    } else {
                        $tmpstr .= substr($string, $i, 1);
                    }
                }
                if (ord(substr($string, $i, 1)) > 129)
                    $i++;
            }
            if (strlen($tmpstr) < $strlen)
                $tmpstr .= $add;
            return $tmpstr;
        }
    }

    static function isEMAIL($value) {
        return preg_match('~^[a-z0-9]+([._\-\+]*[a-z0-9]+)*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+\.)+[a-z0-9]+$~i', $value);
    }

    // 文件后缀
    static function fext($filename, $all = false) {
        $info = pathinfo($filename);
        if ($all)
            return $info;
        return $info ['extension'];
    }

    /**
     *
     * @todo 验证文件是否是图片文件
     *      
     * @param string $imgpath        	
     * @return boolean
     */
    static function isImg($imgpath) {
        return (strpos($imgpath, '..') !== FALSE || !file_exists($imgpath) || !in_array(strtolower(self::Fext($imgpath)), array(
                    'jpg',
                    'jpeg',
                    'bmp',
                    'gif',
                    'png'
                )) || (function_exists('getimagesize') && !@getimagesize($imgpath))) ? false : true;
    }

    static function mvFile($file, $isDel = null, $dirname = null, $filename = null) {
        $file = BASE_PATH . $file;
        if (self::isImg($file)) {
            $dirname = $dirname ? $dirname : '/uploads/' . date('Ymd', time()) . '/';
            Tool::mkdir(BASE_PATH . $dirname);
            $filename = $filename ? $filename : self::random(10) . '.' . self::fext($file);
            @copy($file, BASE_PATH . $dirname . $filename);
            if ($isDel) {
                @unlink($file);
            }
            return $dirname . $filename;
        }
        return false;
    }

    /*
     * 求两个已知经纬度之间的距离,单位为米 @param lng1,lng2 经度 @param lat1,lat2 纬度 @return float 距离，单位米
     */

    static function getdistance($lng1, $lat1, $lng2, $lat2) {  // 根据经纬度计算距离

        // 将角度转为狐度
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2; // 两纬度之差,纬度<90
        $b = $radLng1 - $radLng2; // 两经度之差纬度<180
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        $s = ceil($s);
        /*
         * if($s<1000){ $danwei = "米"; }else{ $s = round(($s/1000),1); $danwei = "公里"; } $s .= $danwei;
         */
        return $s;
    }

    /**
     *
     * @todo 数组排序
     * @param Array $arr        	
     * @param string $keys        	
     * @param string $type        	
     * @return multitype:unknown
     */
    static function arraySort($arr, $keys, $type = 'asc') {
        $keysvalue = $newArray = array();
        foreach ($arr as $k => $v) {
            $keysvalue [$k] = $v [$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $newArray [$k] = $arr [$k];
        }
        return $newArray;
    }

    /**
     * @取得二级域名
     *
     * @param String $host        	
     * @return mixed
     */
    static function domain($host = null) {
        if (!$host) {
            $host = self::getHost();
        }
        $d = self::getDomain($host);
        $domain = str_replace(".{$d}", '', $host);
        return $domain;
    }

    /**
     *
     * @todo 取得URl下的域名
     */
    static function getDomain($url = null) {
        if (!$url) {
            $url = self::getHost();
        }
        $pattern = '/[\w-]+\.(com|net|org|gov|cc|biz|info|cn|me|edu|int|us)(\.(cn|hk|tw))*/';
        preg_match($pattern, $url, $matches);
        if (count($matches) > 0) {
            return $matches [0];
        } else {
            $rs = parse_url($url);
            $main_url = $rs ["host"];
            if (!strcmp(long2ip(sprintf("%u", ip2long($main_url))), $main_url)) {
                return $main_url;
            } else {
                $arr = explode(".", $main_url);
                $count = count($arr);
                $endArr = array(
                    "com",
                    "net",
                    "org",
                    "edu"
                ); // com.cn net.cn 等情况
                if (in_array($arr [$count - 2], $endArr)) {
                    $domain = $arr [$count - 3] . "." . $arr [$count - 2] . "." . $arr [$count - 1];
                } else {
                    $domain = $arr [$count - 2] . "." . $arr [$count - 1];
                }
                return $domain;
            }
        }
    }

    /**
     * @取得当前HOST地址
     *
     * @return unknown
     */
    static function getHost() {
        $host = isset($_SERVER ['HTTP_HOST']) ? $_SERVER ['HTTP_HOST'] : $_SERVER ['SERVER_NAME'];
        return $host;
    }

    /**
     *
     * @todo 写入文件
     * @param String $filename        	
     * @param String $content        	
     * @param string $mode        	
     * @param number $chmod        	
     * @return boolean
     */
    static function writeFile($filename, $content, $mode = 'ab', $chmod = 1) {
        $fp = @fopen($filename, $mode);
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $content);
            fclose($fp);
            $chmod && @chmod($filename, 0666);
            return true;
        }
        return false;
    }

    /**
     *
     * @todo 读取文件
     * @param String $filename        	
     * @return string boolean
     */
    static function readFile($filename) {
        $fp = @fopen($filename, 'r');
        if ($fp) {
            $content = fread($fp, filesize($filename));
            fclose($fp);
            return $content;
        }
        return false;
    }

    /**
     *
     * @todo 取得代码对呀信息
     * @param Int $key        	
     */
    static function errorCode($key, $adapter) {
        if (!file_exists('./data/error.php')) {
            $db = new Msg($adapter);
            $db->writeFile();
        }
        $ini = include './data/error.php';
        if (isset($ini [$key])) {
            return $ini [$key];
        }
        return false;
    }

    /**
     * 取得用户客户端ＩＰ
     *
     * @return string
     */
    static public function getIP() {
        if (isset($_SERVER)) {
            if (isset($_SERVER ['HTTP_X_FORWARDED_FOR'])) {
                $aIps = explode(',', $_SERVER ['HTTP_X_FORWARDED_FOR']);
                foreach ($aIps as $sIp) {
                    $sIp = trim($sIp);
                    if ($sIp != 'unknown') {
                        $sRealIp = $sIp;
                        break;
                    }
                }
            } elseif (isset($_SERVER ['HTTP_CLIENT_IP'])) {
                $sRealIp = $_SERVER ['HTTP_CLIENT_IP'];
            } else {
                if (isset($_SERVER ['REMOTE_ADDR'])) {
                    $sRealIp = $_SERVER ['REMOTE_ADDR'];
                } else {
                    $sRealIp = '0.0.0.0';
                }
            }
        } else {

            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $sRealIp = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $sRealIp = getenv('HTTP_CLIENT_IP');
            } else {
                $sRealIp = getenv('REMOTE_ADDR');
            }
        }
        return $sRealIp;
    }

    /**
     *
     * @todo 链接Memcache
     * @return \Memcache
     */
    static function localCache() {
        $mc = new \Memcache ();
        $mc->connect("localhost", 11211);
        return $mc;
    }


	/**
	 * 一键清除所有Memcache缓存 
	 * 
	 */
	static function clearAllCache()
	{
		$cache = self::localCache();
		if(!$cache){
			return FALSE;
		}
		$cache->flush();
	}
	
    /**
     *
     * @todo 将数据写入到 Memcache
     * @param String $key        	
     * @param string $value        	
     * @param string $time        	
     * @return boolean
     */
    static function setCache($key, $value, $time = null) {
        $key = self::mapCacheKey($key);
        if (strlen($key) > 128)
            return false;
        $time = $time ? $time : 3600 * 24 * 15;
        $cache = @self::localCache();
        if (!$cache)
            return false;
        if (!$key) {
            return FALSE;
        }
        if ($cache->get($key)) {
            return $cache->replace($key, $value, MEMCACHE_COMPRESSED, $time);
        }
        return $cache->set($key, $value, MEMCACHE_COMPRESSED, $time);
    }

    /**
     *
     * @todo 读取 Memcache 数据
     * @param String $key        	
     * @return boolean string
     */
    static function getCache($key, $item = NULL) {
        $key = self::mapCacheKey($key);
        if (strlen($key) > 128)
            return false;
        $cache = @self::localCache();
        if (!$cache)
            return false;

        if (!$key) {
            return FALSE;
        }

        $data = $cache->get($key);
        if (!empty($item) && isset($data [$item])) {
            return $data [$item];
        }
        return $data;
        // return $cache->get ( $key );
    }

    /**
     *
     * @todo 删除缓存
     * @param string $key        	
     * @return boolean
     */
    static function delCache($key) {
        $key = self::mapCacheKey($key);
        if (strlen($key) > 128)
            return false;
        $cache = @self::localCache();
        if (!$cache)
            return false;
        if (!$key) {
            return FALSE;
        }
        $cache->delete($key);
    }

    /**
     * 取得带有域的缓存key 例如 testyourdomain.com
     * 
     * @param undefined $key        	
     *
     */
    static function mapCacheKey($key) {
        return $key .= self::getDomain();
    }

    /**
     * 映射分类名称
     *
     * @param undefined $classid        	
     * @return string
     */
    public static function mapClassLabel($classid = 0) {
        $data = self::getClasses();
        if (isset($data [$classid])) {
            return $data [$classid];
        }
        return "-"; // 未知分类
    }

    /**
     * 获取所有分类类型
     */
    public static function getClasses() {
        $data = array(
            10 => "商品分类",
            11 => "新闻分类",
            12 => "公告帮助分类",
            30 => "关于我们",
            40 => "联系我们",
            50 => "招聘信息",
            60 => "公司动态",
            70 => "合作加盟",
            90 => "公司公告"
        );
        return $data;
    }

    /**
     * 别名映射 
     * @param undefined $classid
     * 
     */
    public static function mapAliasKey($classid = 0)
    {
        $data = self::getAliasMapping();
        if(isset($data[$classid])){
            return $data[$classid];
        }
        return NULL;
    }
    
    public static function mapAliasIndex($key)
    {
        $data = self::getAliasMapping();
        foreach($data as $index=>$val){
            if($val == $key){
                return $index;
            }
        }
        return 0;    
    }
    
    public static function getAliasMapping()
    {
        return array(
                    30 => "_aboutus",
                    40 => "_contact",
                    50 => "_zhaopin",
                    60 => "_news",
                    70 => "_hezuo",
                    90 => "_bulletin"
              );    
    }
    
    /**
     * 生成权限勾选树
     * 
     * @param undefined $options
     *        	用于选择的权限范围
     * @param undefined $existed
     *        	当前已经拥有的权限
     *        	
     */
    public static function genAclCheckTree($options, $existed = array()) {
        $html = "<ul>";
        if (empty($options)) {
            return $html;
        }
        foreach ($options as $opt) {
            $checked = in_array($opt ['acl_id'], $existed) ? "checked='checked'" : "";
            $class = ($opt ['parent_id'] > 0 && count($opt ['sub_tree']) == 0) ? "sub_item" : "item";
            $html .= "<li class='$class'><input type='checkbox' name='acl_id[]' $checked value='" . $opt ['acl_id'] . "'/> " . $opt ['acl_name'];
            if (count($opt ['sub_tree']) > 0) {
                $html .= self::genAclCheckTree($opt ['sub_tree'], $existed);
            }
            $html .= "</li>";
        }
        $html .= "</ul>";
        return $html;
    }

    /**
     * 生成分类的节点树结构
     *
     * @param undefined $rows        	
     * @param $defOpt 选择项的默认值        	
     * @param $this_id 当前分类自身的ID        	
     * @return html
     */
    public static function getTypeTree($rows, $name = "pid", $id = "pid", $defOpt = NULL, $this_id = NULL,$classid = 0,$deepIdx = 0) {
        $select = "<select name='$name' id='$id'>";
		if($classid != 11){//不为新闻分类时才有此选择项,否则直接显示顶级分类[公司动态]
			$select .= "<option value='0'>--请选择--</option>";	
		}
        $select .= self::genOptionHtml($rows, $deepIdx, $defOpt, $this_id);
        $select .= "</select>";
        return $select;
    }

    /**
     *
     * @param undefined $rows        	
     * @param undefined $deepIdx        	
     * @param undefined $defOpt
     *        	选择项的默认值
     * @param undefined $this_id
     *        	当前分类自身的ID
     *        	
     */
    public static function genOptionHtml($rows, $deepIdx = 0, $defOpt = NULL, $this_id = NULL) {
        $option = "";
        if (empty($rows)) {
            return $option;
        }
        $se = self::genLayerDiv($deepIdx);
        foreach ($rows as $r) {
            $name = $r ['is_child'] ? $se . $r ['name'] : $r ['name'];
            $selected = $r ['id'] == $defOpt ? "selected='selected'" : "";
            if ($r ['id'] == $this_id) {
                continue;
            }
			if($r['id'] == 60){//60-公司动态-新闻分类下的一级分类
				$option .= "<option value='" . $r ['id'] . "' $selected >--请选择--</option>";	
			}else{
				$option .= "<option value='" . $r ['id'] . "' $selected>" . $name . "</option>";
			}
            
            if (count($r ['sub_tree']) > 0) {
                $option .= self::genOptionHtml($r ['sub_tree'], $deepIdx + 1, $defOpt, $this_id);
            }
        }
        return $option;
    }

    /**
     * 生成用于列表显示的分类树结构
     *
     * @param undefined $rows        	
     *
     */
    public static function genDisplayTreeList($rows, $deepIdx = 0) {
        $displayData = array();
        $se = self::genLayerDiv($deepIdx);
        foreach ($rows as $r) {
            $tmp = array(
                'id' => $r ['id'],
				'domain'=> $r['domain'],
                'name' => $r ['is_child'] ? $se . $r ['name'] : $r ['name'],
                'pid' => $r ['pid'],
                'is_child' => $r ['is_child'],
                'display' => $r ['display'],
                'sorting' => $r ['sorting']
            );
            $displayData [] = $tmp;
            if (count($r ['sub_tree']) > 0) {
                $displayData = array_merge($displayData, self::genDisplayTreeList($r ['sub_tree'], $deepIdx + 1));
            }
        }
        return $displayData;
    }

    /**
     * 依据当前登录账户的角色权限生成相应的导航菜单
     * 
     * @param undefined $rows        	
     *
     */
    public static function genRoleMenu($rows = NULL) {
        $html = '';
        if ($rows) {
            foreach ($rows as $r) {
                if (!$r ['is_menu']) {
                    continue;
                }
                if ($r ['parent_id'] == 0) {
                    if (count($r ['sub_tree']) > 0) {
                        $html .= '<a href="#' . $r ["act_key"] . '-menu" class="nav-header" data-toggle="collapse"><i class="' . $r ["acl_icon"] . '"></i>' . $r ["acl_name"] . '<i class="icon-chevron-up"></i></a>';
                        $html .= '<ul id="' . $r ["act_key"] . '-menu" class="nav nav-list collapse">';
                        $html .= self::genRoleMenu($r ['sub_tree']);
                        $html .= "</ul>";
                    }
                } else {
					$atid = substr(md5($r ['acl_id']),10);
                    $html .= '<li><a href="' . $r ['acl_url'] . '" id='.$atid.'>' . $r ['acl_name'] . '</a></li>';
                }
            }
        }
        return $html;
    }

    /**
     * 生成用于列表显示的操作树结构
     *
     * @param undefined $rows        	
     * @return array()
     *
     */
    public static function genAccessTreeList($rows, $deepIdx = 0) {
        $displayData = array();
        $se = self::genLayerDiv($deepIdx);
        foreach ($rows as $r) {
            $tmp = array(
                'acl_id' => $r ['acl_id'],
                'acl_name' => $r ['parent_id'] ? $se . $r ['acl_name'] : $r ['acl_name'],
                'acl_url' => $r ['acl_url'],
                'act_key' => $r ['act_key'],
                'is_menu' => $r ['is_menu'],
                'parent_id' => $r ['parent_id'],
                'is_child' => $r ['is_child'],
                'acl_sorting' => $r ['acl_sorting']
            );
            $displayData [] = $tmp;
            if (count($r ['sub_tree']) > 0) {
                $displayData = array_merge($displayData, self::genAccessTreeList($r ['sub_tree'], $deepIdx + 1));
            }
        }
        return $displayData;
    }

    /**
     * 依据层级生成对应的分割符
     *
     * @param $deepIdx 当前层级        	
     * @return string 分割符
     */
    public static function genLayerDiv($deepIdx = 0) {
        $div = "";
        if ($deepIdx > 0) {
            for ($i = 0; $i < $deepIdx; $i++) {
                $div .= "|-- ";
            }
        }
        return $div;
    }

    /**
     *
     * @todo 过滤所有空格，回车，换行
     */
    static function loseSpace($pcon) {
        $pcon = preg_replace("/ /", "", $pcon);
        $pcon = preg_replace("/&nbsp;/", "", $pcon);
        $pcon = preg_replace("/　/", "", $pcon);
        $pcon = preg_replace("/\r\n/", "", $pcon);
        $pcon = str_replace(chr(13), "", $pcon);
        $pcon = str_replace(chr(10), "", $pcon);
        $pcon = str_replace(chr(9), "", $pcon);
        return $pcon;
    }

    /**
     *
     * @todo 支付宝基础信息
     * @param string $partner        	
     * @param string $key        	
     * @return string
     */
    static function alipayConfig($partner, $key) {
        // 合作身份者id，以2088开头的16位纯数字
        $alipay_config ['partner'] = $partner;

        // 安全检验码，以数字和字母组成的32位字符
        $alipay_config ['key'] = $key;

        // ↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        // 签名方式 不需修改
        $alipay_config ['sign_type'] = strtoupper('MD5');

        // 字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config ['input_charset'] = strtolower('utf-8');

        // ca证书路径地址，用于curl中ssl校验
        // 请保证cacert.pem文件在当前文件夹目录中
        $alipay_config ['cacert'] = BASE_PATH . '\asset\cacert.pem';

        // 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config ['transport'] = 'http';
        return $alipay_config;
    }

    /**
     *
     * @todo 支付方式
     * @return multitype:string
     */
    static function payment() {
        $ment = array(
            'alipay' => '支付宝'
        );
        return $ment;
    }

    /**
     *
     * @todo 支付宝接口类型
     * @return multitype:string
     */
    static function apiType() {
        $type = array(
            //1 => '使用标准双接口',
           // 2 => '使用担保交易接口',
            3 => '即时到账交易',
            4 => '平台即时到账'
        );
        return $type;
    }

    /**
     *
     * @todo 记录openid 写入COOKIE
     * @param string $s        	
     * @return boolean Ambigous boolean>
     */
    static function openid($s) {
        if (!$s) return false;
        $openid = self::getCache($s);
        if ($openid) {
            $time = time() + 60 * 60 * 24 * 30;
            self::setCookie('openid', $openid, $time);
            self::delCache($s);
            return $openid;
        }
        return false;
    }
    /**
     * 判断是否是手机浏览器
     *
     * @return boolean
     */
    static public function is_mobile() {
        $user_agent = $_SERVER ['HTTP_USER_AGENT'];
        $mobile_agents = Array(
            "240x320",
            "acer",
            "acoon",
            "acs-",
            "abacho",
            "ahong",
            "airness",
            "alcatel",
            "amoi",
            "android",
            "anywhereyougo.com",
            "applewebkit/525",
            "applewebkit/532",
            "asus",
            "audio",
            "au-mic",
            "avantogo",
            "becker",
            "benq",
            "bilbo",
            "bird",
            "blackberry",
            "blazer",
            "bleu",
            "cdm-",
            "compal",
            "coolpad",
            "danger",
            "dbtel",
            "dopod",
            "elaine",
            "eric",
            "etouch",
            "fly ",
            "fly_",
            "fly-",
            "go.web",
            "goodaccess",
            "gradiente",
            "grundig",
            "haier",
            "hedy",
            "hitachi",
            "htc",
            "huawei",
            "hutchison",
            "inno",
            "ipad",
            "ipaq",
            "ipod",
            "jbrowser",
            "kddi",
            "kgt",
            "kwc",
            "lenovo",
            "lg ",
            "lg2",
            "lg3",
            "lg4",
            "lg5",
            "lg7",
            "lg8",
            "lg9",
            "lg-",
            "lge-",
            "lge9",
            "longcos",
            "maemo",
            "mercator",
            "meridian",
            "micromax",
            "midp",
            "mini",
            "mitsu",
            "mmm",
            "mmp",
            "mobi",
            "mot-",
            "moto",
            "nec-",
            "netfront",
            "newgen",
            "nexian",
            "nf-browser",
            "nintendo",
            "nitro",
            "nokia",
            "nook",
            "novarra",
            "obigo",
            "palm",
            "panasonic",
            "pantech",
            "philips",
            "phone",
            "pg-",
            "playstation",
            "pocket",
            "pt-",
            "qc-",
            "qtek",
            "rover",
            "sagem",
            "sama",
            "samu",
            "sanyo",
            "samsung",
            "sch-",
            "scooter",
            "sec-",
            "sendo",
            "sgh-",
            "sharp",
            "siemens",
            "sie-",
            "softbank",
            "sony",
            "spice",
            "sprint",
            "spv",
            "symbian",
            "tablet",
            "talkabout",
            "tcl-",
            "teleca",
            "telit",
            "tianyu",
            "tim-",
            "toshiba",
            "tsm",
            "up.browser",
            "utec",
            "utstar",
            "verykool",
            "virgin",
            "vk-",
            "voda",
            "voxtel",
            "vx",
            "wap",
            "wellco",
            "wig browser",
            "wii",
            "windows ce",
            "wireless",
            "xda",
            "xde",
            "zte"
        );
        $is_mobile = false;
        foreach ($mobile_agents as $device) {
            if (stristr($user_agent, $device)) {
                $is_mobile = true;
                break;
            }
        }
        return $is_mobile;
    }
	
    /**
     * 简化ajax请求时的输出操作 
     * @param undefined $data array | string
     * 
     */
    static function outputJson($data)
    {
        if(is_array($data)){
            echo json_encode($data);
        }else{
            echo $data;
        }
        exit(0);
    }
	
	//===============页面flash提示信息读取与写入相关方法=======================
	
	/**
	 * 设置提示消息内容 
	 * @param string $key success|error|massage
	 * @param array $msg array('title'=>"标题",'message'=>"提示详细信息")
	 * @param int $expired
	 * 
	 */
	static function setFlash($key,$msg,$expired = NULL)
	{
		if(!in_array($key,self::getFlashKeys())){
			return FALSE;
		}	
		
		if(!isset($msg['title']) || !isset($msg['message'])){
			return FALSE;
		}
		$expired = is_null($expired) ? time()+5 : $expired;
		self::setCookie($key,$msg,$expired);
	}
	
	
	/**
	 * 读取消息内容 
	 * 
	 */
	static function getFlash()
	{
		$msg = NULL;
		foreach(self::getFlashKeys() as $key){
			 
	         if ($data = self::getCookie($key))
	         {
	        	$msg[$key]=json_decode($data);
	         }
		}
		return $msg;
	}
	
	/**
	 * 获取消息有效keys 
	 * 
	 */
	static function getFlashKeys()
	{
		return array('success','error','massage');
	}

}