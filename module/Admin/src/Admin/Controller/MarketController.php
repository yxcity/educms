<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\News;
use Admin\Model\Menu;
use Admin\Model\Market;
/**
 * @todo 大转盘
 * @author
 * @version
 */
class MarketController extends AbstractActionController
{

    private $user;
    private $db;
    private $news_obj;
    private $adapter ;
    /**
    * @取得数据库操作
    * @return Ambigous <object, multitype:, \Admin\Model\Menu>
    */
    function getDB() {
        if(!$this->adapter){
            $this->adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        }
        
        if(!$this->db){
            $this->db = new Market($this->adapter);
        }
        return $this->db;
    }
    
    private function getNewsObj(){
        if(!$this->adapter){
            $this->adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        }
        
        if(!$this->news_obj){
            $this->news_obj = new News($this->adapter);
        }
        return $this->news_obj;
    }
   
    function __construct ()
    {
        $this->user = Tool::getSession('auth','user');
        if (!isset($this->user->domain)){
            $this->forbidden ();
            exit;
        }
    }
    private function get_activity(){
        $id = $this->params()->fromQuery('id',null);
        if(!$id){
            $this->forbidden();
            return;
        }
        
        $mkt_obj = $this->getDB();
        $activity = $mkt_obj->getActivity($id);
        if(!$activity || ($activity['uid'] != $this->user->domain)){
            $this->forbidden();
            return;
        }
        return $activity;
    }
    function snmanagerAction ()
    {
        $viewData = array();
        $db = $this->getDB();        
        $request = $this->getRequest();
        $id = $this->params()->fromQuery('id',null);
        $viewData['user']=$this->user;
        $page=$this->params('page');
        $viewData['rows'] = $db->getGroupdSNList($page, $this->user,3,$id);
        $viewData['action']=$this->params()->fromQuery('action');
        return $viewData;
    }
    
    function vtresultAction ()
    {
        $viewData = array();
        $act = $this->get_activity();
        $mkt_obj = $this->getDB();
        $stats = $mkt_obj->getVoteStatics($act['id']);
        $status_full = array();
        $total = 0;
        for($i=0;$i<count($act['config']['options']);$i++){
            if(isset($stats['option_' . $i])){
                $item = $stats['option_' . $i];
                $total += $item['count'];
            }else{
                $item = array('rate'=>0,'count'=>0);
            }
            $item['name'] = $act['config']['options'][$i]['option_name'];
            array_push($status_full,$item);
        }
        $viewData['total_count']=$total;
        $viewData['stats']=$status_full;
        $viewData['title']=$act['title'];
        $viewData['user']=$this->user;
        return $viewData;
    }

    //换成/lottery/activity?action=edit|add
    function snsaveAction(){
        $viewData['user']=$this->user;
        $action = $this->params()->fromQuery('action');
        $mkt_obj = $this->getDB();
        $this->check_params(array("id"=>array("method"=>"query")));
        $id = $this->params()->fromQuery('id');
        $json_data = array();
        if(!$mkt_obj->getPrize($id,null,null,$this->user->domain)){
            $json_data = array(
                'code'=>'fail',
                'msg'=>'id illegal!!!!',
            );
            exit(json_encode($json_data));
            return;
        }
        
        $update_data = array();
        if($action == 'cash'){
            $update_data['cash_time'] = date("Y-m-d H:i:s");
            $update_data['status'] = 2;
        }else if($action == 'uncash'){
            $update_data['cash_time'] = null;
            $update_data['status'] = 1;
        }else if($action == 'unuse'){
            $update_data['cash_time'] = null;
            $update_data['prize_time'] = null;
            $update_data['status'] = 0;
            $update_data['openid'] = null;
            $update_data['memb_id'] = null;
        }
        
        if($mkt_obj->editPrize($id,$update_data)){
            $json_data['code'] = 'ok';
            $json_data['data'] = $update_data;
        }else{
            $json_data['code'] = 'fail';
            $json_data['data'] = 'update prize error!';
        }
        exit(json_encode($json_data));
        return;
    }
    
    private  function forbidden()
    {
        //if (!isset($this->user->domain) || $this->user->power < 1) $this->redirect()->toUrl('/login');
        $this->getResponse()
                    ->setStatusCode(403)
                    ->setContent("Forbidden,<a href=/login>Back</a>")
                    ->send();
    }
    
    private function check_params($rules)
    {
        $required = array();
        $miss_param = FALSE;
        $post_params = $this->getRequest()->getPost();
        foreach($rules as $key=>$rule){
            if(is_array($rule)){
                if(((!isset($rule['method']) || strtolower($rule['method'])=='post') && ($post_params===NULL  ||!isset($post_params[$key]))) ||
                        (strtolower($rule['method'])=='query' && $this->params()->fromQuery($key)===NULL)){
                    array_push($required,$key);
                    $miss_param = TRUE;
                }
            }elseif (is_string($rule)){
                if(  (!$post_params  ||!isset($post_params[$rule])) && $this->params()->fromQuery($rule) ===NULL){
                        array_push($required,$rule);
                        $miss_param = TRUE;
                }
            }
        }

        if($miss_param){
            echo '{"code":"error","msg":"' . implode(',', $required) . ' required!"}';
            exit;
        }
    }
    
    function indexAction ()
    {
        $viewData = array();
        $db = new Market($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));        
        $request = $this->getRequest();
        $keywords = $this->params()->fromQuery('key',null);
        $type = $this->params()->fromQuery('type',null);
        if ($request->isPost())
        {
    	    $postData = $request->getPost();
            $keywords = Tool::filter($postData['keywords']);
        }
        $viewData['type']=$type;
        $viewData['user']=$this->user;
        $page=$this->params('page');
        $viewData['keywords']=$keywords;
        $viewData['rows'] = $db->getGroupdActivityList($page, $this->user,20,$type,$keywords);
        $viewData['action']=$this->params()->fromQuery('action');
		$viewData['asset']=array('css'=>array('/lib/uploadify/uploadify.css'));
        return $viewData;
    }

    //换成/lottery/activity?action=edit|add
    function activityAction(){
        $viewData['user']=$this->user;
        $viewData['menus'] = $this->getServiceLocator()->get('Admin\Model\Menu' )->getList($this->user->domain, null);
        $type = $this->params()->fromQuery('type');
        $viewData['type'] = $type;
        $action = $this->params()->fromQuery('action');
        $viewData['action'] = '添加';
        if($action == 'edit'){
            $this->check_params(array("id"=>array("method"=>"query")));
            $viewData['activity'] = $this->getDB()->getActivity($this->params()->fromQuery('id'));
            $viewData['type'] = $viewData['activity']['type'];
            $viewData['action'] = '编辑';
        }
        return $viewData;
    }
    
    function vtconfigAction(){
        $viewData['user']=$this->user;
        $viewData['menus'] = $this->getServiceLocator()->get('Admin\Model\Menu' )->getList($this->user->domain, null);
        $viewData['type'] = 3;
        $action = $this->params()->fromQuery('action');
        $vote_type = $this->params()->fromQuery('type');
        if($vote_type==2){
            $viewData['vote_type'] = 2;
        }else{
            $viewData['vote_type'] = 1;
        }
        $viewData['action'] = '添加';
        if($action == 'edit'){
            $this->check_params(array("id"=>array("method"=>"query")));
            $viewData['activity'] = $this->getDB()->getActivity($this->params()->fromQuery('id'));
            $viewData['action'] = '编辑';
        }
        return $viewData;
    }
    
    //处理
    function saveAction(){
         if(!$this->getRequest()->isPost()) {
            echo '{"code":"error","msg":"post method only!"}';
            exit;
        }
        /*多消息时可能创建只有type字段的空消息，参数校验要区分情况进行*/
        $this->check_params(array("title","news_id"));
        $post_data = $this->getRequest()->getPost();
        $data=array();
        $data['type'] = $post_data['type'];
        $data['config'] = json_encode($post_data['config']);
        $data['uid'] = $this->user->domain;
        $data['title'] = $post_data['title'];
        $data['news_id'] = $post_data['news_id'];
        $data['time_type'] = $post_data['time_type'];
        $data['open_time'] = $post_data['open_time'];
        $data['close_time'] = $post_data['close_time'];
        $data['try_count'] = $post_data['try_count'];
        $data['req_score'] = $post_data['req_score'];
        $data['prz_score'] = $post_data['prz_score'];
        $data['can_accu_score'] = $post_data['can_accu_score'];
        $data['menu_id'] = $post_data['menu_id'];
        $data['keyword'] = $post_data['keyword'];
        $data['strict_match_keyword'] = $post_data['strict_match_keyword'];
        $mkt_obj = new Market($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        if($this->params()->fromQuery('action') == 'edit'){
            $this->check_params(array("id"));
            $data['id'] = $post_data['id'];
            if ($mkt_obj ->editActivity($data['id'],$data)){
                echo '{"code":"ok","data":'.json_encode($data).'}';
            }else{
                echo '{"code":"fail","msg":"update market activety error."}';
            }
        } else if($this->params()->fromQuery('action') == 'add'){
            $data['create_time'] = date("Y-m-d H:i:s");
            if ($id = $mkt_obj ->addActivity($data)){
                $data['id'] = $id;
                if($data['type']==1 || $data['type']==2 ){/*抽奖相关的处理奖项序号*/
                    if($mkt_obj->addPrizes($id, $this->user->domain,$post_data['config']['prizes'])){
                        echo '{"code":"ok","data":'.json_encode($data).'}';
                    }else{
                        echo '{"code":"fail","msg":"add prizes error."}';
                    }
                }else{
                    echo '{"code":"ok","data":'.json_encode($data).'}';
                }
            }else{
                echo '{"code":"fail","msg":"add market activety error."}';
            }
        }else{
            echo '{"code":"error","msg":"query parameter action invalid!"}';
        }
        exit;
    }
    
    function _saveAction(){
         if(!$this->getRequest()->isPost()) {
            echo '{"code":"error","msg":"post method only!"}';
            exit;
        }
        /*多消息时可能创建只有type字段的空消息，参数校验要区分情况进行*/
        $this->check_params(array("title","news_id"));
        $post_data = $this->getRequest()->getPost();
        $data=array();
        $data['type'] = $post_data['type'];
        //var_dump($post_data);
        //die;
        $config = array();
        if($data['type']==1 || $data['type']==2){
            $config['prizes'] = $post_data['prizes'];
            $config['prize_pwd'] = $post_data['prize_pwd'];
            $config['total_play_count'] = $post_data['total_play_count'];
        }else if($data['type']==3){
            //投票类型：文字|图片
            //投票选项集合 ID,名称，排列序号、图片、选项链接
            //show_result_type 投票 前|后|活动完成 可见
            //选项类型 单选|多选  最多可选几项
            $config['vote_type'] = $post_data['vote_type'];
            $config['vote_options'] = $post_data['vote_options'];
            $config['vote_show_type'] = $post_data['vote_show_type'];
            $config['vote_sel_type'] = $post_data['vote_sel_type'];
        }
        $config['req_registed'] = $post_data['req_registed'];
        $config['not_open_tips'] = $post_data['not_open_tips'];
        $config['closed_tips'] = $post_data['closed_tips'];
        $data['config'] = json_encode($config);
        $data['uid'] = $this->user->domain;
        $data['title'] = $post_data['title'];
        $data['news_id'] = $post_data['news_id'];
        $data['time_type'] = $post_data['time_type'];
        $data['open_time'] = $post_data['open_time'];
        $data['close_time'] = $post_data['close_time'];
        $data['try_count'] = $post_data['try_count'];
        $data['req_score'] = $post_data['req_score'];
        $data['prz_score'] = $post_data['prz_score'];
        $data['can_accu_score'] = $post_data['can_accu_score'];
        $data['menu_id'] = $post_data['menu_id'];
        $data['keyword'] = $post_data['keyword'];
        $data['strict_match_keyword'] = $post_data['strict_match_keyword'];
        $mkt_obj = new Market($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        if($this->params()->fromQuery('action') == 'edit'){
            $this->check_params(array("id"));
            $data['id'] = $post_data['id'];
            if ($mkt_obj ->editActivity($data['id'],$data)){
                echo '{"code":"ok","data":'.json_encode($data).'}';
            }else{
                echo '{"code":"fail","msg":"update market activety error."}';
            }
        } else if($this->params()->fromQuery('action') == 'add'){
            $data['create_time'] = date("Y-m-d H:i:s");
            if ($id = $mkt_obj ->addActivity($data)){
                if($mkt_obj->addPrizes($id, $this->user->domain,$config['prizes'])){
                    $data['id'] = $id;
                    echo '{"code":"ok","data":'.json_encode($data).'}';
                }else{
                    echo '{"code":"fail","msg":"add prizes error."}';
                }
            }else{
                echo '{"code":"fail","msg":"add market activety error."}';
            }
        }else{
            echo '{"code":"error","msg":"query parameter action invalid!"}';
        }
        exit;
    }
    
    /**
     * @todo 删除消息
     */
    public function deleteAction()
    {
    	$this->check_params(array("id"=>array("method"=>'query')));
        $id = (int)$this->params()->fromQuery('id');
    	if ($id)
    	{
            $db = $this->getDB();
            $row = $db->getActivity($id);
            if ($row['uid']==$this->user->domain){
                    $db->delete($id);
                    echo '{"code":"ok"}';
                    exit();
            }else{
                echo '{"code":"error","msg":"operation illegal!"}';
            }
    	}
    	echo '{"code":"error","msg":"id required!"}';
    	exit();
    }
  
}