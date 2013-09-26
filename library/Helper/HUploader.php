<?php
namespace library\Helper;
use library\Helper\HCommon;

class HUploader{
    
    
    
    public static function setDestDir()
    {
        $defConfig = HCommon::getDefConfig();
        $destDir = $defConfig['upload_dir'];
        $destDir.="/".HCommon::getSubDomain()."/".date("Ymd")."/";
        if(!is_dir($destDir)){
            HCommon::mkdir($destDir);
        }
        return $destDir;
    
    }
    
    public static function doUpload($fieldname = 'filedata',$module_id = NULL,$options = array())
    {
        $temp = $_FILES[$fieldname];
        
        //文件合法性检测
        if(!is_uploaded_file($temp['tmp_name'])){
            return array(FALSE,"您使用了一个非法的文件上传方式");
        }
        
        $maxSize = intval($options['maxSize']);
        $fileExt = HCommon::getFileExt($temp['name']);
        
        if(isset($options['maxSize']) && $temp['size'] > $maxSize){
            return array(FALSE,"上传的文件大小不得超过".HCommon::getFileSize($maxSize)); 
        }
        if(isset($options['allowedExts']) && is_array($options['allowedExts']) && !in_array($fileExt,$options['allowedExts'])){
            return array(FALSE,"仅允许上传的文件类型为".implode(",",$options['allowedExts']));
        }
        if($temp['error'] > 0){
           return array(FALSE,self::mapError($temp['error'])); 
        }
        
        //上传文件到目标路径
        $destDir = self::setDestDir();
        if(!is_dir($destDir)){
            return array(FALSE,"文件上传路径创建失败");
        }
        
        $destFile = $destDir.HCommon::random(8).".".$fileExt;
        if(!move_uploaded_file($temp['tmp_name'],$destFile)){
            return array(FALSE,"文件上传失败,可能没有设置对应的文件写入权限");
        }
        
        
        if(self::checkIsImage($destFile)){//如果是图片 - 生成缩略图
            
            //a.仅限生成指定大小缩略图的情况,此时缩略图会覆盖原图
            if(isset($options['thumbSize'])){
                if(is_int($options['thumbSize'])){
                    self::cropPhoto($destFile,$destFile,$options['thumbSize'],$options['thumbSize']);    
                }elseif(is_array($options['thumbSize']) && count($options['thumbSize']) == 2){//宽与高两项,0-宽 1-高
                    self::cropPhoto($destFile,$destFile,$options['thumbSize'][0],$options['thumbSize'][1]);
                }
                return array(TRUE,self::_getFileSrc($destFile));
    		}
            
            //b.生成多个缩略图的情况,保留原图
    		$config = self::getImageSizeList($module_id);
    		if($config){
    			$thisFile = explode("/",$destFile);
    			foreach($config as $f){
    				if(is_int($f)){
    					$srcFileName = end($thisFile);
    					$targetFileName = self::_getThumbPrefix($f).$srcFileName;
    					$targetFile = str_replace($srcFileName,$targetFileName,$destFile);
    					self::cropPhoto($destFile,$targetFile,$f,$f);
    				}elseif(is_array($f) && count($f) == 2){//0-宽 1-高
    					$srcFileName = end($thisFile);
    					$targetFileName = self::_getThumbPrefix($f).$srcFileName;
    					$targetFile = str_replace($srcFileName,$targetFileName,$destFile);
    					self::cropPhoto($destFile,$targetFile,$f[0], $f[1]);
    				}	
    			}
    		}
                
        }
        return array(TRUE,self::_getFileSrc($destFile));
    }
    
    /**
     * 得到文件的SRC值,例如文件实际路径为 /usr/project/educms/uploads/path/to/test.jpg
     * 其中HTML的src="#"中的#的值则为 /uploads/path/to/test.jpg
     * 项目的BASE_PATH则为 /usr/project/educms
     * @param undefined $destFile
     * 
     */
    private static function _getFileSrc($destFile)
    {
         return str_replace(BASE_PATH,"",$destFile);   
    }
    
    public static function mapError($errorCode = 0)
    {
        $data = array(
            1 => "您上传的文件太大",
            2 => "表单数据太大,文件无法上传",
            3 => "仅上传成功部分数据",
            4 => "您没有选择任何要上传的文件",
        );
        if(isset($data[$errorCode])){
            return $data[$errorCode];
        }
        return "很抱歉,文件上传遇到问题";
    } 
    
    public static function checkIsImage($imagePath)
    {
        if(file_exists($imagePath) && in_array(HCommon::getFileExt($imagePath),array('jpg','jpeg','bmp','gif','png')) && @getimagesize($imagePath)){
            return TRUE;
        }    
        return FALSE;
    }
    
    /**
	 * 查找指定模块下的图片尺寸规格参数 
	 * @param undefined $module_id
	 * 
	 */
	public static function getImageSizeList($module_id = 0)
	{
		$config = array(
			HCommon::_ARTICLE => array(100,200,array(640,320)),
			HCommon::_AVATAR => array(50,200),
		);
		if(isset($config[$module_id])){
			return $config[$module_id];
		}
		return FALSE;
	}
    
    
    /**
	 * 获取缩略图文件名前缀 
	 * @param undefined $str
	 * 
	 */
	private static function _getThumbPrefix($str)
	{
		$prefix = "";
		if(is_int($str)){
			$prefix = $str."_";
		}elseif(is_array($str)){
			$prefix = $str[0]."_".$str[1]."_";
		}
		return $prefix;
	}
    
    
    public static function cropPhoto($o_photo, $d_photo, $width, $height) {

	    $temp_img = self::_createImageFromSth($o_photo);
	    $o_width = imagesx($temp_img);                                //取得原图宽
	    $o_height = imagesy($temp_img);                                //取得原图高
		//判断处理方法
	    if ($width > $o_width || $height > $o_height) {        //原图宽或高比规定的尺寸小,进行压缩
	        $newwidth = $o_width;
	        $newheight = $o_height;

	        if ($o_width > $width) {
	            $newwidth = $width;
	            $newheight = $o_height * $width / $o_width;
	        }

	        if ($newheight > $height) {
	            $newwidth = $newwidth * $height / $newheight;
	            $newheight = $height;
	        }

	        //缩略图片
	        $new_img = imagecreatetruecolor($newwidth, $newheight);
	        imagecopyresampled($new_img, $temp_img, 0, 0, 0, 0, $newwidth, $newheight, $o_width, $o_height);
	        self::_imageSth($new_img, $d_photo);
	        imagedestroy($new_img);
	    } else {                                                                                //原图宽与高都比规定尺寸大,进行压缩后裁剪
	        if ($o_height * $width / $o_width > $height) {        //先确定width与规定相同,如果height比规定大,则ok
	            $newwidth = $width;
	            $newheight = $o_height * $width / $o_width;
	            $x = 0;
	            $y = ($newheight - $height) / 2;
	        } else {                                                                        //否则确定height与规定相同,width自适应
	            $newwidth = $o_width * $height / $o_height;
	            $newheight = $height;
	            $x = ($newwidth - $width) / 2;
	            $y = 0;
	        }

	        //缩略图片
	        $new_img = imagecreatetruecolor($newwidth, $newheight);
	        imagecopyresampled($new_img, $temp_img, 0, 0, 0, 0, $newwidth, $newheight, $o_width, $o_height);
	        self::_imageSth($new_img, $d_photo);
	        imagedestroy($new_img);

	        $temp_img = self::_createImageFromSth($d_photo);
	        $o_width = imagesx($temp_img);                                //取得缩略图宽
	        $o_height = imagesy($temp_img);                                //取得缩略图高
	        //裁剪图片
	        $new_imgx = imagecreatetruecolor($width, $height);
	        imagecopyresampled($new_imgx, $temp_img, 0, 0, $x, $y, $width, $height, $width, $height);
	        self::_imageSth($new_imgx, $d_photo);
	        imagedestroy($new_imgx);
	    }
	}
	
	
    private static function _imageSth($photo,$d_photo)
    {
        $ext = strtolower(strrchr($d_photo,'.'));
		switch($ext){
			case ".png":
			imagepng($photo,$d_photo);
			break;
			case ".gif":
			imagegif($photo,$d_photo);
			break;
			case ".jpg":
			default:
			imagejpeg($photo,$d_photo);
		}
    }
    
	private static function _createImageFromSth($photo)
	{
		$ext = strtolower(strrchr($photo,'.'));
		$tmp = NULL;
		switch($ext){
			case ".png":
			$tmp = imagecreatefrompng($photo);
			break;
			case ".gif":
			$tmp = imagecreatefromgif($photo);
			break;
			case ".jpg":
			default:
			$tmp = imagecreatefromjpeg($photo);
		}
		return $tmp;
	}   
}

?>