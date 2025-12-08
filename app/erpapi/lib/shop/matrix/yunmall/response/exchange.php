<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_yunmall_response_exchange extends erpapi_shop_response_exchange {

    protected function _formatAddParams($params) {
        $sdf = $params;
        $tmallSdf = array(
            'tid'                   =>  $sdf['tid'],
            'oid'                   =>  $sdf['oid'],
            'return_bn'             =>  $sdf['dispute_id'],
            'created'               =>  $sdf['createtime'] ? kernel::single('ome_func')->date2time($sdf['createtime']) : time(),
            'modified'              =>  $sdf['modified'] ? kernel::single('ome_func')->date2time($sdf['modified']) : '',
            'buyer_nick'            =>  $sdf['buyer_nick'],
            'status'                =>  $sdf['status'],
            'reason'                =>  $sdf['reason'],
            'logistics_no'          =>  $sdf['buyer_logistic_no'] ? $sdf['buyer_logistic_no'] : '',
            'buyer_address'         =>  $sdf['buyer_address'] ? $sdf['buyer_address'] : '',
            'logistics_company'      => $sdf['buyer_logistic_name'] ? $sdf['buyer_logistic_name'] : '',
            'buyer_phone'           =>  $sdf['buyer_phone'] ? $sdf['buyer_phone'] : '',
            'seller_address'        =>  $sdf['address'] ? $sdf['address'] : '',
            'bought_bn'             =>  $sdf['bought_bn'],
            'title'                 =>  $sdf['title'],
            'num'                   =>  $sdf['num'],
            'price'                 =>  $sdf['price'],
            'exchange_bn'           =>  $sdf['exchange_bn'],
            'time_out'              =>  $sdf['time_out'],
            'buyer_uid'             =>  $sdf['buyer_uid'],
            'bizType'               => $sdf['bizType'],
            'apply_remark'          => $sdf['remark'] ? $sdf['remark'] : '',
            'shop_type'             =>  $this->__channelObj->channel['shop_type'],
            'shop_id'               =>  $this->__channelObj->channel['shop_id'],
        );

        $formatSdf = parent::_formatAddParams($params);
        $tmallSdf = array_merge($formatSdf, $tmallSdf);
        $orders_detail = $this->getOrderByoid($this->__channelObj->channel['shop_id'],$sdf);
        if (!$orders_detail){
            $this->__apilog['result']['msg'] = '订单不存在';
            return array();
        }
        $tmallSdf['order'] = array(//o.order_id,o.status,o.process_status,o.ship_status,o.pay_status
            'order_id'  =>  $orders_detail['order_id'],
            'status'    =>  $orders_detail['status'],
            'process_status'    =>  $orders_detail['process_status'],
            'ship_status'   =>$orders_detail['ship_status'],
            'pay_status'    =>  $orders_detail['pay_status'],
            'order_bn'      =>  $orders_detail['order_bn'],
        );
        if ($orders_detail['tran_type'] == 'archive'){
            $tmallSdf['order']['tran_type'] = 'archive';
        }
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
                'order_item_id'=>$o_v['item_id'],
            );
        }

        $change_items = array();


        if ($params['exchange_bn']){
            $change_items[] = array(
                'bn'    =>  $params['exchange_bn'],
                'num'   =>  $params['num'],
                'price' =>  floatval($params['price']),
            );
        }
        $tmallSdf['change_items'] = $change_items;
        $tmallSdf['return_items'] = $return_items;
        return $tmallSdf;
    }

    protected function _returnProductAdditional($sdf) {
        $ret = array(
            'model' => 'return_apply_special',
            'data' => array(
                'special' => json_encode(array(
                    'shop_id'               =>  $sdf['shop_id'],
                    'buyer_nick'            =>  $sdf['buyer_nick'],
                    'buyer_logistic_no'     =>  $sdf['logistics_no'],
                    'buyer_logistic_name'   =>  $sdf['logistics_company'],
                    'exchange_sku'          =>  $sdf['exchange_bn'],
                    'refund_type'           =>  'change',
                    'exchange_num'          =>  $sdf['num'],
                    'exchange_price'        =>  floatval($sdf['price']),
                    'oid'                   =>  $sdf['oid'],
                    'buyer_uid'             => $sdf['buyer_uid'],
                    'biz_type'             => $sdf['bizType'],
                ), JSON_UNESCAPED_UNICODE)
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
        $tid = $sdf['tid'];
        $oid = $sdf['oid'];
        $orderModel = app::get('ome')->model('orders');
        $orders = $orderModel->db->selectrow("SELECT o.order_bn,o.order_id,o.status,o.process_status,o.ship_status,o.pay_status FROM sdb_ome_orders as o  WHERE  o.order_bn='".$tid."' AND o.shop_id='".$shop_id."' ");

        if (!$orders){
            return false;
        }
        $items_list =   $orderModel->db->select("SELECT ob.bn,ob.obj_type,ob.obj_id,ob.quantity,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id FROM sdb_ome_order_objects as ob  left join sdb_ome_order_items as i on ob.obj_id=i.obj_id  WHERE ob.order_id=".$orders['order_id']." AND ob.oid=".$oid." AND i.delete='false'");
        if(!$items_list){
            return false;
        }
        $orders['item_list'] = $items_list;

        return $orders;


    }
}
