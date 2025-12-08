<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-11-17
 * @describe 订单类型插件
 */
class erpapi_shop_response_plugins_order_ordertype extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $ordersdf                    = $platform->_ordersdf;
        $ordersdf['shop']['shop_id'] = $platform->__channelObj->channel['shop_id'];
        
        $ordertypesdf = array();
        if ((in_array($ordersdf['order_type'], kernel::single('ome_order_func')->get_normal_order_type())
            || empty($ordersdf['order_type']))) {
            
            //brush特殊订单
            if (app::get('brush')->is_installed()) {
                if(!$platform->_tgOrder || $platform->_tgOrder['process_status'] == 'unconfirmed') {
                    kernel::single('brush_order')->brush_confirm($ordersdf);
                }
            }
            
            //brush刷单
            if ($ordersdf['brush']['farm_id']) {
                if (!$platform->_tgOrder || ($platform->_tgOrder['order_type'] != 'brush' && $platform->_tgOrder['process_status'] == 'unconfirmed')) {
                    //检查订单有退款申请单,不能转换为brush特殊订单
                    if ($platform->_tgOrder) {
                        $refundApply = app::get('ome')->model('refund_apply')->db_dump(array('order_id'=>$platform->_tgOrder['order_id'], 'status|noequal'=>'3'), 'apply_id');
                        if ($refundApply) {
                            $logModel = app::get('ome')->model('operation_log');
                            $logModel->write_log('order_edit@ome', $platform->_tgOrder['order_id'], "因有可用退款申请单不能转为特殊订单");
                            
                            return $ordertypesdf;
                        }
                    }
                    
                    $ordertypesdf['farm_id'] = $ordersdf['brush']['farm_id'];
                    $ordertypesdf['order_id'] = null;
                    
                    $platform->_newOrder['order_type'] = 'brush';
                }
            }
            
            //vopczc
            if ($platform->_newOrder['order_type'] == 'vopczc') {
                $ordertypesdf['vopczc_order_status'] = 'true';
            }
        } 

        if($ordersdf['interrelated_aftersale_no'] && $ordersdf['order_type'] == 'exchange') {
            $ordertypesdf['interrelated_aftersale_no'] = $ordersdf['interrelated_aftersale_no'];
            $ordertypesdf['shop_id'] = $ordersdf['shop']['shop_id'];
        }

        if ($ordersdf['order_type'] == 'platform' || $ordersdf['sh_ship_exists'] == true) {
            foreach ($platform->_ordersdf['order_objects'] as $object) {
                if ($object['logistics_code'] && !$platform->_ordersdf['shipping']['shipping_id']){
                    $platform->_newOrder['logi_no'] = $object['logistics_code'];
                }
                if ($object['logistics_company'] && 
                    (!$platform->_ordersdf['shipping']['shipping_name'] || $platform->_ordersdf['shipping']['shipping_name']=='快递')
                ){
                    $platform->_newOrder['shipping']['shipping_name'] = $object['logistics_company'];
                }
            }
            if ($ordersdf['ship_status'] == '1') {
                $ordertypesdf['platform_consign'] = 'true';
            }
        }elseif($platform->_newOrder['order_type'] == 'custom'){
            //[定制订单]商品定制信息
            $customizationList = [];
            foreach ($platform->_ordersdf['order_objects'] as $object)
            {
                $oid = $object['oid'];
                
                //check
                if(isset($object['customization']) && $object['customization']){
                    $customizationList[$oid] = $object['customization'];
                }
            }
            
            //商品定制信息列表
            if($customizationList){
                $ordertypesdf['is_customization'] = true;
            }
        }
        
        return $ordertypesdf;
    }

    /**
     * 订单完成后处理
     **/
    public function postCreate($order_id, $ordertypesdf)
    {
        $order                    = $this->getOrder($order_id);
        $ordertypesdf['order_id'] = $order_id;
        
        switch($order['order_type'])
        {
            case 'platform':
                if ($ordertypesdf['platform_consign'] == 'true') {
                    register_shutdown_function(function() use($order_id) {
                        kernel::single('ome_order_platform')->deliveryConsign($order_id);
                    }, $order_id);
                }
                break;
            case 'brush':
                //brush特殊订单
                if($ordertypesdf['farm_id']) {
                    $data = array('order_id'=>$order_id, 'farm_id'=>$ordertypesdf['farm_id']);
                    app::get('brush')->model('farm_order')->save($data);
                }
                break;
            case 'vopczc':
                if ($ordertypesdf['vopczc_order_status'] == 'true') {
                    kernel::single('ome_service_order')->exportOrder(array($order));
                }
                break;
            case 'exchange':
                if($ordertypesdf['interrelated_aftersale_no']) {
                    app::get('ome')->model('reship')->update(['change_order_id'=>$order_id], ['reship_bn'=>$ordertypesdf['interrelated_aftersale_no'],'shop_id'=>$ordertypesdf['shop_id']]);
                }
                break;
            case 'custom':
                $orderObjList = [];
                if(isset($ordertypesdf['order_objects']) && $ordertypesdf['order_objects']){
                    foreach ($ordertypesdf['order_objects'] as $objectKey => $objectVal)
                    {
                        $obj_id = $objectVal['obj_id'];
                        
                        //商品定制信息
                        if(isset($objectVal['customization']) && $objectVal['customization']){
                            $orderObjList[$obj_id] = $objectVal;
                        }
                    }
                }
                
                //保存订单object层商品定制信息
                if($orderObjList){
                    $objExtendObj = app::get('ome')->model('order_objects_extend');
                    
                    //insert
                    foreach ($orderObjList as $obj_id => $objectVal)
                    {
                        //json
                        if(is_array($objectVal['customization'])){
                            $customInfo = json_encode($objectVal['customization'], JSON_UNESCAPED_UNICODE);
                        }else{
                            $customInfo = $objectVal['customization'];
                        }
                        
                        //save
                        $saveData = [
                            'order_id' => $order_id,
                            'obj_id' => $obj_id,
                            'customization' => $customInfo,
                        ];
                        $objExtendObj->insert($saveData);
                    }
                }
                
                break;
            default:
                # code...
                break;
        }
    }

    /**
     * 更新后操作
     *
     * @return void
     * @author
     **/
    public function postUpdate($order_id, $ordertypesdf)
    {
        $order                    = $this->getOrder($order_id);
        switch($order['order_type'])
        {
            case 'platform':
                if ($ordertypesdf['platform_consign'] == 'true') {
                    kernel::single('ome_order_platform')->deliveryConsign($order_id);
                }
                break;
            case 'brush':
                //brush特殊订单
                if($ordertypesdf['farm_id']) {
                    $data = array('order_id'=>$order_id, 'farm_id'=>$ordertypesdf['farm_id']);
                    app::get('brush')->model('farm_order')->save($data);
                    
                    $logModel = app::get('ome')->model('operation_log');
                    $logModel->write_log('order_edit@ome', $order_id, '转为特殊订单');
                    
                    //释放冻结
                    $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
                    
                    $branchBatchList = [];
                    $order_items = $this->getOrderItems($order_id);
                    $order_objects = $this->getOrderObjects($order_id);
                    $order_objects = array_column($order_objects,null,'obj_id');
                    foreach ((array)$order_items as $order_item)
                    {
                        if ($order_item['product_id'] && $order_item['delete'] == 'false') {
                            
                            //[扣减]基础物料店铺冻结
                            $branchBatchList[] = [
                                'bm_id'     =>  $order_item['product_id'],
                                'sm_id'     =>  $order_objects[$order_item['obj_id']]['goods_id'],
                                'obj_type'  =>  material_basic_material_stock_freeze::__ORDER,
                                'bill_type' =>  0,
                                'obj_id'    =>  $order_id,
                                'branch_id' =>  '',
                                'bmsq_id'   =>  material_basic_material_stock_freeze::__SHARE_STORE,
                                'num'       =>  $order_item['nums'],
                            ];
                        }
                    }
                    //释放基础物料店铺冻结
                    $err = '';
                    $basicMStockFreezeLib->unfreezeBatch($branchBatchList, __CLASS__.'::'.__FUNCTION__, $err);
                    
                    //清除订单级预占店铺冻结流水
                    // unfreezeBatch已经清除
                    // $basicMStockFreezeLib->delOrderFreeze($order_id);
                    
                }
                break;
            case 'vopczc':
                break;
            case 'exchange':
                break;
            default:
                //code...
                break;
        }
    }
}
