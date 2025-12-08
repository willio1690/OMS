<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 补发订单业务Lib方法
 *
 * @author wangbiao@shopex.cn
 * @version 2024.03.20
 */
class ome_order_bufa
{
    /**
     * 延迟创建补发赠品订单
     *
     * @param array $orderIds 订单ID
     * @return array
     */
    public function createOrder($params)
    {
        $logMdl = app::get('ome')->model('operation_log');
        
        //opinfo
        $opinfo = kernel::single('ome_func')->getDesktopUser();
        
        //get
        $order_id = $params['obj_id'];
        
        //exec
        $result = $this->createBufaOrder($params);
        if($result['rsp'] == 'succ'){
            //log原订单记录日志
            $msg = 'CRM补发赠品订单号：'. $result['order_bn'] .'创建成功';
            $logMdl->write_log('order_preprocess@ome', $order_id, $msg, time(), $opinfo);
        }elseif($result['is_create_order'] == 'true'){
            //log原订单记录日志
            $msg = 'CRM补发赠品订单创建成功，复制敏感信息失败：'. $result['error_msg'];
            $logMdl->write_log('order_preprocess@ome', $order_id, $msg, time(), $opinfo);
        }else{
            //log原订单记录日志
            $msg = 'CRM补发赠品订单创建失败：'. $result['error_msg'];
            $logMdl->write_log('order_preprocess@ome', $order_id, $msg, time(), $opinfo);
        }
        
        return $result;
    }
    
    /**
     * 创建补发赠品订单
     *
     * @param array $orderIds 订单ID
     * @return array
     */
    public function createBufaOrder($params)
    {
        $orderMdl = app::get('ome')->model('orders');
        $salesMdl = app::get('material')->model('sales_material');
        
        $orderLib = kernel::single('ome_order');
        $salesMLib = kernel::single('material_sales_material');
        
        //setting
        $result = array('rsp'=>'fail', 'error_msg'=>'');
        $error_msg = '';
        
        //get
        $order_id = $params['obj_id'];
        $extendInfo = json_decode($params['extend_info'], true);
        $giftSalesMaterial = $extendInfo['giftSalesMaterial'];
        
        //check
        if(empty($order_id) || empty($giftSalesMaterial)){
            $error_msg = '任务参数无效';
            $result['error_msg'] = $error_msg;
            return $result;
        }
        
        //订单信息
        $ordersdf = $orderMdl->db_dump(array('order_id'=>$order_id), '*');
        if(empty($ordersdf)){
            $error_msg = '订单信息不存在';
            $result['error_msg'] = $error_msg;
            return $result;
        }
        
        $order_bn = $ordersdf['order_bn'];
        
        //check是否已经退货
        if(in_array($ordersdf['ship_status'], array('4'))){
            $error_msg = '订单已经退货，不能赠送赠品';
            $result['error_msg'] = $error_msg;
            return $result;
        }
        
        //check是否已经全额退款
        if(in_array($ordersdf['pay_status'], array('5'))){
            $error_msg = '订单已经全额退款，不能赠送赠品';
            $result['error_msg'] = $error_msg;
            return $result;
        }
        
        //check是否有退货申请单
        $sql = "SELECT return_id,return_bn FROM sdb_ome_return_product WHERE order_id=". $order_id ." AND status NOT IN('5','9','10')";
        $returnInfo = $orderMdl->db->selectrow($sql);
        if($returnInfo){
            $error_msg = '订单有退货申请单：'. $returnInfo['return_bn'] .'，不能赠送赠品';
            $result['error_msg'] = $error_msg;
            return $result;
        }
        
        //检查是否已经创建过补发订单
        $checkOrder = $orderMdl->db_dump(array('relate_order_bn'=>$order_bn, 'order_type'=>'bufa'), 'order_id,order_bn');
        if($checkOrder){
            $error_msg = '已经有关联补发订单：'. $checkOrder['order_bn'] .'不能重复创建';
            $result['error_msg'] = $error_msg;
            return $result;
        }
        
        //format
        $giftBns = array();
        $goodsList = array();
        foreach ($giftSalesMaterial as $key => $val)
        {
            $sales_material_bn = $val['sales_material_bn'];
            
            $giftBns[$sales_material_bn] = $sales_material_bn;
            $goodsList[$sales_material_bn] = $val;
        }
        
        //根据赠品货号,找到对应的销售物料
        $saleMaterList = $salesMdl->getList('sm_id,sales_material_name,sales_material_bn,sales_material_type',array('sales_material_bn'=>$giftBns));
        if(empty($saleMaterList)){
            $error_msg = '销售物料('. implode(',', $giftBns) .')不存在';
            $result['error_msg'] = $error_msg;
            return $result;
        }
        
        //补发订单信息(复制原平台订单信息)
        $buOrderSdf = $ordersdf;
        
        //订单号
        $bufa_order_bn = $orderMdl->gen_id('bufa');
        $buOrderSdf['order_bn'] = $bufa_order_bn;
        unset($buOrderSdf['order_id'], $buOrderSdf['archive'], $buOrderSdf['splited_num'], $buOrderSdf['itemnum'], $buOrderSdf['discount'], $buOrderSdf['pmt_goods'], $buOrderSdf['pmt_order']);
        unset($buOrderSdf['is_tax'], $buOrderSdf['cost_tax'], $buOrderSdf['tax_company'], $buOrderSdf['is_protect'], $buOrderSdf['refund_money'], $buOrderSdf['print_finish']);
        unset($buOrderSdf['up_time'], $buOrderSdf['download_time'], $buOrderSdf['last_modified'], $buOrderSdf['outer_lastmodify'], $buOrderSdf['logi_id'], $buOrderSdf['logi_no']);
        unset($buOrderSdf['group_id'], $buOrderSdf['op_id'], $buOrderSdf['sync']);
        
        //重置订单信息
        $buOrderSdf['order_type'] = 'bufa'; //补发订单类型
        $buOrderSdf['source'] = 'local'; //订单来源
        $buOrderSdf['createway'] = 'local'; //订单生成类型
        $buOrderSdf['relate_order_bn'] = $order_bn; //关联订单号
        
        $buOrderSdf['confirm'] = 'N';
        $buOrderSdf['process_status'] = 'unconfirmed';
        $buOrderSdf['status'] = 'active';
        $buOrderSdf['pay_status'] = '1';
        $buOrderSdf['ship_status'] = '0';
        
        $buOrderSdf['pay_bn'] = $bufa_order_bn; //支付单号,直接使用补发订单号
        $buOrderSdf['createtime'] = time();
        $buOrderSdf['paytime'] = time();
        $buOrderSdf['modifytime'] = time();
        $buOrderSdf['is_modify'] = 'false';
        $buOrderSdf['logi_no'] = '';
        $buOrderSdf['source_status'] = '';
        $buOrderSdf['payed'] = 0.00; //已付金额
        $buOrderSdf['total_amount'] = 0.00; //订单总额
        $buOrderSdf['final_amount'] = 0.00; //订单换算汇率后总额
        $buOrderSdf['cost_item'] = 0.00; //商品金额
        $buOrderSdf['custom_mark'] = ''; //客户备注
        $buOrderSdf['mark_text'] = ''; //商家备注
        
        //收货人信息
        $buOrderSdf['consignee'] = array(
            'name' => $ordersdf['ship_name'],
            'addr' =>$ordersdf['ship_addr'],
            'zip' => $ordersdf['ship_zip'],
            'telephone' => $ordersdf['ship_tel'],
            'mobile' => $ordersdf['ship_mobile'],
            'email' => $ordersdf['ship_email'],
            'area' => $ordersdf['ship_area'],
        );
        
        //快递信息
        $buOrderSdf['shipping'] = array(
            'shipping_id' => 0,
            'is_cod' => 'false',
            'shipping_name' => '快递',
            'cost_shipping' => 0,
            'is_protect' => '',
            'cost_protect' => 0,
        );
        
        //订单明细
        $order_objects = array();
        foreach($saleMaterList as $goodsInfo)
        {
            $obj_type = 'gift';
            $obj_alias = '赠品';
            $item_type = 'gift';
            $sales_material_bn = $goodsInfo['sales_material_bn'];
            
            //CRM赠品赠送的数量
            $quantity = $goodsList[$sales_material_bn]['quantity'];
            
            //obj
            $orderObjs = array(
                'obj_type' => $obj_type,
                'obj_alias' => $obj_alias,
                'shop_goods_id' => '-1', //CRM赠品类型标识
                'goods_id' => $goodsInfo['sm_id'],
                'bn' => $goodsInfo['sales_material_bn'],
                'name' => $goodsInfo['sales_material_name'],
                'price' => 0.00,
                'sale_price' => 0.00,
                'pmt_price' => 0.00,
                'amount' => 0.00,
                'quantity' => $quantity,
            );
            
            //获取基础物料列表
            $order_items = array();
            $basicMInfos = $salesMLib->getBasicMBySalesMId($goodsInfo['sm_id']);
            foreach($basicMInfos as $basiKey => $basicMInfo)
            {
                $items = array(
                    'product_id' => $basicMInfo['bm_id'],
                    'shop_goods_id' => '-1', //CRM赠品类型标识
                    'shop_product_id' => '-1', //CRM赠品类型标识
                    'bn' => $basicMInfo['material_bn'],
                    'name' => $basicMInfo['material_name'],
                    'cost' => 0.00,
                    'price' => 0.00,
                    'amount' => 0.00,
                    'sale_price'=> 0.00,
                    'pmt_price' => 0.00,
                    'quantity' => $quantity * $basicMInfo['number'], //CRM返回的赠品数量
                    'sendnum' => 0,
                    'item_type' => $item_type,
                );
                
                $order_items[] = $items;
            }
            
            $orderObjs['order_items'] = $order_items;
            
            //merge
            $order_objects[] = $orderObjs;
        }
        
        $buOrderSdf['order_objects'] = $order_objects;
        
        //创建补发类型的订单
        $orderResult = $orderMdl->create_order($buOrderSdf, $error_msg);
        if(!$orderResult){
            $error_msg = '创建补发赠品订单号失败：'. $error_msg;
            $result['error_msg'] = $error_msg;
            return $result;
        }
        
        //更新为已支付(0元订单创建时是未支付)
        $orderMdl->update(array('pay_status'=>'1'), array('order_id'=>$buOrderSdf['order_id']));
        
        //复制原订单收件人敏感数据
        $bufaResult = $orderLib->createBufaOrderEncrypt($buOrderSdf, $error_msg);
        if(!$bufaResult && $error_msg){
            $error_msg = '复制原平台订单收件人敏感数据失败：'. $error_msg;
            $result['error_msg'] = $error_msg;
            $result['is_create_order'] = 'true';
            
            return $result;
        }
        
        $result['rsp'] = 'succ';
        $result['order_bn'] = $buOrderSdf['order_bn'];
        return $result;
    }
}