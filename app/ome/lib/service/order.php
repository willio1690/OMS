<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_order{
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app)
    {
        $this->app = $app;
    }

    /**
     * 订单编辑 iframe
     * @access public
     * @param string $order_id 订单ID
     * @param Bool $is_request 是否发起请求
     * @param Array $ext 扩展参数
     */
    public function update_iframe($order_id,$is_request=true,$ext=array()){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        $rs = kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateIframe($order, $is_request, $ext);
        return $rs;
    }
       

    /**
     * 订单编辑
     * @access public
     * @param string $order_id 订单号
     */
    public function update_order($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        $rs = kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrder($order);
        
        return $rs;#ecshop的修改订单是直连,此处需要要返回rs
    }
    
    /**
     * 订单备注修改
     * @access public
     * @param string $order_id 订单号
     * @param string $memo 订单备注
     */
    public function update_memo($order_id, $memo){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrderMemo($order, $memo);
    }
    
    /**
     * 订单备注添加
     * @access public
     * @param string $order_id 订单号
     * @param string $memo 订单备注
     */
    public function add_memo($order_id, $memo){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_addOrderMemo($order, $memo);
    }
    
    /**
     * 订单状态修改
     * @access public
     * @param string $order_id 订单号
     * @param string $status 状态
     * @param string $memo 备注
     * @param string $mode 请求类型:sync同步  async异步
     */
    public function update_order_status($order_id,$status='',$memo='',$mode='sync'){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        return kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrderStatus($order,$status,$memo,$mode);
    }

    /**
     * 订单暂停与恢复
     * @access public
     * @param string $order_id 订单号
     * @param string $status 订单状态true:暂停 false:恢复
     */
    public function update_order_pause_status($order_id,$status=''){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        if($status == 'true') {
            $status = 'pause';
        } elseif($status == 'false') {
            $status = 'restore';
        } else {
            $status = $order['pause'] == 'true' ? 'pause' : 'restore';
        }
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrderStatus($order,$status,'','async');
    }
    
    /**
     * 更新订单发票信息
     * @access public
     * @param string $order_id 订单号
     */
    public function update_order_tax($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrderTax($order);
        #$this->router->setShopId($order['shop_id'])->update_order_tax($order);
    }
    
    
    /**
     * 买家留言添加
     * @access public
     * @param string $order_id 订单号
     * @param string $memo 买家留言
     */
    public function add_custom_mark($order_id, $memo){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_addOrderCustomMark($order, $memo);
        #$this->router->setShopId($order['shop_id'])->add_order_custom_mark($order,$memo);
    }

    /**
     * 更新交易发货人信息
     * @access public
     * @param string $order_id 订单号
     */
    public function update_consigner_info($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrderConsignerinfo($order);
        #$this->router->setShopId($order['shop_id'])->update_order_consignerinfo($order);
    }
    
    /**
     * 更新代销人信息
     * @access public
     * @param string $order_id 订单号
     */
    public function update_sellingagent_info($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrderSellagentinfo($order);
        #$this->router->setShopId($order['shop_id'])->update_order_sellagentinfo($order);
    }
    
    /**
     * 订单失效时间
     * @access public
     * @param string $order_id 订单号
     * @param string $order_limit_time 订单失效时间
     */
    public function update_order_limit_time($order_id,$order_limit_time=''){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrderLimitTime($order, $order_limit_time);
        #$this->router->setShopId($order['shop_id'])->update_order_limittime($order,$order_limit_time);
    }
    
    /**
     * 更新交易收货人信息
     * @access public
     * @param string $order_id 订单号
     */
    public function update_shippinginfo($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);
        kernel::single('erpapi_router_request')->set('shop', $order['shop_id'])->order_updateOrderShippingInfo($order);
    }

    /**
     * 获取发票抬头
     * 
     * @return void
     * @author 
     * */
    public function get_invoice($order_bn,$shop_id)
    {
        $rs = kernel::single('erpapi_router_request')->set('shop', $shop_id)->invoice_getOrderInvoice($order_bn);
        if($rs){
            if($rs['rsp'] == 'succ'){
                $tmp = $rs['data'];
                return $tmp;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 订单批次索引号（返回或更新）
     * @param sdf $orderSdf 订单sdf结构
     * @param string $process （get：返回当前的批次索引号(获取一次都占一个号，慎用)；add：如果已存在批次索引号，则不更新；update：不管有没有批次索引号，都更新）
     */
    public function order_job_no($orderSdf, $process='get'){
        return kernel::single("ome_order_batch")->order_job_no($orderSdf, $process);
    }
    
    public function destroy_running_no($shop_id, $username, $md5){
        return kernel::single("ome_order_batch")->destroy_running_no($shop_id, $username, $md5);
    }

    /**
     * 获取子订单的订单号
     * @access public
     * @param String $oid 子订单号
     * @return 订单号
     */
    public function getOrderBnByoid($oid='',$node_id='') {
        return kernel::single('ome_order')->getOrderBnByoid($oid,$node_id);
    }

    /**
     * 订单号是否存在
     * @access public
     * @param String $order_bn 订单号
     * @param String $node_id 节点ID
     * @return bool
     */
    public function order_is_exists($order_bn='',$node_id=''){
        return kernel::single('ome_order')->order_is_exists($order_bn,$node_id);
    }

    #判断订单订单在前端是否可以发货
    public function isDeliveryOnShop($arrOrderBn, $shopId) {
        $rsp = kernel::single('erpapi_router_request')->set('shop', $shopId)->order_getOrderStatus($arrOrderBn);
        $result = array('rsp'=>'succ');
        if($rsp['rsp'] == 'succ') {
            $data = $rsp['data'];
            foreach($arrOrderBn as $val) {
                if(!$data[$val]) {
                    $result = array(
                            'rsp'=>'fail',
                            'msg'=>'订单' . $val . '状态不对(' . $rsp['msg_id'] . ')'
                    );
                    break;
                }
            }
        } else {
            if($rsp['err_msg'] != '接口被禁止') {
                $result = array(
                        'rsp' => 'fail',
                        'msg' => ($rsp['msg'] ? $rsp['msg'] : $rsp['err_msg']) . '(' . $rsp['msg_id'] . ')'
                );
            }
        }
        return $result;
    }

    /**
     * exportOrder
     * @param mixed $arrOrder arrOrder
     * @return mixed 返回值
     */
    public function exportOrder($arrOrder) {
        $shopOrder = array();
        $bnId = array();
        foreach ($arrOrder as $val) {
            $shopOrder[$val['shop_id']][] = $val['order_bn'];
            $bnId[$val['order_bn']] = $val['order_id'];
        }
        foreach ($shopOrder as $shopId => $arrOrderBn) {
            $rs = kernel::single('erpapi_router_request')->set('shop', $shopId)->order_getOrderStatus($arrOrderBn);
            if ($rs['rsp'] == 'succ') {
                $data = $rs['data'];
                foreach ($data as $orderBn => $val) {
                    if($bnId[$orderBn]) {
                        app::get('ome')->model('order_extend')->updateBoolExtendStatus($bnId[$orderBn], ome_order_bool_extendstatus::__EXPORT_ORDER);
                    }
                }
            }
        }
    }

    /**
     * orderIsDeliveryOnShop
     * @param mixed $arrShopOrderBn arrShopOrderBn
     * @return mixed 返回值
     */
    public function orderIsDeliveryOnShop($arrShopOrderBn) {
        foreach ($arrShopOrderBn as $shopId => $arrOrderBn) {
            $result = $this->isDeliveryOnShop($arrOrderBn, $shopId);
            if($result['rsp'] == 'fail') {
                return $result;
            }
        }
        return array('rsp'=>'succ');
    }

}