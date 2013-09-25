<?php
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\User;
class MenuController extends AbstractActionController{
	private $user;
	private $db;
	private $viewData;
	function __construct(){
		$this->user = Tool::getSession('auth', 'user');
		$this->viewData['user']=$this->user;
	}
	/**
	 * @todo 菜单列表
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
	function indexAction() {
            $this->viewData['message'] = json_decode(Tool::getCookie('message'));
            $rows = $this->getDB()->getList($this->user->domain);
            $rowsTmp = array();
            if ($rows) {
                foreach ($rows as $val) {
                    if (!$val['pid']) {
                        $rowsTmp[$val['id']] = $val;
                    }
                    if ($val['pid']) {
                        $rowsTmp[$val['pid']]['next'][] = $val;
                        unset($rows[$val['id']]);
                    }
                }
                unset($rows);
            }
            $userDB=new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
            $this->viewData['userData']=$userDB->getUser($this->user->id);
            $this->viewData['rows'] = $rowsTmp;
		    $this->viewData['bp'] = $this->BackendPlugin();
            return $this->viewData;
        }
	/**
	 * @todo 菜单获取使用凭配置
	 */
	function configAction()
	{
		if (!isset($this->user->id)) $this->redirect()->toRoute('login');
		$db = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		$res = $this->getRequest();
		if ($res->isPost())
		{
			$data=$res->getPost()->toArray();
			$acRes = $this->getDB()->accessToken($data['appid'], $data['appSecret']);
			if ($acRes)
			{
				$db->editUser($this->user->id,null,$data);
				exit('{"data":"配置成功","isok":true}');
			}else 
			{
				exit('{"data":"配置失败","isok":false}');
			}
		}
		$this->viewData['row'] = $db->getUser($this->user->id);
		return $this->viewData;
	}
	/**
        * @todo 创建菜单
        */
        function createAction() {
            if (!isset($this->user->id))$this->redirect()->toRoute('login');
            $res = $this->getRequest();
            if ($res->isPost()) {
                $data = $res->getPost()->toArray();
                unset($data['submit']);
                $pid = $data['pid'] ? $data['pid'] : 0;
                $rows = $this->getDB()->getList($this->user->domain, $pid);
                if ($pid && count($rows) >= 5) {
                    Tool::setCookie('message', array('title' => '添加失败', 'message' => '二级菜单最多5个', 'alert' => 'error'), time() + 3);
                    $this->redirect()->toUrl("/menu");
                } elseif (!$pid && count($rows) >= 3) {
                    Tool::setCookie('message', array('title' => '添加失败', 'message' => '一级菜单最多3个', 'alert' => 'error'), time() + 3);
                    $this->redirect()->toUrl("/menu");
                } else {
                    $data['menuname'] = Tool::filter($data['menuname'], true);
                    $data['domain'] = $this->user->domain;
                    $data['uid'] = $this->user->id;
                    $data['key'] = uniqid($this->user->domain . ":"); /* lwq:生成唯一的key标识 */
                    $res = $this->getDB()->insert($data);
                    if ($res) {
                        Tool::setCookie('message', array('title' => '添加成功', 'message' => "添加菜单成功", "alert" => "success"), time() + 3);
                        $this->redirect()->toUrl("/menu");
                    }
                    $this->viewData['message'] = (object) array('title' => '添加失败', 'message' => "添加菜单失败", 'alert' => 'error');
                }
            }

           $userDB=new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
           $this->viewData['userData']=$userDB->getUser($this->user->id);
           $this->viewData['rows'] = $this->getDB()->getList($this->user->domain, 0);
           return $this->viewData;
        }
        
        /**
        * @todo 创建菜单
        */
        function listAction() {
           if (!isset($this->user->id))$this->redirect()->toRoute('login');
           $menus = $this->getDB()->getList($this->user->domain, 0);
           $json_data = array(
                'code'=>'ok',
                'data'=>$menus
            );
            echo json_encode($json_data);
            die;
        }
        
	/**
	 * @todo 编辑菜单
	 */
        function editAction() {
            if (!isset($this->user->id))$this->redirect()->toRoute('login');
            $id = (int) $this->params()->fromQuery('id');
            if (!$id)
                $this->redirect()->toUrl('/menu');
            $res = $this->getRequest();
            if ($res->isPost()) {
                $data = $res->getPost()->toArray();
                unset($data['submit']);
                $pid = $data['pid'] ? $data['pid'] : 0;
                $rows = $this->getDB()->getList($this->user->domain, $pid);
                if ($pid && count($rows) > 5) {
                    Tool::setCookie('message', array('title' => '编辑失败', 'message' => '二级菜单最多5个', 'alert' => 'error'), time() + 3);
                    $this->redirect()->toUrl("/menu");
                } elseif (!$pid && count($rows) > 3) {
                    Tool::setCookie('message', array('title' => '编辑失败', 'message' => '一级菜单最多3个', 'alert' => 'error'), time() + 3);
                    $this->redirect()->toUrl("/menu");
                } else {
                    $data['menuname'] = Tool::filter($data['menuname'], true);
                    $res = $this->getDB()->insert($data, $id);
                    if ($res) {
                        Tool::setCookie('message', array('title' => '编辑成功', 'message' => "编辑菜单成功", "alert" => "success"), time() + 3);
                        $this->redirect()->toUrl("/menu");
                    }
                    $this->viewData['message'] = (object) array('title' => '编辑失败', 'message' => "编辑菜单失败", 'alert' => 'error');
                }
            }
            $row = $this->getDB()->getMenuId($id);
            if (!$row)
                $this->redirect()->toUrl('/menu');
            $this->viewData['row'] = $row;
            $this->viewData['rows'] = $this->getDB()->getList($this->user->domain, 0);
            return $this->viewData;
        }
	
	function deleteAction()
	{
		if (!isset($this->user->id)) $this->redirect()->toRoute('login');
		$id = (int)$this->params()->fromQuery('id');
		if (!$id){
			exit('{"data":"缺少参数","isok":false}');
		}
		$res = $this->getDB()->delete($id);
		if ($res)
		{
                    echo json_encode(array('code'=>'ok','data'=>$res));
                    exit;
		}
		exit('{"data":"删除失败","code":"fail"}');
	}
	
        private function _filterKey(&$obj,$keys){
            foreach($obj as $key=>$value){
                if(!in_array($key, $keys)){
                        unset($obj[$key]);
                }
            }
        }
        
	function refreshAction(){
            $keys = array('name','key','type','button','sub_button');
            $rows = $this->getDB()->getList($this->user->domain,0);
            if ($rows){
                foreach ($rows as &$val){
                    $sub_btns = $this->getDB()->getList($this->user->domain,$val['id']);
                    if (is_array($sub_btns) && count($sub_btns)>0){
                        foreach ($sub_btns as &$sub_val) {
                            $sub_val['type']="click";
                            $sub_val['name']=urlencode($sub_val['menuname']);
                            $sub_val['key']=urlencode($sub_val['key']);
                            $this->_filterKey($sub_val,$keys);
                        }
                        $val['name']=urlencode($val['menuname']);
                        $val['sub_button']=$sub_btns;
                        $val['type']="click";
                    }else 
                    {
                        if(empty($val['pid'])){
                            $val['type']="click";
                            $val['name']=urlencode($val['menuname']);
                            $val['key']=urlencode($val['key']);
                        }
                    }
                    $this->_filterKey($val,$keys);
                }
            }
            $data = urldecode(json_encode(array('button'=>$rows)));
            $uDB = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
            $row=$uDB->getUser($this->user->id);
            $accessToken = $this->getDB()->accessToken($row['appId'],$row['appSecret']);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$accessToken}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $tmpInfo = curl_exec($ch);
            if (curl_errno($ch)) {
                Tool::setCookie('message', array('title'=>'提交失败','message'=>"请查看菜单数量是否超出限定","alert"=>"error"),time()+3);
                $this->redirect()->toUrl("/menu");
            }
            curl_close($ch);
            Tool::setCookie('message', array('title'=>'提交成功','message'=>"菜单24小时后生效，或你可以取消关注公众帐号后重新关注公众帐号可及时看到菜单","alert"=>"success"),time()+3);
            $this->redirect()->toUrl("/menu");
            $response = $this->getResponse();
            $response->setStatusCode(200);
            return $response;
	}
	
	/**
	 * @取得数据库操作
	 * @return Ambigous <object, multitype:, \Admin\Model\Menu>
	 */
	function getDB() {
		if (! $this->db) {
                    $this->db = $this->getServiceLocator ()->get ( 'Admin\Model\Menu' );
		}
		return $this->db;
	}
	
}