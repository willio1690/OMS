<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 抖音换货业务
 */
class erpapi_shop_matrix_luban_response_exchange extends erpapi_shop_response_exchange
{
    /**
     * 格式化数据
     * 
     * @param array $params
     * @param array
     */

    protected function _formatAddParams($params)
    {
        //format
        $sdf = $params;
        
        //data
        $data = array(
                'tid' => $sdf['tid'], //订单号
                'oid' => $sdf['oid'], //子单号
                'return_bn'             => $sdf['dispute_id'],
                'status'                => $sdf['status'],
                'reason'                => $sdf['reason'],
                'comment'               => $sdf['desc'],
                'modified'              => $sdf['modified'] ? kernel::single('ome_func')->date2time($sdf['modified']) : '',
                'created'               => $sdf['createtime'] ? kernel::single('ome_func')->date2time($sdf['createtime']) : time(),
                'refund_phase'          => $sdf['refund_phase'],
                'advance_status'        => $sdf['advance_status'],
                'cs_status'             => $sdf['cs_status'],
                'good_status'           => $sdf['good_status'],
                'alipay_no'             => $sdf['alipay_no'],
                'buyer_nick'            => $sdf['buyer_nick'],
                'desc'                  => $sdf['desc'],
                'logistics_no'          => $sdf['buyer_logistic_no'] ? $sdf['buyer_logistic_no'] : '',
                //'buyer_address'         => $sdf['buyer_address'] ? $sdf['buyer_address'] : '',
                'logistics_company'     => $sdf['buyer_logistic_name'] ? $sdf['buyer_logistic_name'] : '',
                //'buyer_phone'           => $sdf['buyer_phone'] ? $sdf['buyer_phone'] : '',
                'seller_address'        => $sdf['address'] ? $sdf['address'] : '',
                'seller_logistic_no'    => $sdf['seller_logistic_no'] ? $sdf['seller_logistic_no'] : '',
                'seller_logistic_name'  => $sdf['seller_logistic_name'] ? $sdf['seller_logistic_name'] : '',
                'bought_bn'             => $sdf['bought_bn'], //退货货号
                'title'                 => $sdf['title'],
                'num'                   => $sdf['num'], //换货数量
                'price'                 => $sdf['price'], //退货单价(单件商品金额)
                'exchange_bn'           => $sdf['exchange_bn'], //换货货号
                'time_out'              => $sdf['time_out'],
                'operation_contraint'   => $sdf['operation_contraint'],
                'refund_version'        => $sdf['refund_version'],
                'shop_type'             => $this->__channelObj->channel['node_type'],
                'shop_id'               => $this->__channelObj->channel['shop_id'],
                'apply_remark'          => $sdf['remark'] ? $sdf['remark'] : '',
                'kinds'                 => 'change',
                //'buyer_name' => $sdf['buyer_name'] ? $sdf['buyer_name'] : '', //买家名称
                //'buyer_phone' => $sdf['buyer_phone'] ? $sdf['buyer_phone'] : '', //买家电话
                //'buyer_province' => $sdf['buyer_state'] ? $sdf['buyer_state'] : '', //买家省份
                //'buyer_city' => $sdf['buyer_city'] ? $sdf['buyer_city'] : '', //买家市
                //'buyer_district' => $sdf['buyer_district'] ? $sdf['buyer_district'] : '', //买家区
                //'buyer_town' => $sdf['buyer_town'] ? $sdf['buyer_town'] : '', //买家镇
                'buyer_address' => $sdf['buyer_address'] ? $sdf['buyer_address'] : '', //买家地址
                //'index_field' => $sdf['index_field'] ? $sdf['index_field'] : '', //json加密数据扩展字段
                'platform_status' => intval($sdf['after_sale_status']), //平台售后单状态
                'org_order_bn' => $sdf['tid'],
                'org_oid' => $sdf['oid'],
        );
        
        $formatSdf = parent::_formatAddParams($params);
        $data = array_merge($formatSdf, $data);
        
        //平台订单号去除A字母
        if(substr($data['platform_order_bn'], -1) === 'A') {
            $data['platform_order_bn'] = substr($data['platform_order_bn'], 0, -1);
        }
        
        //平台换货状态信息
        $data['exchange_status'] = array(
                'after_sale_status' => intval($sdf['after_sale_status']), //售后状态
                'refund_status' => intval($sdf['refund_status']), //退款状态
                'after_sale_type' => intval($sdf['after_sale_type']), //售后类型
        );
        
        //order
        $error_msg = '';
        $orders_detail = $this->getOrderByoid($this->__channelObj->channel['shop_id'], $sdf, $error_msg);
        if (!$orders_detail){
            $this->__apilog['result']['msg'] = $error_msg;
            return array();
        }
        
        //[兼容]抖音未给换货人姓名、手机号、详细地址,直接取原订单上信息
        if(empty($data['buyer_name'])){
            $data['buyer_name'] = $orders_detail['ship_name'];
        }
        
        if(empty($data['buyer_phone'])){
            $data['buyer_phone'] = ($orders_detail['ship_mobile'] ? $orders_detail['ship_mobile'] : $orders_detail['ship_tel']);
        }
        
        if(empty($data['buyer_address'])){
            $data['buyer_address'] = $orders_detail['ship_addr'];
        }else{
            $data['buyer_address'] = $data['buyer_address'].'***';
        }
        
        //[格式化]json加密数据扩展字段
        if($data['index_field']){
            $indexFields = json_decode($data['index_field'], true);
            
            //定义订单上加密字段名
            $encryptData = array(
                    'receiver_name_index_origin' => $indexFields['encrypt_post_receiver'],
                    'receiver_mobile_index_origin' => $indexFields['encrypt_post_tel_sec'],
                    'receiver_address_index_origin' => $indexFields['encrypt_post_address'],
            );
            
            //json
            $data['index_field'] = json_encode($encryptData);
        }
        
        //order
        $data['order'] = array(
                'order_id' => $orders_detail['order_id'],
                'status' => $orders_detail['status'],
                'process_status' => $orders_detail['process_status'],
                'ship_status' => $orders_detail['ship_status'],
                'pay_status' => $orders_detail['pay_status'],
                'order_bn' => $orders_detail['order_bn'],
        );
        
        //归档标识
        if ($orders_detail['tran_type'] == 'archive'){
            $data['order']['tran_type'] = 'archive';
        }
        
        //items
        $return_items = array();
        foreach($orders_detail['item_list'] as $o_v)
        {
            //退换货数量
            $sdf['num'] = ($sdf['num'] ? $sdf['num'] : $o_v['quantity']);
            
            $price = round($o_v['divide_order_fee']/$o_v['nums'],2);
            $radio = $sdf['num']/$o_v['quantity'];
            
            $return_num = $sdf['num'];
            if($o_v['obj_type'] == 'pkg'){
                $return_num = (int)($radio * $o_v['nums']);
            }
            
            //items
            $return_items[] =array(
                    'bn' => $o_v['item_bn'],
                    'name' => $o_v['name'],
                    'product_id' => $o_v['product_id'],
                    'num' => $return_num,
                    'price' => $price,
                    'sendNum' => $o_v['sendnum'],
                    'order_item_id' => $o_v['item_id'],
            );
        }
        
        //平台换货信息
        $change_items = array();
        if($params['exchange_bn']){
            $exchange_bn = $params['exchange_bn'];
            
            $change_items[] = array(
                'bn' => $exchange_bn, //换货货号
                'num' => $params['num'], //换货数量
                'price' => floatval($params['price']), //退货单价(单件商品金额)
            );
        }
        
        $data['change_items'] = $change_items;
        $data['return_items'] = $return_items;
        
        return $data;
    }
    
    /**
     * 获取换货明细
     * 
     * @param string $shop_id
     * @param array $params
     * @param array
     */
    public function getOrderByoid($shop_id, $sdf, &$error_msg=null)
    {
        $orderModel = app::get('ome')->model('orders');
        
        //订单号
        $tid = $sdf['tid'];

        //子单号
        $oid = $sdf['oid'];
        
        //平台退货商品货号
        $bought_bn = $sdf['bought_bn'];
        
        //是否归档订单
        $is_archive = false;
        
        //订单信息
        $sql = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status, ship_name,ship_addr,ship_mobile,ship_tel,is_fail FROM sdb_ome_orders WHERE order_bn='". $tid ."' AND shop_id='". $shop_id ."'";
        $orderInfo = $orderModel->db->selectrow($sql);
        //抖音订单号去A查询
        if(empty($orderInfo) && substr($tid, -1) === 'A'){
            $order_bn = substr($tid,0,-1);
            $sql = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status, ship_name,ship_addr,ship_mobile,ship_tel,is_fail FROM sdb_ome_orders WHERE order_bn='". $order_bn ."' AND shop_id='". $shop_id ."'";
            $orderInfo = $orderModel->db->selectrow($sql);
        }
        if($orderInfo['is_fail'] == 'true') {
            $error_msg = '订单为失败订单，不接受';
            return false;
        }
        if(empty($orderInfo)){
            //[兼容]归档订单信息
            $sql = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status, ship_name,ship_addr,ship_mobile,ship_tel FROM sdb_archive_orders WHERE order_bn='". $tid ."' AND shop_id='". $shop_id ."'";
            $orderInfo = $orderModel->db->selectrow($sql);
            if (empty($orderInfo)) {
                $sql = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status, ship_name,ship_addr,ship_mobile,ship_tel FROM sdb_archive_orders WHERE order_bn='". $order_bn ."' AND shop_id='". $shop_id ."'";
                $orderInfo = $orderModel->db->selectrow($sql);
            }
            $is_archive = true;
        }
        
        //check
        if(empty($orderInfo)){
            $error_msg = '订单信息不存在';
            return false;
        }
        
        $order_id = $orderInfo['order_id'];
        
        //换货商品信息
        if($is_archive){
            $sql = "SELECT ob.bn,ob.obj_type,ob.obj_id,ob.quantity,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id ";
            $sql .= "FROM sdb_archive_order_objects AS ob LEFT JOIN sdb_archive_order_items AS i on ob.obj_id=i.obj_id ";
            $sql .= "WHERE ob.order_id=". $order_id ." AND ob.oid='". $oid ."'";
            
            if($bought_bn){
                $sql .= " AND ob.bn='". $bought_bn ."'";
            }
            
            $sql .= " AND ob.delete='false'";
            
            //归档标识
            $orderInfo['tran_type'] = 'archive';
            
        }else{
            $sql = "SELECT ob.bn,ob.obj_type,ob.obj_id,ob.quantity,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id ";
            $sql .= "FROM sdb_ome_order_objects AS ob LEFT JOIN sdb_ome_order_items AS i on ob.obj_id=i.obj_id ";
            $sql .= "WHERE ob.order_id=". $order_id ." AND ob.oid='". $oid ."'";
            
            if($bought_bn){
                $sql .= " AND ob.bn='". $bought_bn ."'";
            }
            
            $sql .= " AND ob.delete='false'";
        }
        
        $items_list = $orderModel->db->select($sql);
        if(empty($items_list)){
            $error_msg = '换货商品明细不存在';
            return false;
        }
        
        $orderInfo['item_list'] = $items_list;
        
        return $orderInfo;
    }
    
    /**
     * 平台扩展信息
     */
    protected function _returnProductAdditional($sdf)
    {
        $refund_fee = ($sdf['refund_fee'] ? $sdf['refund_fee'] : $sdf['price']);
        
        $ret = array(
                'model' => 'return_product_luban',
                'data' => array(
                    'shop_id'       => $sdf['shop_id'],
                    'oid'           => $sdf['oid'],
                    'refund_type'   => 'change', //换货
                    'bill_type'     => 'return_bill', //退货单
                    'refund_fee'    => $refund_fee,
                    'exchange_num'  => $sdf['num'],
                    'exchange_sku'  =>  $sdf['exchange_bn'],
                    'exchange_price'=>  floatval($sdf['price']),
                    'org_oid' => $sdf['org_oid'],
                    'org_order_bn' => $sdf['org_order_bn'],
                ),
        );
        
        return $ret;
    }
    
    /**
     * 判断是否已产生换货后退货订单
     * 
     * @param $sdf
     * @return mixed|void
     */
    protected function _tranChange($sdf)
    {
        $db = kernel::database();
        
        $oid = $sdf['org_oid'];
        $org_order_bn = $sdf['org_order_bn'];
        
        //check
        if(empty($oid) && empty($org_order_bn)){
            return array();
        }
        
        //获取上一次换货单据信息
        $sql = "SELECT r.change_order_id FROM sdb_ome_return_product_luban as t LEFT JOIN sdb_ome_reship as r ON t.return_bn=r.reship_bn ";
        $sql .= " WHERE r.is_check not in('5','9') AND r.return_type='change' AND t.org_oid='". $oid ."' AND t.org_order_bn='". $org_order_bn ."'";
        $reship_detail = $db->select($sql);
        if($reship_detail){
            $change_order_ids = array_column($reship_detail, 'change_order_id');
            
            //获取上一次换货创建的已发货订单信息
            $sql = "SELECT o.order_id as change_order_id FROM sdb_ome_orders as o WHERE o.order_id in (".implode(',', $change_order_ids).") AND o.ship_status in('1')";
            $order_detail = $db->selectrow($sql);
        
            return $order_detail;
        }
        
        return array();
    }
}
