<?php
namespace Admin\Model;
use library\Helper\HModel;
use library\Helper\HUploader;

class File extends HModel
{

   
    function __construct ($adapter)
    {
        parent::__construct($adapter);
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
		$config = HUploader::getImageSizeList($module_id);
		if(!$config){
			return FALSE;
		}
		$thisFile = explode("/",$srcFile);
		$srcFileName = end($thisFile);
		if(is_null($thumbSize)){//所有缩略图
			foreach($config as $f){		
				$targetFileName = HUploader::_getThumbPrefix($f).$srcFileName;
				$ftFile = str_replace($srcFileName,$targetFileName,$srcFile);
				$thumbs[HUploader::_getThumbPrefix($f)] = file_exists(BASE_PATH.$ftFile) ? $ftFile : $srcFile;
			}
			return $thumbs;
		}
		$targetFileName = HUploader::_getThumbPrefix($thumbSize).$srcFileName;
		$tFile = str_replace($srcFileName,$targetFileName,$srcFile);
		if(file_exists(BASE_PATH.$tFile)){
			return $tFile;
		}		
		return $srcFile;
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
}