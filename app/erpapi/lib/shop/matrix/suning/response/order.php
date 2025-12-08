<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 苏宁订单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_suning_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');

        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead')) {
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
        parent::_analysis();

        if (!$this->_ordersdf['lastmodify']) $this->_ordersdf['lastmodify'] = date('Y-m-d H:i:s');

        $this->_ordersdf['payinfo']['pay_name'] = '在线支付';

        // 获取货号(实际传的是货品ID)
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object) {
            $goods = array();

            if (!$object['bn'] || $object['bn'] == $object['shop_goods_id']) {
                $goods = $this->items_custom_get($object['shop_goods_id']);
                $this->_ordersdf['order_objects'][$objkey]['bn'] = $goods['bn'];
            }

            if ($object['sale_price']) $this->_ordersdf['order_objects'][$objkey]['sale_price'] = round($object['sale_price'],3);

            foreach ($object['order_items'] as $itemkey => $item) {

                if ($goods) {
                    $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['bn']           = isset($goods['sku']) ? $goods['sku']['bn'] : $goods['bn'];
                    $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['product_attr'] = $goods['sku']['properties'] ? $goods['sku']['properties'] : array();
                }

                if ($item['sale_price']) $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['sale_price'] = round($item['sale_price'],3);
            }
        }

        if ($this->_ordersdf['total_amount']) $this->_ordersdf['total_amount'] = round($this->_ordersdf['total_amount'],3);
        if ($this->_ordersdf['cur_amount']) $this->_ordersdf['cur_amount'] = round($this->_ordersdf['cur_amount'],3);

        // 判断订单优惠是否多余pmt_order
        $total_amount = (float) $this->_ordersdf['cost_item']
                                + (float) $this->_ordersdf['shipping']['cost_shipping']
                                + (float) $this->_ordersdf['shipping']['cost_protect']
                                + (float) $this->_ordersdf['discount']
                                + (float) $this->_ordersdf['cost_tax']
                                + (float) $this->_ordersdf['payinfo']['cost_payment']
                                - (float) $this->_ordersdf['pmt_goods'];

        if (0 == bccomp($total_amount, $this->_ordersdf['total_amount'],3) && 0 != bccomp($this->_ordersdf['pmt_order'], 0,3)) {
            $this->_ordersdf['pmt_order'] = '0';
        }
        
    }

    /**
     * 获取sku
     *
     * @return void
     * @author
     **/
    protected function items_custom_get($num_iid)
    {
        $goods = array();

        $rs = kernel::single('erpapi_router_request')->set('shop', $this->__channelObj->channel['shop_id'])->product_items_custom_get($num_iid);

        if ($rs['rsp'] == 'fail' || !$rs['data'] ){
            return array();
        }

        $item = $rs['data']['item'];

        $goods['bn']  = trim($item['outer_id']);
        $goods['iid'] = $item['iid'];

        if ($item['skus']['sku']) {
            foreach ($item['skus']['sku'] as $key => $value) {
                $goods['sku']['bn']     = trim($value['outer_id']);
                $goods['sku']['sku_id'] = $value['sku_id'];

                $details_goods = $this->item_get($value['iid']);

                $goods['sku']['properties'] = $details_goods['skus'][$value['sku_id']]['properties'];
            }
        }

        return $goods;
    }

    /**
     * 商品
     *
     * @return void
     * @author
     **/
    protected function item_get($num_iid)
    {
        static $goods;

        if ($goods[$num_iid]) return $goods[$num_iid];

        $rs = kernel::single('erpapi_router_request')->set('shop', $this->__channelObj->channel['shop_id'])->product_item_get($num_iid);
        if ($rs['rsp'] == 'fail' || !$rs['data'] ){
            return array();
        }

        $item = $rs['data']['item'];

        $goods[$num_iid]['outer_id'] = $item['outer_id'];
        $goods[$num_iid]['iid']      = $item['iid'];

        if ($item['skus']['sku']) {
            foreach ($item['skus']['sku'] as $key => $value) {

                $product_attr = array();

                $goods[$num_iid]['skus'][$value['sku_id']]['sku_id'] = $value['sku_id'];
                $goods[$num_iid]['skus'][$value['sku_id']]['outer_id'] = $value['outer_id'];
                $goods[$num_iid]['skus'][$value['sku_id']]['iid'] = $value['iid'];
                if ($value['properties']) {
                    $properties = array_filter(explode(';',$value['properties']));
                    foreach ($properties as $property) {
                        list($label_name,$label_value) = explode(':',$property);

                        $product_attr[] = array('label' => $label_name,'value' => $label_value);
                    }

                    $goods[$num_iid]['skus'][$value['sku_id']]['properties'] = $product_attr;
                } else {
                    $goods[$num_iid]['skus'][$value['sku_id']]['properties'] = '';
                }
            }
        }

        return $goods[$num_iid];
    }

}
