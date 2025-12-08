<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 千牛修改地址接口
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
class erpapi_dealer_response_qianniu extends erpapi_dealer_response_abstract
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
     * 改地址消息
     * @var array
     */
    public $_notifysdf = array();
    
    /**
     * 平台请求OMS是否允许修改收货地址
     * 
     * @param $sdf
     * @return array
     */
    public function address_modify($sdf)
    {
        $sdf['order_bn'] = $sdf['bizOrderId'];
        
        //前置处理平台数据（例如：去除抖音平台订单A字母）
        $sdf = $this->preFormatData($sdf);
        
        //获取平台名称
        $platform_name = $this->getPlatformName();
        
        //接收的原平台订单数据
        $this->_qnordersdf = $sdf;
        
        //apilog
        $this->__apilog['result']['data'] = array('order_bn'=>$this->_qnordersdf['plat_order_bn']);
        $this->__apilog['original_bn']    = $this->_qnordersdf['plat_order_bn'];
        $this->__apilog['title']          = $platform_name . '平台通知修改订单收货地址['. $sdf['plat_order_bn'] .']';
        
        //是否允许修改收货地址
        $accept = $this->_canModify();
        if ($accept === false) {
            return array();
        }
        
        //格式化参数
        $this->_formatSdf();
        
        //check
        if (!$this->_qnordersdf['consignee']){
            $this->__apilog['result']['msg'] = '地区格式有误';
            $this->__apilog['result']['msg_code'] = '300003';
            
            return array();
        }
        
        //地址是否发生变化
        $oldconsignee = array(
            'name' => $this->_order_detail['consignee']['name'],
            'area' => $this->_order_detail['consignee']['area'],
            'addr' => $this->_order_detail['consignee']['addr'],
            'zip' => $this->_order_detail['consignee']['zip'],
            'mobile' => $this->_order_detail['consignee']['mobile'],
        );
        
        $newconsignee = $this->_qnordersdf['consignee'];
        
        //diff
        $diff_consignee = array_diff_assoc($newconsignee, $oldconsignee);
        if (!$diff_consignee){
            $this->__apilog['result']['msg'] = '收货地址没有变化,无需修改';
            
            return array();
        }
        
        $new_order = array();
        $new_order['plat_order_id'] = $this->_order_detail['plat_order_id'];
        $new_order['consignee'] = $newconsignee;
        
        //暂停成功
        $new_order['confirm']        = 'N';
        $new_order['process_status'] = 'unconfirmed';
        $new_order['pause']          = 'false';
        
        $convert_order = array(
            'new_order' => $new_order,
            'order_detail' => $this->_order_detail,
        );
        
        return $convert_order;
    }
    
    /**
     * 平台更换经销订单地址
     * 
     * @param $sdf
     * @return array
     */
    public function order_addr_modify($sdf)
    {
        $sdf['order_bn'] = ($sdf['orderId'] ? $sdf['orderId'] : $sdf['bizOrderId']);
        
        //前置处理平台数据（例如：去除抖音平台订单A字母）
        $sdf = $this->preFormatData($sdf);
        
        $this->__apilog['original_bn'] = $sdf['plat_order_bn'];
        $this->__apilog['title'] = '平台更换订单收货地址';
        
        //return
        $this->__apilog['result']['data'] = array('order_bn'=>$sdf['plat_order_bn']);
        $this->__apilog['result']['msg'] = '不支持平台更换经销订单地址';
        $this->__apilog['result']['msg_code'] = '300010';
    
        return array();
    }
    
    /**
     * 换货修改sku
     * 
     * @param $sdf
     * @return array|false
     */
    public function modifysku($sdf)
    {
        $sdf['order_bn'] = $sdf['bizOrderId'];
        
        //前置处理平台数据（例如：去除抖音平台订单A字母）
        $sdf = $this->preFormatData($sdf);
        
        $this->__apilog['original_bn'] = $sdf['plat_order_bn'];
        $this->__apilog['title'] = '平台更换经销订单SKU';
        
        //return
        $this->__apilog['result']['data'] = array('order_bn'=>$sdf['plat_order_bn']);
        $this->__apilog['result']['msg'] = '不支持平台更换经销订单SKU';
        $this->__apilog['result']['msg_code'] = '300020';
        
        return array();
    }
    
    /**
     * 是否允许修改收货地址
     * 
     * @return bool
     */
    protected function _canModify()
    {
        $jxOrderLib = kernel::single('dealer_platform_orders');
        
        //获取经销订单信息
        $filter = array('plat_order_bn'=>$this->_qnordersdf['plat_order_bn'], 'shop_id'=>$this->__channelObj->channel['shop_id']);
        $this->_order_detail = $jxOrderLib->getOrderDetail($filter);
        if(empty($this->_order_detail)){
            $this->__apilog['result']['msg'] = '经销订单不存在';
            $this->__apilog['result']['msg_code'] = '300001';
            
            return false;
        }
        
        //只能修改未发货订单(部分发货不允许修改)
        if (!in_array($this->_order_detail['status'], array('active')) || !in_array($this->_order_detail['ship_status'], array('0')) || !in_array($this->_order_detail['process_status'], array('unconfirmed','confirmed','splitting','splited'))){
            $this->__apilog['result']['msg'] = '经销订单状态不允许修改地址';
            $this->__apilog['result']['msg_code'] = '300002';
            
            return false;
        }
        
        return true;
    }
    
    /**
     * 格式化参数
     * 
     * @return boolean
     */
    protected function _formatSdf()
    {
        if (is_string($this->_qnordersdf['modifiedAddress'])) {
            $this->_qnordersdf['modifiedAddress'] = json_decode($this->_qnordersdf['modifiedAddress'],true);
        }
        
        $modifiedAddress = $this->_qnordersdf['modifiedAddress'];
        
        //地区
        $area = $modifiedAddress['province'].'/'.$modifiedAddress['city'].'/'.$modifiedAddress['area'];
        if($modifiedAddress['town']){
            $area .= '/'. $modifiedAddress['town'];
        }
        
        //详细地址
        $addressDetail = '';
        if($modifiedAddress['addressDetail']){
            if(false !== strpos($modifiedAddress['addressDetail'], $modifiedAddress['town'])){
                $addressDetail = $modifiedAddress['addressDetail'];
            }else{
                //详细地址加上：镇
                $addressDetail = $modifiedAddress['town'] .','. $modifiedAddress['addressDetail'];
            }
        }
        
        //consignee
        $this->_qnordersdf['consignee'] = array(
            'name' => $modifiedAddress['name'],
            'area' => $area,
            'mobile' => $modifiedAddress['phone'],
            'zip' => trim($modifiedAddress['postCode']),
            'addr' => $addressDetail,
        );
        
        return true;
    }
}
