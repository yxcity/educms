<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Message;
use Admin\Model\News;
use Admin\Model\Fans;
/**
 * @todo 消息素材类
 * @author
 * @version
 */
class MessageController extends AbstractActionController
{

    private $user;
    function __construct ()
    {
        $this->user = Tool::getSession('auth','user');
        if (!isset($this->user->domain)){
            $this->forbidden ();
            exit;
        }
    }

    function indexAction ()
    {
        $viewData = array();
        $page = $this->params('page');
        $msg_obj = new Message($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['rows'] = $msg_obj->getTasksList($page,$this->user,10);
        $viewData['user'] = $this->user;
        $account = $msg_obj->getWechatAccount($this->user->domain);
        if($account && isset($account['password'])){
            unset($account['password']);//客户端不下发帐号密码信息
        }
        $viewData['account'] = $account;
        return $viewData;
    }
    
    function addtaskAction(){
        $page = $this->params('page');
        $nickname = $this->params()->fromQuery('nickname');
        $viewData = array();
        $fans_obj = new Fans($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['fans'] = $fans_obj->getFansList($nickname,$this->user->domain,$page);
        $viewData['user'] = $this->user;
        return $viewData;
    }
    
    function _addtaskAction(){
        $viewData = array();
        $msg_obj = new Message($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['cities'] = $msg_obj->getAllCities();
        $viewData['user'] = $this->user;
        return $viewData;
    }
    
    function taskinfoAction(){
        if(!$this->getRequest()->isPost()) {
            echo '{"code":"error","msg":"post method only!"}';
            exit;
        }
        //数据库插入文本消息
        $this->check_params(array("ids"));
        $post_data = $this->getRequest()->getPost();
        $ids = explode('|',$post_data['ids']);
        $msg_obj = new Message($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        if($tasks_stat = $msg_obj->getTasksStat($ids,$this->user->domain)){
            echo json_encode(array(
                "code"=>"ok",
                "data"=>$tasks_stat
            ));
        }else{
            echo '{"code":"error","msg":"add news error."}';
        }
        exit;
    }
    
    function sendmassAction()
    {   
         if(!$this->getRequest()->isPost()) {
            echo '{"code":"error","msg":"post method only!"}';
            exit;
        }
        //数据库插入文本消息
        $this->check_params(array("content"));
        $post_data = $this->getRequest()->getPost();
        $news_data=array();
        $news_data['type'] = 2;
        $news_data['uid'] = $this->user->domain;
        $news_data['description'] = $post_data['content'];
        $news_data['create_time'] = date("Y-m-d H:i:s");
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $news_obj = new News($adapter);
        $fan_obj = new Fans($adapter);
        if(!($news_id = $news_obj->addNews($news_data))){
            echo '{"code":"error","msg":"add news error."}';
            exit;
        }
        
        $massend_data=array();
        $massend_data['uid'] = $this->user->domain;
        $massend_data['news_id'] = $news_id;
        $massend_data['create_time'] = date("Y-m-d H:i:s");
        $master_id = 'dmaster:'.md5(uniqid(rand(),true));
        $massend_data['job_id'] = $master_id;
        if(isset($post_data['nickname'])){/*之后将所有检索条件合并到一处*/
            $fans = $fan_obj->getFansListEx($this->user->domain,$post_data['nickname']);
            $ids = array();
            foreach($fans  as $fan){
                array_push($ids,$fan['fakeid']);
            }
            $massend_data['to_ids']=json_encode($ids);
        }elseif(isset($post_data['ids'])){
            $fans = $fan_obj->getFansListEx($this->user->domain,$post_data['nickname'],explode('|', $post_data['ids']));
            $ids = array();
            foreach($fans as $fan){
                array_push($ids,$fan['fakeid']);
            }
            $massend_data['to_ids']=json_encode($ids);
        }else{
            $this->check_params(array("sex","cities"));
            $massend_data['sex'] = $post_data['sex'];
            $massend_data['cities'] = $post_data['cities'];
        }
        $msg_obj = new Message($adapter);
        if(false == ($task_id = $msg_obj->addMassSendTask($massend_data))){
            echo '{"code":"error","msg":"add massend data error."}';
            exit;
        }
        //插入队列
        $config = $this->getServiceLocator()->get('config');
        \Resque::setBackend($config['redis']['host']);
        $args = array(
                'id' => $task_id,
                'time' => time(),
                'config'=>array(
                    'redis'=>$config['redis'],
                    'db'=>$config['db']
                ),
                'task' => array(
                    'master'=>'dtask\\task\master\\Massend',
                    'worker'=>'dtask\\task\sub\\Massend',
                    'master_id'=>$master_id
                    )
            );

        $jobId = \Resque::enqueue('MasterTasks', 'dtask\\MasterTask', $args, true);

        echo '{"code":"ok","data":{"id":'.$task_id.'}}';
        exit;
        
    }
    
    /*ajax handle*/
    function updateaccountAction ()
    {
        $request = $this->getRequest();
        $this->check_params(array("account","password"));
        $msg_obj  = new Message($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $uid = $this->user->domain;
        $data = array();
        $post_data = $request->getPost();
        $data['account'] = $post_data['account'];
        $data['password'] = md5($post_data['password']);
        if($msg_obj->getWechatAccount($uid)){
            unset($data['account']);/*己绑定公众平台无法再修改帐号*/
            $ret = $msg_obj->saveWechatAccount($uid, $data);
        }else{
            $data['uid']=$uid;
            $ret = $msg_obj->addWechatAccount($data);
        }
        if ($ret){
           $this->sync_users($uid,1);
            echo '{"code":"ok"}';
        }else{
            echo '{"code":"error","msg":"update account error!"}';
        }
        exit;
    }
    
    function syncuserAction()
    {
        $uid = $this->user->domain;
        $this->sync_users($uid);
        echo '{"code":"ok"}';
        die;
    }
    
    private function sync_users($uid,$type=0)
    {
        //插入队列,修改为从全局配置文件读取
        $config = $this->getServiceLocator()->get('config');
        \Resque::setBackend($config['redis']['host']);
        $master_id = 'dmaster:'.md5(uniqid(rand(),true));
        $args = array(
                'uid' => $uid,
                'scan_type' =>$type,
                'time' => time(),
                'config'=>array(
                    'redis'=>$config['redis'],
                    'db'=>$config['db']
                ),
                'task' => array(
                    'master'=>'dtask\\task\master\\SynUser',
                    'worker'=>'',
                    'master_id'=>$master_id
                    )
            );
        $jobId = \Resque::enqueue('MasterTasks', 'dtask\\MasterTask', $args, true);
    }
    
    private  function forbidden()
    {
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
}