<?php

namespace wechat;


class QyWeChat
{
    const MSGTYPE_TEXT = 'text';
    const MSGTYPE_IMAGE = 'image';
    const MSGTYPE_LOCATION = 'location';
    const MSGTYPE_LINK = 'link';        //暂不支持
    const MSGTYPE_EVENT = 'event';
    const MSGTYPE_MUSIC = 'music';        //暂不支持
    const MSGTYPE_NEWS = 'news';
    const MSGTYPE_VOICE = 'voice';
    const MSGTYPE_VIDEO = 'video';
    const EVENT_SUBSCRIBE = 'subscribe';      //订阅
    const EVENT_UNSUBSCRIBE = 'unsubscribe';    //取消订阅
    const EVENT_LOCATION = 'LOCATION';       //上报地理位置
    const EVENT_ENTER_AGENT = 'enter_agent';    //用户进入应用
    const EVENT_MENU_VIEW = 'VIEW';                //菜单 - 点击菜单跳转链接
    const EVENT_MENU_CLICK = 'CLICK';              //菜单 - 点击菜单拉取消息
    const EVENT_MENU_SCAN_PUSH = 'scancode_push';      //菜单 - 扫码推事件(客户端跳URL)
    const EVENT_MENU_SCAN_WAITMSG = 'scancode_waitmsg';    //菜单 - 扫码推事件(客户端不跳URL)
    const EVENT_MENU_PIC_SYS = 'pic_sysphoto';       //菜单 - 弹出系统拍照发图
    const EVENT_MENU_PIC_PHOTO = 'pic_photo_or_album'; //菜单 - 弹出拍照或者相册发图
    const EVENT_MENU_PIC_WEIXIN = 'pic_weixin';         //菜单 - 弹出微信相册发图器
    const EVENT_MENU_LOCATION = 'location_select';    //菜单 - 弹出地理位置选择器
    const EVENT_SEND_MASS = 'MASSSENDJOBFINISH';        //发送结果 - 高级群发完成
    const EVENT_SEND_TEMPLATE = 'TEMPLATESENDJOBFINISH';//发送结果 - 模板消息发送结果
    const API_URL_PREFIX = 'https://qyapi.weixin.qq.com/cgi-bin';
    const USER_CREATE_URL = '/user/create?';
    const USER_UPDATE_URL = '/user/update?';
    const USER_DELETE_URL = '/user/delete?';
    const USER_BATCHDELETE_URL = '/user/batchdelete?';
    const USER_GET_URL = '/user/get?';
    const USER_LIST_URL = '/user/simplelist?';
    const USER_LIST_INFO_URL = '/user/list?';
    const USER_GETINFO_URL = '/user/getuserinfo?';
    const USER_INVITE_URL = '/invite/send?';
    const DEPARTMENT_CREATE_URL = '/department/create?';
    const DEPARTMENT_UPDATE_URL = '/department/update?';
    const DEPARTMENT_DELETE_URL = '/department/delete?';
    const DEPARTMENT_MOVE_URL = '/department/move?';
    const DEPARTMENT_LIST_URL = '/department/list?';
    const TAG_CREATE_URL = '/tag/create?';
    const TAG_UPDATE_URL = '/tag/update?';
    const TAG_DELETE_URL = '/tag/delete?';
    const TAG_GET_URL = '/tag/get?';
    const TAG_ADDUSER_URL = '/tag/addtagusers?';
    const TAG_DELUSER_URL = '/tag/deltagusers?';
    const TAG_LIST_URL = '/tag/list?';
    const MEDIA_UPLOAD_URL = '/media/upload?';
    const MEDIA_GET_URL = '/media/get?';
    const AUTHSUCC_URL = '/user/authsucc?';
    const MASS_SEND_URL = '/message/send?';
    const MENU_CREATE_URL = '/menu/create?';
    const MENU_GET_URL = '/menu/get?';
    const MENU_DELETE_URL = '/menu/delete?';
    const TOKEN_GET_URL = '/gettoken?';
    const TICKET_GET_URL = '/get_jsapi_ticket?';
    const CALLBACKSERVER_GET_URL = '/getcallbackip?';
    const OAUTH_PREFIX = 'https://open.weixin.qq.com/connect/oauth2';
    const OAUTH_AUTHORIZE_URL = '/authorize?';

    const CHAT_CREATE_URL = '/chat/create?';
    const CHAT_GET_URL = '/chat/get?';
    const CHAT_UPDATE_URL = '/chat/update?';
    const CHAT_QUIT_URL = '/chat/quit?';
    const CHAT_CLEARNOTIFY_URL = '/chat/clearnotify?';
    const CHAT_SEND_URL = '/chat/send?';
    const CHAT_SETMUTE_URL = '/chat/setmute?';

    private $token;
    private $encodingAesKey;
    private $appid;         //也就是企业号的CorpID
    private $appsecret;
    private $appsecret_im;
    private $access_token;
    private $agentid;       //应用id   AgentID
    private $postxml;
    private $agentidxml;    //接收的应用id   AgentID
    private $_msg;
    private $_receive;
    private $_text_filter = true;
    private $jsapi_ticket;

    public $debug = false;
    public $errCode = 40001;
    public $errMsg = "no access";

    public $im = false;

    public function __construct($options)
    {
        $this->token = isset($options['token']) ? $options['token'] : '';
        $this->encodingAesKey = isset($options['encodingaeskey']) ? $options['encodingaeskey'] : '';
        $this->appid = isset($options['appid']) ? $options['appid'] : '';
        $this->appsecret = isset($options['appsecret']) ? $options['appsecret'] : '';
        $this->appsecret_im = isset($options['appsecret_im']) ? $options['appsecret_im'] : '';
        $this->agentid = isset($options['agentid']) ? $options['agentid'] : '';
        $this->debug = isset($options['debug']) ? $options['debug'] : false;
        $this->im = isset($options['im']) ? $options['im'] : false;
    }

    protected function log($log)
    {
        return true;
    }

    /**
     * 数据XML编码
     *
     * @param mixed $data 数据
     *
     * @return string
     */
    public static function data_to_xml($data)
    {
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "item id=\"$key\"";
            $xml .= "<$key>";
            $xml .= (is_array($val) || is_object($val)) ? self::data_to_xml($val) : self::xmlSafeStr($val);
            list($key,) = explode(' ', $key);
            $xml .= "</$key>";
        }

        return $xml;
    }

    public static function xmlSafeStr($str)
    {
        return '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $str) . ']]>';
    }

    /**
     * XML编码
     *
     * @param mixed  $data 数据
     * @param string $root 根节点名
     * @param string $attr 根节点属性
     *
     * @return string
     */
    public function xml_encode($data, $root = 'xml', $attr = '')
    {
        if (is_array($attr)) {
            $_attr = array();
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml = "<{$root}{$attr}>";
        $xml .= self::data_to_xml($data);
        $xml .= "</{$root}>";

        return $xml;
    }

    /**
     * 微信api不支持中文转义的json结构
     *
     * @param $arr
     *
     * @return string
     */
    static function json_encode($arr)
    {
        $parts = array();
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys($arr);
        $max_length = count($arr) - 1;
        if (!empty($keys) && ($keys [0] === 0) && ($keys [$max_length] === $max_length)) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for ($i = 0; $i < count($keys); $i++) { //See if each key correspondes to its position
                if ($i != $keys [$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }
        foreach ($arr as $key => $value) {
            if (is_array($value)) { //Custom handling for arrays
                if ($is_list)
                    $parts [] = self::json_encode($value); /* :RECURSION: */
                else
                    $parts [] = '"' . $key . '":' . self::json_encode($value); /* :RECURSION: */
            } else {
                $str = '';
                if (!$is_list)
                    $str = '"' . $key . '":';
                //Custom handling for multiple data types
                if (!is_string($value) && is_numeric($value) && $value < 2000000000)
                    $str .= $value; //Numbers
                elseif ($value === false)
                    $str .= 'false'; //The booleans
                elseif ($value === true)
                    $str .= 'true';
                else
                    $str .= '"' . addcslashes($value, "\\\"\n\r\t/") . '"'; //All other things
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts [] = $str;
            }
        }
        $json = implode(',', $parts);
        if ($is_list)
            return '[' . $json . ']'; //Return numerical JSON

        return '{' . $json . '}'; //Return associative JSON
    }

    /**
     * 过滤文字回复\r\n换行符
     *
     * @param string $text
     *
     * @return string|mixed
     */
    private function _auto_text_filter($text)
    {
        if (!$this->_text_filter) return $text;

        return str_replace("\r\n", "\n", $text);
    }

    /**
     * GET 请求
     *
     * @param      $url
     * @param bool $log
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    private function http_get($url, $log = true)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        $log && $this->log(['url' => $url, 'data' => $sContent]);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * POST 请求
     *
     * @param string  $url
     * @param         $param
     * @param boolean $post_file 是否文件上传
     *
     * @return string content
     */
    private function http_post($url, $param, $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        version_compare(PHP_VERSION, '5.5', '>') || curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        $this->log(['url' => $url, 'param' => $strPOST, 'data' => $sContent]);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * For weixin server validation
     *
     * @param $str
     *
     * @return bool
     */
    private function checkSignature($str)
    {
        $signature = isset($_GET["msg_signature"]) ? $_GET["msg_signature"] : '';
        $timestamp = isset($_GET["timestamp"]) ? $_GET["timestamp"] : '';
        $nonce = isset($_GET["nonce"]) ? $_GET["nonce"] : '';
        $tmpArr = array($str, $this->token, $timestamp, $nonce);//比普通公众平台多了一个加密的密文
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $shaStr = sha1($tmpStr);
        if ($shaStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 微信验证，包括post来的xml解密
     *
     * @param bool $return 是否返回
     *
     * @return bool
     */
    public function valid($return = false)
    {
        $encryptStr = "";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $postStr = file_get_contents("php://input");
            $array = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->log($postStr);
            if (isset($array['Encrypt'])) {
                $encryptStr = $array['Encrypt'];
                $this->agentidxml = isset($array['AgentID']) ? $array['AgentID'] : '';
            }
        } else {
            $encryptStr = isset($_GET["echostr"]) ? $_GET["echostr"] : '';
        }
        if ($encryptStr) {
            $ret = $this->checkSignature($encryptStr);
        }
        if (!isset($ret) || !$ret) {
            if (!$return)
                $this->errMsg = 'no access';

            return false;
        }
        $pc = new Prpcrypt($this->encodingAesKey);
        $array = $pc->decrypt($encryptStr, $this->appid);
        if (!isset($array[0]) || ($array[0] != 0)) {
            if (!$return)
                $this->errMsg = '解密失败！';

            return false;
        }
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $this->postxml = $array[1];

            //$this->log($array[1]);
            return ($this->postxml != "");
        } else {
            $echoStr = $array[1];
            if ($return) {
                return $echoStr;
            } else {
                if (!$return)
                    $this->errMsg = $echoStr;

                return false;
            }
        }
    }

    /**
     * 获取微信服务器发来的信息
     */
    public function getRev()
    {
        if ($this->_receive) return $this;
        $postStr = $this->postxml;
        $this->log($postStr);
        if (!empty($postStr)) {
            $this->_receive = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!isset($this->_receive['AgentID'])) {
                $this->_receive['AgentID'] = $this->agentidxml; //当前接收消息的应用id
            }
        }

        return $this;
    }

    /**
     * 获取微信服务器发来的信息
     */
    public function getRevData()
    {
        return $this->_receive;
    }

    /**
     * 获取微信服务器发来的原始加密信息
     */
    public function getRevPostXml()
    {
        return $this->postxml;
    }

    /**
     * 获取消息发送者
     */
    public function getRevFrom()
    {
        if (isset($this->_receive['FromUserName']))
            return $this->_receive['FromUserName'];
        else
            return false;
    }

    /**
     * 获取消息接受者
     */
    public function getRevTo()
    {
        if (isset($this->_receive['ToUserName']))
            return $this->_receive['ToUserName'];
        else
            return false;
    }

    /**
     * 获取接收消息的应用id
     */
    public function getRevAgentID()
    {
        if (isset($this->_receive['AgentID']))
            return $this->_receive['AgentID'];
        else
            return false;
    }

    /**
     * 获取接收消息的类型
     */
    public function getRevType()
    {
        if (isset($this->_receive['MsgType']))
            return $this->_receive['MsgType'];
        else
            return false;
    }

    /**
     * 获取消息ID
     */
    public function getRevID()
    {
        if (isset($this->_receive['MsgId']))
            return $this->_receive['MsgId'];
        else
            return false;
    }

    /**
     * 获取消息发送时间
     */
    public function getRevCtime()
    {
        if (isset($this->_receive['CreateTime']))
            return $this->_receive['CreateTime'];
        else
            return false;
    }

    /**
     * 获取接收消息内容正文
     */
    public function getRevContent()
    {
        if (isset($this->_receive['Content']))
            return $this->_receive['Content'];
        else
            return false;
    }

    /**
     * 获取接收消息图片
     */
    public function getRevPic()
    {
        if (isset($this->_receive['PicUrl']))
            return array(
                'mediaid' => $this->_receive['MediaId'],
                'picurl'  => (string)$this->_receive['PicUrl'],    //防止picurl为空导致解析出错
            );
        else
            return false;
    }

    /**
     * 获取接收地理位置
     */
    public function getRevGeo()
    {
        if (isset($this->_receive['Location_X'])) {
            return array(
                'x'     => $this->_receive['Location_X'],
                'y'     => $this->_receive['Location_Y'],
                'scale' => (string)$this->_receive['Scale'],
                'label' => (string)$this->_receive['Label']
            );
        } else
            return false;
    }

    /**
     * 获取上报地理位置事件
     */
    public function getRevEventGeo()
    {
        if (isset($this->_receive['Latitude'])) {
            return array(
                'x'         => $this->_receive['Latitude'],
                'y'         => $this->_receive['Longitude'],
                'precision' => $this->_receive['Precision'],
            );
        } else
            return false;
    }

    /**
     * 获取接收事件推送
     */
    public function getRevEvent()
    {
        if (isset($this->_receive['Event'])) {
            $array['event'] = $this->_receive['Event'];
        }
        if (isset($this->_receive['EventKey']) && !empty($this->_receive['EventKey'])) {
            $array['key'] = $this->_receive['EventKey'];
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        } else {
            return false;
        }
    }

    /**
     * 获取自定义菜单的扫码推事件信息
     *
     * 事件类型为以下两种时则调用此方法有效
     * Event     事件类型，scancode_push
     * Event     事件类型，scancode_waitmsg
     *
     * @return array|bool
     */
    public function getRevScanInfo()
    {
        if (isset($this->_receive['ScanCodeInfo'])) {
            if (!is_array($this->_receive['SendPicsInfo'])) {
                $array = (array)$this->_receive['ScanCodeInfo'];
                $this->_receive['ScanCodeInfo'] = $array;
            } else {
                $array = $this->_receive['ScanCodeInfo'];
            }
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        } else {
            return false;
        }
    }

    /**
     * 获取自定义菜单的图片发送事件信息
     *
     * 事件类型为以下三种时则调用此方法有效
     * Event     事件类型，pic_sysphoto        弹出系统拍照发图的事件推送
     * Event     事件类型，pic_photo_or_album  弹出拍照或者相册发图的事件推送
     * Event     事件类型，pic_weixin          弹出微信相册发图器的事件推送
     *
     * @return array|bool
     */
    public function getRevSendPicsInfo()
    {
        if (isset($this->_receive['SendPicsInfo'])) {
            if (!is_array($this->_receive['SendPicsInfo'])) {
                $array = (array)$this->_receive['SendPicsInfo'];
                if (isset($array['PicList'])) {
                    $array['PicList'] = (array)$array['PicList'];
                    $item = $array['PicList']['item'];
                    $array['PicList']['item'] = array();
                    foreach ($item as $key => $value) {
                        $array['PicList']['item'][$key] = (array)$value;
                    }
                }
                $this->_receive['SendPicsInfo'] = $array;
            } else {
                $array = $this->_receive['SendPicsInfo'];
            }
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        } else {
            return false;
        }
    }

    /**
     * 获取自定义菜单的地理位置选择器事件推送
     *
     * 事件类型为以下时则可以调用此方法有效
     * Event     事件类型，location_select        弹出系统拍照发图的事件推送
     *
     * @return array|bool
     *
     * array (
     *   'Location_X' => '33.731655000061',
     *   'Location_Y' => '113.29955200008047',
     *   'Scale' => '16',
     *   'Label' => '某某市某某区某某路',
     *   'Poiname' => '',
     * )
     */
    public function getRevSendGeoInfo()
    {
        if (isset($this->_receive['SendLocationInfo'])) {
            if (!is_array($this->_receive['SendLocationInfo'])) {
                $array = (array)$this->_receive['SendLocationInfo'];
                if (empty($array['Poiname'])) {
                    $array['Poiname'] = "";
                }
                if (empty($array['Label'])) {
                    $array['Label'] = "";
                }
                $this->_receive['SendLocationInfo'] = $array;
            } else {
                $array = $this->_receive['SendLocationInfo'];
            }
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        } else {
            return false;
        }
    }

    /**
     * 获取接收语音推送
     */
    public function getRevVoice()
    {
        if (isset($this->_receive['MediaId'])) {
            return array(
                'mediaid' => $this->_receive['MediaId'],
                'format'  => $this->_receive['Format'],
            );
        } else
            return false;
    }

    /**
     * 获取接收视频推送
     */
    public function getRevVideo()
    {
        if (isset($this->_receive['MediaId'])) {
            return array(
                'mediaid'      => $this->_receive['MediaId'],
                'thumbmediaid' => $this->_receive['ThumbMediaId']
            );
        } else
            return false;
    }

    /**
     * 设置回复消息
     * Example: $obj->text('hello')->reply();
     *
     * @param string $text
     *
     * @return $this
     */
    public function text($text = '')
    {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_TEXT,
            'Content'      => $this->_auto_text_filter($text),
            'CreateTime'   => time(),
        );
        $this->Message($msg);

        return $this;
    }

    /**
     * 设置回复消息
     * Example: $obj->image('media_id')->reply();
     *
     * @param string $mediaid
     *
     * @return $this
     */
    public function image($mediaid = '')
    {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_IMAGE,
            'Image'        => array('MediaId' => $mediaid),
            'CreateTime'   => time(),
        );
        $this->Message($msg);

        return $this;
    }

    /**
     * 设置回复消息
     * Example: $obj->voice('media_id')->reply();
     *
     * @param string $mediaid
     *
     * @return $this
     */
    public function voice($mediaid = '')
    {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_IMAGE,
            'Voice'        => array('MediaId' => $mediaid),
            'CreateTime'   => time(),
        );
        $this->Message($msg);

        return $this;
    }

    /**
     * 设置回复消息
     * Example: $obj->video('media_id','title','description')->reply();
     *
     * @param string $mediaid
     * @param string $title
     * @param string $description
     *
     * @return $this
     */
    public function video($mediaid = '', $title = '', $description = '')
    {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_IMAGE,
            'Video'        => array(
                'MediaId'     => $mediaid,
                'Title'       => $title,
                'Description' => $description
            ),
            'CreateTime'   => time(),
        );
        $this->Message($msg);

        return $this;
    }

    /**
     * 设置回复图文
     *
     * @param array $newsData
     *        数组结构:
     *        array(
     *        "0"=>array(
     *        'Title'=>'msg title',
     *        'Description'=>'summary text',
     *        'PicUrl'=>'http://www.domain.com/1.jpg',
     *        'Url'=>'http://www.domain.com/1.html'
     *        ),
     *        "1"=>....
     *        )
     *
     * @param array $newsData
     *
     * @return $this
     */
    public function news($newsData = array())
    {
        $count = count($newsData);
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_NEWS,
            'CreateTime'   => time(),
            'ArticleCount' => $count,
            'Articles'     => $newsData,
        );
        $this->Message($msg);

        return $this;
    }

    /**
     * 设置发送消息
     *
     * @param string $msg    消息数组
     * @param bool   $append 是否在原消息数组追加
     *
     * @return array|string
     */
    public function Message($msg = '', $append = false)
    {
        if (is_null($msg)) {
            $this->_msg = array();
        } elseif (is_array($msg)) {
            if ($append)
                $this->_msg = array_merge($this->_msg, $msg);
            else
                $this->_msg = $msg;

            return $this->_msg;
        } else {
            return $this->_msg;
        }

        return false;
    }

    /**
     * 回复微信服务器, 此函数支持链式操作
     * Example: $this->text('msg tips')->reply();
     *
     * @param array $msg    要发送的信息, 默认取$this->_msg
     * @param bool  $return 是否返回信息而不抛出到浏览器 默认:否
     *
     * @return bool|string
     */
    public function reply($msg = array(), $return = false)
    {
        if (empty($msg))
            $msg = $this->_msg;
        $xmldata = $this->xml_encode($msg);
        $this->log($xmldata);
        $pc = new Prpcrypt($this->encodingAesKey);
        $array = $pc->encrypt($xmldata, $this->appid);
        $ret = $array[0];
        if ($ret != 0) {
            $this->log('encrypt err!');

            return false;
        }
        $timestamp = time();
        $nonce = rand(77, 999) * rand(605, 888) * rand(11, 99);
        $encrypt = $array[1];
        $tmpArr = array($this->token, $timestamp, $nonce, $encrypt);//比普通公众平台多了一个加密的密文
        sort($tmpArr, SORT_STRING);
        $signature = implode($tmpArr);
        $signature = sha1($signature);
        $smsg = $this->generate($encrypt, $signature, $timestamp, $nonce);
        $this->log($smsg);
        if ($return)
            return $smsg;
        elseif ($smsg) {
            echo $smsg;

            return true;
        } else
            return false;
    }

    private function generate($encrypt, $signature, $timestamp, $nonce)
    {
        //格式化加密信息
        $format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";

        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }

    /**
     * 设置缓存，按需重载
     *
     * @param $cachename
     * @param $value
     * @param $expired
     *
     * @return bool
     */
    protected function setCache($cachename, $value, $expired)
    {
        //TODO: set cache implementation
        return false;
    }

    /**
     * 获取缓存，按需重载
     *
     * @param string $cachename
     *
     * @return mixed
     */
    protected function getCache($cachename)
    {
        //TODO: get cache implementation
        return false;
    }

    /**
     * 清除缓存，按需重载
     *
     * @param string $cachename
     *
     * @return boolean
     */
    protected function removeCache($cachename)
    {
        //TODO: remove cache implementation
        return false;
    }

    /**
     * HTTP 请求基本数据
     *
     * @return array
     * @author wb <pithyone@vip.qq.com>
     */
    public function baseHttpData()
    {
        $timestamp = time();
        $sign = md5($this->access_token . $timestamp . $this->token);

        $data = [
            'access_token' => $this->access_token,
            'timestamp'    => $timestamp,
            'sign'         => $sign,
        ];

        return $data;
    }

    public function httpBaseRet($result)
    {
        $json = json_decode($result, true);
        if (!$json) return false;

        if (0 !== $json['errcode']) {
            $this->errCode = $json['errcode'];
            $this->errMsg = $json['errmsg'];

            return false;
        }

        return $json;
    }

    public function httpPostRet($url, $data, $param = [], $post_file = false)
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        $param = http_build_query(array_merge(['access_token' => $this->access_token], $param));
        $url .= $param;
        $result = $this->http_post($url, $data, $post_file);
        if (!$result) return false;

        return $this->httpBaseRet($result);
    }

    public function httpGetRet($url, $param = [])
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        $param = http_build_query(array_merge(['access_token' => $this->access_token], $param));
        $url .= $param;
        $result = $this->http_get($url);
        if (!$result) return false;

        return $this->httpBaseRet($result);
    }

    /**
     * 通用auth验证方法
     *
     * @param string $appid
     * @param string $appsecret
     * @param string $token
     *
     * @return bool|mixed|string
     */
    public function checkAuth($appid = '', $appsecret = '', $token = '')
    {
        if (!$appid || !$appsecret) {
            $appid = $this->appid;
            if ($this->im === true) $appsecret = $this->appsecret_im;
            else $appsecret = $this->appsecret;
        }
        if ($token) { //手动指定token，优先使用
            $this->access_token = $token;

            return $this->access_token;
        }
        if ($this->im === true) $authname = 'qywechat_im_access_token' . $appid;
        else $authname = 'qywechat_access_token' . $appid;

        if ($rs = $this->getCache($authname)) {
            $this->access_token = $rs;

            return $rs;
        }
        $result = $this->http_get(self::API_URL_PREFIX . self::TOKEN_GET_URL . 'corpid=' . $appid . '&corpsecret=' . $appsecret);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || 0 !== $json['errcode']) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];

                return false;
            }
            $this->access_token = $json['access_token'];
            $expire = $json['expires_in'] ? intval($json['expires_in']) - 100 : 3600;
            $this->setCache($authname, $this->access_token, $expire);

            return $this->access_token;
        }

        return false;
    }

    /**
     * 删除验证数据
     *
     * @param string $appid
     *
     * @return bool
     */
    public function resetAuth($appid = '')
    {
        if (!$appid) $appid = $this->appid;
        $this->access_token = '';
        $authname = 'qywechat_access_token' . $appid;
        $this->removeCache($authname);

        return true;
    }

    /**
     * 删除JSAPI授权TICKET
     *
     * @param string $appid
     *
     * @return bool
     */
    public function resetJsTicket($appid = '')
    {
        if (!$appid) $appid = $this->appid;
        $this->jsapi_ticket = '';
        $authname = 'qywechat_jsapi_ticket' . $appid;
        $this->removeCache($authname);

        return true;
    }

    /**
     * 获取JSAPI授权TICKET
     *
     * @param string $appid        用于多个appid时使用,可空
     * @param string $jsapi_ticket 手动指定jsapi_ticket，非必要情况不建议用
     *
     * @return bool|mixed|string
     */
    public function getJsTicket($appid = '', $jsapi_ticket = '')
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        if (!$appid) $appid = $this->appid;
        if ($jsapi_ticket) { //手动指定token，优先使用
            $this->jsapi_ticket = $jsapi_ticket;

            return $this->jsapi_ticket;
        }
        $authname = 'qywechat_jsapi_ticket' . $appid;
        if ($rs = $this->getCache($authname)) {
            $this->jsapi_ticket = $rs['jsapi_ticket'];

            return ['jsapi_ticket' => $this->jsapi_ticket, 'invalid' => $rs['expire'] - (time() - $rs['time'])];
        }
        $result = $this->http_get(self::API_URL_PREFIX . self::TICKET_GET_URL . 'access_token=' . $this->access_token);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || 0 !== $json['errcode']) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];

                return false;
            }
            $this->jsapi_ticket = $json['ticket'];
            $expire = $json['expires_in'] ? intval($json['expires_in']) - 200 : 3600;
            $timestamp = time();
            $rs = ['jsapi_ticket' => $this->jsapi_ticket, 'time' => $timestamp, 'expire' => $expire];
            $this->setCache($authname, $rs, $expire);

            return ['jsapi_ticket' => $this->jsapi_ticket, 'invalid' => $rs['expire'] - (time() - $rs['time'])];
        }

        return false;
    }


    /**
     * 获取JsApi使用签名
     *
     * @param string $url       网页的URL，自动处理#及其后面部分
     * @param int    $timestamp 当前时间戳 (为空则自动生成)
     * @param string $noncestr  随机串 (为空则自动生成)
     * @param string $appid     用于多个appid时使用,可空
     *
     * @return array|bool 返回签名字串
     */
    public function getJsSign($url, $timestamp = 0, $noncestr = '', $appid = '')
    {
        if (!$this->jsapi_ticket && !$this->getJsTicket($appid) || !$url) return false;
        if (!$timestamp)
            $timestamp = time();
        if (!$noncestr)
            $noncestr = $this->generateNonceStr();
        $ret = strpos($url, '#');
        if ($ret)
            $url = substr($url, 0, $ret);
        $url = trim($url);
        if (empty($url))
            return false;
        $arrdata = array("timestamp" => $timestamp, "noncestr" => $noncestr, "url" => $url, "jsapi_ticket" => $this->jsapi_ticket);
        $sign = $this->getSignature($arrdata);
        if (!$sign)
            return false;
        $signPackage = array(
            "appid"     => $this->appid,
            "noncestr"  => $noncestr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $sign
        );

        return $signPackage;
    }

    /**
     * 获取签名
     *
     * @param array  $arrdata 签名数组
     * @param string $method  签名方法
     *
     * @return boolean|string 签名值
     */
    public function getSignature($arrdata, $method = "sha1")
    {
        if (!function_exists($method)) return false;
        ksort($arrdata);
        $paramstring = "";
        foreach ($arrdata as $key => $value) {
            if (strlen($paramstring) == 0)
                $paramstring .= $key . "=" . $value;
            else
                $paramstring .= "&" . $key . "=" . $value;
        }
        $Sign = $method($paramstring);

        return $Sign;
    }

    /**
     * 生成随机字串
     *
     * @param int $length
     *
     * @return string
     */
    public function generateNonceStr($length = 16)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $str;
    }

    /**
     * 创建菜单
     *
     * @param        $data
     * @param string $agentid
     *
     * @return bool
     */
    public function createMenu($data, $agentid = '')
    {
        if ($agentid == '') {
            $agentid = $this->agentid;
        }

        $url = self::API_URL_PREFIX . self::MENU_CREATE_URL;
        $param = ['agentid' => $agentid];

        return $this->httpPostRet($url, self::json_encode($data), $param);
    }

    /**
     * 获取菜单
     *
     * @param string $agentid
     *
     * @return bool|mixed
     */
    public function getMenu($agentid = '')
    {
        if ($agentid == '') {
            $agentid = $this->agentid;
        }
        $url = self::API_URL_PREFIX . self::MENU_GET_URL;
        $param = ['agentid' => $agentid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 删除菜单
     *
     * @param string $agentid
     *
     * @return bool|mixed
     */
    public function deleteMenu($agentid = '')
    {
        if ($agentid == '') {
            $agentid = $this->agentid;
        }
        $url = self::API_URL_PREFIX . self::MENU_DELETE_URL;
        $param = ['agentid' => $agentid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 上传多媒体文件 (只有三天的有效期，过期自动被删除)
     *
     * @param $data
     * @param $type
     *
     * @return bool|mixed
     */
    public function uploadMedia($data, $type)
    {
        $url = self::API_URL_PREFIX . self::MEDIA_UPLOAD_URL;
        $param = ['type' => $type];

        return $this->httpPostRet($url, $data, $param, true);
    }

    /**
     * 根据媒体文件ID获取媒体文件
     *
     * @param string $media_id 媒体文件id
     * @param        $media_id
     *
     * @return bool|mixed
     */
    public function getMedia($media_id)
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        $result = $this->http_get(self::API_URL_PREFIX . self::MEDIA_GET_URL . 'access_token=' . $this->access_token . '&media_id=' . $media_id, false);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json)
                return $result;

            if (0 !== $json['errcode']) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];

                return false;
            }

            return $result;
        }

        return false;
    }

    /**
     * 获取企业微信服务器IP地址列表
     *
     * @return bool
     */
    public function getServerIp()
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        $result = $this->http_get(self::API_URL_PREFIX . self::CALLBACKSERVER_GET_URL . 'access_token=' . $this->access_token);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || 0 !== $json['errcode']) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];

                return false;
            }

            return $json['ip_list'];
        }

        return false;
    }

    /**
     * 创建部门
     *
     * @param array $data 结构体为:
     *                    array (
     *                    "name" => "邮箱产品组",   //部门名称
     *                    "parentid" => "1"         //父部门id
     *                    "order" =>  "1",            //(非必须)在父部门中的次序。从1开始，数字越大排序越靠后
     *                    )
     *
     * @return boolean|array
     * 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "created",  //对返回码的文本描述内容
     *   "id": 2               //创建的部门id。
     * }
     */
    public function createDepartment($data)
    {
        $url = self::API_URL_PREFIX . self::DEPARTMENT_CREATE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 更新部门
     *
     * @param array $data 结构体为:
     *                    array(
     *                    "id" => "1"               //(必须)部门id
     *                    "name" =>  "邮箱产品组",   //(非必须)部门名称
     *                    "parentid" =>  "1",         //(非必须)父亲部门id。根部门id为1
     *                    "order" =>  "1",            //(非必须)在父部门中的次序。从1开始，数字越大排序越靠后
     *                    )
     *
     * @return boolean|array 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "updated"  //对返回码的文本描述内容
     * }
     */
    public function updateDepartment($data)
    {
        $url = self::API_URL_PREFIX . self::DEPARTMENT_UPDATE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 删除部门
     *
     * @param $id
     *
     * @return boolean|array 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "deleted"  //对返回码的文本描述内容
     * }
     */
    public function deleteDepartment($id)
    {
        $url = self::API_URL_PREFIX . self::DEPARTMENT_DELETE_URL;

        $param = ['id' => $id];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 移动部门
     *
     * @param $data
     *    array(
     *    "department_id" => "5",    //所要移动的部门
     *    "to_parentid" => "2",        //想移动到的父部门节点，根部门为1
     *    "to_position" => "1"        //(非必须)想移动到的父部门下的位置，1表示最上方，往后位置为2，3，4，以此类推，默认为1
     *    )
     *
     * @return boolean|array 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "ok"  //对返回码的文本描述内容
     * }
     */
    public function moveDepartment($data)
    {
        $url = self::API_URL_PREFIX . self::DEPARTMENT_MOVE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 获取部门列表
     *
     * @return boolean|array     成功返回结果
     * {
     *    "errcode": 0,
     *    "errmsg": "ok",
     *    "department": [          //部门列表数据。以部门的order字段从小到大排列
     *        {
     *            "id": 1,
     *            "name": "广州研发中心",
     *            "parentid": 0,
     *            "order": 40
     *        },
     *       {
     *          "id": 2
     *          "name": "邮箱产品部",
     *          "parentid": 1,
     *          "order": 40
     *       }
     *    ]
     * }
     */
    public function getDepartment()
    {
        $url = self::API_URL_PREFIX . self::DEPARTMENT_LIST_URL;

        return $this->httpGetRet($url);
    }

    /**
     * 创建成员
     *
     * @param array $data 结构体为:
     *                    array(
     *                    "userid" => "zhangsan",
     *                    "name" => "张三",
     *                    "department" => [1, 2],
     *                    "position" => "产品经理",
     *                    "mobile" => "15913215421",
     *                    "gender" => 1,     //性别。gender=0表示男，=1表示女
     *                    "tel" => "62394",
     *                    "email" => "zhangsan@gzdev.com",
     *                    "weixinid" => "zhangsan4dev"
     *                    )
     *
     * @return boolean|array
     * 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "created",  //对返回码的文本描述内容
     * }
     */
    public function createUser($data)
    {
        $url = self::API_URL_PREFIX . self::USER_CREATE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 更新成员
     *
     * @param array $data 结构体为:
     *                    array(
     *                    "userid" => "zhangsan",
     *                    "name" => "张三",
     *                    "department" => [1, 2],
     *                    "position" => "产品经理",
     *                    "mobile" => "15913215421",
     *                    "gender" => 1,     //性别。gender=0表示男，=1表示女
     *                    "tel" => "62394",
     *                    "email" => "zhangsan@gzdev.com",
     *                    "weixinid" => "zhangsan4dev"
     *                    )
     *
     * @return boolean|array 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "updated"  //对返回码的文本描述内容
     * }
     */
    public function updateUser($data)
    {
        $url = self::API_URL_PREFIX . self::USER_UPDATE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 删除成员
     *
     * @param $userid
     *
     * @return bool|mixed
     */
    public function deleteUser($userid)
    {
        $url = self::API_URL_PREFIX . self::USER_DELETE_URL;
        $param = ['userid' => $userid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 批量删除成员
     *
     * @param $userids
     *
     * @return bool|mixed
     */
    public function deleteUsers($userids)
    {
        if (!$userids) return false;
        $data = ['useridlist' => $userids];
        $url = self::API_URL_PREFIX . self::USER_BATCHDELETE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 获取成员信息
     *
     * @param $userid
     *
     * @return bool|mixed
     */
    public function getUserInfo($userid)
    {
        $url = self::API_URL_PREFIX . self::USER_GET_URL;
        $param = ['userid' => $userid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 获取部门成员
     *
     * @param     $department_id
     * @param int $fetch_child
     * @param int $status
     *
     * @return bool|mixed
     */
    public function getUserList($department_id, $fetch_child = 0, $status = 0)
    {
        $url = self::API_URL_PREFIX . self::USER_LIST_URL;
        $param = ['department_id' => $department_id, 'fetch_child' => $fetch_child, 'status' => $status];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 获取部门成员详情
     *
     * @param     $department_id
     * @param int $fetch_child
     * @param int $status
     *
     * @return bool|mixed
     */
    public function getUserListInfo($department_id, $fetch_child = 0, $status = 0)
    {
        $url = self::API_URL_PREFIX . self::USER_LIST_INFO_URL;
        $param = ['department_id' => $department_id, 'fetch_child' => $fetch_child, 'status' => $status];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 根据code获取成员信息
     *
     * @param     $code
     * @param int $agentid
     *
     * @return bool|mixed
     */
    public function getUserId($code, $agentid = 0)
    {
        if (!$agentid) $agentid = $this->agentid;
        $url = self::API_URL_PREFIX . self::USER_GETINFO_URL;
        $param = ['code' => $code, 'agentid' => $agentid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 邀请成员关注
     *
     * @param        $userid
     * @param string $invite_tips
     *
     * @return bool|mixed
     */
    public function sendInvite($userid, $invite_tips = '')
    {
        $data = ['userid' => $userid];
        if (!$invite_tips) {
            $data['invite_tips'] = $invite_tips;
        }

        $url = self::API_URL_PREFIX . self::USER_INVITE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 创建标签
     *
     * @param array $data 结构体为:
     *                    array(
     *                    "tagname" => "UI"
     *                    )
     *
     * @return boolean|array
     * 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "created",  //对返回码的文本描述内容
     *   "tagid": "1"
     * }
     */
    public function createTag($data)
    {
        $url = self::API_URL_PREFIX . self::TAG_CREATE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 更新标签
     *
     * @param array $data 结构体为:
     *                    array(
     *                    "tagid" => "1",
     *                    "tagname" => "UI design"
     *                    )
     *
     * @return boolean|array 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "updated"  //对返回码的文本描述内容
     * }
     */
    public function updateTag($data)
    {
        $url = self::API_URL_PREFIX . self::TAG_UPDATE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 删除标签
     *
     * @param $tagid
     *
     * @return boolean|array 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "deleted"  //对返回码的文本描述内容
     * }
     */
    public function deleteTag($tagid)
    {
        $url = self::API_URL_PREFIX . self::TAG_DELETE_URL;
        $param = ['tagid' => $tagid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 获取标签成员
     *
     * @param $tagid
     *
     * @return boolean|array     成功返回结果
     * {
     *    "errcode": 0,
     *    "errmsg": "ok",
     *    "userlist": [
     *          {
     *              "userid": "zhangsan",
     *              "name": "李四"
     *          }
     *      ]
     * }
     */
    public function getTag($tagid)
    {
        $url = self::API_URL_PREFIX . self::TAG_GET_URL;
        $param = ['tagid' => $tagid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 增加标签成员
     *
     * @param array $data 结构体为:
     *                    array (
     *                    "tagid" => "1",
     *                    "userlist" => array(    //企业员工ID列表
     *                    "user1",
     *                    "user2"
     *                    )
     *                    )
     *
     * @return boolean|array
     * 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "ok",  //对返回码的文本描述内容
     *   "invalidlist"："usr1|usr2|usr"     //若部分userid非法，则会有此段。不在权限内的员工ID列表，以“|”分隔
     * }
     */
    public function addTagUser($data)
    {
        $url = self::API_URL_PREFIX . self::TAG_ADDUSER_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 删除标签成员
     *
     * @param array $data 结构体为:
     *                    array (
     *                    "tagid" => "1",
     *                    "userlist" => array(    //企业员工ID列表
     *                    "user1",
     *                    "user2"
     *                    )
     *                    )
     *
     * @return boolean|array
     * 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "deleted",  //对返回码的文本描述内容
     *   "invalidlist"："usr1|usr2|usr"     //若部分userid非法，则会有此段。不在权限内的员工ID列表，以“|”分隔
     * }
     */
    public function delTagUser($data)
    {
        $url = self::API_URL_PREFIX . self::TAG_DELUSER_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 获取标签列表
     *
     * @return boolean|array     成功返回数组结果，这里附上json样例
     * {
     *    "errcode": 0,
     *    "errmsg": "ok",
     *    "taglist":[
     *       {"tagid":1,"tagname":"a"},
     *       {"tagid":2,"tagname":"b"}
     *    ]
     * }
     */
    public function getTagList()
    {
        $url = self::API_URL_PREFIX . self::TAG_LIST_URL;

        return $this->httpGetRet($url);
    }

    /**
     * 主动发送信息接口
     *
     * @param array $data 结构体为:
     *                    array(
     *                    "touser" => "UserID1|UserID2|UserID3",
     *                    "toparty" => "PartyID1|PartyID2 ",
     *                    "totag" => "TagID1|TagID2 ",
     *                    "safe":"0"            //是否为保密消息，对于news无效
     *                    "agentid" => "001",    //应用id
     *                    "msgtype" => "text",  //根据信息类型，选择下面对应的信息结构体
     *
     *         "text" => array(
     *                 "content" => "Holiday Request For Pony(http://xxxxx)"
     *         ),
     *
     *         "image" => array(
     *                 "media_id" => "MEDIA_ID"
     *         ),
     *
     *         "voice" => array(
     *                 "media_id" => "MEDIA_ID"
     *         ),
     *
     *         " video" => array(
     *                 "media_id" => "MEDIA_ID",
     *                 "title" => "Title",
     *                 "description" => "Description"
     *         ),
     *
     *         "file" => array(
     *                 "media_id" => "MEDIA_ID"
     *         ),
     *
     *         "news" => array(            //不支持保密
     *                 "articles" => array(    //articles  图文消息，一个图文消息支持1到10个图文
     *                     array(
     *                         "title" => "Title",             //标题
     *                         "description" => "Description", //描述
     *                         "url" => "URL",                 //点击后跳转的链接。可根据url里面带的code参数校验员工的真实身份。
     *                         "picurl" => "PIC_URL",          //图文消息的图片链接,支持JPG、PNG格式，较好的效果为大图640*320，
     *                                                         //小图80*80。如不填，在客户端不显示图片
     *                     ),
     *                 )
     *         ),
     *
     *         "mpnews" => array(
     *                 "articles" => array(    //articles  图文消息，一个图文消息支持1到10个图文
     *                     array(
     *                         "title" => "Title",             //图文消息的标题
     *                         "thumb_media_id" => "id",       //图文消息缩略图的media_id
     *                         "author" => "Author",           //图文消息的作者(可空)
     *                         "content_source_url" => "URL",  //图文消息点击“阅读原文”之后的页面链接(可空)
     *                         "content" => "Content"          //图文消息的内容，支持html标签
     *                         "digest" => "Digest description",   //图文消息的描述
     *                         "show_cover_pic" => "0"         //是否显示封面，1为显示，0为不显示(可空)
     *                     ),
     *                 )
     *         )
     * )
     * 请查看官方开发文档中的 发送消息 -> 消息类型及数据格式
     *
     * @return boolean|array
     * 如果对应用或收件人、部门、标签任何一个无权限，则本次发送失败；
     * 如果收件人、部门或标签不存在，发送仍然执行，但返回无效的部分。
     * {
     *    "errcode": 0,
     *    "errmsg": "ok",
     *    "invaliduser": "UserID1",
     *    "invalidparty":"PartyID1",
     *    "invalidtag":"TagID1"
     * }
     */
    public function sendMessage($data)
    {
        $url = self::API_URL_PREFIX . self::MASS_SEND_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 二次验证
     * 企业在开启二次验证时，必须填写企业二次验证页面的url。
     * 当员工绑定通讯录中的帐号后，会收到一条图文消息，
     * 引导员工到企业的验证页面验证身份，企业在员工验证成功后，
     * 调用如下接口即可让员工关注成功。
     *
     * @param $userid
     *
     * @return boolean|array 成功返回结果
     * {
     *   "errcode": 0,        //返回码
     *   "errmsg": "ok"  //对返回码的文本描述内容
     * }
     */
    public function authSucc($userid)
    {
        $url = self::API_URL_PREFIX . self::AUTHSUCC_URL;
        $param = ['userid' => $userid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * oauth 授权跳转接口
     *
     * @param string $callback 回调URI
     * @param string $state    重定向后会带上state参数，企业可以填写a-zA-Z0-9的参数值
     * @param string $scope
     *
     * @return string
     * @author wb <pithyone@vip.qq.com>
     */
    public function getOauthRedirect($callback, $state = 'STATE', $scope = 'snsapi_base')
    {
        return self::OAUTH_PREFIX . self::OAUTH_AUTHORIZE_URL . 'appid=' . $this->appid . '&redirect_uri=' . urlencode($callback) . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
    }

    /**
     * 成员登录授权
     *
     * @param $auth_code
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    public function getLoginInfo($auth_code)
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        $result = $this->http_post(self::API_URL_PREFIX . '/service/get_login_info?access_token=' . $this->access_token, self::json_encode(['auth_code' => $auth_code]));
        if ($result) {
            $json = json_decode($result, true);

            return $json;
        }

        return false;
    }

    /**
     * 创建会话
     *
     * @param array $data 结构体为:
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    public function createChat($data)
    {
        $url = self::API_URL_PREFIX . self::CHAT_CREATE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 获取会话
     *
     * @param $chatid
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    public function getChatInfo($chatid)
    {
        $url = self::API_URL_PREFIX . self::CHAT_GET_URL;
        $param = ['chatid' => $chatid];

        return $this->httpGetRet($url, $param);
    }

    /**
     * 修改会话信息
     *
     * @param $data
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    public function updateChat($data)
    {
        $url = self::API_URL_PREFIX . self::CHAT_UPDATE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 退出会话
     *
     * @param $data
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    public function quitChat($data)
    {
        $url = self::API_URL_PREFIX . self::CHAT_QUIT_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 清除会话未读状态
     *
     * @param $data
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    public function clearnotifyChat($data)
    {
        $url = self::API_URL_PREFIX . self::CHAT_CLEARNOTIFY_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 会话发消息
     *
     * @param $data
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    public function sendChat($data)
    {
        $url = self::API_URL_PREFIX . self::CHAT_SEND_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }

    /**
     * 设置成员新消息免打扰
     *
     * @param $data
     *
     * @return bool|mixed
     * @author wb <pithyone@vip.qq.com>
     */
    public function setmuteChat($data)
    {
        $url = self::API_URL_PREFIX . self::CHAT_SETMUTE_URL;

        return $this->httpPostRet($url, self::json_encode($data));
    }
}