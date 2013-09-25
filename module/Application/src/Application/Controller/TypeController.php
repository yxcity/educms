<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\User;
use Admin\Model\Type;
use module\Application\src\Model\Tool;

class TypeController extends AbstractActionController
{
    
    function indexAction ()
    {
        $domain = Tool::domain();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Type($adapter);
        $tmpRows = $db->getTypeTree ( $domain, 10 );
        $str='';
        if ($tmpRows) {
        
        	foreach ( $tmpRows as $key=>$val ) {
        		if ($val ['pid'] == 0) {
        			if ($val['sub_tree'] && $val['sub_tree'])
        			{
        				$str.= "<h1 onClick=\"javascript:ShowMenu(this,'NO{$key}')\"><a>{$val['name']}<i></i></a></h1>";
        				$str.=$this->_subTree($val,$key);
        			}else 
        			{
        				$str.= "<h1><a href=\"/product/list?id={$val['id']}\">{$val['name']}<i></i></a></h1>";
        			}
        			
        		}
        	}
        }
        $viewData['typeStr'] = $str;
        //$viewData['rows'] = $db->typeAll($domain);
        $uDB = new User($adapter);
        $viewData['row']=$uDB->clickDomain($domain);
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    function areasAction()
    {
    	$pid=$this->params()->fromQuery('pid');
    	$db = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    	$rows=$db->areas($pid);
    	if ($rows)
    	{
    		$str='';
    	    foreach ($rows as $val) {
    			$str.= "<option value=\"{$val['areaid']}\">{$val['name']}</option>".PHP_EOL;
    		}
    		echo $str;
    	}
    	exit();
    }
    
    function _subTree($val,$i)
    {
    	$str="";
    	$ulStr="";
    	$str = "<span id=\"NO{$i}\" class=\"no\">";
    	if ($val['sub_tree'] && $val['sub_tree'])
    	{
    		foreach ($val['sub_tree'] as $key=>$val) {
    			if ($val['sub_tree'] && $val['sub_tree'])
    			{
    				$str.= "<h2 id=\"NO{$i}{$key}_h2\" onClick=\"javascript:ShowMenu(this,'NO{$i}{$key}',className='prhh')\"><a>{$val['name']}</a></h2>";
    				$ulStr.=$this->_subTreeUL($val,$i,$key);
    			}else 
    			{
    				$str.= "<h2 id=\"NO{$i}{$key}_h2\"><a href=\"/product/list?id={$val['id']}\">{$val['name']}</a></h2>";
    			}
    			
    		}
    	}
    	$str .= "{$ulStr}</span>";
    	return $str;
    }
    
    function _subTreeUL($val,$i,$key)
    {
    	
    	$str="<ul id=\"NO{$i}{$key}\" class=\"no\">";
    	if ($val['sub_tree'] && $val['sub_tree'])
    	{
    		foreach ($val['sub_tree'] as $key=>$val) {
    			$str.= "<li><a href=\"/product/list?id={$val['id']}\">{$val['name']}</a></li>";
    		}
    	}
    	$str.="</ul>";
    	return $str;
    }
    
    
}