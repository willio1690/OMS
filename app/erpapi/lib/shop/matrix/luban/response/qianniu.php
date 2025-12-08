<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单接口处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_luban_response_qianniu extends erpapi_shop_response_qianniu
{
    /**
     * ERP订单
     * 
     * @var string
     * */

    public $_order_detail= array();

    /**
     * 订单接收格式
     * 
     * @var string
     * */
    public $_qnordersdf = array();




        /**
     * 添加ress_modify
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function address_modify($sdf)
    {
        $sdf['bizOrderId'] = strpos($sdf['bizOrderId'], 'A') ? $sdf['bizOrderId'] : $sdf['bizOrderId'].'A';
        return parent::address_modify($sdf);
    }
    
    /**
     * 是否接收订单
     *
     * @return void
     * @author
     **/
    protected function _canModify()
    {
        $orderModel = app::get('ome')->model('orders');
        $filter = array('order_bn'=>$this->_qnordersdf['bizOrderId'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $order_detail = $orderModel->dump($filter,'*',array("order_objects"=>array("*",array("order_items"=>array('*')))));
        if (empty($order_detail) &&  substr($this->_qnordersdf['bizOrderId'], -1) === 'A') {
            //抖音订单号去A查询
            $this->_qnordersdf['bizOrderId'] = substr($this->_qnordersdf['bizOrderId'], 0, -1);
            $filter = array('order_bn'=>$this->_qnordersdf['bizOrderId'],'shop_id'=>$this->__channelObj->channel['shop_id']);
            $order_detail = $orderModel->dump($filter,'*',array("order_objects"=>array("*",array("order_items"=>array('*')))));
            $this->__apilog['result']['data'] = array('tid'=>$filter['order_bn']);
            $this->__apilog['original_bn']    = $filter['order_bn'];
            $this->__apilog['title']          = '千牛/平台修改订单地址['.$filter['order_bn'].']';
        }
        $this->_order_detail = $order_detail;
        
        if (!$this->_order_detail){
            $shopId = $this->__channelObj->channel['shop_id'];
            $orderRsp = kernel::single('erpapi_router_request')->set('shop',$shopId)->order_get_order_detial($this->_qnordersdf['bizOrderId']);
            if ($orderRsp['rsp'] == 'succ') {
                $msg = '';
                $rs = kernel::single('ome_syncorder')->get_order_log($orderRsp['data']['trade'],$shopId,$msg);
                if ($rs) {
                    $this->_order_detail = $orderModel->dump($filter,'*',array("order_objects"=>array("*",array("order_items"=>array('*')))));
                }
            }
            if(!$this->_order_detail) {
                return true;
            }
        }
        //只针对未发货  部分发货也不可以修改
        if (!in_array($this->_order_detail['status'],array('active')) || !in_array($this->_order_detail['ship_status'],array('0')) || !in_array($this->_order_detail['process_status'],array('unconfirmed','confirmed','splitting','splited'))){
            $this->__apilog['result']['msg'] = '对应状态不可以 编辑订单';
            $this->__apilog['result']['msg_code'] = '200006';
            return false;
        }
        
        return true;
    }
    
    protected function _formatSdf(){
        if (is_string($this->_qnordersdf['modifiedAddress'])) {
            $this->_qnordersdf['modifiedAddress'] = json_decode($this->_qnordersdf['modifiedAddress'],true);
        }

        $modifiedAddress = $this->_qnordersdf['modifiedAddress'];

        if ($modifiedAddress['name'])           $this->_qnordersdf['consignee']['name']     = $modifiedAddress['name'];
        if ($modifiedAddress['province'])       $this->_qnordersdf['consignee']['province'] = $modifiedAddress['province'];
        if ($modifiedAddress['city'])           $this->_qnordersdf['consignee']['city']     = $modifiedAddress['city'];
        if ($modifiedAddress['area'])           $this->_qnordersdf['consignee']['area']     = $modifiedAddress['area'];
        if ($modifiedAddress['addressDetail'])  $this->_qnordersdf['consignee']['addr']     = false !== strpos($modifiedAddress['addressDetail'], $modifiedAddress['town']) ?$modifiedAddress['addressDetail'] : $modifiedAddress['town'].$modifiedAddress['addressDetail'];
        if ($modifiedAddress['postCode'])       $this->_qnordersdf['consignee']['zip']      = $modifiedAddress['postCode'];
        if ($modifiedAddress['phone'])          $this->_qnordersdf['consignee']['mobile']   = $modifiedAddress['phone'];

    }


    protected function _formatModifysku($sdf) {
        $params = parent::_formatModifysku($sdf);
        $params['update_order'] = true;
        //抖音订单号去A查询
        $orders_obj = app::get('ome')->model('orders');
        $order_filter = array('order_bn'=>$params['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $orders_info = $orders_obj->dump($order_filter);
        if (empty($orders_info) &&  substr($params['order_bn'], -1) === 'A') {
            $params['order_bn'] = substr($params['order_bn'], 0, -1);
            $order_filter = array('order_bn'=>$params['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
            $orders_info = $orders_obj->dump($order_filter);
        }
        if (!$orders_info) {
            //如果没有找到，先去拉取订单
            $shopId = $this->__channelObj->channel['shop_id'];
            $orderRsp = kernel::single('erpapi_router_request')->set('shop', $shopId)->order_get_order_detial($params['order_bn']);
            if ($orderRsp['rsp'] == 'succ') {
                kernel::single('ome_syncorder')->get_order_log($orderRsp['data']['trade'], $shopId, '');
            }
        }
        return $params;
    }
}
