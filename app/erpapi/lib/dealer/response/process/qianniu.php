<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 千牛修改地址业务
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
class erpapi_dealer_response_process_qianniu extends erpapi_dealer_response_abstract
{
    /**
     * 平台请求OMS是否允许修改收货地址
     * 
     * @param $convertOrder
     * @return string[]
     */

    public function address_modify($convertOrder)
    {
        $operLobObj = app::get('ome')->model('operation_log');
        $jxOrderLib = kernel::single('dealer_platform_orders');
        
        $new_order = $convertOrder['new_order'];
        $order_detail = $convertOrder['order_detail'];
        
        $shop_id = $order_detail['shop_id'];
        $plat_order_bn = $order_detail['plat_order_bn'];
        
        //走编辑订单一样的流程
        $plat_order_id = $new_order['plat_order_id'];
        if(empty($plat_order_id)) {
            return array('rsp'=>'succ','msg'=>'平台请求修改收货地址成功');
        }
        
        //暂停经销订单
        $result = $jxOrderLib->pausePlatformOrder($plat_order_id);
        if ($result['rsp'] != 'succ'){
            $msg = '经销订单状态不支持修改地址';
            
            //通知平台是否允许修改地址
            $this->_confirmModifyAdress($shop_id, ['plat_order_bn'=>$plat_order_bn, 'confirm'=>false]);
            
            return array('rsp'=>'fail','msg'=>$msg, 'msg_code'=>'300007');
        }
        
        //consignee
        $consignee = $new_order['consignee'];
        foreach ($consignee as $k => $v)
        {
            if($index = strpos($v, '>>')) {
                $consignee[$k] = substr($v, 0, $index);
            }
            
            if($k == 'area') {
                //经销订单收货地区格式为：浙江省/杭州市/萧山区/戴村镇，没有：mainland: 前辍
                //list(,$consignee[$k],) = explode(':', $v);
                //$consignee[$k] = str_replace('/', '-', $consignee[$k]);
            }
        }
        
        //通知平台是否允许修改地址
        $this->_confirmModifyAdress($shop_id, ['plat_order_bn'=>$plat_order_bn,'confirm'=>true]);
        
        //log
        $msgLog = "平台通知修改收货地址为：". $consignee['name'].' '.$consignee['mobile'].' '.$consignee['area'].' '.$consignee['addr'];
        $operLobObj->write_log('order_modify@dealer', $plat_order_id, $msgLog);
        
        return array('rsp'=>'succ','msg'=>'平台通知修改经销订单收货地址');
    }
    
    /**
     * order_addr_modify
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function order_addr_modify($sdf)
    {
        return array('rsp'=>'succ', 'msg'=>'平台更换经销订单地址成功');
    }
    
    /**
     * modifysku
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function modifysku($sdf)
    {
        return array('rsp'=>'succ', 'msg'=>'平台更换经销订单SKU成功');
    }
    
    /**
     * 通知平台是否允许修改收货地址
     * @todo：现在只有抖音平台支持；
     * 
     * @param $shop_id
     * @param $data
     * @return array
     */
    private function _confirmModifyAdress($shop_id, $data)
    {
        $result = kernel::single('erpapi_router_request')->set('dealer', $shop_id)->order_confirmModifyAdress($data);
        
        return $result;
    }
}
