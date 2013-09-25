<?php
namespace module\Application\src\Model;
use Zend\Db\TableGateway\TableGateway;
class Api
{
    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
    /**
     * @todo 保存用户提交信息
     * @param array $data
     */
    function saveData($data,$uid = null)
    {
        if ($data->MsgType=='event')
        {
        	$tid = $this->saveEvent($data);
        	return true;
        }
        if ($uid)
        {
            $inData['uid']=$uid;
        }
        $inData['userName']=$data->FromUserName;
        $inData['openid']=$data->FromUserName;
    	$inData['msgType']=$data->MsgType;
    	$inData['createTime']=$data->CreateTime;
    	$inData['msgId']=$data->MsgId;
    	if ($data->MsgType=='link')
    	{
    	    $inData['url']=$data->Url;
    	}
    	if ($data->MsgType=='image')
    	{
    	     $inData['url']=$data->PicUrl;
    	}
    	$inData['content']=isset($data->Content)?$data->Content:null;
    	$inData['locationX']=isset($data->Location_X)?$data->Location_X:null;
    	$inData['locationY']=isset($data->Location_Y)?$data->Location_Y:null;
    	$inData['scale']=isset($data->Scale)?$data->Scale:null;
    	$inData['label']=isset($data->Label)?$data->Label:null;
    	$inData['title']=isset($data->Title)?$data->Title:null;
    	$inData['description']=isset($data->Description)?$data->Description:null;
    	$table = new TableGateway('keywords', $this->adapter);
    	$table->insert($inData);
    	$tid =  $table->getLastInsertValue();
    	return $tid;
    }
    /**
     * @保存用户 用关注事件
     * @param array $data
     */
    function saveEvent($data)
    {
        $inData['userName']=$data->FromUserName;
        $inData['createTime']=$data->CreateTime;
        $inData['event']=$data->Event;
        $table = new TableGateway('subscribe', $this->adapter);
        $table->insert($inData);
        $tid = $table->getLastInsertValue();
        return $tid;
    }
}