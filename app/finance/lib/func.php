<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_func{

    /**
     * 获取所有店铺
     * @access public 
     * @return Array 店铺列表
     */
    public function shop_list($filter = array()){
        $shop_list = array();
        if ($instance = kernel::service('service.shop')){
            if (method_exists($instance,'shop_list')){
                $shop_list = $instance->shop_list($filter,'shop_id,node_id,name,node_type');
            }
        }
        return $shop_list;
    }
    
    /**
     * 获取支付宝已授权的淘宝店铺
     * @access public 
     * @return Array 店铺列表
     */
    public function taobao_shop_list(){
        $shop_list = array();
        if ($instance = kernel::service('service.shop')){
            if (method_exists($instance,'shop_list')){
                $shop_list = $instance->shop_list(array('node_type'=>'taobao','alipay_authorize'=>'true'),'shop_id,node_id,name');
            }
        }
        return $shop_list;
    }

    /**
     * 获取店铺信息
     * @access public
     * @param String $node_id 节点ID
     * @return Array 店铺信息
     */
    public function getShopByNodeID($node_id=''){
        $shop_info = array();
        if ($instance = kernel::service('service.shop')){
            if (method_exists($instance,'getRowByNodeId')){
                $shop_info = $instance->getRowByNodeId($node_id);
            }
        }
        $shop_info['shop_name'] = isset($shop_info['name']) ? $shop_info['name'] : '';
        return $shop_info;
    }

    /**
     * 通过店铺ID获取店铺信息
     * @access public
     * @param String $shop_id 店铺ID
     * @return Array 店铺信息
     */
    public function getShopByShopID($shop_id=''){
        $shop_info = array();
        if ($instance = kernel::service('service.shop')){
            if (method_exists($instance,'getRowByNodeId')){
                $shop_info = $instance->getRowByShopId($shop_id);
            }
        }
        $shop_info['shop_name'] = isset($shop_info['name']) ? $shop_info['name'] : '';
        return $shop_info;
    }

    /**
     * 获取子订单的订单号
     * @access public
     * @param String $oid 子订单号
     * @return String 订单号
     */
    public function getOrderBnByoid($oid='',$node_id=''){
        if (empty($oid)) return NULL;

        $order_bn = '';
        if ($instance = kernel::service('service.order')){
            if (method_exists($instance,'getOrderBnByoid')){
                $order_bn = $instance->getOrderBnByoid($oid,$node_id);
            }
        }
        return $order_bn;
    }

    /**
     * 订单号是否存在
     * @access public
     * @param String $order_bn 订单号
     * @param String $node_id 节点ID
     * @return bool
     */
    public function order_is_exists($order_bn='',$node_id=''){
        if (empty($order_bn)) return false;
        $rs = false;
        if ($instance = kernel::service('service.order')){
            if (method_exists($instance,'order_is_exists')){
                $rs = $instance->order_is_exists($order_bn,$node_id);
            }
        }
        return $rs;
    }

    /**
     * 添加队列
     * @access public
     * @param String $title 队列标题
     * @param String $worker 队列执行类方法
     * @param mixed $params 队列参数
     * @param String $type 队列类型
     * @return bool
     */
    public function addTask($title,$worker,$params,$type='slow'){
        if (empty($params)) return false;

        $oQueue = app::get('base')->model('queue');
        $queueData = array(
            'queue_title' => $title,
            'start_time'  => time(),
            'params'      => $params,
            'worker'      => $worker,
        );

        $result = $oQueue->save($queueData);

        return $result;
    }

    
    /**
     * 接口请求
     * @access public
     * @return bool
     */
    public function request($method,$params,$callback,$log_title,$shop_id,$time_out=5,$queue=false,$addon='',$write_log='',$mode='sync'){
        $rpcObj = kernel::single('ome_rpc_request');
        if ($mode == 'sync') {
            $rs = $rpcObj->call($method,$params,$shop_id);
            $rs  = (array)$rs;
            if (isset($rs['data'])) {
                $rs['data'] = json_decode($rs['data'],true);
            }
        } elseif ($mode == 'async') {
            $rs = $rpcObj->request($method,$params,$callback,$log_title,$shop_id,$time_out,$queue,$addon,$write_log);
        } else {
            $rs = array('rsp'=>'fail','err_msg'=>'请求类型错误！');
        }

        return $rs;
    }

    /**
     * 生成99%不重复的随机数
     * @access public 
     * @return String md5随机数
     */
    public static function md5_randnums(){
        $microtime = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    /**
     * 根据字段组获取唯一编号
     * @access public 
     * @return md5 唯一编号
     */
    public static function unique_id($field_array = array()){
        $unique_id = '';
        if($field_array){
            $field_str = join('+',$field_array);
            $unique_id = md5($field_str);
        }
        return $unique_id;
    }


    /**
     * 新增流水操作日志
     * 
     * @param      String  $bill_bn  单据编号
     * @param      String  $op_name  操作者
     * @param      String  $content  内容
     * @param      String  $log_type 类型
     */
    public static function addOpLog($bill_bn,$op_name,$content,$log_type='核销')
    {
        $data = array();
        $data['log_bn'] = $bill_bn;
        $data['log_type'] = $log_type;
        $data['op_name'] = $op_name;
        $data['log_time'] = time();
        $data['content'] = $content;
        $data['ip'] = kernel::single("base_request")->get_remote_addr();
        return app::get('finance')->model('bill_op_logs')->save($data);
    }


}