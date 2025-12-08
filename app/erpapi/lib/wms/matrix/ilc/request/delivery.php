<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_ilc_request_delivery extends erpapi_wms_request_delivery
{
    protected function _format_delivery_create_params($sdf)
    {
        $params = parent::_format_delivery_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['delivery_items']){
            sort($sdf['delivery_items']);
            foreach ((array) $sdf['delivery_items'] as $k => $v){
                $barcode = kernel::single('material_codebase')->getBarcodeBybn($v['bn']);
                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'       => $barcode,
                    'item_name'       => $v['product_name'],
                    'item_quantity'   => (int)$v['number'],
                    'item_price'      => (float)$v['price'],
                    'item_line_num'   => ($k + 1),// 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'      => $sdf['order_bn'],//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'         => $v['bn'],// 外部系统商品sku
                    'is_gift'         => $v['is_gift'] == 'ture' ? '1' : '0',// 是否赠品
                    'item_remark'     => $v['memo'],// TODO: 商品备注
                    'inventory_type'  => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => (float)$v['sale_price']//成交额
                );
            }
        }

        $params['items']  = json_encode($items);
        $params['receiver_name'] = $sdf['consignee']['name'] ? $sdf['consignee']['name'] : '服务站';
        $params['receiver_zip'] = '200000';
        $params['receiver_country'] = '中国';
        $params['shipping_type'] = $params['logistics_code'];
        
        $order_type_arr = array('normal'=>'发货订单','360buy'=>'京东订单');
        $order_type = $params['shop_code'];
        $params['total_trade_fee'] = $sdf['is_cod']=='true' ? $params['cod_fee']:$params['total_trade_fee'];
        if (!$order_type){

            $order_type = $order_type_arr[$sdf['shop_type']] ? $order_type_arr[$sdf['shop_type']] : $order_type_arr['normal'];
        }

        $params['order_type'] = $order_type;
        $logistics_no = $sdf['logi_no'];
        // 京东订单提供运单号
        $params['logistics_no'] = $logistics_no;
        if ($params['logistics_no'] === false) {
            return array();
        }
        
        return $params;
    }


    protected function _format_delivery_search_params($sdf)
    {
        $params = array(
            'out_order_code'=>$sdf['delivery_bn'],    
        );
        return $params;
    }

    protected function _format_delivery_cancel_params($sdf)
    {
        $params = array(
            'warehouse_code' => $sdf['branch_bn'],
            'out_order_code' => $sdf['outer_delivery_bn'],
            'uniqid'         => self::uniqid(),
        );
        return $params;
    }
}