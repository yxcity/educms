<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Admin\Form\CollectForm;
use Admin\Form\CollectVerify;
use Admin\Tools\Simple;
use Admin\Tools\Tool;
use Zend\Session\Container;
use Zend\Authentication\Adapter\ValidatableAdapterInterface;

class CollectController extends AbstractActionController
{

    public $rule;
    public $m;
    protected $db;

    public function indexAction ()
    {
    	$page=$this->params('page',1);
    	$rows=$this->getDB()->collectList($page);
    	return array('rows'=>$rows);
    }

    public function createAction ()
    {
		$request = $this->getRequest ();
		if ($request->isPost ()) {
		        $data=$request->getPost();
		        if ($data['urlTest'])
		        {
		        	$this->rule=$data;
		        	$this->m='url';
		            $this->redirect()->toRoute('collect',array('action'=>'test'));
		        }
		        
		        if ($data['contentTest'])
		        {
		        	self::test($data, 'content');
		        }
		        
		        if ($data['submit'])
		        {
		            $collect = new CollectVerify();
		            $collect->exchangeArray ( $data );
		            $this->getDB ()->saveCollect($collect);
		        }
				// Redirect to list of albums
				//return $this->redirect ()->toRoute ( 'collect' );
			
		}
    }

    
    public function editAction ()
    {
    	$id=(int)$this->params('page');
    	if (!$id) $this->redirect()->toRoute('collect',array('action'=>'create'));
    	$request=$this->getRequest();
    	if ($request->isPost())
    	{
    		    $data=$request->getPost();
    		    if ($data['urlTest'])
    		    {
		        	$session=new Container('collectTest');
		        	$session->collect=array('data'=>$data,'m'=>'url');
		            $this->redirect()->toRoute('collect',array('action'=>'test'));
    		    }
    		    
    		    if ($data['contentTest'])
    		    {
    		        $session=new Container('collectTest');
    		        $session->collect=array('data'=>$data,'m'=>'content');
    		        $this->redirect()->toRoute('collect',array('action'=>'test'));
    		    }
    		    if ($data['submit']){
    		        $collect=new CollectVerify();
    		        $collect->exchangeArray($data);
    		        $this->getDB()->saveCollect($collect);
    		        $this->redirect()->toRoute('collect',array('action'=>'edit','page'=>$collect->id));
    		    }
    	}
    	$row=$this->getDB()->getCollectID($id);
    	if ($row['rule'])
    	{
    	    $row['rule']=unserialize($row['rule']);
    	}
        return array('id'=>$id,'row'=>$row);
    }

    public function doAction ()
    {
        /* $url='http://news.qq.com/newsgn/gdxw/gedixinwen.htm';
        $simple = new Simple();
        $dom=$simple->file_get_html($url);
        if (!$dom) return false;
        $res=$dom->find('html a');
       foreach ($res as $a)
       {
           if (strpos($a->href,'120.htm')) continue;
           if (strpos($a->href,'/a/2013'))
            {
                var_dump($a->plaintext);
                var_dump($a->href);
            }
       }  */
        $url="http://news.qq.com/a/20130507/000615.htm";
        $simple= new Simple();
        $dom=$simple->file_get_html($url);
        $contnet=$dom->find('div [id=Cnt-Main-Article-QQ]',0);
        $html=$simple->str_get_html($contnet);
        echo $html;
        exit();
    }

    public function dataAction ()
    {}

    public function getDB ()
    {
        if (! $this->db) {
            $sm = $this->getServiceLocator();
            $this->db = $sm->get('Admin\Model\Collect');
        }
        return $this->db;
    }
    
    public function testAction ()
    {
        $session = new Container('collectTest');
        $sData = $session->collect;
        $data = $sData['data'];
        $m = $sData['m'];
        $simple = new Simple();
        $tool = new Tool();
        $list='';
        $error='';
        if ($m == 'url') {
            $url=$data['url'];
            if (!isset($data['url'])) return false;
            if (strpos($url,'{page}')){
                $start=(int)$data['rule']['urlStart'];
                $url=str_replace('{page}', $start, $url);
            } 
            if (!$tool->checkUrl($url)) return false;
           $data['url']=$url;
           $list=$simple->list_link($data);
        }
        $content='';
        if ($m=='content')
        {
        	if (!isset($data['rule']['url']) || !$tool->checkUrl($url)) return false;
            $content=$simple->content($data['rule'], $data['rule']['url']);
        	var_dump($content);
        }
        return array('list'=>$list,'content'=>$content,'error'=>$error);
    }
}