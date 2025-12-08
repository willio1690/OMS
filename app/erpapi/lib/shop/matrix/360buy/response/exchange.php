<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * sunjing@shopex.cn
 * 天猫换货
 *
 */
class erpapi_shop_matrix_360buy_response_exchange extends erpapi_shop_response_exchange {

    protected function _formatAddParams($params) {
        // 不支持换货
        return array ();

        $sdf = parent::_formatAddParams($params);
        $tmallSdf = array(
            // 'oid'                   =>  $sdf['tid'],
            'return_bn'            =>  $sdf['dispute_id'],
            'status'               =>  $sdf['status'],
            'reason'               =>  $sdf['reason'],
            'comment'              =>  $sdf['desc'],
            'modified'             =>  $sdf['modified'] ? kernel::single('ome_func')->date2time($sdf['modified']) : '',
            'created'              =>  $sdf['createtime'] ? kernel::single('ome_func')->date2time($sdf['createtime']) : time(),
            'refund_phase'         =>  $sdf['refund_phase'],
            'advance_status'       =>  $sdf['advance_status'],
            'cs_status'            =>  $sdf['cs_status'],
            'good_status'          =>  $sdf['good_status'],
            'alipay_no'            =>  $sdf['alipay_no'],
            'buyer_nick'           =>  $sdf['buyer_nick'],
            'desc'                 =>  $sdf['desc'],
            'logistics_no'         =>  $sdf['buyer_logistic_no'] ? $sdf['buyer_logistic_no'] : '',
            'buyer_address'        =>  $sdf['buyer_address'] ? $sdf['buyer_address'] : '',
            'logistics_company'    =>  $sdf['buyer_logistic_name'] ? $sdf['buyer_logistic_name'] : '',
            'buyer_phone'          =>  $sdf['buyer_phone'] ? $sdf['buyer_phone'] : '',
            'seller_address'       =>  $sdf['address'] ? $sdf['address'] : '',
            'seller_logistic_no'   =>  $sdf['seller_logistic_no'] ? $sdf['seller_logistic_no'] : '',
            'seller_logistic_name' =>  $sdf['seller_logistic_name'] ? $sdf['seller_logistic_name'] : '',
            'bought_bn'            =>  $sdf['bought_bn'],
            'title'                =>  $sdf['title'],
            'num'                  =>  $sdf['num'],
            'price'                =>  $sdf['price'],
            'exchange_bn'          =>  $sdf['exchange_bn'],
            'time_out'             =>  $sdf['time_out'],
            'operation_contraint'  =>  $sdf['operation_contraint'],
            'refund_version'       =>  $sdf['refund_version'],
            'shop_type'            =>  $this->__channelObj->channel['node_type'],
            'shop_id'              =>  $this->__channelObj->channel['shop_id'],
            'pick_address'         => $sdf['pick_ware_address'],
            'return_address'       => $sdf['return_ware_address'],
            'customer_info'        => $sdf['customer_info'],
            'apply_detail'         => $sdf['apply_detail_list'],
        );

        $orders_detail = $this->getOrderByoid($this->__channelObj->channel['shop_id'],$sdf);
        if (!$orders_detail){
            $this->__apilog['result']['msg'] = '订单不存在';
            return array();
        }
        $tmallSdf['order'] = array(
            'order_id'       => $orders_detail['order_id'],
            'status'         => $orders_detail['status'],
            'process_status' => $orders_detail['process_status'],
            'ship_status'    => $orders_detail['ship_status'],
            'pay_status'     => $orders_detail['pay_status'],
            'order_bn'       => $orders_detail['order_bn'],
        );

        $return_items = array();
        foreach($orders_detail['item_list'] as $o_v){
            $price = round($o_v['divide_order_fee']/$o_v['nums'],2);
            $radio = $sdf['num']/$o_v['quantity'];
            $return_items[] =array(
                'bn'        =>  $o_v['item_bn'],
                'name'      =>  $o_v['name'],
                'product_id'=>  $o_v['product_id'],
                'num'       =>  $o_v['obj_type'] == 'pkg' ? (int)($radio * $o_v['nums']) : $sdf['num'],
                'price'     =>  $price,//换货目前价格就为0
                'sendNum'   =>  $o_v['sendnum'],

            );
        }

        $change_items = array();
        if ($orders_detail['item_list'][0]['bn']) {
            $change_items[] = array(
                'bn'    =>  $orders_detail['item_list'][0]['bn'],
                'num'   =>  1,
                'price' =>  bcdiv($orders_detail['item_list'][0]['sale_price'], $orders_detail['item_list'][0]['quantity'])
            );
        }

        // $change_items = array();
        // if ($params['exchange_bn']){
        //     $change_items[] = array(
        //         'bn'    =>  $params['exchange_bn'],
        //         'num'   =>  $params['num'],
        //         'price' =>  floatval($params['price']),
        //     );
        // }

        $tmallSdf['change_items'] = $change_items;
        $tmallSdf['return_items'] = $return_items;
        return $tmallSdf;
    }

    protected function _returnProductAdditional($sdf) {

        $ret = array(
            'model' => 'return_product_360buy',
            'data' => array(
                'shop_id'                 =>  $sdf['shop_id'],
                'pick_address'            =>  $sdf['pick_address'],
                'return_address'          =>  $sdf['return_address'],
                'customer_info'           =>  $sdf['customer_info'],
                'apply_detail'            =>  $sdf['apply_detail'],
                // 'alipay_no'            =>  $sdf['alipay_no'],
                // 'buyer_nick'           =>  $sdf['buyer_nick'],
                // 'buyer_logistic_no'    =>  $sdf['logistics_no'],
                // 'buyer_address'        =>  $sdf['buyer_address'],
                // 'buyer_logistic_name'  =>  $sdf['logistics_company'],
                // 'buyer_phone'          =>  $sdf['buyer_phone'],
                // 'seller_address'       =>  $sdf['address'],
                // 'seller_logistic_no'   =>  $sdf['seller_logistic_no'],
                // 'seller_logistic_name' =>  $sdf['seller_logistic_name'],
                'exchange_sku'            =>  $sdf['exchange_bn'],
                'refund_version'          =>  $sdf['refund_version'],
                'refund_type'             =>  'change',
                'exchange_num'            =>  $sdf['num'],
                'exchange_price'          =>  floatval($sdf['price']),
                'oid'                     =>  $sdf['oid'],
            )
        );
        return $ret;
    }




    /**
     * 获取OrderByoid
     * @param mixed $shop_id ID
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */

    public function getOrderByoid($shop_id, $sdf){
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->db->selectrow("SELECT o.order_bn,o.order_id,o.status,o.process_status,o.ship_status,o.pay_status FROM sdb_ome_orders as o  WHERE  o.order_bn='".$sdf['tid']."' AND o.shop_id='".$shop_id."' ");
        if (!$order){
            return false;
        }

        $items_list = $orderModel->db->select("SELECT ob.bn,ob.obj_type,ob.obj_id,ob.quantity,ob.sale_price,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee FROM sdb_ome_order_objects as ob  left join sdb_ome_order_items as i on ob.obj_id=i.obj_id  WHERE ob.order_id=".$order['order_id']." AND ob.oid='".$sdf['oid']."'  AND i.delete='false'");
        if(!$items_list){
            return false;
        }

        $order['item_list'] = $items_list;

        return $order;


    }
}
