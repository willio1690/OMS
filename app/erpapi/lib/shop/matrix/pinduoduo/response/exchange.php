<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2022/29
 * @Describe: 拼多多换货业务
 */
class erpapi_shop_matrix_pinduoduo_response_exchange extends erpapi_shop_response_exchange
{
    
    protected $_change_return_type = true;
    /**
     * 格式化数据
     * @Author: xueding
     * @Vsersion: 2022/12/29 上午10:29
     * @param $params
     * @return array
     */
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
        );
        $formatSdf = parent::_formatAddParams($params);
        $data      = array_merge($formatSdf, $data);
        
        //平台换货状态信息
        $data['exchange_status'] = array(
            'after_sale_status' => intval($sdf['after_sale_status']), //售后状态
            'refund_status'     => intval($sdf['refund_status']), //退款状态
            'after_sale_type'   => intval($sdf['after_sale_type']), //售后类型
        );
        
        //order
        $error_msg     = '';
        $orders_detail = $this->getOrderByoid($this->__channelObj->channel['shop_id'], $sdf, $error_msg);
        if (!$orders_detail) {
            $this->__apilog['result']['msg'] = $error_msg;
            return array();
        }
        
        //未给换货人姓名、手机号、详细地址,直接取原订单上信息
        if (empty($data['buyer_name'])) {
            $data['buyer_name'] = $orders_detail['ship_name'];
        }
        
        if (empty($data['buyer_phone'])) {
            $data['buyer_phone'] = ($orders_detail['ship_mobile'] ? $orders_detail['ship_mobile'] : $orders_detail['ship_tel']);
        }
        
        if (empty($data['buyer_address'])) {
            $data['buyer_address'] = $orders_detail['ship_addr'];
        } else {
            $data['buyer_address'] = $data['buyer_address'] . '***';
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
            
            $price =  $o_v['nums'] ? bcdiv((float)$o_v['divide_order_fee'], (float)$o_v['nums'], 2) : 0;
            $radio = $o_v['quantity'] ? bcdiv((float)$sdf['num'], (float)$o_v['quantity'], 2) : 0;
            
            $return_num = $sdf['num'];
            if ($o_v['obj_type'] == 'pkg') {
                $return_num = bcmul((float)$radio, (float)$o_v['nums']);
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
     * 获取换货明细
     *
     * @param string $shop_id
     * @param array $params
     * @param array
     */
    public function getOrderByoid($shop_id, $sdf, &$error_msg = null)
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
        $sql       = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status, ship_name,ship_addr,ship_mobile,ship_tel FROM sdb_ome_orders WHERE order_bn='" . $tid . "' AND shop_id='" . $shop_id . "'";
        $orderInfo = $orderModel->db->selectrow($sql);
        
        if (empty($orderInfo)) {
            //[兼容]归档订单信息
            $sql        = "SELECT order_id,order_bn,status,process_status,ship_status,pay_status, ship_name,ship_addr,ship_mobile,ship_tel FROM sdb_archive_orders WHERE order_bn='" . $tid . "' AND shop_id='" . $shop_id . "'";
            $orderInfo  = $orderModel->db->selectrow($sql);
            $is_archive = true;
        }
        
        if (empty($orderInfo)) {
            $error_msg = '订单信息不存在';
            return false;
        }
        
        $order_id = $orderInfo['order_id'];
        
        //换货商品信息
        if ($is_archive) {
            $sql = "SELECT ob.bn,ob.obj_type,ob.obj_id,ob.quantity,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id,i.item_type ";
            $sql .= "FROM sdb_archive_order_objects AS ob LEFT JOIN sdb_archive_order_items AS i on ob.obj_id=i.obj_id ";
            $sql .= "WHERE ob.order_id=" . $order_id . " AND ob.oid='" . $oid . "'";
            
            if ($bought_bn) {
                $sql .= " AND ob.bn='" . $bought_bn . "'";
            }
            
            $sql .= " AND ob.delete='false'";
            
            //归档标识
            $orderInfo['tran_type'] = 'archive';
        } else {
            $sql = "SELECT ob.bn,ob.obj_type,ob.obj_id,ob.quantity,i.bn as item_bn,i.name,i.product_id,i.nums,i.sale_price,i.sendnum,i.divide_order_fee,i.item_id,i.item_type ";
            $sql .= "FROM sdb_ome_order_objects AS ob LEFT JOIN sdb_ome_order_items AS i on ob.obj_id=i.obj_id ";
            $sql .= "WHERE ob.order_id=" . $order_id . " AND ob.oid='" . $oid . "'";
            
            if ($bought_bn) {
               // $sql .= " AND ob.bn='" . $bought_bn . "'";
            }
            
            $sql .= " AND ob.delete='false'";
        }
        
        $items_list = $orderModel->db->select($sql);
        if (empty($items_list)) {
            $error_msg = '换货商品明细不存在';
            return false;
        }
        
        $orderInfo['item_list'] = $items_list;
        
        return $orderInfo;
    }
    
    /**
     * 平台扩展信息
     * @Author: xueding
     * @Vsersion: 2022/12/29 上午10:31
     * @param $sdf
     * @return array
     */
    protected function _returnProductAdditional($sdf)
    {
        $refund_fee = ($sdf['refund_fee'] ? $sdf['refund_fee'] : $sdf['price']);
        
        $ret = array(
            'model' => 'return_product_pinduoduo',
            'data'  => array(
                'shop_id'        => $sdf['shop_id'],
                'oid'            => $sdf['oid'],
                'refund_type'    => 'change', //换货
                'bill_type'      => 'return_bill', //退货单
                'refund_fee'     => $refund_fee,
                'exchange_num'   => $sdf['num'],
                'exchange_sku'   => $sdf['exchange_bn'],
                'exchange_price' => floatval($sdf['price']),
            ),
        );
        
        return $ret;
    }
}
