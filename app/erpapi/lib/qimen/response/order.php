<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_qimen_response_order extends erpapi_qimen_response_abstract
{
    /**
     * 接收的数据
     *
     * @var array
     * */
    public $_ordersdf = array();
    
    /**
     * qimen正向同步
     *
     * @param mixed $params 参数
     * @return array
     */
    public function add($params)
    {
        // 获取数据
        $params = $this->_getResponseData($params);
        
        // 单据号
        $original_bn = $params['order_sn'];
        
        // apilog
        $this->__apilog['original_bn'] = $original_bn;
        $this->__apilog['title'] = 'qimen正向同步订单['. $original_bn .']';
        
        // Setting
        $error_msg = '';
        
        // 数据格式化
        $result = $this->_analysis($params, $error_msg);
        if(!$result){
            $this->__apilog['result']['msg'] = ($error_msg ? $error_msg : '接收数据无效!');
            return false;
        }
        
        // 校验接收的数据
        $result = $this->_canAccept();
        if (!$result) {
            return array();
        }
        
        return $this->_ordersdf;
    }
    
    /**
     * qimen逆向同步
     *
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function update($params)
    {
        // 获取数据
        $params = $this->_getResponseData($params);
        
        // 单据号
        $original_bn = $params['order_sn'];
        
        // apilog
        $this->__apilog['original_bn'] = $original_bn;
        $this->__apilog['title'] = 'qimen逆向同步订单['. $original_bn .']';
        
        // Setting
        $error_msg = '';
        
        // 数据格式化
        $result = $this->_analysis($params, $error_msg);
        if(!$result){
            $this->__apilog['result']['msg'] = ($error_msg ? $error_msg : '接收数据无效。');
            return false;
        }
        
        // 校验接收的数据
        $result = $this->_canAccept();
        if (!$result) {
            return array();
        }
        
        return $this->_ordersdf;
    }
    
    /**
     * 数据格式化
     *
     * @param $params 接收的数据
     * @params $error_msg 错误信息
     * @return void
     */
    protected function _analysis($params, &$error_msg=null)
    {
        // check
        if(!isset($params['extendProps']) || empty($params['extendProps'])){
            $error_msg = 'extendProps字段值不可为空';
            return false;
        }
        
        // json
        if(is_string($params['extendProps'])){
            $sdf = json_decode($params['extendProps'], true);
        }else{
            $sdf = $params['extendProps'];
        }
        
        // 接收单据的完整数据
        $this->_ordersdf = $sdf;
        
        // 兼容订单号
        if (empty($this->_ordersdf['order_bn']) && isset($this->_ordersdf['tid']) && $this->_ordersdf['tid']) {
            $this->_ordersdf['order_bn'] = $this->_ordersdf['tid'];
        }
        
        // 负销售
        if(in_array($this->_ordersdf['method'], ['ome.aftersalev2.add', 'ome.exchange.add'])){
            // 售后申请的商品明细
            if(isset($this->_ordersdf['refund_item_list']) && $this->_ordersdf['refund_item_list'] && is_array($this->_ordersdf['refund_item_list'])){
                $this->_ordersdf['refund_item_list'] = json_encode($this->_ordersdf['refund_item_list'], JSON_UNESCAPED_UNICODE);
            }
            
            // 换货申请的商品明细
            if(isset($this->_ordersdf['apply_detail_list']) && $this->_ordersdf['apply_detail_list'] && is_array($this->_ordersdf['apply_detail_list'])){
                $this->_ordersdf['apply_detail_list'] = json_encode($this->_ordersdf['apply_detail_list'], JSON_UNESCAPED_UNICODE);
            }
        }
        
        return true;
    }
    
    /**
     * 校验接收的数据
     *
     * @return bool
     */
    protected function _canAccept()
    {
        if (empty($this->_ordersdf)) {
            $this->__apilog['result']['msg'] = '接收的数据不完整';
            return false;
        }
        
        if (empty($this->_ordersdf['order_bn']) && empty($this->_ordersdf['tid'])) {
            $this->__apilog['result']['msg'] = '没有接收到订单号';
            return false;
        }
        
        if (empty($this->_ordersdf['method'])) {
            $this->__apilog['result']['msg'] = 'method方法名不能为空';
            return false;
        }
        
        if (empty($this->_ordersdf['node_id'])) {
            $this->__apilog['result']['msg'] = 'node_id节点不能为空';
            return false;
        }
        
        return true;
    }
}
