<?php
/**
 * 1.方法范围 全局公共使用的基本方法,包括字符处理,缓存处理,配置文件引用,安全过滤等等.
 * 2.所有的方法都是静态的public方法
 * 3.该类为Controller|Model提供辅助静态方法,目的是保持Controller|Model的纯洁性及代码的可重用性 
 */
namespace library\Helper;

class HCommon {
    
    //POST|GET数据类型定义
    const _INT = 10;
    const _FLOAT = 11;
    const _EMAIL = 12;
    const _STR = 13;
    const _URL = 14;
    const _IP = 15;
    const _TEXT = 16;
    
    //文件上传模块定义
    const _ARTICLE = 10;//文章模块
    const _AVATAR = 11;//用户头像模块
    const _PPT = 12;//教师课件模块
    
    
    /**
     * 获取主机名
     * @return string (例如 www.baidu.com) 
     * 
     */
    public static function getHost()
    {
        $host = isset($_SERVER ['HTTP_HOST']) ? $_SERVER ['HTTP_HOST'] : $_SERVER ['SERVER_NAME'];
        return $host;   
    }
    
    /**
     * 获取二级域名名称
     * @return string (例如www.baidu.com中的www) 
     * 
     */
    public static function getSubDomain()
    {
        return str_replace(strchr(self::getHost(),"."),"",self::getHost());
    }
    
    public static function getDefConfig($file = 'common')
    {
        $fullPath = dirname(BASE_PATH)."/config/def/{$file}.php";
        if(file_exists($fullPath)){
            return require_once($fullPath);
        }
        return FALSE;
    }
    
    //----------以下为Cookie与Session的相关处理-------------
     /**
     *
     * @todo 设置 SESSION
     * @param unknown $data        	
     * @return boolean
     */
    public static function setSession($name, $data, $expire = NULL, $_cookieId = NULL) {
        if (!$data || !is_array($data))
            return false;

        $defConfig = self::getDefConfig();
        $expire = is_null($expire) ? $defConfig['expires'] : $expire;
        $name = self::_getIdentify($name, $_cookieId);
        self::setCache($name, $data, $expire);
        return true;
    }
    
    
    /**
     *
     * @todo 取得SESSION
     * @param String $key        	
     * @return Ambigous <boolean, unknown>
     */
    public static function getSession($name, $key, $_cookieId = NULL) {
        $name = self::_getIdentify($name, $_cookieId);
        return self::getCache($name, $key);
    }
    
    /**
     * 获取当前登录账户的Cookie唯一标识 _identify
     */
    public static function _getIdentify($key = NULL, $_cookieId = NULL) {
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
     *
     * @todo 设置 COOKIE
     * @param String $name        	
     * @param String|Array $value        	
     * @param string $time        	
     */
    public static function setCookie($name, $value, $time = null) {
        $defConfig = self::getDefConfig();
        $domain = self::getHost();
        $time = $time ? $time : time() + $defConfig['expires']; // expires for cookie

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
    
    
    //----------------------以下為文件與目錄處理相關函數---------------
    /**
     *
     * @todo 生成目录
     * @param string $string        	
     * @return boolean
     */
    public static function mkdir($string) {
        $pattern = '/^([\S]+\/)+/';
        if (preg_match($pattern, $string)) {
            $fullPath = "";
            $dirArray = explode("/", $string);
            foreach ($dirArray as $each_d) {
                $fullPath .= $each_d . "/";
                if (!is_dir($fullPath)) {
                    @mkdir($fullPath, 511);
                }
            }
            return true;
        }
        return false;
    }
    
    
    /**
      * 获取文件扩展名
      * @return string
      */
    public static function getFileExt($filepath)
    {
        $ext = strrchr( $filepath , '.' );
        $ext = strtolower(substr($ext,1));
        return $ext;
    }
    
    
    /**
	 * 依据文档字节数显示对应的单位,如100MB,10GB,200KB
	 * @param undefined $bytes 文件字节数
	 *
	 */
	public static function getFileSize($bytes){
		if ($bytes >= pow(2,40)) {
			$return = round($bytes/pow(1024, 4),2);
			$suffix="T";
		}elseif ($bytes >= pow(2,30)) {
			$return = round($bytes/pow(1024, 3),2);
			$suffix="G";
		}elseif ($bytes >= pow(2,20)) {
			$return = round($bytes/pow(1024, 2),2);
			$suffix="M";
		}elseif ($bytes >= pow(2,10)) {
			$return = round($bytes/pow(1024, 1),2);
			$suffix="K";
		}else{
			$return = $bytes;
			$suffix="B";
		}
		return $return.$suffix;
	}
    
    //---------------------以下為字符處理相關函數------------------
    /**
     * 取得随机数
     *
     * @param int $length        	
     * @param boolean $isNum        	
     * @return string
     */
    public static function random($length, $isNum = FALSE) {
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
    public static function cutStr($string, $sublen, $add = '&#8230;', $start = 0, $code = 'UTF-8') {
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
    
    /**
     * 简化ajax请求时的输出操作 
     * @param undefined $data array | string
     * 
     */
    public static function outJson($data = NULL)
    {
        if(is_array($data)){
            echo json_encode($data);
        }else{
            echo $data;
        }
        exit(0);
    }
    
    
    //---------------------以下为缓存处理相关函数---------------------------
    /**
     *
     * @todo 链接Memcache
     * @return \Memcache
     */
    public static function localCache() {
        $defConfig = self::getDefConfig();
        $mc = new \Memcache ();
        $mc->connect($defConfig['memcache']['host'], $defConfig['memcache']['port']);
        return $mc;
    }


	/**
	 * 一键清除所有Memcache缓存 
	 * 
	 */
	public static function clearAllCache()
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
    public static function setCache($key, $value, $time = null) {
        $defConfig = self::getDefConfig();
        $key = self::mapCacheKey($key);
        if (strlen($key) > $defConfig['memcache']['key_size'])
            return false;
        $time = $time ? $time : $defConfig['memcache']['expires'];
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
    public static function getCache($key, $item = NULL) {
        $defConfig = self::getDefConfig();
        $key = self::mapCacheKey($key);
        if (strlen($key) > $defConfig['memcache']['key_size'])
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
    }

    /**
     *
     * @todo 删除缓存
     * @param string $key        	
     * @return boolean
     */
    public static function delCache($key) {
        $defConfig = self::getDefConfig();
        $key = self::mapCacheKey($key);
        if (strlen($key) > $defConfig['memcache']['key_size'])
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
    public static function mapCacheKey($key = NULL) {
        if(empty($key)){
            return FALSE;
        }
        return $key .= self::getHost();
    }
    
    
    //===============页面flash提示信息读取与写入相关方法=======================
	
	/**
	 * 设置提示消息内容 
	 * @param string $key success|error|massage
	 * @param array $msg array('title'=>"标题",'message'=>"提示详细信息")
	 * @param int $expired
	 * 
	 */
	public static function setFlash($key,$msg,$expired = NULL)
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
	public static function getFlash()
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
	public static function getFlashKeys()
	{
		return array('success','error','massage');
	}
    
    
    /**
     * 用户POST|GET数据过滤 
     * @param undefined $str
     * @param undefined $type
     * 
     */
    public static function filterStr($str,$type = self::_STR)
    {
        switch($type){
            case self::_INT:
                $str = intval($str);
            break;
            case self::_EMAIL:
                $str = (string)filter_var($str,FILTER_VALIDATE_EMAIL);
            break;
            case self::_STR:
                $str = (string)filter_var($str,FILTER_SANITIZE_STRING);
            break;
            case self::_URL:
                $str = (string)filter_var($str,FILTER_VALIDATE_URL);
            break;
            case self::_IP:
                $str = (string)filter_var($str,FILTER_VALIDATE_IP);
            break;
            case self::_TEXT:
                $str = (string)filter_var($str,FILTER_UNSAFE_RAW);
            break;
        }
        return $str;
    }
}
?>