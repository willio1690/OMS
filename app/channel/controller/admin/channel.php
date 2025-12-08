<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#第三方应用中心
class channel_ctl_admin_channel extends desktop_controller
{
    #定义应用类型，该数组的键必须和sdb_ome_channel表channel_type字段保持绝对一致
    public $workground     = "channel_center";
    public static $appType = array('crm' => 'crm', 'wwgenius' => '旺旺精灵');

    private $_node_type = [
        'kuaidi' => [
            'kdn'   => '快递鸟',
            'other' => '其他',
        ],
        'cloudprint' => [
            'yilianyun'   => '易联云',

        ],
        'ticket' => [
            'feisuo' => '飞梭',
        ],
    ];

  

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $channel_type = $_GET['channel_type'];

        $schema  = app::get('channel')->model('channel')->_columns();
        $typemap = $schema['channel_type']['type'];

        $params = array(
            'title'                  => sprintf('%s授权', $typemap[$channel_type]),
            'actions'                => array(
                array(
                    'label'  => sprintf('添加%s', $typemap[$channel_type]),
                    'href'   => $this->url . '&act=add&p[]=' . $channel_type,
                    'target' => "dialog::{width:700,height:400,title:'" . sprintf('添加%s', $typemap[$channel_type]) . "'}",
                ),
                array(
                    'label'  => '查看绑定关系',
                    'href'   => $this->url . '&act=view_bindrelation&p[]=' . $channel_type,
                    'target' => '_blank',
                ),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'base_filter'            => array('channel_type' => $channel_type),
        );

        $this->finder('channel_mdl_channel', $params);
    }

    #查看绑定关系
    /**
     * view_bindrelation
     * @param mixed $channel_type channel_type
     * @return mixed 返回值
     */
    public function view_bindrelation($channel_type)
    {
        $params = kernel::single('channel_rpc_response_bind')->get_params(0, $channel_type, '', 'accept');

        echo sprintf('<title>查看绑定关系</title><iframe width="100%%" height="95%%" frameborder="0" src="%s" ></iframe>', MATRIX_RELATION_URL . '?' . http_build_query($params));
    }

    /**
     * 查看奇门绑定关系
     * @return mixed 返回值
     */
    public function view_qimen_bindrelation()
    {
        $channel_lib = kernel::single('channel_channel');
        $qimen_data = $channel_lib->getQimenJushitaErp();
        
        // 对 Secret Key 进行打码处理
        if (!empty($qimen_data['secret_key'])) {
            $secret_key = $qimen_data['secret_key'];
            $length = strlen($secret_key);
            if ($length > 8) {
                // 显示前4位和后4位，中间用星号代替
                $qimen_data['secret_key'] = substr($secret_key, 0, 4) . str_repeat('*', $length - 8) . substr($secret_key, -4);
            } else {
                // 如果长度小于等于8，全部用星号代替
                $qimen_data['secret_key'] = str_repeat('*', $length);
            }
        }
        
        // 获取后端服务地址：域名 + /index.php/openapi/process/handle
        $base_url = kernel::base_url(true);
        $api_url = rtrim($base_url, '/') . '/index.php/openapi/process/handle';
        
        $this->pagedata['qimen_data'] = $qimen_data;
        $this->pagedata['has_data'] = !empty($qimen_data);
        $this->pagedata['api_url'] = $api_url;
        
        $this->display('admin/channel/qimen_bindrelation.html');
    }

    /**
     * apply_bindrelation
     * @param mixed $channel_id ID
     * @return mixed 返回值
     */
    public function apply_bindrelation($channel_id)
    {
        $params = kernel::single('channel_rpc_response_bind')->get_params($channel_id, '', '', 'apply');
        $this->pagedata['license_iframe_url'] = MATRIX_RELATION_URL . '?' . http_build_query($params);
        $this->display('admin/channel/bindrelation.html');
    }

    /**
     * undocumented function
     * 
     * @return void
     * @author
     * */
    public function save()
    {
        $channel = $_POST['channel'];
     

        $this->begin($this->url . '&act=index&channel_type=' . $channel['channel_type']);

        $channelMdl = app::get('channel')->model('channel');
        
        //[第三方快递]使用shipper字段保存对接方式类型
        if($channel['channel_mode'] && $channel['channel_type'] == 'kuaidi'){
            $channel['shipper'] = $channel['channel_mode'];
        }
     
        // 如果有额外参数,则检查
        if(isset($_POST['config']) && !empty($_POST['config'])){
            $channel = $this->__formatConfig($_POST['config'],$channel);
        }

        $rs = $channelMdl->save($channel);

        $this->end($rs);
    }

        /**
     * edit
     * @param mixed $channel_id ID
     * @return mixed 返回值
     */
    public function edit($channel_id)
    {
        $channelMdl = app::get('channel')->model('channel');

        $channel                = $channelMdl->dump($channel_id);
        $this->pagedata['data'] = $channel;

        $schema  = app::get('channel')->model('channel')->_columns();
        $typemap = $schema['channel_type']['type'];

        $this->pagedata['channel_type_name'] = $typemap[$channel['channel_type']];

        //[快递]接入方式
        $channelModes = kernel::single('channel_rpc_response_bind')->getBindKdMode();
        $this->pagedata['channel_modes'] = $channelModes;
        
        if($channel['shipper']){
            $channel_mode_name = $channelModes[$channel['shipper']];
            if($channel_mode_name){
                $this->pagedata['channel_mode'] = $channel['shipper'];
                $this->pagedata['channel_mode_name'] = $channel_mode_name;
            }
        }

        // 接入渠道 适配器
        $adapter_list = kernel::single('channel_auth_config')->getAdapterList($channel['channel_type']);

        $this->pagedata['adapter_list'] = $adapter_list;
        
        $this->display('admin/channel/add.html');
    }

    /**
     * 添加
     * @param mixed $channel_type channel_type
     * @return mixed 返回值
     */
    public function add($channel_type)
    {
        $schema  = app::get('channel')->model('channel')->_columns();
        $typemap = $schema['channel_type']['type'];

        // 渠道类型
        $this->pagedata['channel_type'] = [$channel_type => $typemap[$channel_type]];
        // 节点类型(平台)
        $this->pagedata['node_type']    = $this->_node_type[$channel_type] ? $this->_node_type[$channel_type] : ['other' => '其他'];

        // 接入渠道 适配器
        $adapter_list = kernel::single('channel_auth_config')->getAdapterList($channel_type);
     
        $this->pagedata['adapter_list'] = $adapter_list;
        
        $this->display('admin/channel/add.html');
    }


    /**
     * 接入方式切换调整配置页面 
     */
    public function confightml($channel_type,$adapter, $channel_id = null)
    {
        if($channel_id){
            $channelMdl = $this->app->model('channel');
            $channel = $channelMdl->db_dump($channel_id, '*');
            $channel['config'] = @unserialize($channel['config']);
            $this->pagedata['channel'] = $channel;
        }

        $platform_list = kernel::single('channel_auth_config')->getPlatformList($channel_type,$adapter);

        $this->pagedata['platform_list'] = $platform_list;
        $this->pagedata['channel_type'] = $channel_type;
        $this->pagedata['adapter'] = $adapter;
        
        $viewPath = sprintf('admin/channel/%s/%s/auth.html', $channel_type, $adapter);

        $this->display($viewPath);
    }

    /**
     * platformconfig
     * @param mixed $channel_type channel_type
     * @param mixed $adapter adapter
     * @param mixed $node_type node_type
     * @param mixed $channel_id ID
     * @return mixed 返回值
     */
    public function platformconfig($channel_type, $adapter, $node_type,$channel_id = null)
    {
    
        if(!$channel_type || !$adapter || !$node_type){
            return '';
        }
        if ($channel_id) {
            $channelMdl = $this->app->model('channel');
            $channel = $channelMdl->db_dump($channel_id, '*');
            // 判断当前平台是否一致
            if($node_type == $channel['node_type']){
                $channel['config'] = @unserialize($channel['config']);
                $this->pagedata['channel'] = $channel;
            }
        }
      
        $platform_params = kernel::single('channel_auth_config')->getPlatformParam($channel_type, $adapter, $node_type);
      
        $this->pagedata['platform_params'] = $platform_params;
        $this->pagedata['platform'] = $node_type;

        // 接入方式
        $channelModes = kernel::single('channel_rpc_response_bind')->getBindKdMode();
        $this->pagedata['channel_modes'] = $channelModes;
   
        $viewPath = sprintf('admin/channel/%s/%s/platformconfig.html', $channel_type, $adapter);
    
        $this->display($viewPath);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function gen_private_key()
    {
        $lowercaseLetters = 'abcdefghijklmnopqrstuvwxyz';
        $uppercaseLetters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%&';

        $randomString = '';

        // 随机选择至少一个字符组中的字符
        $randomString .= $lowercaseLetters[random_int(0, strlen($lowercaseLetters) - 1)];
        $randomString .= $uppercaseLetters[random_int(0, strlen($uppercaseLetters) - 1)];
        $randomString .= $numbers[random_int(0, strlen($numbers) - 1)];
        $randomString .= $symbols[random_int(0, strlen($symbols) - 1)];

        $totalLength = 32; // 总长度为32位

        // 剩余长度为总长度减去已选择的字符数量
        $remainingLength = $totalLength - 4;

        // 随机选择剩余位置的字符，从包含所有字符的字符串中选择
        $allCharacters = $lowercaseLetters . $uppercaseLetters . $numbers . $symbols;
        for ($i = 0; $i < $remainingLength; $i++) {
            $randomCharacter = $allCharacters[random_int(0, strlen($allCharacters) - 1)];
            $randomString .= $randomCharacter;
        }

        // 将随机生成的字符串打乱顺序，增加随机性
        $randomString = str_shuffle($randomString);

        echo $randomString; // 输出32位包含符号、大小写字母和数字的随机字符串
        exit;
    }


    private function __formatConfig($config,$channel)
    {
     
        // 尝试获取是否有应用类型配置类
        if(isset($channel['channel_type'])){
            $channelConfigClassName = sprintf("channel_%s_config", $channel['channel_type']);
        
            // 数据验证
            try {
                if (class_exists($channelConfigClassName)) {
                    $channel = kernel::single($channelConfigClassName)->formatConfig($config, $channel);
                }
            } catch (Exception $e) {
            }
        }
     
        return $channel;
    }
}
