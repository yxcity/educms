<?php
/**
 * Admin模块全局共享的Action调用 
 */
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use library\Helper\HCommon;
use library\Helper\HUploader;

class CommonController extends AbstractActionController{
    
    
    /**
	 *AJAX异步方式上传各类图片文件公共方法 
	 * 
	 */
	public function ajaxUploadAction()
	{
		$this->user =HCommon::getSession('auth','user',HCommon::filterStr($_POST['_identify']));
		$request = $this->getRequest();
		$postData = $request->getPost();
		
		$module_id = $this->_checkIsValidModule(HCommon::filterStr($postData['module_id'],HCommon::_INT));//上传文件所属模块
		$target_id = HCommon::filterStr($postData['target_id'],HCommon::_INT);//上传文件所属对象
		$thumb_size = HCommon::filterStr($postData['thumb_size'],HCommon::_INT);//返回的图片缩略图尺寸
        
		if(!$module_id){
			$file = NULL;//设为一个非法的文件上传操作
		}
        
        $defConfig = HCommon::getDefConfig();
        $options = array(
            'maxSize'=>$defConfig['upload_max_filesize'],
            'allowedExts'=>$defConfig['upload_allowed_exts']
        );
        $uploadRst = HUploader::doUpload("filedata",$module_id,$options);
    	if($uploadRst[0])
    	{
			
			//添加时上传处理
			if(isset($postData['act']) && $postData['act'] == "add"){
				echo $uploadRst[1];
				exit(0);
			}
			
			//修改时上传处理
			$fileInfo = array(
				'file_desc' => " - ",
				'path' => $uploadRst[1],
				'filesize' => filesize(BASE_PATH.$uploadRst[1]),
				'is_img' => HUploader::checkIsImage($uploadRst[1]) ? 1 : 0,
				'created_time' => time(),
				'module_id' => $module_id,
				'target_id' => $target_id,
				'owner_id' => $this->user->id
			);
			$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
			if($file_id = $dbFile->addFile($fileInfo)){
				echo json_encode(array(
					'req' => "ok",
					'msg' => "图片上传成功",
					'file_path' => File::getThumbFile($fileInfo['path'],$module_id,$thumb_size),
					'file_id' => $file_id
				));
				exit(0);
			}
		}
            
		//添加商品时上传图片处理
		if(isset($postData['act']) && $postData['act'] == "add"){
			echo "error";
			exit(0);
		}
		
		//修改商品时上传图片处理
		echo json_encode(array(
			'req' => "error",
			'msg' => "图片上传失败,请稍候重试"
		));
		exit(0);
	}
	
	/**
	 * 检查被上传文件所在模块是否合法 
	 * @param undefined $module_id
	 * 
	 */
	private function _checkIsValidModule($module_id = NULL)
	{
		if(File::getImageSizeList($module_id) === FALSE){
			return FALSE;
		}
		return $module_id;
	}
}

?>