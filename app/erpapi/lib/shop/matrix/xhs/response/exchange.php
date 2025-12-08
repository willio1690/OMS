<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_xhs_response_exchange extends erpapi_shop_response_exchange {

    protected $_change_return_type = true;
    protected function _formatAddParams($params) {
        $sdf = parent::_formatAddParams($params);
        $sdf['bought_sku'] = $params['bought_sku'];
        $sdf = $this->_getReturnExchangeItems($sdf);
        $sdf['shop_type'] =  'xhs';
        return $sdf;
    }
    
    protected function _getReturnExchangeItems($sdf){
        $orders_detail = $this->_getOrderDetail($sdf);
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
    
    protected function _getOrderDetail(&$sdf){
        $order = app::get('ome')->model('orders')->db_dump(array('order_bn'=>$sdf['tid'],'shop_id'=>$sdf['shop_id']),'order_bn,order_id,status,process_status,ship_status,pay_status');
        
        if (!$order) return false;
        
        $objFilter = array('bn' => $sdf['bought_bn'], 'order_id'=>$order['order_id']);
        $objMdl = app::get('ome')->model('order_objects');
        $object = $objMdl->db_dump($objFilter,'order_id,obj_id,bn,obj_type,quantity');
        if(!$object) {
            $object = $objMdl->db_dump([
                'oid' => $sdf['bought_sku'], 'order_id'=>$order['order_id']
            ],'order_id,obj_id,bn,obj_type,quantity');
            if($object && $sdf['exchange_bn'] == $sdf['bought_bn']) {
                $sdf['exchange_bn'] = $object['bn'];
            }
        }
        if (!$object) return false;
        
        $item_list = array();
        $itemMdl = app::get('ome')->model('order_items');
        foreach ($itemMdl->getList('*',array('obj_id'=>$object['obj_id'])) as $value) {
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
        $sql          = "SELECT change_order_id FROM sdb_ome_reship as r  WHERE  r.is_check not in('5','9') AND r.return_type='change' AND  r.order_id='" . $order_id . "'";
        
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

    /**
     * 获取OrderByoid
     * @param mixed $shop_id ID
     * @param mixed $sdf sdf
     * @param mixed $error_msg error_msg
     * @return mixed 返回结果
     */
    public function getOrderByoid($shop_id, $sdf, &$error_msg = null)
    {

        return $this->_getOrderDetail($sdf);
    }
    
}
