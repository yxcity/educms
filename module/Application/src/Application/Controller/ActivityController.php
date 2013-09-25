<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use module\Application\src\Model\Tool;
use Admin\Model\Market;
use Admin\Model\News;
use Zend\Mvc\Controller\AbstractActionController;
use library\utils\ErrCode;

class ActivityController extends AbstractActionController
{
    private $domain;
    private $member;
    private $mkt_obj;
    private $news_obj;
    private $adapter;
    private $prize_table = array(
        array( 1,32,62, 92,122,152,182,212,242,272,302,332),
        array(29,58,88,118,148,178,208,238,268,298,328,358)
    );
    private $err_codes;
    private $prize_str = array(
        '谢谢参与',
        '一等奖',
        '二等奖',
        '三等奖',
        '四等奖',
        '五等奖',
        '六等奖',
        null
    );
   
    function __construct() {
        $this->domain = $domain = Tool::domain();
        $this->member = json_decode(Tool::getCookie('member'),true);
        if(!isset($this->member['id'])){
            $this->member['id'] = null;
        }
        
        if(!isset($this->member['openid'])){
            $this->member['openid'] = null;
        }
        
        //$this->member['openid'] = 'testopenidxxxx123';
    }
    
    private function gen_disk_table($prizes){
        $prize_count =  count($prizes);
        $grid_count = count($this->prize_table[0]);
        $sep_count = ceil($grid_count / $prize_count);
        $json_data = array();
        //echo "prize_count=$prize_count,grid_count=$grid_count,sep_count=$sep_count\n";
        for($i=0,$j=0;$i<$grid_count;$i++){
            if(($i % $sep_count == 0)){
                //echo "i=$i,j=$j,prize=" . $this->prize_str[$prizes[$j]['type']] . "\n";
                array_push($json_data,array(
                    'name'=>$this->prize_str[$prizes[$j]['type']],
                    'type'=>$prizes[$j]['type'],
                    'count'=>$prizes[$j]['count'],
                    'prize'=>$prizes[$j]['name'],
                    'range'=>array($this->prize_table[0][$i],$this->prize_table[1][$i]),
                ));
                $prizes[$j]['index'] = $i;
                $j++;
            }else{
                //echo "i=$i," . $this->prize_str[0] . "\n";
                array_push($json_data,array(
                    'name'=>$this->prize_str[0],
                    'type'=>0,
                    'range'=>array($this->prize_table[0][$i],$this->prize_table[1][$i]),
                ));
            }
        }

        for($i=$grid_count-1;$j<$prize_count&&$i>=0;$i--){
            if($json_data[$i]['name'] == $this->prize_str[0]){
                //echo "i=$i,j=$j,prize=" . $this->prize_str[$prizes[$j]['type']] . "\n";
                $prizes[$j]['index'] = $i;
                $json_data[$i]['name'] = $this->prize_str[$prizes[$j++]['type']];
            }
        }

        return array($prizes,$json_data);
    }
    /*
     * @param $play_count 预计总抽奖次数，用于控制抽奖概率.$play_count<=0时，直接返回谢谢参与.
     */
    private function gen_prize_result($play_count,$prize_list){
        //echo "play_count:$play_count\n";
        list($new_prize_list,$json_data) = $this->gen_disk_table($prize_list);
        $grid_empty = array();
        foreach($json_data as $prize){
            if($prize['name'] == $this->prize_str[0]){
                array_push($grid_empty,$prize);
            }
        }
        
        if($play_count > 0){
            /*随机获取中奖信息*/
            $result = $this->getRand($play_count,$new_prize_list);
        }else{
            $result = null;            
        }
        
        if($result){
            //echo "yes:" . $result['index'] . "\n";
            $result = $json_data[$result['index']];
        }else{/*未中奖，随机选择一个未中奖框*/
            $index = mt_rand(0,count($grid_empty)-1);
            //echo "no:" . $index . "\n";
            $result = $grid_empty[$index];
        }
        //var_dump($result);
        //var_dump($new_prize_list);
        
        return array(
            'angle'=>mt_rand($result['range'][0],$result['range'][1]),
            'prize'=>$result['name'],/*中奖名称*/
            'type'=>$result['type']/*中奖类型*/
        );
    }

    private function getRand($total_play_count,$prize_list){
        foreach ($prize_list as $prize) { 
            $rand_num = mt_rand(1, $total_play_count); 
            if ($rand_num <= $prize['avai_count']) { 
                return $prize;
            } else { 
                $total_play_count -= $prize['avai_count']; 
            } 
        } 
        return null; 
    } 

    private function getActivityInfo(&$info){
        $id = $this->params()->fromRoute('id');
        if(!$id){
            $id = $this->params()->fromQuery('id');
            if(!$id){
                return ErrCode::ERR_MISS_ID;
            }
        }
        
        $mkt_obj = $this->getMarketObj();
        $activity = $mkt_obj->getActivity($id);
        $info = $activity;
        if($activity['uid'] != $this->domain){
            return ErrCode::ERR_ILLEGAL_ID;
        }
        
        if(!($this->member && (isset($this->member['id']) || isset($this->member['openid'])))){
            return ErrCode::ERR_NOT_LOGIN;
        }
        
        if(isset($activity['config']) 
                && isset($activity['config']['req_registed'])
                && $activity['config']['req_registed'] 
                && (!$this->member || !$this->member['id'])){
            return ErrCode::ERR_NOT_LOGIN;
        }
        if($activity['time_type'] == 1){
            $now = time();
            if($now < strtotime($activity['open_time'])){
                return ErrCode::ERR_NOT_OPEN;
            }

            if( $now > strtotime($activity['close_time']) ){
                return ErrCode::ERR_CLOSED;
            }
        }
        return ErrCode::ERR_OK;
    }
    
    function lotteryAction()
    {
        $viewData = null;
        $info = null;
        $ret = $this->getActivityInfo($info);
        if($ret !=  ErrCode::ERR_OK){
            if($ret == ErrCode::ERR_CLOSED && isset($info['config']['closed_tips'])){
                $viewData['err_info'] = $info['config']['closed_tips'];
            }else if($ret == ErrCode::ERR_NOT_OPEN && isset($info['config']['not_open_tips'])){
                $viewData['err_info'] = $info['config']['not_open_tips'];
            }
            
            if(!isset($viewData['err_info'])){
                 $viewData['err_info'] = ErrCode::getCodeStr($ret);
            }
            $viewData['err_code'] = $ret;
        }
        //$viewData['info'] = $info['config'];
        $viewData['disk_info'] = $this->getDiskInfo($info);
        $news_obj = $this->getNewsObj();
        $viewData['news_info'] = $news_obj->getNews($info['news_id']);
        /*计算用户可抽奖次数*/
        $user_play_count = $info['try_count'];
        $mkt_obj = $this->getMarketObj();
        //var_dump($info);
        //die;
        $rcds = $mkt_obj->getRecords($info['id'],$this->member['id'],$this->member['openid']);
        if(is_array($rcds) && count($rcds) ){
            $user_play_count = $user_play_count - count($rcds) - 1;
        }
        $viewData['play_count'] = ($user_play_count>0?$user_play_count:0);
        $viewData['won_prizes'] = $mkt_obj->getPrizes($info['id'],1,null,$this->member['id'],$this->member['openid']);
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    function scratchAction()
    {
        $viewData = null;
        $info = null;
        $ret = $this->getActivityInfo($info);
        if($ret !=  ErrCode::ERR_OK){
            if($ret == ErrCode::ERR_CLOSED && isset($info['config']['closed_tips'])){
                $viewData['err_info'] = $info['config']['closed_tips'];
            }else if($ret == ErrCode::ERR_NOT_OPEN && isset($info['config']['not_open_tips'])){
                $viewData['err_info'] = $info['config']['not_open_tips'];
            }
            
            if(!isset($viewData['err_info'])){
                 $viewData['err_info'] = ErrCode::getCodeStr($ret);
            }
            $viewData['err_code'] = $ret;
        }
        //$viewData['info'] = $info['config'];
        $viewData['disk_info'] = $this->getDiskInfo($info);
        $news_obj = $this->getNewsObj();
        $viewData['news_info'] = $news_obj->getNews($info['news_id']);
        /*计算用户可抽奖次数*/
        $user_play_count = $info['try_count'];
        $mkt_obj = $this->getMarketObj();
        $rcds = $mkt_obj->getRecords($info['id'],$this->member['id'],$this->member['openid']);
        if(is_array($rcds) && count($rcds) ){
            $user_play_count = $user_play_count - count($rcds) - 1;
        }
        $viewData['play_count'] = ($user_play_count>0?$user_play_count:0);
        $viewData['won_prizes'] = $mkt_obj->getPrizes($info['id'],1,null,$this->member['id'],$this->member['openid']);
        
        //$viewData['result'] = $this->prizeAction();
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    
    /*判断奖项是否可用，生成表盘*/
    private function getDiskInfo($activity_info){
        $mkt_obj = $this->getMarketObj();
        $prizes = $mkt_obj->getPrizeInfo($activity_info['id']);
        if(!($prizes && is_array($prizes) && count($prizes))){
            return null;
        }
        
        list($new_pizes,$data) = $this->gen_disk_table($prizes);
        return $data;
    }
    
    function prizeAction(){
        $ret = $this->getActivityInfo($activity_info);
        $json_data = array();
        if($ret  !=  ErrCode::ERR_OK){
            $json_data['msg']=ErrCode::getCodeStr($ret);
            goto PRIZE_ERR;
        }
        
        if(!($this->member && (isset($this->member['id']) || isset($this->member['openid'])))){
            $json_data['msg'] = '没有找到用户信息！';
            goto PRIZE_ERR;
        }
        
        /*!!!!!!!!!!!!!!!!这一串都是数据库操作，后面可以用存储过程实现*/
        $mkt_obj = $this->getMarketObj();
        /*只允许注册用户访问*/
        if(isset($activity_info['config']) && $activity_info['config']['req_registed'] && (!$this->member || !$this->member['id'])){
            $json_data['msg'] = '只有注册用户才能参加!';
            goto PRIZE_ERR;
        }
        
        $user_play_count = $activity_info['try_count'];
        $rcds = $mkt_obj->getRecords($activity_info['id'],$this->member['id'],$this->member['openid']);
        if(is_array($rcds) && count($rcds) >=  $user_play_count){
            $json_data['msg'] = '每个用户只允许抽奖'.$activity_info['try_count'].'次哦!';
            goto PRIZE_ERR;
        }else{
            $user_play_count = $user_play_count - count($rcds) - 1;
        }
        
        /*查询所有抽奖记录，计算当前剩余抽奖次数*/
        $rcds = $mkt_obj->getRecords($activity_info['id']);
        if(is_array($rcds) && count($rcds)){
            $total_played_count =  count($rcds);
        }else{
            $total_played_count = 0;
        }
        
        if($total_played_count >= $activity_info['config']['total_play_count']){
            $total_play_count = ($activity_info['config']['total_play_count']<100?$activity_info['config']['total_play_count']:100);
        }else{
            $total_play_count = $activity_info['config']['total_play_count'] - $total_played_count;
        }
        
        /*查询剩余奖项奖信息*/
        $total_prizes = $mkt_obj->getPrizeInfo($activity_info['id']);
        $avai_prizes = $mkt_obj->getPrizeInfo($activity_info['id'],0);
        if(!($avai_prizes && is_array($avai_prizes) && count($avai_prizes))){
            $total_play_count = 0;/*没有奖项可抽了*/
        }else{
            foreach($total_prizes as &$total_prize){
                $total_prize['avai_count'] = 0;
                foreach($avai_prizes as $avai_prize){
                  if($total_prize['name'] == $avai_prize['name']){
                        $total_prize['avai_count'] = $avai_prize['count'];
                    }
                }
            }
        }
        
        //判断积分最低要求：$activity_info['req_score'];
        //处理奖励积分$activity_info['prz_score'];
        //echo "total_play_count:$total_play_count \n";
        $result_data=$this->gen_prize_result($total_play_count,$total_prizes);
        $result_data['play_count'] = $user_play_count;
        /*抽奖记录写入数据库*/
        $record = array(
            'memb_id' =>$this->member['id'],
            'openid'=>$this->member['openid'],
            'create_time'=>date("Y-m-d H:i:s"),
            'activity_id'=>$activity_info['id']
            /*agent_type,ipaddr*/
        );
        $connection = $this->adapter->getDriver()->getConnection();
        $connection->beginTransaction();
        if($result_data['type'] > 0 ){
            $available_prizes = $mkt_obj->getPrizes($activity_info['id'],0,$result_data['type']);
            shuffle($available_prizes);/*打乱结果，取第一个*/
            $now = date("Y-m-d H:i:s");
            $record['prize_id'] = $available_prizes[0]['id'];
            $result_data['sn'] = $available_prizes[0]['sn'];
            $result_data['id'] = $available_prizes[0]['id'];
            $result_data['prize_time'] = $now;
            $result_data['name'] = $available_prizes[0]['name'];
            $result_data['activity_id'] = $available_prizes[0]['activity_id'];
            if(!$mkt_obj->editPrize($record['prize_id'],array(
                'memb_id'=>$this->member['id'],
                'openid'=>$this->member['openid'],
                'prize_time'=>$now,
                'status'=>1/*未兑现*/
                ))){
                    $json_data['msg'] = '抽奖出错!';
                    $connection->rollback();
                    goto PRIZE_ERR;
                }
        }
        
        if(!$mkt_obj->addRecord($record)){
            $json_data['msg'] = '添加抽奖记录出错!';
            $connection->rollback();
            goto PRIZE_ERR;
        }
        $connection->commit();
        $json_data['data']=$result_data;
        $json_data['code']='ok';
        exit(json_encode($json_data));
        exit;
PRIZE_ERR:
        $json_data['code']='fail';
        exit(json_encode($json_data));
    }
    
    private function getMarketObj(){
        if(!$this->adapter){
            $this->adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        }
        
        if(!$this->mkt_obj){
            $this->mkt_obj = new Market($this->adapter);
        }
        return $this->mkt_obj;
    }
    
    function savephoneAction(){
        $ret = $this->getActivityInfo($activity_info);
        $json_data = array();
        if($ret  !=  ErrCode::ERR_OK){
            $json_data['msg']=ErrCode::getCodeStr($ret);
            $json_data['code'] = 'fail';
            exit(json_encode($json_data));
            return;
        }
        
        $this->check_params(array("phone","prize_id"));
        $post_params = $this->getRequest()->getPost();
        $update_data = array();
        $update_data['phone'] = $post_params['phone'];
        $mkt_obj = $this->getMarketObj();
        if($mkt_obj->editPrize($post_params['prize_id'],$update_data,$this->domain)){
            $json_data['code'] = 'ok';
        }else{
            $json_data['code'] = 'fail';
            $json_data['msg']="update cell phone error!";
        }
        exit(json_encode($json_data));
        return;
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
    
    function voteAction()
    {
        $viewData = null;
        $info = null;
        $ret = $this->getActivityInfo($info);
        if($ret !=  ErrCode::ERR_OK){
            if($ret == ErrCode::ERR_CLOSED && isset($info['config']['closed_tips'])){
                $viewData['err_info'] = $info['config']['closed_tips'];
            }else if($ret == ErrCode::ERR_NOT_OPEN && isset($info['config']['not_open_tips'])){
                $viewData['err_info'] = $info['config']['not_open_tips'];
            }
            
            if(!isset($viewData['err_info'])){
                 $viewData['err_info'] = ErrCode::getCodeStr($ret);
            }
            $viewData['err_code'] = $ret;
        }
        $viewData['config'] = $info['config'];
        $news_obj = $this->getNewsObj();
        $viewData['news_info'] = $news_obj->getNews($info['news_id']);
        /*计算用户可抽奖次数*/
        $user_play_count = $info['try_count'];
        $mkt_obj = $this->getMarketObj();
        $rcds = $mkt_obj->getRecords($info['id'],$this->member['id'],$this->member['openid']);
        if(is_array($rcds) && count($rcds) ){
            $user_play_count = $user_play_count - count($rcds);
        }
        /*查询总投票人数*/
        $rcds = $mkt_obj->getRecords($info['id']);
        if(is_array($rcds) && count($rcds) ){
            $viewData['total_played_count'] = count($rcds);
        }else{
            $viewData['total_played_count'] = 0;
        }
        $viewData['play_count'] = ($user_play_count>0?$user_play_count:0);
        $viewData['date'] = $info['create_time'];
        $viewData['stat'] = $mkt_obj->getVoteStatics($info['id']);
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
     function saveAction(){
        $ret = $this->getActivityInfo($activity_info);
        $json_data = array();
        if($ret  !=  ErrCode::ERR_OK){
            $json_data['msg']=ErrCode::getCodeStr($ret);
            $json_data['code'] = 'fail';
            exit(json_encode($json_data));
            return;
        }
        
        $this->check_params(array("data"));
        $post_params = $this->getRequest()->getPost();
        $record = array(
            'memb_id' =>$this->member['id'],
            'openid'=>$this->member['openid'],
            'create_time'=>date("Y-m-d H:i:s"),
            'activity_id'=>$activity_info['id'],
            'ipaddr'=>  ip2long($_SERVER['REMOTE_ADDR']),
            'data'=>  json_encode($post_params['data'])
        );
        
        $mkt_obj = $this->getMarketObj();
        if(!$mkt_obj->addRecord($record)){
            $json_data['code'] = 'fail';
            $json_data['msg']="update cell phone error!";
        }else{
            $json_data['code'] = 'ok';
            //$json_data['data'] = $mkt_obj->getVoteStatics($activity_info['id']);
        }
        exit(json_encode($json_data));
        return;
    }
}