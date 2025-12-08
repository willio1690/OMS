<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * sunjing@shopex.cn
 * 快手换货
 *
 */
class erpapi_shop_matrix_kuaishou_response_exchange extends erpapi_shop_response_exchange {

    protected function _formatAddParams($params)
    {
        $sdf = $params;
       
        $data = array(
            'tid'                  => $sdf['tid'], //订单号
            'oid'                  => $sdf['oid'], //子单号
            'return_bn'            => $sdf['dispute_id'],
            'status'               => $sdf['status'],
            'reason'               => $sdf['reason'],
            'comment'              => $sdf['desc'],
            'modified'             => $sdf['modified'] ? kernel::single('ome_func')->date2time($sdf['modified']) : '',
            'created'              => $sdf['createtime'] ? kernel::single('ome_func')->date2time($sdf['createtime']) : time(),
            'refund_phase'         => $sdf['refund_phase'],
            'advance_status'       => $sdf['advance_status'],
            'cs_status'            => $sdf['cs_status'],
            'good_status'          => $sdf['good_status'],
            'alipay_no'            => $sdf['alipay_no'],
            'buyer_nick'           => $sdf['buyer_nick'],
            'desc'                 => $sdf['desc'],
            'logistics_no'         => $sdf['buyer_logistic_no'] ? $sdf['buyer_logistic_no'] : '',
            'logistics_company'    => $sdf['buyer_logistic_name'] ? $sdf['buyer_logistic_name'] : '',
            'seller_address'       => $sdf['address'] ? $sdf['address'] : '',
            'seller_logistic_no'   => $sdf['seller_logistic_no'] ? $sdf['seller_logistic_no'] : '',
            'seller_logistic_name' => $sdf['seller_logistic_name'] ? $sdf['seller_logistic_name'] : '',
            'bought_bn'            => $sdf['bought_bn'], //退货货号
            'title'                => $sdf['title'],
            'num'                  => $sdf['num'], //换货数量
            'price'                => $sdf['price'], //退货单价(单件商品金额)
            'exchange_bn'          => $sdf['exchange_bn'], //换货货号
            'time_out'             => $sdf['time_out'],
            'operation_contraint'  => $sdf['operation_contraint'],
            'refund_version'       => $sdf['refund_version'],
            'shop_type'            => $this->__channelObj->channel['node_type'],
            'shop_id'              => $this->__channelObj->channel['shop_id'],
            'apply_remark'         => $sdf['remark'] ? $sdf['remark'] : '',
            'kinds'                => 'change',
            'buyer_address'        => $sdf['buyer_address'] ? $sdf['buyer_address'] : '', //买家地址

            'platform_status'      => intval($sdf['after_sale_status']), //平台售后单状态
            'index_field'          => $sdf['index_field'],
            'updateTime'           => $sdf['updateTime'],    
            'org_order_bn'         => $sdf['tid'],
            'org_oid'              => $sdf['oid'],   
        );
        $formatSdf = parent::_formatAddParams($params);
        $data      = array_merge($formatSdf, $data);
        
      
        //order
        $error_msg     = '';
        $orders_detail = $this->getOrderByoid($this->__channelObj->channel['shop_id'], $sdf, $error_msg);
        if (!$orders_detail) {
            $this->__apilog['result']['msg'] = $error_msg;
            return array();
        }
        
        
        //[格式化]json加密数据扩展字段
        if ($data['index_field']) {
            $indexFields = json_decode($data['index_field'], true);
        
            //定义订单上加密字段名
            $encryptData = array(
                'receiver_name_index'           => $indexFields['receiver_name_index'],
                'receiver_name_index_origin'    => $indexFields['receiver_name_index_origin'],
                'receiver_mobile_index'         => $indexFields['receiver_mobile_index'], 
                'receiver_mobile_index_origin'  => $indexFields['receiver_mobile_index_origin'],
                'receiver_address_index'        => $indexFields['receiver_address_index'],
                'receiver_address_index_origin' => $indexFields['receiver_address_index_origin'],
            );
            
            $data['index_field'] = json_encode($encryptData);
            $data['updateTime'] = $indexFields['updateTime'];
        }
        
        //order
        $data['order'] = array(
            'order_id'       => $orders_detail['order_id'],
            'status'         => $orders_detail['status'],
            'process_status' => $orders_detail['process_status'],
            'ship_status'    => $orders_detail['ship_status'],
            'pay_status'     => $orders_detail['pay_status'],
            'order_bn'       => $orders_detail['order_bn'],
        );
        
        //归档标识
        if ($orders_detail['tran_type'] == 'archive') {
            $data['order']['tran_type'] = 'archive';
        }
        
        //items
        $return_items = array();
        foreach ($orders_detail['item_list'] as $o_v) {
            //退换货数量
            $sdf['num'] = ($sdf['num'] ? $sdf['num'] : $o_v['quantity']);
            
            $price = round($o_v['divide_order_fee'] / $o_v['nums'], 2);
            $radio = $sdf['num'] / $o_v['quantity'];
            
            $return_num = $sdf['num'];
            if ($o_v['obj_type'] == 'pkg') {
                $return_num = (int)($radio * $o_v['nums']);
            }
            
            //items
            $return_items[] = array(
                'bn'            => $o_v['item_bn'],
                'name'          => $o_v['name'],
                'product_id'    => $o_v['product_id'],
                'num'           => $return_num,
                'price'         => $price,
                'sendNum'       => $o_v['sendnum'],
                'order_item_id' => $o_v['item_id'],
            );
        }
        
        //平台换货信息
        $change_items = array();
        if ($params['exchange_bn']) {
            $exchange_bn = $params['exchange_bn'];
            
            $change_items[] = array(
                'bn'    => $exchange_bn, //换货货号
                'num'   => $params['num'], //换货数量
                'price' => floatval($params['price']), //退货单价(单件商品金额)
            );
        }
        
        $data['change_items'] = $change_items;
        $data['return_items'] = $return_items;
        
        return $data;
    }

   


    /**
     * 获取OrderByoid
     * @param mixed $shop_id ID
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */

    public function getOrderByoid($shop_id, $sdf){

        $oid = $sdf['oid'];
        $orderModel = app::get('ome')->model('orders');
        $tid = $sdf['tid'];
        $orders = $orderModel->db->selectrow("SELECT o.order_bn,o.order_id,o.status,o.process_status,o.ship_status,o.pay_status FROM sdb_ome_orders as o  WHERE  o.order_bn='".$tid."' AND o.shop_id='".$shop_id."' ");

        if (!$orders){
            return false;
        }
        $order_id = $orders['order_id'];
        
        $items_list =   $orderModel->db->select("SELECT ob.bn,ob.obj_type,ob.obj_id,ob.quantity,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id FROM sdb_ome_order_objects as ob  left join sdb_ome_order_items as i on ob.obj_id=i.obj_id  WHERE ob.order_id=".$orders['order_id']." AND ob.bn='".$sdf['bought_bn']."' AND i.delete='false'");
       
        if(!$items_list){
            return false;
        }
        $orders['item_list'] = $items_list;
        
        return $orders;


    }

    
    protected function _returnProductAdditional($sdf)
    {
        $refund_fee = ($sdf['refund_fee'] ? $sdf['refund_fee'] : $sdf['price']);
        
        $special = [

            'oid'            => $sdf['oid'],
            'refund_type'    => 'change', //换货
            'refund_fee'     => $refund_fee,
            'exchange_num'   => $sdf['num'],
            'exchange_sku'   => $sdf['exchange_bn'],
            'exchange_price' => floatval($sdf['price']),
            'updateTime'    =>  $sdf['updateTime'],
           
        ];
        $ret = array(
            'model' => 'return_apply_special',
            'data' => array(
                'org_oid'       => $sdf['org_oid'],
                'org_order_bn'  =>  $sdf['org_order_bn'],
                'special' => json_encode($special, JSON_UNESCAPED_UNICODE)
            )
        );
        
        return $ret;
    }

    /*
    * 判断是否已产生换货后退货订单
    *
    *
    */
    protected function _tranChange($sdf){
        $order = $sdf['order'];
        $order_id = $order['order_id'];
        $tid = $order['order_bn'];
        $refund_item_list = $sdf['refund_item_list'];
      
        $oid = $sdf['org_oid'];
        $org_order_bn = $sdf['org_order_bn'];
        $db = kernel::database();
        $sql = "SELECT r.change_order_id FROM  sdb_ome_return_apply_special as t LEFT JOIN sdb_ome_reship as r ON t.return_bn=r.reship_bn WHERE  r.is_check not in('5','9') AND r.return_type='change' AND  t.org_oid='".$oid."' AND t.org_order_bn='".$org_order_bn."'";

        $reship_detail = $db->select($sql);
     
        if($reship_detail){
            $change_order_ids = array_column($reship_detail,'change_order_id');

            $sql = "SELECT o.order_id as change_order_id FROM sdb_ome_orders as o  WHERE  o.order_id in (".implode(',',$change_order_ids).") AND o.ship_status in('1')";
            $order_detail = $db->selectrow($sql);

            return $order_detail;
        }

    }


   
}
