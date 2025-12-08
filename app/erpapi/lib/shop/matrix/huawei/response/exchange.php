<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 华为商城换货业务
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_shop_matrix_huawei_response_exchange extends erpapi_shop_response_exchange
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
                'return_bn' => $sdf['dispute_id'], //换货申请单号
                'status' => $sdf['status'], //换货单状态
                'reason' => $sdf['reason'], //售后原因
                'modified' => $sdf['modified'] ? kernel::single('ome_func')->date2time($sdf['modified']) : '',
                'created' => $sdf['createtime'] ? kernel::single('ome_func')->date2time($sdf['createtime']) : time(),
                //'refund_phase' => $sdf['refund_phase'],
                //'advance_status' => $sdf['advance_status'],
                //'cs_status' => $sdf['cs_status'],
                //'good_status' => $sdf['good_status'],
                //'alipay_no' => $sdf['alipay_no'],
                //'comment' => $sdf['desc'],
                //'desc' => $sdf['desc'],
                'buyer_nick' => $sdf['buyer_nick'], //买家昵称
                'logistics_no' => $sdf['buyer_logistic_no'] ? $sdf['buyer_logistic_no'] : '', //买家发货的物流单号
                'buyer_address' => $sdf['buyer_address'] ? $sdf['buyer_address'] : '', //买家换货地址
                'logistics_company' => $sdf['buyer_logistic_name'] ? $sdf['buyer_logistic_name'] : '', //买家发货的快递公司
                'buyer_phone' => $sdf['buyer_phone'] ? $sdf['buyer_phone'] : '', //买家联系方式
                'seller_address' => $sdf['address'] ? $sdf['address'] : '', //卖家换货地址
                'seller_logistic_no' => $sdf['seller_logistic_no'] ? $sdf['seller_logistic_no'] : '', //卖家发货快递单号
                'seller_logistic_name' => $sdf['seller_logistic_name'] ? $sdf['seller_logistic_name'] : '', //卖家发货物流公司
                'bought_bn' => $sdf['bought_bn'], //购买商品的货号
                'title' => $sdf['title'], //退货商品名称
                'num' => $sdf['num'], //退货商品数量
                'price' => $sdf['price'], //退货单价(单件商品金额)
                'exchange_bn' => $sdf['exchange_bn'], //换货货号
                'time_out' => $sdf['time_out'], //超时时间
                'operation_contraint' => $sdf['operation_contraint'], //操作场景
                'refund_version' => $sdf['refund_version'], //换货版本
                'apply_remark' => $sdf['remark'] ? $sdf['remark'] : '', //买家申请售后备注
                'shop_type' => $this->__channelObj->channel['node_type'],
                'shop_id' => $this->__channelObj->channel['shop_id'],
                'kinds'  => 'change',
        );
        $formatSdf = parent::_formatAddParams($params);
        $data = array_merge($formatSdf, $data);
        
        $error_msg = '';
        $orders_detail = $this->getOrderByoid($this->__channelObj->channel['shop_id'], $sdf, $error_msg);
        if (!$orders_detail){
            $this->__apilog['result']['msg'] = $error_msg;
            return array();
        }
        
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
        $oidItems = array();
        foreach($orders_detail['item_list'] as $o_v)
        {
            $oid = $o_v['oid'];
            
            //退换货数量
            $sdf['num'] = ($sdf['num'] ? $sdf['num'] : $o_v['quantity']);
            
            $price = round($o_v['divide_order_fee'] / $o_v['nums'],2);
            $radio = $sdf['num'] / $o_v['quantity'];
            
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
            
            //oid
            $oidItems[$oid] = $o_v;
        }
        
        //平台换货信息
        $change_items = array();
        $exchange_bn = $params['exchange_bn'];
        $exchange_sku = $params['exchange_sku'];
        if(!empty($exchange_bn)){
            $change_items[] = array(
                'bn' => $exchange_bn, //换货货号
                'num' => $params['num'], //换货数量
                'price' => floatval($params['price']), //退货单价(单件商品金额)
            );
        }elseif($exchange_sku && $oidItems[$exchange_sku]){
            //华为平台换货商品 与 购买商品oid一致
            $change_items[] = array(
                'bn' => $oidItems[$exchange_sku]['bn'], //换货货号
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
        $sql = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status FROM sdb_ome_orders WHERE order_bn='". $tid ."' AND shop_id='". $shop_id ."'";
        $orderInfo = $orderModel->db->selectrow($sql);
        if(empty($orderInfo)){
            //[兼容]归档订单信息
            $sql = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status FROM sdb_archive_orders WHERE order_bn='". $tid ."' AND shop_id='". $shop_id ."'";
            $orderInfo = $orderModel->db->selectrow($sql);
            
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
            $sql = "SELECT ob.oid,ob.bn,ob.obj_type,ob.obj_id,ob.quantity,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id ";
            $sql .= "FROM sdb_archive_order_objects AS ob LEFT JOIN sdb_archive_order_items AS i on ob.obj_id=i.obj_id ";
            $sql .= "WHERE ob.order_id=". $order_id ." AND ob.oid='". $oid ."'";
            
            if($bought_bn){
                $sql .= " AND ob.bn='". $bought_bn ."'";
            }
            
            $sql .= " AND ob.delete='false'";
            
            //归档标识
            $orderInfo['tran_type'] = 'archive';
        }else{
            $sql = "SELECT ob.oid,ob.bn,ob.obj_type,ob.obj_id,ob.quantity,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id ";
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
     * 
     * @param array $sdf
     * @return array
     */
    protected function _returnProductAdditional($sdf)
    {
        $refund_fee = ($sdf['refund_fee'] ? $sdf['refund_fee'] : $sdf['price']);
        
        $ret = array(
                'model' => 'return_product_huawei',
                'data' => array(
                    'shop_id'       => $sdf['shop_id'],
                    'oid'           => $sdf['oid'],
                    'refund_type'   => 'change', //换货
                    'bill_type'     => 'return_bill', //退货单
                    'refund_fee'    => $refund_fee,
                    'exchange_num'  => $sdf['num'],
                    'exchange_sku'  =>  $sdf['exchange_bn'],
                    'exchange_price'=>  floatval($sdf['price']),
                ),
        );
        
        return $ret;
    } 
}
