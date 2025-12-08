<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_taobao_response_order_b2b extends erpapi_shop_matrix_taobao_response_order
{
    protected $_update_accept_dead_order = true;

    protected function _operationSel(){
        parent::_operationSel();
        $can_update = false;
        if ($this->_tgOrder && !$this->_operationSel) {
            // 修数据：由于淘经销解密失败导致数据异常
            if (50 == strlen($this->_tgOrder['consignee']['mobile']) ||
                    30 == strlen($this->_tgOrder['consignee']['telephone']) ||
                    38 == strlen($this->_tgOrder['consignee']['name']) ||
                    54 == strlen($this->_tgOrder['consignee']['addr'])
                    ) {
                        $can_update = true;
                    }
                    
                    // 判断是否有退款
                    foreach ($this->_ordersdf['order_objects'] as $object) {
                        if($object['status'] == 'TRADE_REFUNDING') {
                            $can_update = true; break;
                        }
                    }
        }
        
        if ( $can_update ) $this->_operationSel = 'update';
        
    }
    
    /**
     * _canAccept
     * @return mixed 返回值
     */

    public function _canAccept()
    {
        if($this->_ordersdf['t_type'] == 'fenxiao'){
            if($this->_ordersdf['member_info']['uname'] == $this->__channelObj->channel['addon']['nickname']){
                $this->__apilog['result']['msg'] = '会员与店铺名相同，标识订单已经存在';
                return false;
            }          
        }

        return parent::_canAccept();
    }

    protected function _analysis()
    {
        parent::_analysis();

        if ($this->_ordersdf['t_type'] == 'fenxiao') {
            $tmp_fx_order_id          = $this->_ordersdf['fx_order_id'];
            $this->_ordersdf['fx_order_id'] = $this->_ordersdf['order_bn'];
            $this->_ordersdf['order_bn']    = $tmp_fx_order_id;
        }

        if ( !$this->_ordersdf['order_source'] ) $this->_ordersdf['order_source'] = $this->_ordersdf['order_type'];

        if ($this->_ordersdf['is_tax'] == 'None') $this->_ordersdf['is_tax'] = 'false';

        foreach((array)$this->_ordersdf['selling_agent'] as $k=>$v){
            if($k == 'agent'){
                $this->_ordersdf['selling_agent']['member_info'] = $this->_ordersdf['selling_agent']['agent'];
                unset($this->_ordersdf['selling_agent']['agent']);
            }
        }
        
        // 退款锁定
        $trade_refunding = false; $trade_refundmoney = 0;        
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object) {
            if($object['status'] == 'TRADE_REFUNDING') $trade_refunding = true;

            foreach ($object['order_items'] as $itemkey => $item) {
                if($item['quantity'] == '0') unset($this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]);
                

                // 判断订单支付状态是否为退款中...
                if ($item['status'] == 'TRADE_REFUNDING') $trade_refunding = true;
            }

            if(empty($object['order_items'])) unset($this->_ordersdf['order_objects'][$objkey]);
        }

        // 退款中...
        if ($trade_refunding == true) $this->_ordersdf['pay_status'] = '7';

        // 判断是否有退款
        if ($this->_ordersdf['payed'] > $this->_ordersdf['total_amount']) {
            $this->_ordersdf['pay_status'] = '6';
            $this->_ordersdf['pause']      = 'true';
        }

        // 前端拒绝退款并手动发货
        if ($this->_ordersdf['ship_status'] == '1' && $this->_ordersdf['status'] == 'finish') {
            $this->_ordersdf['status'] = 'active';
        }
        
        // 订单已经取消...
        if ($this->_ordersdf['status'] == 'dead') {
            $this->_ordersdf['pay_status'] = '5';
            $this->_ordersdf['payed']      = '0';
        }

        $this->mergeItemsForB2b();
        
    }

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'sellingagent';
        $plugins[] = 'tbfx';

        return $plugins;
    }

    protected function get_update_components()
    {
        $components = array('master','items','shipping','consignee','consigner','custommemo','markmemo','marktype','member','tax','oversold');

        return $components;
    }


    /**
     * 获取_update_plugins
     * @return mixed 返回结果
     */
    public function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();
        $plugins[] = 'promotion';
        $plugins[] = 'refundapply';
        $plugins[] = 'tbfx';
        $plugins[] = 'sellingagent';
        $plugins[] = 'crm';

        return $plugins;
    }


    /**
     * 淘分销订单，相同货号，类型，价格的订单明细合并
     *
     * @return void
     * @author 
     **/
    private function mergeItemsForB2b()
    {
        $objIdent = array(); $_use_itemtfxv = false;
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object) {

            $obj_type = $object['obj_type'] ? $object['obj_type'] : 'goods';

            $ident = sprintf('%u',crc32($object['bn'] . '-' . $obj_type));
            if ($object['bn'] && false !== array_search($ident, $objIdent)) {
                $_use_itemtfxv = true;
            }
            $objIdent[] = $ident;

            $order_items = array(); $replace = false;
            foreach ($object['order_items'] as $item) {
                $item_type = $item['item_type'] ? $item['item_type'] : 'product';

                // 销售单价
                $quantity = $item['quantity'] ? $item['quantity'] : 1;
                $subtotal = $item['amount'] ? $item['amount'] : bcmul((float)$item['price'], $quantity,3);
                $sale_price = $item['sale_price'] ? (float)$item['sale_price'] : bcsub($subtotal, (float)$item['pmt_price'],3);

                $unit_sale_price = bcdiv((float)$sale_price, $quantity,3);


                $itemkey = sprintf('%u',crc32($item['bn'] . '-' . $item_type . $unit_sale_price));

                if (isset($order_items[$itemkey])) { // 如果存在，说明有合并
                    // 各相关值叠加
                    $order_items[$itemkey]['quantity']   += $item['quantity'];
                    $order_items[$itemkey]['pmt_price']  += $item['pmt_price'];
                    $order_items[$itemkey]['price']      += $item['price'];
                    $order_items[$itemkey]['amount']     += $item['amount'];
                    $order_items[$itemkey]['sale_price'] += $item['sale_price'];

                    $replace = true;

                    $_use_itemtfxv = true;
                } else {
                    $order_items[$itemkey] = $item;
                }
            }

            if ($replace === true) {
                $this->_ordersdf['order_objects'][$objkey]['order_items'] = $order_items;                
            }
        }

        if ($_use_itemtfxv) {
            // 订单obj明细唯一标识
            $this->object_comp_key = 'bn-oid-obj_type';

            // 订单item唯一标识
            $this->item_comp_key = 'bn-unit_sale_price-item_type';
        }
    }
}
