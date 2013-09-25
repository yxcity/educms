<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Admin\Model\File;

class Article
{

    private $adapter;
	public $_news = 60;//公司动态
	public $_blt = 90;//公司公告
	public $_help = 12;//帮助公告
	private $_artTag = 10;//文章标签
	private $_pageSize = 20;//每页显示记录数
	private $_display = 1;//可显示记录

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
    
	
	/**
	 * 通过classid获取文章信息 
	 * @param undefined $classid
	 * @return 数组 | FALSE
	 * 
	 */
	function getArtByClassId($classid,$domain = NULL)
	{
		if(!$classid){
			return FALSE;
		}
		
		$table= new TableGateway('wx_articles',$this->adapter);
		$rowSet = $table->select(array('classid'=>$classid,'domain'=>$domain));
		if($rowSet->count() < 1){
			return FALSE;
		}
		$row = $rowSet->current();
		$row['created_time_str'] = $row['created_time'] > 0 ? date('Y-m-d',$row['created_time']) : "";
		$row['updated_time_str'] = $row['updated_time'] > 0 ? date('Y-m-d',$row['updated_time']) : "";
		return $row;
	}
	
	
	/**
	 * 保存文章信息 
	 * @param undefined $data
	 * @param undefined $classid
	 * @param undefined $domain
	 * 
	 */
	function saveArtInfo($data,$classid,$domain = NULL)
	{
		//标题与内容为必填项
		if(!isset($data['art_title']) || empty($data['art_title'])){
			return FALSE;
		}
		if(!isset($data['art_content']) || empty($data['art_content'])){
			return FALSE;
		}
		
		//新增|更新内容
		$table = new TableGateway('wx_articles',$this->adapter);
		$row = $this->getArtByClassId($classid,$domain);
		$flag = FALSE;
		if(!$row){
			//新增
			$data['created_time'] = time();
			$data['domain'] = $domain;
			$data['classid'] = $classid;
			$data['display'] = 1;
			$data['is_removable'] = 0;
			$flag = $table->insert($data);
		}else{
			//更新
			$data['updated_time'] = time();
			$data['display'] = 1;
			$data['is_removable'] = 0;
			$flag = $table->update($data,array('classid'=>$classid,'domain'=>$domain));
		}
		return $flag;
	}
	
    /**
     * 查找文章下的图片资源 
     * @param undefined $domain 所在域
     * @param undefined $commend　推荐类型
     * @param undefined $limit 图片数量
     * 
     */
	function getArtImages($domain,$commend = 2,$limit = 5)
    {
       $sql = "SELECT t1.art_id,t1.art_title,art_title_url,t1.classid,t3.path,t3.module_id FROM `wx_articles` as t1";
       $sql.=" INNER JOIN `wx_files` as t3 ON t1.art_id = t3.target_id AND t3.module_id = t1.classid";
       $sql.=" WHERE t1.domain = '".$domain."' AND t1.classid IN(".$this->_news.",".$this->_blt.") AND commend='".$commend."' AND t1.display = '".$this->_display."'";
       $sql.=" ORDER BY t1.created_time DESC LIMIT $limit ";
       $rowSet = $this->adapter->query($sql,"execute");
       
       $dbRst = array();
       if($rowSet->count() > 0){
           foreach($rowSet as $r){
               $r->thumb = File::getThumbFile($r->path,$r->module_id,400);
               $r->link = $this->_mapFlashLink($r->classid,$r->art_id,$r->art_title_url);
               $dbRst[] = $r;
           }
       } 
       return $dbRst;
    }
    
    
    private function _mapFlashLink($classid,$art_id = 0,$link = NULL)
    {
        if(!empty($link)){
            return $link;
        }
        
        $link = "#";
        if($classid == $this->_news){
            $link = "/index/artdetail?art_id=".$art_id;    
        }elseif($classid == $this->_blt){
            $link = "/index/bltdetail?art_id=".$art_id;
        }
        return $link;    
    }
    
	/**
	 * 新闻动态 - 微站|微商城 
	 * 
	 */
	function getArtList($domain,$pageSize = 0,$order = NULL,$thisPage = 1,$getCount = TRUE,$subclassid = 0,$keyword = NULL)
	{	
		$where = " WHERE t1.domain = '".$domain."' AND t1.classid = '".$this->_news."' AND t1.display = '".$this->_display."'";
		if($subclassid > 0){//查找当前分类及其所有子分类
			//$where.= " AND t1.subclassid = '$subclassid'";
            $sbClasses = $this->_getSubClasses($domain,$subclassid);
			array_push($sbClasses,$subclassid);
			$sbClasses = implode(",",$sbClasses);
			$where.= " AND t1.subclassid IN($sbClasses)";
		}
        if($keyword){
            $where.=" AND (t1.art_title LIKE '%$keyword%' OR t1.art_content LIKE '%$keyword%')";
        }
        if($order){
		    $orderby = " order by {$order} desc";
		}else{
		    $orderby = " order by art_id desc";
		}
		
		if($getCount){
			//只获取记录数
			$sql = "SELECT COUNT(*) as CNT FROM `wx_articles` as t1 $where".$orderby;
			$rowSet = $this->adapter->query($sql,"execute");
			if($rowSet->count() > 0){
				return $rowSet->current()->CNT;
			}
			return 0;
		}
		
		//处理分页数据
		$pageSize = $pageSize > 0 ? $pageSize : $this->_pageSize;
		$offSet = ($thisPage - 1) * $pageSize;
		$order = is_null($order) ? "updated_time DESC" : $order;
		$sql = "SELECT t1.*,t2.username,t3.path,t3.module_id FROM `wx_articles` as t1 LEFT JOIN `users` as t2 ON t1.owner_id = t2.id";
		$sql.=" LEFT JOIN `wx_files` as t3 ON t1.art_id = t3.target_id AND t3.module_id = ".File::$m_news;
		$sql.=$where;
		$sql.= " $orderby LIMIT ".$offSet.",$pageSize ";
		$rowSet = $this->adapter->query($sql,"execute");
		
		$dbRst = array();
		if($rowSet->count() > 0){
			foreach($rowSet as $r){
				$r->thumb = File::getThumbFile($r->path,$r->module_id,400);
				$dbRst[] = $r;
			}
		}
		return $dbRst;
	}
	
    
    /**
	 * 公司公告 - 微站|微商城 
	 * 
	 */
	function getBltList($domain,$pageSize = 0,$order = NULL,$thisPage = 1,$getCount = TRUE,$keyword = NULL)
	{	
		$where = " WHERE t1.domain = '".$domain."' AND t1.classid = '".$this->_blt."' AND t1.display = '".$this->_display."'";
		
        if($keyword){
            $where.=" AND (t1.art_title LIKE '%$keyword%' OR t1.art_content LIKE '%$keyword%')";
        }
        if($order){
		    $orderby = " order by {$order} desc";
		}else{
		    $orderby = " order by art_id desc";
		}
		
		if($getCount){
			//只获取记录数
			$sql = "SELECT COUNT(*) as CNT FROM `wx_articles` as t1 $where".$orderby;
			$rowSet = $this->adapter->query($sql,"execute");
			if($rowSet->count() > 0){
				return $rowSet->current()->CNT;
			}
			return 0;
		}
		
		//处理分页数据
		$pageSize = $pageSize > 0 ? $pageSize : $this->_pageSize;
		$offSet = ($thisPage - 1) * $pageSize;
		$order = is_null($order) ? "updated_time DESC" : $order;
		$sql = "SELECT t1.*,t2.username,t3.path,t3.module_id FROM `wx_articles` as t1 LEFT JOIN `users` as t2 ON t1.owner_id = t2.id";
		$sql.=" LEFT JOIN `wx_files` as t3 ON t1.art_id = t3.target_id AND t3.module_id = ".File::$m_blt;
		$sql.=$where;
		$sql.= " $orderby LIMIT ".$offSet.",$pageSize ";
		$rowSet = $this->adapter->query($sql,"execute");
		
		$dbRst = array();
		if($rowSet->count() > 0){
			foreach($rowSet as $r){
				$r->thumb = File::getThumbFile($r->path,$r->module_id,400);
				$dbRst[] = $r;
			}
		}
		return $dbRst;
	}
	
	/**
     * @新闻列表
     * @param int $page
     * @param Object $user
     * @return \Zend\Paginator\Paginator
     */
    function newsList ($page,$classid,$domain,$nums=30,$keywords=null,$subclassid = 0)
    {
    	$select = new Select('wx_articles');
		$where = "domain = '".$domain."' AND classid = ".$classid." AND display =  1";
    	if ($keywords)
    	{
    		$where .=" AND (art_title like '%{$keywords}%' OR art_content like '%{$keywords}%')";
    	}
		if($subclassid > 0){//查找当前分类及其所有子分类
			$sbClasses = $this->_getSubClasses($domain,$subclassid);
			array_push($sbClasses,$subclassid);
			$sbClasses = implode(",",$sbClasses);
			$where.= " AND subclassid IN($sbClasses)";
		}
		$select->where($where);
    	$select->order('created_time desc');
    	$adapter = new DbSelect($select, $this->adapter);
    	$paginator = new Paginator($adapter);
    	$paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
    	return $paginator;
    }
	
	
	/**
	 * 添加公司动态 
	 * @param undefined $data
	 * 
	 */
	function addArt($data)
	{
		$table = new TableGateway('wx_articles', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
    			'art_title' => $data['art_title'],
				'domain'	=> $data['domain'],
				'classid'=>$data['classid'],
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
	 * 获取公司动态内容 
	 * @param undefined $art_id
	 * 
	 */
	function getArtById($art_id,$classid,$domain = NULL,$ext = TRUE)
	{
		if(!$art_id){
			return FALSE;
		}
		$table = new TableGateway('wx_articles',$this->adapter);
		$rowSet = $table->select(array('art_id'=>$art_id,'domain'=>$domain,'classid'=>$classid,'display'=>1));
		if($rowSet->count() > 0){
			$row = $rowSet->current();
			if($ext){//获取扩展信息,此时查找标签信息
				$row->tag = $this->_getTagsByArtId($row->art_id);
			}
			return $row;
		}
		return FALSE;
	}
	
	/**
	 * 查找新闻分类下所有子分类 
	 * @param undefined $pid
	 * @param $rev 递归查找
	 * 
	 */
	private function _getSubClasses($domain,$pid = 60,$rev = TRUE)
	{
		$classes = array();
		$table = new TableGateway('type',$this->adapter);
		$rowSet = $table->select(array('pid'=>$pid,'domain'=>$domain,'display'=>$this->_display,'classid'=>11));
		if($rowSet->count() > 0){
			foreach($rowSet as $row){
				array_push($classes,$row->id);
				if($rev){
					$classes = array_merge($classes,$this->_getSubClasses($domain,$row->id,$rev));
				}
			}
		}
		return $classes;
	}
	
    /**
	 * 查找当前分类及其父类的ID集合
     * 用于导航菜单的层级生成
	 * @param undefined $class_id
	 * @return string id1,id2,id3
	 * 
	 */
    public function getClassAndParentIds($class_id = 0)
	{
		$ids = array();
        if(!$class_id){
            return $ids;
        }
		$sql = "SELECT pid,name FROM `type` WHERE classid = 11 AND id = '$class_id'";
		$rowSet = $this->adapter->query($sql,"execute");
		if($rowSet->count() > 0){
			$current = $rowSet->current();
            if($class_id!=60){
                array_push($ids,array('id'=>$class_id,'name'=>$current->name));    
            }
            if($current->pid > 0){
                $ids = array_merge($this->getClassAndParentIds($current->pid),$ids);    
            }
		}
		return $ids;
	} 
	
    
	/**
	 * 查找文章的标签 
	 * @param undefined $art_id
	 * 
	 */
	private function _getTagsByArtId($art_id = 0)
	{
		$tagStr = "";
		if(empty($art_id)){
			return $tagStr;
		}
		$table = new TableGateway('wx_tags',$this->adapter);
		$rowSet = $table->select(array('obj_type'=>$this->_artTag,'obj_id'=>$art_id));
		if($rowSet->count() > 0){
			$tgArr = array();
			foreach($rowSet as $r){
				array_push($tgArr,$r->tag_name);
			}
			$tagStr = implode(",",$tgArr);
		}	
		return $tagStr;
	}
	
	
	/**
     * @编辑公司动态
     * @param int $id
     * @param array $data
     * @return boolean
     */

    function editArt ($data,$art_id,$domain)
    {
        
        $table = new TableGateway('wx_articles', $this->adapter);
        $flag = $table->update($data, array(
            'art_id' => $art_id,
			'domain'=>$domain
        ));
		if($flag){
			return TRUE;
		}
		return FALSE;
    }
	
	
	/**
     * @todo 删除公司动态
     * @param int $id
     * @return boolean
     */
    function delArt($art_id,$domain)
    {
    	$art_id = (int)$art_id;
		$table = new TableGateway('wx_articles', $this->adapter);
    	$flag = $table->delete(array('art_id'=>$art_id,'domain'=>$domain));
		if(!$flag){
			return FALSE;
		}
    	return TRUE;
    }
	
	
	/**
	 * 添加文章标签信息 
	 * @param undefined $tags 必须是一个数组(例如 array('云计算','SNS'))
	 * @param undefined $art_id
	 * 
	 */
	function addTag($tags,$art_id)
	{
		if(!is_array($tags)){
			return FALSE;
		}
		
		//查找已存在的标签
		$table = new TableGateway('wx_tags',$this->adapter);
		$rowSet = $table->select(array('obj_type'=>$this->_artTag,'obj_id'=>$art_id));
		$existedTags = array();
		if($rowSet->count() > 0){
			foreach($rowSet as $r){
				array_push($existedTags,$r->tag_name);
			}
		}
		
		if(empty($existedTags)){
			foreach($tags as $et){
				$table->insert(array(
					'tag_name'=>$et,
					'obj_type'=>$this->_artTag,
					'obj_id'=>$art_id,
					'created_time'=>time()
				));
			}
			return TRUE;	
		}
		
		//删除现有标签并添加新的标签
		$defDiff = array_values(array_diff($existedTags,$tags));
		$addDiff = array_values(array_diff($tags,$existedTags));
		if(!empty($defDiff)){
			foreach($defDiff as $df){
				$del_sql = "DELETE FROM `wx_tags` WHERE obj_id = '$art_id' AND obj_type = '".$this->_artTag."' AND tag_name = '$df'";
				$this->adapter->query($del_sql,"execute");	
			}
		}
		if(!empty($addDiff)){
			foreach($addDiff as $item){
				$table->insert(array(
					'tag_name'=>$item,
					'obj_type'=>$this->_artTag,
					'obj_id'=>$art_id,
					'created_time'=>time()
				));
			}	
		}
		return TRUE;
	}
	
	/**
	 *  
	 * @param undefined $art_id
	 * @param undefined $thumbSize
	 * 
	 */
	function getArtFileInfo($art_id,$thumbSize = NULL,$module_id = 0)
	{
		$module_id = empty($module_id) ? File::$m_news : $module_id;
		$sql = "SELECT * FROM `wx_files` WHERE module_id = '".$module_id."' AND target_id = '$art_id' LIMIT 1";
		$rowSet = $this->adapter->query($sql,"execute");
		if($rowSet->count() > 0){
			$row = $rowSet->current();
			$row->thumb = File::getThumbFile($row->path,$row->module_id,$thumbSize);
			return $row;
		}
		return FALSE;
	}
    
    /*阅读文章*/
    function incReadCount($id)
    {
        $id = (int) $id;
        if($id > 0 ){
            return $this->adapter->query("update wx_articles set art_pv=art_pv+1 where art_id={$id}",'execute');
        }else{
            return false;
        }
    }
	
}