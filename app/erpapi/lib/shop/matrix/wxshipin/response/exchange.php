<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_wxshipin_response_exchange extends erpapi_shop_response_exchange {

     protected function _formatAddParams($params) {
       
        $sdf = parent::_formatAddParams($params);
       
        //[格式化]json加密数据扩展字段
        if($params['index_field']){
            $indexFields = json_decode($params['index_field'], true);
            
            //加判断有值才存否则影响换货生成订单
            if($indexFields['receiver_name_index_origin']){
                $encryptData['receiver_name_index_origin'] = $indexFields['receiver_name_index_origin'];
            }
            if($indexFields['receiver_mobile_index_origin']){
                $encryptData['receiver_mobile_index_origin'] = $indexFields['receiver_mobile_index_origin'];
            }
            if($indexFields['receiver_address_index_origin']){
                $encryptData['receiver_address_index_origin'] = $indexFields['receiver_address_index_origin'];
            }
            if($encryptData){
               
                $sdf['index_field'] = json_encode($encryptData);
            }
            
            
        }
        $sdf = $this->_getReturnExchangeItems($sdf);
        $sdf['shop_type'] =  'wxshipin';
      
        return $sdf;
    }
    
    protected function _getReturnExchangeItems($sdf){
        $orders_detail = $this->getOrderByoid($sdf['shop_id'], $sdf);
      
        if (!$orders_detail || !$orders_detail['item_list']){
            return array();
        }
        
        $sdf['order'] = array(
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
            
            $return_items[] = array(
                'bn'            => $o_v['item_bn'],
                'name'          => $o_v['name'],
                'product_id'    => $o_v['product_id'],
                'num'           => $o_v['obj_type'] == 'pkg' ? (int)($radio * $o_v['nums']) : $sdf['num'],
                'price'         => $price,//换货目前价格就为0
                'sendNum'       => $o_v['sendnum'],
                'order_item_id' => $o_v['order_item_id'],
                'item_type'     => $o_v['item_type'],
            );
        }
        
        $change_items = array();
        
        if ($sdf['exchange_bn']){
            $change_items[] = array(
                'bn'    =>  $sdf['exchange_bn'],
                'num'   =>  $sdf['num'],
                'price' =>  floatval($sdf['price']),
            );
        }
        
        $sdf['change_items'] = $change_items;
        $sdf['return_items'] = $return_items;
        
        return $sdf;
    }
    
    protected function getOrderByoid($shop_id, $sdf){
        $order = app::get('ome')->model('orders')->db_dump(array('order_bn'=>$sdf['tid'],'shop_id'=>$sdf['shop_id']),'order_bn,order_id,status,process_status,ship_status,pay_status');
       
        $orderModel = app::get('ome')->model('orders');
        if(!$order){

            $sql = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status, ship_name,ship_addr,ship_mobile,ship_tel FROM sdb_archive_orders WHERE order_bn='". $sdf['tid'] ."' AND shop_id='". $sdf['shop_id'] ."'";
            $order = $orderModel->db->selectrow($sql);
            
            $is_archive = true;
        }
        if (!$order) return false;
        
        if ($is_archive) {
            

            $object = $orderModel->db->selectrow("SELECT ob.bn,ob.obj_type,ob.obj_id,ob.quantity FROM sdb_archive_order_objects AS ob WHERE ob.order_id=" . $order['order_id'] . " AND ob.bn='" . $sdf['bought_bn'] . "' AND ob.delete='false'");
           
           
            //归档标识
            $order['tran_type'] = 'archive';

            $items = $orderModel->db->select("SELECT i.bn ,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id,i.item_type  FROM sdb_archive_order_items AS i WHERE i.obj_id=".$object['obj_id']."");
            
        } else {

            $objFilter = array('bn' => $sdf['bought_bn'], 'order_id'=>$order['order_id']);
            $objMdl = app::get('ome')->model('order_objects');
            $object = $objMdl->db_dump($objFilter,'order_id,obj_id,bn,obj_type,quantity');


            $itemMdl = app::get('ome')->model('order_items');
            $items = $itemMdl->getList('*',array('obj_id'=>$object['obj_id']));

        }
       
        if (!$items) return false;
        $item_list = array();
        foreach ($items as $value) {
            $item_list[] = array(
                'bn'               => $object['bn'],
                'obj_type'         => $object['obj_type'],
                'obj_id'           => $object['obj_id'],
                'quantity'         => $object['quantity'],
                'item_bn'          => $value['bn'],
                'name'             => $value['name'],
                'product_id'       => $value['product_id'],
                'nums'             => $value['nums'],
                'sale_price'       => $value['sale_price'],
                'sendnum'          => $value['sendnum']-$value['return_num'],
                'divide_order_fee' => $value['divide_order_fee'],
                'order_item_id'    => $value['item_id'],
                'item_type'        => $value['item_type'],
            );
        }
        
        $order['item_list'] = $item_list;
        
        return $order;
    }
    
    /**
     * 判断是否已产生换货后退货订单
     * @Author: xueding
     * @Vsersion: 2023/7/18 下午2:44
     * @param $sdf
     * @return mixed
     */
    protected function _tranChange($sdf)
    {
        $order            = $sdf['order'];
        $order_id         = $order['order_id'];
        
        $db           = kernel::database();
        $sql          = "SELECT change_order_id FROM sdb_ome_reship as r  WHERE  r.is_check not in('5','9') AND r.return_type='change' AND  r.order_id=" . $order_id ;
        
        $reship_detail = $db->select($sql);
        
        if ($reship_detail) {
            $change_order_ids = array_column($reship_detail, 'change_order_id');
            
            $sql          = "SELECT o.order_id as change_order_id FROM sdb_ome_orders as o  WHERE  o.order_id in (" . implode(',',
                    $change_order_ids) . ") AND o.ship_status in('1')";
            $order_detail = $db->selectrow($sql);
            
            return $order_detail;
        }
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
    
}
