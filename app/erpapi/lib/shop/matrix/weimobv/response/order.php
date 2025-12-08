<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: ghc
 * Date: 18/10/10
 * Time: 上午10:51
 */
class erpapi_shop_matrix_weimobv_response_order extends erpapi_shop_response_order{
    protected $_update_accept_dead_order = true;
    
    /**
     * 数据解析
     * 
     * @return void
     */

    protected function _analysis()
    {
        parent::_analysis();
        
        //objects
        foreach ((array)$this->_ordersdf['order_objects'] as $objKey => $objVal)
        {
            //check没有items层
            if(!isset($objVal['order_items'])){
                continue;
            }
            
            //items
            foreach ($objVal['order_items'] as $itemKey => $itemVal)
            {
                //检查：没有sale_amount字段则打标
                if(!isset($itemVal['sale_amount'])){
                    continue;
                }
                
                //检查：divide_order_fee字段已经有金额则无需进行格式化
                if(isset($itemVal['divide_order_fee']) && $itemVal['divide_order_fee'] > 0){
                    continue;
                }
                
                $divide_order_fee = $itemVal['sale_amount'];
                
                //子单实付金额
                //@todo：现在divide_order_fee子单实付金额是OMS自己计算,导致多个SKU时与平台子单实付金额不一致，退款时无法编码删除退款的SKU商品；
                $this->_ordersdf['order_objects'][$objKey]['order_items'][$itemKey]['divide_order_fee'] = $divide_order_fee;
                
                //子单优惠分摊
                if($itemVal['sale_price'] > $divide_order_fee){
                    $this->_ordersdf['order_objects'][$objKey]['order_items'][$itemKey]['part_mjz_discount'] = $itemVal['sale_price'] - $divide_order_fee;
                }
            }
        }
    }
    
    /**
     * 订单是否创建
     * @return bool|void
     */
    protected function _canAccept(){
        if($this->_ordersdf['shipping']['shipping_name'] == '自提') {
            $this->__apilog['result']['msg'] = '到店自提订单暂不支持';
            return false;
        }
        if($this->_ordersdf['is_delivery'] == 'N') {
            $this->__apilog['result']['msg'] = '不发货订单不接收';
            return false;
        }

        if ($this->_ordersdf['o2o_info']){
            
            $o2o_info = $this->_ordersdf['o2o_info'];
            $weimobvconf =  app::get('ome')->getConf('shop.weimobv.config');
            if ($o2o_info && $weimobvconf['store_conf']){
                $store_conf = explode(',',$weimobvconf['store_conf']);
                
                if(!in_array($o2o_info['store_code'],$store_conf)){
                    $this->__apilog['result']['msg'] = '不是 '.$weimobvconf['store_conf'] .'门店订单不收';
                    return false;
                }
            }
        }
       
        return parent::_canAccept();
    }

    /**
     * @return array
     */
    protected function get_update_components()
    {
        $components = array('markmemo','marktype');

        if ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }
        

        // 如果没有收货人信息，
        if (!$this->_tgOrder['consignee']['name'] || !$this->_tgOrder['consignee']['area'] || !$this->_tgOrder['consignee']['addr']) {
            $components[] = 'consignee';
        }

        return $components;
    }

    protected function _operationSel()
    {
        parent::_operationSel();

        if ($this->_tgOrder) {
            $this->_operationSel = 'update';
        }
    }

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'weimobvo2o';
        

        return $plugins;
    }
}