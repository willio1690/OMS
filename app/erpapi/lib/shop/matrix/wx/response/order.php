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
class erpapi_shop_matrix_wx_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype');

        if ($this->_ordersdf['pay_status'] != '1') {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }

        return $components;
    }

    protected function _analysis()
    {
        if ($this->_ordersdf['status'] == 'dead' && $this->_ordersdf['shipping']['is_cod'] != 'true') $this->_ordersdf['pay_status'] = '5';

        parent::_analysis();

        // 修复微信小店支付费用bug
        if($this->_ordersdf['payinfo']['cost_payment'] && $this->_ordersdf['payinfo']['cost_payment'] == $this->_ordersdf['payed']){
            $this->_ordersdf['payinfo']['cost_payment'] = 0;
        }

        if(!$this->_ordersdf['lastmodify']){
            $this->_ordersdf['lastmodify'] = date('Y-m-d H:i:s',time());
        }

        // 获取货号
        foreach ($this->_ordersdf['order_objects'] as $objkey => &$object) {
            $product_info   = array();

            $product_info  = $this->item_get($object['shop_goods_id']);
            foreach ($object['order_items'] as $k => &$v) {
                // 重新获取货号
                if ($product_info['skus'][$v['shop_product_id']]['bn']) {
                    $v['bn']      = $product_info['skus'][$v['shop_product_id']]['bn'];
                    $object['bn'] = $product_info['skus'][$v['shop_product_id']]['bn'];
                }
            }
        }
    }

    private function item_get($num_iid){
        static $goods;

        if (isset($goods[$num_iid])) return $goods[$num_iid];

        $rs = kernel::single('erpapi_router_request')->set('shop',$this->__channelObj->channel['shop_id'])->product_item_get($num_iid);
        
        $goods[$num_iid] = array();    
        if ($rs['rsp'] == 'succ' && $rs['data']) {
            $item = $rs['data']['item'];
    
            if ($item['skus']['sku']) {
                foreach ($item['skus']['sku'] as $key => $value) {
                    $goods[$num_iid]['skus'][$value['sku_id']]['sku_id'] = $value['sku_id'];
                    $goods[$num_iid]['skus'][$value['sku_id']]['bn']     = $value['bn'];
                }
            }
        }
        
        return $goods[$num_iid];
    }
}
