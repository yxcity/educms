<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use module\Application\src\Model\Tool;

class File
{

    private $adapter;
	public static $m_news = 60;//新闻模块
	public static $m_goods = 50; //商品模块
	public static $m_avatar = 40; //会员头像
	public static $m_brand = 70;//品牌LOGO
	public static $m_shop = 80;//门店模块
	public static $m_blt = 90;//公司公告
	
	
    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
	
	/**
	 * 添加一个新的文件信息 
	 * @param undefined $data
	 * 
	 */
	function addFile($data)
	{
		$table = new TableGateway('wx_files', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
    			'path' => $data['path'],
				'module_id'	=> $data['module_id'],
				'target_id'=>$data['target_id'],
    	));
    	if($rows->count()> 0){
			return FALSE;
		}
		
		//添加一条新的记录
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
		
	}
	
	
	/**
	 * 保存模块图片至数据表 
	 * 
	 */
	function saveModuleImages($module_id,$target_id,$owner_id,$imgs = NULL)
	{
		$table = new TableGateway('wx_files', $this->adapter);
		if(is_array($imgs) && count($imgs) > 0){
			foreach($imgs as $m){
				$fileInfo = array(
					'file_desc' => " - ",
					'path' => $m,
					'filesize' => filesize(BASE_PATH.$m),
					'is_img' => 1,
					'created_time' => time(),
					'module_id' => $module_id,
					'target_id' => $target_id,
					'owner_id' => $owner_id
				);
				$table->insert($fileInfo);
			}
		}
	}
	
	
	/**
	 * 更新文件信息 
	 * @param undefined $data
	 * @param undefined $file_id
	 * 
	 */
	function updateFile($data,$file_id)
	{
		$file_id = intval($file_id);
		if(!$file_id){
			return $this->addFile($data);
		}
		$table = new TableGateway('wx_files', $this->adapter);
		return $table->update($data,array('file_id'=>$file_id));
	}
	
	
	/**
	 * 查找指定模块下的图片尺寸规格参数 
	 * @param undefined $module_id
	 * 
	 */
	public static function getImageSizeList($module_id = 0)
	{
		$config = array(
			self::$m_goods => array(100,200,array(640,320)),
			self::$m_news => array(200,400,array(320,480)),
			self::$m_avatar => array(50,100,array(120,480)),
			self::$m_brand => array(400),
			self::$m_shop => array(200,400,array(640,320)),
			self::$m_blt => array(200,400,array(320,480)),
		);
		if(isset($config[$module_id])){
			return $config[$module_id];
		}
		return FALSE;
	}
	
	/**
	 * 文件上传的通用接口 
	 * @param undefined $field
	 * @param undefined $userId
	 * @param undefined $valid_exts
	 * @param undefined $module_id
	 * @param undefined $options
	 * 
	 */
	public static function doUpload($field, $userId, $valid_exts = array('gif','jpg','png'),$module_id = NULL,$options = NULL)
	{
		
		$dirname = "/uploads/".substr(md5($userId),0,10)."/".date("Ymd")."/";
		$fileInfo = Tool::uploadfile($field,$dirname,NULL,$valid_exts);
		
		//1.仅限生成指定大小缩略图的情况,此时缩略图会覆盖原图
		if(isset($options['thumb_size']) && isset($fileInfo['file'])){
            if(is_int($options['thumb_size'])){
                self::cropPhoto(BASE_PATH.$fileInfo['file'],BASE_PATH.$fileInfo['file'],$options['thumb_size'],$options['thumb_size']);    
            }elseif(is_array($options['thumb_size']) && count($options['thumb_size']) == 2){//宽与高两项,0-宽 1-高
                self::cropPhoto(BASE_PATH.$fileInfo['file'],BASE_PATH.$fileInfo['file'],$options['thumb_size'][0],$options['thumb_size'][1]);
            }
			return $fileInfo;
		}
		
		//2.生成多个缩略图的情况,保留原图
		if(isset($fileInfo['file'])){
			//生成缩略图
			$config = self::getImageSizeList($module_id);
			if($config){
				$thisFile = explode("/",$fileInfo['file']);
				foreach($config as $f){
					if(is_int($f)){
						$srcFileName = end($thisFile);
						$targetFileName = self::_getThumbPrefix($f).$srcFileName;
						$targetFile = BASE_PATH.str_replace($srcFileName,$targetFileName,$fileInfo['file']);
						self::cropPhoto(BASE_PATH.$fileInfo['file'],$targetFile,$f,$f);
					}elseif(is_array($f)){
						$srcFileName = end($thisFile);
						$targetFileName = self::_getThumbPrefix($f).$srcFileName;
						$targetFile = BASE_PATH.str_replace($srcFileName,$targetFileName,$fileInfo['file']);
						self::cropPhoto(BASE_PATH.$fileInfo['file'],$targetFile,$f[0], $f[1]);
					}	
				}
			}
		}
		return $fileInfo;
			
	}
	
	
	/**
	 * 删除指定账户下的所有文件 
	 * @param undefined $userId
	 * 
	 */
	public function delUserFiles($userId)
	{
		//删除表中文件记录
		$table = new TableGateway('wx_files', $this->adapter);
		$flag = $table->delete(array('owner_id'=>$userId));
		if($flag){
			//删除磁盘文件
			$dirName = BASE_PATH."/uploads/".substr(md5($userId),0,10)."/";
			self::_delUserFiles($dirName);
		}
		return $flag;
	}
	
	private static function _delUserFiles($dirName)
	{
		if ( $handle = opendir($dirName) ) {
		   while ( false !== ( $item = readdir( $handle ) ) ) {
			   if ( $item != "." && $item != ".." ) {
				   if ( is_dir( "$dirName/$item" ) ) {
				   		self::_delUserFiles("$dirName/$item");
				   } else {
				   		unlink("$dirName/$item");
				   }
			   }
		   }
		   closedir( $handle );
		   rmdir($dirName);
		}
	}
	
	
	/**
	 * 删除指定Module+target下的所有文件及表记录
	 * @param undefined $module_id
	 * @param undefined $target_id
	 * @param undefined $owner_id
	 * 
	 */
	public function delFileAndRecord($module_id,$target_id,$owner_id = 0,$file_id = 0)
	{
		$table = new TableGateway('wx_files', $this->adapter);
    	$where = array('module_id'=>$module_id,'target_id'=>$target_id);
		if($owner_id > 0){
			$where['owner_id'] = $owner_id;
		}
		if($file_id  > 0){
			$where['file_id'] = $file_id;
		}
		$rowSet = $table->select($where);
		if($rowSet->count() > 0){
			//删除文件
			foreach($rowSet as $r){
				self::delSrcAndThumbFiles($r->path,$module_id);
			}
		}
		
		//删除表中记录
		$flag = $table->delete($where);
		if(!$flag){
			return FALSE;
		}
    	return TRUE;	
	}
	
	/**
	 * 删除某个文件对象的原图及缩略图 
	 * @param undefined $srcFile
	 * @param undefined $module_id
	 * 
	 */
	public static function delSrcAndThumbFiles($srcFile,$module_id)
	{
		self::delFileFromDisk(BASE_PATH.$srcFile);
		$files = self::getThumbFile($srcFile,$module_id);
		if($files){
			foreach($files as $fIndex => $fVal){
				self::delFileFromDisk(BASE_PATH.$fVal);
			}
		}
	}
	
	/**
	 *　查找指定模块下的指定对象相关的文件 
	 * @param undefined $module_id
	 * @param undefined $target_id
	 * @param undefined $$options 控制参数 ie $options = array('thumbSize'=>100)
	 * 
	 */
	public function getFiles($module_id,$target_id,$options = NULL)
	{
		$table = new TableGateway('wx_files', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
				'module_id'	=> $module_id,
				'target_id'=> $target_id,
    	));
		if($rows->count() > 0){
			$dbRst = array();
			$thumbSize = isset($options['thumbSize']) ? $options['thumbSize'] : NULL;
			foreach($rows as $r){
				$r->thumbs = self::getThumbFile($r->path,$r->module_id,$thumbSize);
				$dbRst[] = $r;
			}
			return $dbRst;
		}
		return FALSE;
	}
	
	/**
	 * 查找缩略图 
	 * @param undefined $srcFile string
	 * @param $module_id int
	 * @param undefined $thumbSize NULL | string | array
	 * 
	 */
	public static function getThumbFile($srcFile,$module_id,$thumbSize = NULL)
	{
		$config = self::getImageSizeList($module_id);
		if(!$config){
			return FALSE;
		}
		$thisFile = explode("/",$srcFile);
		$srcFileName = end($thisFile);
		if(is_null($thumbSize)){//所有缩略图
			foreach($config as $f){		
				$targetFileName = self::_getThumbPrefix($f).$srcFileName;
				$ftFile = str_replace($srcFileName,$targetFileName,$srcFile);
				$thumbs[self::_getThumbPrefix($f)] = file_exists(BASE_PATH.$ftFile) ? $ftFile : $srcFile;
			}
			return $thumbs;
		}
		$targetFileName = self::_getThumbPrefix($thumbSize).$srcFileName;
		$tFile = str_replace($srcFileName,$targetFileName,$srcFile);
		if(file_exists(BASE_PATH.$tFile)){
			return $tFile;
		}		
		return $srcFile;
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
	
	
	/**
	 * 删除文件操作 
	 * @param undefined $file
	 * 
	 */
	public static function delFileFromDisk($file)
	{
		if(file_exists($file)){
			unlink($file);
		}
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
	
	/**
	 * 将商品图片从商品表批量导入至wx_files表 
	 * 
	 */
	public function doTransfer()
	{
		$table = new TableGateway('wx_files', $this->adapter);
		$sql = "SELECT id,images,addtime FROM `shop` WHERE images IS NOT NULL";
		$rowSet = $this->adapter->query($sql,"execute");
		if($rowSet->count() > 0){
			foreach($rowSet as $r){
				$imgs = unserialize($r->images);
				foreach($imgs as $m){
					$flag = $table->insert(array(
						'file_desc' => '-',
						'path' => $m,
						/*'filesize' => filesize(BASE_PATH.$m),*/
						'is_img' => 1,
						'created_time' =>$r->addtime,
						'module_id' => self::$m_shop,
						'target_id' => $r->id,
						'owner_id' => 0
					));
					if(!$flag){
						continue;
					}
					$u_sql = "UPDATE `shop` SET images = NULL WHERE id = ".$r->id;
					$this->adapter->query($u_sql,"execute");
					echo("<br />Updated img => ".$m);
				}
			}
		}else{
			echo "no img for Update";
		}
		exit(0);
	}
	
}