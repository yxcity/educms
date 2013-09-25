<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Api;
use Admin\Model\Commodity;
use Admin\Model\Shop;
use Admin\Model\User;
use Admin\Model\Autoreply;
use Admin\Model\News;
use Admin\Model\Menu;
use Admin\Model\Market;
use module\Application\src\Model\Tool;

class ApiController extends AbstractActionController {

    private $token;
    private $domain;
    private $post;
    private $adapter;
    private $s;

    function __construct() {
        $this->post = $this->getPost();
    }

    function indexAction() {
        $echostr = isset($_GET['echostr']) ? $_GET['echostr'] : null;
        $domain = Tool::domain();
        $this->adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        //接入验证
        if ($echostr) {
            $db = new User($this->adapter);
            $row = $db->clickDomain($domain);
            if ($row) {
                $this->token = $row['token'];
                echo $echostr;
            } else {
                echo 'Error';
            }
            exit();
        }
        if ($this->post) {
            $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            $s = uniqid();
            $this->s = $s;
            $data = array();
            $content = null;
            // 关键字
            if ($this->post->MsgType == 'text') {
                $keyword = $this->post->Content;

                if ($keyword == '客户登记' && $domain == 'yalong') {
                    $str = 'VIP客户登陆地址  <a href="http://register.veigou.com/">点击开始登陆</a>';
                    $content = $this->postContent($this->post->FromUserName, $this->post->ToUserName, $str);
                } else {
                    $content = $this->getContent($adapter, $keyword, $domain, false, $s);
                    
                    /* 查询营销活动 */
                    if (!$content) {
                        $content = $this->getMarketReply($adapter, $keyword, null,$domain);
                    }
                    /* 查询自动回复内容 */
                    if (!$content) {
                        $content = $this->getAutoreply($adapter, $keyword, $domain, $s);
                    }

                    if (!$content) {
                        $db = new User($this->adapter);
                        $row = $db->clickDomain($domain);
                        $row['nodata'] = $this->_href($row['nodata'], $s);
                        if ($row['nodata']) {
                            $str = $row['nodata'];
                        } else {
                            $str = '暂时没有您要的结果，您的问题我们已经收到，我们会尽快处理，请直接点击这里进入广场 <a href="' . BASE_URL . '?s=' . $s . '">体验</a>';
                        }
                        $content = $this->postContent($this->post->FromUserName, $this->post->ToUserName, $str);
                    }
                }
            }

            // 地理位置
            if ($this->post->MsgType == 'location') {
                $y = $this->post->Location_X;
                $x = $this->post->Location_Y;
                $db = new Shop($adapter);
                $rows = $db->location($x, $y, $domain);
                if ($rows) {
                    $i = 0;
                    foreach ($rows as $key => $val) {
                        if ($i >= 10)
                            continue;
                        if ($key == 0) {
                            $data[$key]['title'] = $val['shopname'];
                        } else {
                            if ($val['range'] < 1000) {
                                $danwei = "米";
                            } else {
                                $val['range'] = round(($val['range'] / 1000), 1);
                                $danwei = "公里";
                            }
                            $data[$key]['title'] = $val['shopname'] . "\n距离:{$val['range']}{$danwei}";
                        }
                        $data[$key]['description'] = strip_tags(htmlspecialchars_decode(stripcslashes($val['content'])));
                        $data[$key]['pic'] = Tool::isImg(BASE_PATH . $val['thumb']) ? BASE_URL . "{$val['thumb']}" : BASE_URL . '/images/shop.jpg';
                        $data[$key]['url'] = BASE_URL . "/stores?id={$val['id']}&s={$s}";
                        $i++;
                    }
                    $content = $this->postPic($this->post->FromUserName, $this->post->ToUserName, $data);
                }
            }

            // 事件处理

            if ($this->post->MsgType == 'event' && $this->post->Event == 'subscribe') {
                $user = new User($this->adapter);
                $row = $user->clickDomain($domain);
                if ($row['wc'] == 2) {
                    $content = $this->getContent($adapter, null, $domain, true, $s);
                    if (!$content) {
                        $row['nodata'] = $this->_href($row['nodata'], $s);
                        if ($row['nodata']) {
                            $str = $row['nodata'];
                        } else {
                            $str = '暂时没有您要的结果，您的问题我们已经收到，我们会尽快处理，请直接点击这里进入广场 <a href="' . BASE_URL . '?s=' . $s . '">体验</a>';
                        }
                        $content = $this->postContent($this->post->FromUserName, $this->post->ToUserName, $str);
                    }
                } else {
                    $row['welcome'] = $this->_href($row['welcome'], $s);
                    $str = $row['welcome'] ? $row['welcome'] : '欢迎关注，精彩从现在开始';
                    $content = $this->postContent($this->post->FromUserName, $this->post->ToUserName, $str);
                }
            }

            //自定义菜单
            if ($this->post->MsgType == 'event' && $this->post->Event == 'CLICK') {
                $event_key = $this->post->EventKey;
                $menu_obj = new Menu(null, $adapter);
                $news_obj = new News($adapter);
                /* 查询营销活动 */
                $content = $this->getMarketReply($adapter, null, $event_key,$domain);
                
                if (!$content &&($news = $menu_obj->getNewsByKey($event_key))) {
                    $content = $this->postNews($this->post->FromUserName, $this->post->ToUserName, $news_obj->getNews($news['id']));
                }
                if (!$content) {
                    $db = new User($this->adapter);
                    $row = $db->clickDomain($domain);
                    if ($row ['nodata']) {
                        $str = $this->_href($row ['nodata'], $s);
                    } else {
                        $str = '暂时没有您要的结果，您的问题我们已经收到，我们会尽快处理，请直接点击这里进入广场 <a href="' . BASE_URL . '?s=' . $s . '">体验</a>';
                    }
                    $content = $this->postContent($this->post->FromUserName, $this->post->ToUserName, $str);
                }
            }
            echo $content;
            $this->saveData($this->post, $adapter, $domain);
            $openid = (string) $this->post->FromUserName;
            Tool::setCache($s, $openid);
            //$this->getServiceLocator()->get('Logger');
        }
        exit();
    }

    function postNews($to, $from, $news,$url=null) {
        if ($news['type'] == 2) {/* 文本 */
            return $this->postContent($to, $from, $news['description']);
        } else if ($news['type'] == 1) {/* 多图文 */
            $data = array_merge(array($news), $news['children']);
        } else {//单图文
            $data = array($news);
        }

        /* 修正链接 */
        foreach ($data as &$item) {
            $item['pic'] = "http://" . $_SERVER['HTTP_HOST'] . $item['pic_url'];
            /* 图文消息，如果有正文则连接到正文显示页 */
            if($url){
                $item['url'] = $url;
            }else if (($item['type'] == 1 || $item['type'] == 0) && isset($item['content']) && $item['content'] != '') {
                $item['url'] = "http://" . $_SERVER['HTTP_HOST'] . "/s/news/{$item['id']}?s={$this->s}";
            }
        }
        return $this->postPic($to, $from, $data);
    }
    
    /*
     * @描述 获取营销活动回复
     * @参数
     */
    function getMarketReply($adapter,$keword,$key_event,$domain){
        $mkt_obj = new Market($adapter);
        $news_obj = new News($adapter);
        $reply_news_list = $mkt_obj->findActivitysNews($keword,$key_event,$domain);
        if($reply_news_list && count($reply_news_list)>0){
            shuffle($reply_news_list);
            $data =$news_obj->getNews($reply_news_list[0]['id']) ;
            $act_url = null;
            switch($reply_news_list[0]['activity_type']){
                case 1:{
                    $act_url = "http://" . $_SERVER['HTTP_HOST'] . "/s/activity/lottery/{$reply_news_list[0]['activity_id']}" ;
                    break;
                }
                case 2:{
                    $act_url = "http://" . $_SERVER['HTTP_HOST'] . "/s/activity/scratch/{$reply_news_list[0]['activity_id']}" ;
                    break;
                }
                case 3:{
                    $act_url = "http://" . $_SERVER['HTTP_HOST'] . "/s/activity/vote/{$reply_news_list[0]['activity_id']}" ;
                    break;
                }
                default:{
                    $act_url = "http://" . $_SERVER['HTTP_HOST'] . "/s/activity/lottery/{$reply_news_list[0]['activity_id']}" ;
                }
            }
            return $this->postNews($this->post->FromUserName, $this->post->ToUserName,$data, $act_url);
        }
        return null;
    }
    function getAutoreply($adapter, $keyword, $domain, $s) {
        $autoreply_obj = new Autoreply($adapter);
        $auto_relies = $autoreply_obj->findReplyByKeyword($keyword, $domain);
        if (!$auto_relies || count($auto_relies) == 0) {
            return null;
        }

        /* 多条规则匹配时打扰顺序，随机回复一条 */
        if (count($auto_relies) > 1) {
            srand((float) microtime() * 1000000);
            shuffle($auto_relies);
        }

        /* 选中规则中有多个回复匹配时打乱顺序，随机回复一条 */
        $reply_item = $auto_relies[0];
        if ($reply_item['replies'] && count($reply_item['replies']) > 1) {
            srand((float) microtime() * 1000000);
            shuffle($reply_item['replies']);
        }
        $data = array();
        $news_obj = new News($adapter);
        foreach ($reply_item['replies'] as $news_item) {//偷了个懒，不用判断数组是否有值
            $data = $news_obj->getNews($news_item['id']);
            return $this->postNews($this->post->FromUserName, $this->post->ToUserName, $data);
        }

        return null;
    }

    function getContent($adapter, $keyword = null, $domain = null, $welcome = null, $s = null) {
        $data = array();
        $db = new Commodity($adapter);
        $rows = $db->keyList($keyword, $domain, $welcome, true);
        if ($welcome && !$rows->count())
            return false;
        if ($rows->count()) {
            $i = 0;
            foreach ($rows as $key => $val) {
                if ($i >= 10)
                    continue;
                $data[$key]['title'] = $val['name'] . "[商品]";
                $data[$key]['description'] = strip_tags(htmlspecialchars_decode(stripcslashes($val['weixin'])));
                $data[$key]['pic'] = Tool::isImg(BASE_PATH . $val['thumb']) ? BASE_URL . $val['thumb'] : BASE_URL . '/images/29.jpg';
                $data[$key]['url'] = BASE_URL . "/product?id={$val['id']}&s={$s}";
                $i++;
            }

            if ($welcome) {
                $content = $this->postPic($this->post->FromUserName, $this->post->ToUserName, $data);
                return $content;
            }
        }
        if ($rows->count() <= '10') {
            $dbs = new shop($adapter);
            $rowss = $dbs->keyList($keyword, $domain);
            if ($rowss->count()) {
                $i = 0;
                foreach ($rowss as $key => $val) {
                    if (($i + $rows->count()) >= 10)
                        continue;
                    $datalist[$key]['title'] = $val['shopname'] . "[门店]";
                    $datalist[$key]['description'] = strip_tags(htmlspecialchars_decode(stripcslashes($val['content'])));
                    $datalist[$key]['pic'] = Tool::isImg(BASE_PATH . $val['thumb']) ? BASE_URL . $val['thumb'] : BASE_URL . '/images/29.jpg';
                    $datalist[$key]['url'] = BASE_URL . "/stores?id={$val['id']}&s={$s}";
                    $i++;
                }
                $data = array_merge($data, $datalist);
            }
        }
        $content = $this->postPic($this->post->FromUserName, $this->post->ToUserName, $data);

        if (!$rows->count() && !$rowss->count())
            return false;
        return $content;
    }

    private function postContent($to, $form, $string) {
        $content = "<xml><ToUserName><![CDATA[{$to}]]></ToUserName><FromUserName><![CDATA[{$form}]]></FromUserName><CreateTime>" . time() . "</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[{$string}]]></Content><FuncFlag>0</FuncFlag></xml>";
        return $content;
    }

    private function postMusic($to, $form, $data) {
        $content = " <xml><ToUserName><![CDATA[{$to}]]></ToUserName><FromUserName><![CDATA[{$form}]]></FromUserName><CreateTime>" . time() . "</CreateTime><MsgType><![CDATA[music]]></MsgType><Music><Title><![CDATA[{$data['title']}]]></Title><Description><![CDATA[{$data['description']}]]></Description><MusicUrl><![CDATA[{$data['url']}]]></MusicUrl><HQMusicUrl><![CDATA[{$data['HQurl']}]]></HQMusicUrl></Music><FuncFlag>0</FuncFlag></xml>";
        return $content;
    }

    private function postPic($to, $form, $data) {
        $content = "<xml><ToUserName><![CDATA[{$to}]]></ToUserName><FromUserName><![CDATA[{$form}]]></FromUserName><CreateTime>" . time() . "</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>" . count($data) . "</ArticleCount><Articles>";
        foreach ($data as $val) {
            $content .= "<item><Title><![CDATA[{$val['title']}]]></Title><Description><![CDATA[{$val['description']}]]></Description><PicUrl><![CDATA[{$val['pic']}]]></PicUrl><Url><![CDATA[{$val['url']}]]></Url></item>";
        }
        $content .= "</Articles><FuncFlag>1</FuncFlag></xml>";
        return $content;
    }

    /**
     * @todo 保存用户提交
     * @param array $data
     * @param array $adapter
     */
    private function saveData($data, $adapter, $uid = null) {
        $apiDb = new Api($adapter);
        return $apiDb->saveData($data, $uid);
    }

    /**
     *
     * @todo 取得微信服务器POST的数据
     * @return SimpleXMLElement boolean
     */
    private function getPost() {
        $post = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : null;
        if (!empty($post)) {
            $post = simplexml_load_string($post, 'SimpleXMLElement', LIBXML_NOCDATA);
            return $post;
        }
        return false;
    }

    /**
     * 验证是否来自微信服务器
     *
     * @return boolean
     */
    private function checkSignature() {
        $signature = isset($_GET["signature"]) ? $_GET["signature"] : null;
        $timestamp = isset($_GET["timestamp"]) ? $_GET["signature"] : null;
        $nonce = isset($_GET["nonce"]) ? $_GET["nonce"] : null;
        $tmpArr = array(
            $this->token,
            $timestamp,
            $nonce
        );
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @todo 替换链接
     * @param string $str
     * @param string $s
     * @return Ambigous <string, mixed>
     */
    private function _href($str, $s) {
        $str = htmlspecialchars_decode(stripslashes($str));
        preg_match_all('/<a.*?(?: |\\t|\\r|\\n)?href=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>(.+?)<\/a.*?>/sim', $str, $m);
        if (isset($m[1])) {
            foreach ($m[1] as $key => $val) {
                if (strstr($val, '?')) {
                    $val = $val . "&s={$s}";
                }
                $val = str_replace('{openid}', "?s={$s}", $val);
                $substr = substr($val, -1, 2);
                if ($substr == 'm/' || $substr == 'n/') {
                    $val = str_replace('m/', "m?s={$s}", $val);
                    $val = str_replace('n/', "n?s={$s}", $val);
                }
                $keyword = $m[2][$key];
                $href = '<a href="' . $val . '">' . $keyword . '</a>';
                $str = str_replace($keyword, $href, strip_tags($str));
            }
        }
        return $str;
    }

}