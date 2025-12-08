<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 系统自动审单
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: consign.php 2016-10-20
 */
class erpapi_shop_response_plugins_order_combine extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $combine = array();
        
        //check过滤经销商模拟创建OMS订单
        if($platform->_newOrder['delivery_mode'] == 'shopyjdf'){
            return $combine;
        }
        
        //开启系统自动审单(默认:忽略可合并的订单)
        $cfg_combine = app::get('ome')->getConf('ome.order.is_auto_combine');
        if($cfg_combine == 'true'){
            //过滤手动拉下来的订单
            if($platform->_ordersdf['auto_combine'] !== false){
                $combine['order_id']    = null;
            }
        } else {
            $cfg_pre    = app::get('ome')->getConf('ome.order.pre_sel_branch');
            if($cfg_pre == 'true') {
                $combine['order_id']    = null;
            }
        } 

        if($platform->_ordersdf['cnAuto'] == 'true'){
            $combine['cnAuto'] = 'true';
        }
        
        // 现货订单自动审单
        $isOffline = false;
        if ($platform->_ordersdf['order_type'] == 'offline' && kernel::single('ome_order_bool_type')->isO2opick($platform->_ordersdf['order_bool_type'])){
            $isOffline = true;
        }
        
        if ($isOffline === true){
            $combine['offlineAuto'] = 'true';
        }
        
        // 闪购订单自动审单
        if (isset($platform->_ordersdf['is_xsdbc']) && $platform->_ordersdf['is_xsdbc'] == 1){
            $combine['offlineAuto'] = 'true';
        }
        
        return $combine;
    }
    
    /**
     * 订单完成后处理
     *
     * @return void
     * @author
     **/
    public function postCreate($order_id, $combine)
    {
        //支付状态读取订单表(预售功能定制)
        $order_info    = app::get('ome')->model('orders')->dump(array('order_id'=>$order_id), 'pay_status, status, order_type, is_cod');

        // 检测京东订单是否有微信支付先用后付的单据
        $use_before_payed = false;
        if ($order_info['shop_type'] == '360buy') {
            $labelCode = kernel::single('ome_bill_label')->getLabelFromOrder($order_info['order_id']);
            $labelCode = array_column($labelCode, 'label_code');
            $use_before_payed = kernel::single('ome_order')->canDeliveryFromBillLabel($labelCode);
        }
        
        //订单必须已支付OR货到付款,并且过滤单拉的订单
        if(($order_info['pay_status'] == '1' || $order_info['shipping']['is_cod'] == 'true' || $use_before_payed) && $order_info['status'] == 'active' && in_array($order_info['order_type'], kernel::single('ome_order_func')->get_normal_order_type()))
        {
            //执行自动审单
            kernel::single('ome_order')->auto_order_combine($order_id,$combine);
        }
    }
}
