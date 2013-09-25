<?php
namespace Admin\Tools;

use Admin\Tools\SimpleDom;
//use Admin\Tools\Tool;

define('HDOM_TYPE_ELEMENT', 1);
define('HDOM_TYPE_COMMENT', 2);
define('HDOM_TYPE_TEXT', 3);
define('HDOM_TYPE_ENDTAG', 4);
define('HDOM_TYPE_ROOT', 5);
define('HDOM_TYPE_UNKNOWN', 6);
define('HDOM_QUOTE_DOUBLE', 0);
define('HDOM_QUOTE_SINGLE', 1);
define('HDOM_QUOTE_NO', 3);
define('HDOM_INFO_BEGIN', 0);
define('HDOM_INFO_END', 1);
define('HDOM_INFO_QUOTE', 2);
define('HDOM_INFO_SPACE', 3);
define('HDOM_INFO_TEXT', 4);
define('HDOM_INFO_INNER', 5);
define('HDOM_INFO_OUTER', 6);
define('HDOM_INFO_ENDSPACE', 7);
define('DEFAULT_TARGET_CHARSET', 'UTF-8');
define('DEFAULT_BR_TEXT', "\r\n");
define('DEFAULT_SPAN_TEXT', " ");
define('MAX_FILE_SIZE', 600000);
// helper functions
// -----------------------------------------------------------------------------
class Simple
{
    // get html dom from file
    // $maxlen is defined in the code as PHP_STREAM_COPY_ALL which is defined as -1.
    function file_get_html ($url, $use_include_path = false, $context = null, $offset = -1, $maxLen = -1, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
    {
        // We DO force the tags to be terminated.
        $dom = new SimpleDom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        // For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
        $contents = @file_get_contents($url, $use_include_path, $context, $offset);
        // Paperg - use our own mechanism for getting the contents as we want to control the timeout.
        // $contents = retrieve_url_contents($url);
        if (empty($contents) || strlen($contents) > MAX_FILE_SIZE) {
            return false;
        }
        // The second parameter can force the selectors to all be lowercase.
        $dom->load($contents, $lowercase, $stripRN);
        return $dom;
    }
    
    // get html dom from string
    function str_get_html ($str, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
    {
        $dom = new SimpleDom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        if (empty($str) || strlen($str) > MAX_FILE_SIZE) {
            $dom->clear();
            return false;
        }
        $dom->load($str, $lowercase, $stripRN);
        return $dom;
    }
    
    // dump html dom tree
    function dump_html_tree ($node, $show_attr = true, $deep = 0)
    {
        $node->dump($node);
    }
    /**
     * 取得列表地址
     * @param Array $data
     */
    function list_link($data)
    {
    	$tool= new Tool();
        if (! $tool->checkUrl($data['url'])) {
    		return false;
    	}
    	$dom = $this->file_get_html($data['url']);
    	if (! $dom) {
    		return false;
    	}
    	//替换
    	$replace=array();
    	if ($data['rule']['urlReplace'])
    	{
    		$replace=explode(';', $data['rule']['urlReplace']);
    	}
    	//含有
    	$contain=array();
    	if ($data['rule']['contain'])
    	{
    		$contain=explode(';', $data['rule']['contain']);
    	}
    	//不含有
    	$exclusive=array();
    	if ($data['rule']['exclusive'])
    	{
    		$exclusive=explode(';', $data['rule']['exclusive']);
    	}
    	$find=$data['rule']['limits']?"{$data['rule']['limits']} a":'a';
    	$res = $dom->find($find);
    	$list=array();
    	foreach ($res as $k => $a) {
    		if ($a->plaintext && $a->href) {
    			if ($contain) {
    				foreach ($contain as $c) {
    					if (strpos($a->href,$c))
    					{
    						$list[$k] = array(
    								'title' => $a->plaintext,
    								'href' => $a->href
    						);
    					}
    				}
    			} else {
    				$list[$k] = array(
    						'title' => $a->plaintext,
    						'href' => $a->href
    				);
    			}
    		}
    	}
    	
    	//过滤替换
    	if ($exclusive || $replace)
    	{
    		foreach ($list as $key=>$val) {
    			foreach ($exclusive as $e) {
    				if (strpos($val['href'],$e))
    				{
    					unset($list[$key]);
    				}
    			}
    			foreach ($replace as $r) {
    				if ($r)
    				{
    					$rTemp=explode('=>', $r);
    					if (isset($rTemp[0]) && isset($rTemp[1]))
    					{
    						$list[$key]['href']=str_replace($rTemp[0], $rTemp[1], $val['href']);
    					}
    				}
    			}
    		}
    	}
    	return $list;
    }
    
    function content($rule,$url)
    {
    	$tool = new Tool();
    	if (!$tool->checkUrl($url)) return false;
    	$dom = $this->file_get_html($url);
    	if (!$dom) return false;
    	$content=array();
    	if ($rule['title'])
    	{
    	    $title=$dom->find($rule['title'],0);
    	}else
    	{
    		$title=$dom->find('title',0);
    	}
    	$title=$title->plaintext;
    	if ($rule['titleFilter'])
    	{
    		$title=$this->filter($rule['titleFilter'], $title);
    	}
    	
    	if ($rule['titleReplace'])
    	{
    	    $title=$this->replace($rule['titleReplace'], $title);
    	}
    	$content['title']=$title;
    	$keywords=null;
    	$description=null;
    	if (!$rule['keywords'] && !$rule['description'])
    	{
    	    $meta=$dom->find('meta');
    	    foreach ($meta as $val) {
    	    	if (strtolower($val->name)=='keywords') $keywords=$val->content;
    	    	if (strtolower($val->name)=='description') $description=$val->content;
    	    }
    	}
    	
    	if ($rule['keywords'])
    	{
    	    $keywords=$dom->find($rule['keywords'],0)->plaintext;
    	}
    	$keywords=$this->filter($rule['keywordsFilter'], $keywords);
    	$keywords=$this->replace($rule['keywordsReplace'], $keywords);
    	$content['keywords']=$keywords;
    	
    	if ($rule['description'])
    	{
    	    $description=$dom->find($rule['description'],0)->plaintext;
    	}
    	$description=$this->filter($rule['descriptionFilter'], $description);
    	$description=$this->replace($rule['descriptionReplace'], $description);
    	$content['description']=$description;
    	
    	if ($rule['content'])
    	{
    	   $text = isset($rule['text']) ? 1 : 0;
    	   $body=$dom->find($rule['content'],0);
    	   $body=$text?$body->plaintext:$body->innertext;
    	   if ($rule['paging'])
    	   {
    	   	$page=$dom->find("{$rule['paging']} a");
    	   	foreach ($page as $val) {
    	   		if ($val->href)
    	   		{
    	   			$body.= $this->paging($rule['content'], $val->href,$text);
    	   		}
    	   	}
    	  }
    	    if ($rule['contentFilter'])
    	    {
    	        $body=$this->filter($rule['contentFilter'], $body);
    	    }
    	    if ($rule['contentReplace'])
    	    {
    	        $body=$this->replace($rule['contentReplace'], $body);
    	    }
    	    
    	   $content['body']=$body;
    	}
    	return $content;
    }

    function paging($rule,$url,$text=null)
    {
    	$tool = new Tool();
    	if (!$tool->checkUrl($url)) return false;
        $dom= $this->file_get_html($url);
        if (!$dom) return false;
        $body=$dom->find($rule,0);
        $body=$text?$body->plaintext:$body->innertext;
    	return $body;
    }
    
    function replace($rule,$content){
    	$r=explode(';', $rule);
    	if ($r && is_array($r))
    	{
    		foreach ($r as $val) {
    		    $val=explode('=>', $val);
    		    if (isset($val[0]) && isset($val[1]))
    		    {
    		        $content=str_replace($val[0], $val[1], $content);
    		    }
    		}
    	}
    	return $content;
    }
    
    function filter($rule,$content){
    	$r = explode(';', $rule);
    	if ($r && is_array($r))
    	{
    	    foreach ($r as $val) {
    			$content=str_replace($val, '', $content);
    		}
    	}
    	return $content;
    }
    
}
?>