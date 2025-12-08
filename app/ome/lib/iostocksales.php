<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_iostocksales {

    /**
     * 存储出入库、销售记录
     * @access public
     * @param String $data 出入库、销售记录
     * @param String $msg 消息
     * @return boolean 成功or失败
     */
    public function iostock_set($data,$io,&$msg,$type=null)
    {
        $allow_commit = false;
        $iostock_instance = kernel::single('ome_iostock');
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            // $iostock_data = $data['iostock'];

            $type = $type ? $type : constant('ome_iostock::LIBRARY_SOLD');

            $iostock_bn = $iostock_instance->get_iostock_bn($type);

            if ( $iostock_instance->set($iostock_bn, $data, $type, $iostock_msg, $io) ){
                // $rs = $this->data_to_sale($data, $iostock_data, $iostock_bn, $io);
                // $allow_commit = $rs['allow_commit'];
                // $sales_msg = $rs['msg'];
                return true;
            }
        }

        // if ($allow_commit == true){
        //     return true;
        // }else{
        //     $msg = $iostock_msg ? '出入库错误：' . implode(',', $iostock_msg) : '';
        //     $msg .= $sales_msg ? ($msg ? "\n" : '') . '销售单生成失败：' . implode(',', $sales_msg) : '';
        //     $msg .= $msg ? '' : '出入库或销售单操作失败';
        //     return false;
        // }

        $msg = '出入库失败：' . implode(',', $iostock_msg);

        return false;
    }

    /**
     * 合并发货单按付款金额贡献度分摊物流费
     * 
     * @return void
     * @author 
     * */
    private function _share_delivery_cost($order_id, $delivery)
    {
        $delivery_cost_actual = $delivery['delivery_cost_actual'];

        $dOrderMdl = app::get('ome')->model('delivery_order');
        $delivery_order = $dOrderMdl->getList('*', array ('delivery_id' => $delivery['delivery_id']));

        $filterOrderId = array (0);
        foreach ($delivery_order as $value) {
            $filterOrderId[] = $value['order_id'];
        }

        $orderMdl = app::get('ome')->model('orders');
        $orderList = $orderMdl->getList('order_id,payed', array ('order_id' => $filterOrderId));

        $payedAmount = 0;
        foreach ($orderList as $value) {
            $payedAmount += $value['payed'];
        }

        $cpp = $delivery_cost_actual > 0 ? bcdiv($delivery_cost_actual, $payedAmount, 5) : 0;

        $i = 1; $c = count($orderList);
        foreach ($orderList as $value) {
            if ($i == $c) {
                $costs[$value['order_id']] = $delivery_cost_actual;
            } else {
                $costs[$value['order_id']] = bcmul($value['payed'], $cpp, 2);

                $delivery_cost_actual -= $costs[$value['order_id']];
            }

            $i++;
        }

        return $costs[$order_id];
    }

    //方法已弃用(请不要使用)
        /**
     * sale_set
     * @param mixed $data 数据
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function sale_set($data, &$msg) {
        if (!$data) {
            $msg = '销售单生成失败：缺少销售数据';
            return false;
        }

        $deliveryMdl    = app::get('ome')->model('delivery');
        $dItemDetailMdl = app::get('ome')->model('delivery_items_detail');
        $iostockMdl     = app::get('ome')->model('iostock');

        $sales_instance = kernel::single('ome_sales');
        if ( method_exists($sales_instance, 'set') ) {
            // 按订单生成销售单
            foreach($data as $sale) {
                $sale['delivery_cost_actual'] = 0;

                // 获取订单对应的所有出入库明细
                $deliveryList = $deliveryMdl->getDeliversByOrderId($sale['order_id']);
                if (!$deliveryList) {
                    $msg = '销售单生成失败：订单未生成相关发货单';
                    return false;
                }
                $filterDeliveryId = array (0);

                // 物流成本均摊
                foreach ((array) $deliveryList as $delivery) {
                    if ($delivery['is_bind'] == 'true') {
                        $sale['delivery_cost_actual'] += $this->_share_delivery_cost($sale['order_id'],$delivery);
                    } else {
                        $sale['delivery_cost_actual'] += $delivery['delivery_cost_actual'];
                    }

                    $filterDeliveryId[] = $delivery['delivery_id'];
                }

                // 组织数据
                $iostockList = array ();
                foreach ($iostockMdl->getList('iostock_id,iostock_bn,type_id,original_bn,original_id,original_item_id,bn,unit_cost,inventory_cost', array ('original_id' => $filterDeliveryId, 'type_id' => constant('ome_iostock::LIBRARY_SOLD'))) as $iostock) {
                    $iostockList[$iostock['original_item_id']] = $iostock;
                }

                $dItemDetailList = array ();
                foreach ($dItemDetailMdl->getList('*', array ('delivery_id' => $filterDeliveryId)) as $detail) {
                    $dItemDetailList[$detail['order_id']][$detail['order_obj_id']][$detail['order_item_id']]['inventory_cost'] += $iostockList[$detail['item_detail_id']]['inventory_cost'];
                    $dItemDetailList[$detail['order_id']][$detail['order_obj_id']][$detail['order_item_id']]['iostock_id']     = $iostockList[$detail['item_detail_id']]['iostock_id'];
                    $dItemDetailList[$detail['order_id']][$detail['order_obj_id']][$detail['order_item_id']]['iostock_bn']     = $iostockList[$detail['item_detail_id']]['iostock_bn'];
                }

                // 成本 
                foreach ($sale['sales_items'] as $si_key => $si_value) {
                    if (!$si_value['order_item_id']) {   // 捆绑/礼包
                        $inventory_cost = 0;

                        foreach ($dItemDetailList[$sale['order_id']][$si_value['obj_id']] as $did_value) {
                            $inventory_cost += $did_value['inventory_cost'];

                            $si_value['iostock_id'] = $did_value['iostock_id'];

                            !$sale['iostock_bn'] && $sale['iostock_bn'] = $did_value['iostock_bn'];
                        }

                        $si_value['cost_amount']      = $inventory_cost;
                        $si_value['cost']             = bcdiv($si_value['cost_amount'], $si_value['nums'], 2);
                        $si_value['gross_sales']      = bcsub($si_value['sales_amount'], $si_value['cost_amount'], 2);
                        $si_value['gross_sales_rate'] = bcdiv($si_value['gross_sales'], $si_value['sales_amount'], 4) * 100;

                    } else {    // 普通商品
                        $si_value['iostock_id']       = $dItemDetailList[$sale['order_id']][$si_value['obj_id']][$si_value['order_item_id']]['iostock_id'];
                        $si_value['cost_amount']      = $dItemDetailList[$sale['order_id']][$si_value['obj_id']][$si_value['order_item_id']]['inventory_cost'];
                        $si_value['cost']             = bcdiv($si_value['cost_amount'], $si_value['nums'], 2);
                        $si_value['gross_sales']      = bcsub($si_value['sales_amount'], $si_value['cost_amount'], 2);
                        $si_value['gross_sales_rate'] = bcdiv($si_value['gross_sales'], $si_value['sales_amount'], 4) * 100;

                        !$sale['iostock_bn'] && $sale['iostock_bn'] = $dItemDetailList[$sale['order_id']][$si_value['obj_id']][$si_value['order_item_id']]['iostock_bn'];
                    }

                    $sale['sales_items'][$si_key] = $si_value;
                }

                // 销售单号
                $sale['sale_bn'] = $sales_instance->get_salse_bn();

                if ( !$sales_instance->set($sale, $sales_msg) ){
                    $msg = '销售单生成失败：' . implode(',', $sales_msg);
                    return false;
                }
            }

            return true;
        }

        $msg = '销售单生成SET方法不存在';

        return false;
    }

    /**
     * 存储出入库、销售记录
     * @access public
     * @param String $data 出入库、销售记录
     * @param String $msg 消息
     * @return boolean 成功or失败
     */
    public function set($data,$io,&$msg,$type=null)
    {
        //拆单配置
        $orderSplitLib    = kernel::single('ome_order_split');
        $split_seting     = $orderSplitLib->get_delivery_seting();
	
        $allow_commit = false;
        // kernel::database()->beginTransaction();
        $iostock_instance = kernel::single('ome_iostock');
        $sales_instance = kernel::single('ome_sales');
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            $iostock_data = $data['iostock'];
            if(!$type){
                eval('$type='.get_class($iostock_instance).'::LIBRARY_SOLD;');
            }

            $iostock_bn = $iostock_instance->get_iostock_bn($type);

            if ( $iostock_instance->set($iostock_bn, $iostock_data, $type, $iostock_msg, $io) ){

                if ( method_exists($sales_instance, 'set') )
                {
                    if ($data['sales']['sales_items']){
                        //[拆单]过滤部分拆分OR部分发货时,不存储销售记录
                        $get_order_id       = intval($data['sales']['order_id']);
                        $get_delivery_id    = intval($data['sales']['delivery_id']);
                        
                        if(!empty($split_seting)){
                            if($data['split_type'] && $get_order_id){
                                $allow_commit   = $orderSplitLib->check_order_all_delivery($get_order_id, $get_delivery_id);
                            }
                            
                            //[拆单]获取订单对应所有iostock出入库单
                            $order_delivery_iostock_data    = $orderSplitLib->get_delivery_iostock_data($iostock_data);
                            
                            //多个发货单累加物流成本
                            $delivery_cost_actual           = $orderSplitLib->count_delivery_cost_actual($get_order_id);
                            if($delivery_cost_actual)
                            {
                                $sales_data['delivery_cost_actual']  = $delivery_cost_actual;
                            }
                        }else{
                            //防止_拆单多个发货单后_未发货就关闭“拆单功能”_出现生成多个发货单的错误
                            $allow_commit       = $orderSplitLib->check_order_all_delivery($get_order_id, $get_delivery_id);
                        }
                        
                        if(!$allow_commit)
                        {
                            //存储销售记录
                            $branch_id = '';
                            if ($data['sales']['sales_items']){
                                foreach ($data['sales']['sales_items'] as $k=>$v)
                                {
                                    //[拆单]多个发货单时_iostock_id为NULL重新获取
                                    if(!empty($iostock_data[$v['item_detail_id']]['iostock_id']))
                                    {
                                        $v['iostock_id'] = $iostock_data[$v['item_detail_id']]['iostock_id'];
                                    }else{
                                        $v['iostock_id']   = $order_delivery_iostock_data[$v['item_detail_id']]['iostock_id'];
                                    }
                                    
                                    $data['sales']['sales_items'][$k] = $v;
                                }
                            }
                            $data['sales']['iostock_bn'] = $iostock_bn;
                            $sales_data = $data['sales'];
                            $sale_bn = $sales_instance->get_salse_bn();
                            $sales_data['sale_bn'] = $sale_bn;
                            if ( $sales_instance->set($sales_data, $sales_msg) ){
                                $allow_commit = true;
                            }
                        }
                    } else{
                        foreach($data['sales'] as $k=>$v){
                            //[拆单]过滤部分拆分OR部分发货时,不存储销售记录
                            $get_order_id       = intval($v['order_id']);
                            $get_delivery_id    = intval($v['delivery_id']);
                            
                            if(!empty($split_seting))
                            {
                                if($data['split_type'] && $get_order_id)
                                {
                                    $allow_commit   = $orderSplitLib->check_order_all_delivery($get_order_id, $get_delivery_id);
                                    
                                    if($allow_commit)
                                    {
                                        continue;
                                    }
                                }
                                
                                //获取订单对应所有iostock出入库单
                                $order_delivery_iostock_data    = $orderSplitLib->get_delivery_iostock_data($iostock_data);
                                
                                //多个发货单累加物流成本
                                $delivery_cost_actual           = $orderSplitLib->count_delivery_cost_actual($get_order_id);
                                if($delivery_cost_actual)
                                {
                                    $data['sales'][$k]['delivery_cost_actual']  = $delivery_cost_actual;
                                }
                            }else{
                                //防止_拆单多个发货单后未发货就关闭“拆单功能”_出现生成多个发货单的错误
                                $allow_commit   = $orderSplitLib->check_order_all_delivery($get_order_id, $get_delivery_id);
                                
                                if($allow_commit)
                                {
                                    continue;
                                }
                            }
                            
                            //存储销售记录
                            $branch_id = '';
                            if ($data['sales'][$k]['sales_items']){
                                foreach ($data['sales'][$k]['sales_items'] as $kk=>$vv)
                                {
                                    //[拆单]多个发货单时_iostock_id为NULL重新获取
                                    if(!empty($iostock_data[$vv['item_detail_id']]['iostock_id']))
                                    {
                                        $vv['iostock_id'] = $iostock_data[$vv['item_detail_id']]['iostock_id'];
                                    }else {
                                        $vv['iostock_id']   = $order_delivery_iostock_data[$vv['item_detail_id']]['iostock_id'];
                                    }
                                    
                                    $data['sales'][$k]['sales_items'][$kk] = $vv;
                                }
                            }
                            $data['sales'][$k]['iostock_bn'] = $iostock_bn;
                            $sale_bn = $sales_instance->get_salse_bn();
                            $data['sales'][$k]['sale_bn'] = $sale_bn;
                            if ( $sales_instance->set($data['sales'][$k], $sales_msg) ){
                                $allow_commit = true;
                            }
                        }

                    }

                }

                //更新销售单上的成本单价和成本金额等字段
                kernel::single('tgstockcost_instance_router')->set_sales_iostock_cost($io,$iostock_data);
            }
        }

        if ($allow_commit == true){
            // kernel::database()->commit();
            return true;
        }else{
            // kernel::database()->rollBack();
            $msg['instock'] = $iostock_msg;
            $msg['sales'] = $sales_msg;
            return false;
        }
    }



    /**
     * 组织出库数据
     * @access public
     * @param String $delivery_id 发货单ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($delivery_id){
        $delivery_items_detailObj = app::get('ome')->model('delivery_items_detail');

        //发货单信息
        $sql = 'SELECT `branch_id`,`delivery_bn`,`op_name`,`delivery_time`,`is_cod` FROM `sdb_ome_delivery` WHERE `delivery_id`=\''.$delivery_id.'\'';
        $delivery_detail = $delivery_items_detailObj->db->selectrow($sql);
        $delivery_items_detail = $delivery_items_detailObj->getList('*', array('delivery_id'=>$delivery_id), 0, -1);

        $iostock_data = array();
        if ($delivery_items_detail){
            foreach ($delivery_items_detail as $k=>$v){
                $iostock_data[$v['item_detail_id']] = array(
                    'order_id' => $v['order_id'],
                    'branch_id' => $delivery_detail['branch_id'],
                    'original_bn' => $delivery_detail['delivery_bn'],
                    'original_id' => $delivery_id,
                    'original_item_id' => $v['item_detail_id'],
                    'supplier_id' => '',
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $v['number'],
                    'cost_tax' => '',
                    'oper' => $delivery_detail['op_name'],
                    'create_time' => $delivery_detail['delivery_time'],
                    'operator' => $delivery_detail['op_name'],
                    'settle_method' => '',
                    'settle_status' => '0',
                    'settle_operator' => '',
                    'settle_time' => '',
                    'settle_num' => '',
                    'settlement_bn' => '',
                    'settlement_money' => '0',
                    'memo' => '',
                );
            }
        }
        unset($delivery_detail,$delivery_items_detail);
        return $iostock_data;
    }

///////////////////////////////////////////////////////////

    /**
     * 重写 组织销售单数据
     * @access public
     * @param Array $delivery_id 发货单ID
     * @return sales_data 销售单数据
     * */

    public function get_sales_data($delivery_id,$deliverytime = false){
        $order_original_data = array();
        $sales_data = array();

        $deliveryObj = app::get('ome')->model('delivery');
        $orderIds = $deliveryObj->getOrderIdsByDeliveryIds(array($delivery_id));

        $ome_original_dataLib = kernel::single('ome_sales_original_data');
        $ome_sales_dataLib = kernel::single('ome_sales_data');
        foreach ($orderIds as $key => $orderId){
            $order_original_data = $ome_original_dataLib->init($orderId);
            if($order_original_data){
                $sales_data[$orderId] = $ome_sales_dataLib->generate($order_original_data,$delivery_id);
                if(!$sales_data[$orderId]){
                    return false;
                }
            }else{
                return false;
            }
            unset($order_original_data);
        }

        //平摊预估物流运费，主要处理订单合并发货以及多包裹单的运费问题
        $ome_sales_logistics_feeLib = kernel::single('ome_sales_logistics_fee');
        $ome_sales_logistics_feeLib->calculate($orderIds,$sales_data);

        return $sales_data;

    }

    public function get_sales_delivery_data($deliveryId, $salesData = array()) {
        $deliveryObj = app::get('ome')->model('delivery');
        $deliveryDetail = $deliveryObj->db_dump(array('delivery_id'=>$deliveryId),'process');
        $delivery_items_detailObj = app::get('ome')->model('delivery_items_detail');
        $delivery_items_detail = $delivery_items_detailObj->getList('*', array('delivery_id'=>$deliveryId), 0, -1);
        if(empty($salesData)) {
            $salesData = $this->get_sales_data($deliveryId);
        }
        $productId = array();
        foreach ($delivery_items_detail as $item) {
            $productId[] = $item['product_id'];
        }
        $productGoods = kernel::single('ome_goods_product')->getProductGoods($productId);
        $arrOrder = $this->order;
        $itemSalePrice = kernel::single('ome_sales_price')->getItemProductSalePrice($salesData, $arrOrder, $productGoods);
        $salesDeliveryData = array();
        foreach ($delivery_items_detail as $val) {
            $tmpSaleDeliveryData = array();
            $tmpSaleDeliveryData['delivery_id'] = $val['delivery_id'];
            $tmpSaleDeliveryData['delivery_item_id'] = $val['delivery_item_id'];
            $tmpSaleDeliveryData['order_id'] = $val['order_id'];
            $tmpSaleDeliveryData['order_obj_id'] = $val['order_obj_id'];
            $tmpSaleDeliveryData['order_item_id'] = $val['order_item_id'];
            $tmpSaleDeliveryData['item_type'] = $val['item_type'];
            $tmpSaleDeliveryData['shop_id'] = $arrOrder[$val['order_id']]['shop_id'];
            $tmpSaleDeliveryData['branch_id'] = $salesData[$val['order_id']]['branch_id'];
            $tmpSaleDeliveryData['pay_time'] = $arrOrder[$val['order_id']]['paytime'];
            $tmpSaleDeliveryData['delivery_time'] = $salesData[$val['order_id']]['ship_time'];
            $tmpSaleDeliveryData['product_id'] = $val['product_id'];
            $tmpSaleDeliveryData['cat_id'] = $productGoods[$val['product_id']]['goods']['cat_id'];
            $tmpSaleDeliveryData['type_id'] = $productGoods[$val['product_id']]['goods']['type_id'];
            $tmpSaleDeliveryData['brand_id'] = $productGoods[$val['product_id']]['goods']['brand_id'];
            $tmpSaleDeliveryData['bn'] = $val['bn'];
            $tmpSaleDeliveryData['name'] = $productGoods[$val['product_id']]['name'];
            $tmpSaleDeliveryData['nums'] = $val['number'];
            $tmpItemSalePrice = $itemSalePrice[$val['order_item_id']];
            $tmpSaleDeliveryData['spec_name'] = $tmpItemSalePrice['spec_name'];
            if($val['number'] == $tmpItemSalePrice['number']){
                $tmpSaleDeliveryData['price'] = $tmpItemSalePrice['price'];
                $tmpSaleDeliveryData['pmt_price'] = $tmpItemSalePrice['pmt_price'];
                $tmpSaleDeliveryData['sale_price'] = $tmpItemSalePrice['sale_price'];
                $tmpSaleDeliveryData['apportion_pmt'] = $tmpItemSalePrice['apportion_pmt'];
                $tmpSaleDeliveryData['sales_amount'] = $tmpItemSalePrice['sales_amount'];
            } else {
                $sendNum = $arrOrder[$val['order_id']]['order_objects'][$val['order_obj_id']]['order_items'][$val['order_item_id']]['sendnum'];
                if($deliveryDetail['process'] == 'true') {
                    $sendNum -= $val['number'];
                }
                if(($val['number'] + $sendNum) == $tmpItemSalePrice['number']) {
                    $tmpSaleDeliveryData['pmt_price'] = bcsub($tmpItemSalePrice['pmt_price'],
                        bcmul($sendNum/$tmpItemSalePrice['number'], $tmpItemSalePrice['pmt_price'], 2), 2);
                    $tmpSaleDeliveryData['sale_price'] = bcsub($tmpItemSalePrice['sale_price'],
                        bcmul($sendNum/$tmpItemSalePrice['number'], $tmpItemSalePrice['sale_price'], 2), 2);
                    $tmpSaleDeliveryData['apportion_pmt'] = bcsub($tmpItemSalePrice['apportion_pmt'],
                        bcmul($sendNum/$tmpItemSalePrice['number'], $tmpItemSalePrice['apportion_pmt'], 2), 2);
                    $tmpSaleDeliveryData['price'] = bcdiv(
                        bcadd($tmpSaleDeliveryData['sale_price'], $tmpSaleDeliveryData['pmt_price'], 2),
                        $val['number'], 2);;
                    $tmpSaleDeliveryData['sales_amount'] = bcsub($tmpSaleDeliveryData['sale_price'], $tmpSaleDeliveryData['apportion_pmt'], 2);
                } else {
                    $tmpSaleDeliveryData['pmt_price'] = bcmul($tmpItemSalePrice['pmt_price'],
                        $val['number']/$tmpItemSalePrice['number'], 2);
                    $tmpSaleDeliveryData['sale_price'] = bcmul($tmpItemSalePrice['sale_price'],
                        $val['number']/$tmpItemSalePrice['number'], 2);
                    $tmpSaleDeliveryData['apportion_pmt'] = bcmul($tmpItemSalePrice['apportion_pmt'],
                        $val['number']/$tmpItemSalePrice['number'], 2);
                    $tmpSaleDeliveryData['price'] = bcdiv(
                        bcadd($tmpSaleDeliveryData['sale_price'], $tmpSaleDeliveryData['pmt_price'], 2),
                        $val['number'], 2);;
                    $tmpSaleDeliveryData['sales_amount'] = bcsub($tmpSaleDeliveryData['sale_price'], $tmpSaleDeliveryData['apportion_pmt'], 2);
                }
            }
            $salesDeliveryData[] = $tmpSaleDeliveryData;
        }
        return $salesDeliveryData;
    }

    #订单修改
        /**
     * order_update_sales_delivery
     * @param mixed $deliveryId ID
     * @param mixed $orderId ID
     * @return mixed 返回值
     */
    public function order_update_sales_delivery($deliveryId, $orderId) {
        $salesDeliveryData = $this->get_sales_delivery_data($deliveryId);
        $saleDeliveryModel = app::get('sales')->model('delivery_order_item');
        foreach ($salesDeliveryData as $val) {
            if($val['order_id'] == $orderId) {
                $upData = array();
                $upData['price'] = $val['price'];
                $upData['pmt_price'] = $val['pmt_price'];
                $upData['sale_price'] = $val['sale_price'];
                $upData['apportion_pmt'] = $val['apportion_pmt'];
                $upData['sales_amount'] = $val['sales_amount'];
                $saleDeliveryModel->update($upData, array('delivery_id'=>$val['delivery_id'], 'order_item_id'=>$val['order_item_id']));
            }
        }
    }
    
    /**
     * [拆单]判断订单是否已全部发货
     * 
     * @param int $order_id
     * @param int $delivery_id
     * @return boolean
     */
    public function check_order_all_delivery($order_id, $delivery_id)
    {
        //订单"部分拆分"不生成销售单
        $sql    = "SELECT process_status FROM sdb_ome_orders WHERE order_id='".$order_id."'";
        $row    = kernel::database()->selectrow($sql);
        if($row['process_status'] == 'splitting')
        {
            return true;
        }
        
        //判断——订单所属发货单是否全部发货 process!='true'
        $sql    = "SELECT dord.delivery_id, d.delivery_bn, d.process, d.status FROM sdb_ome_delivery_order AS dord 
                        LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id) 
                        WHERE dord.order_id='".$order_id."' AND d.delivery_id!='".$delivery_id."' AND d.process!='true' 
                        AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' AND d.status NOT IN('failed','cancel','back','return_back')";
        $row    = kernel::database()->selectrow($sql);
        if(!empty($row))
        {
           return true;
        }
        
        return false;
    }
    
    /**
     * [拆单]余单撤消后_生成销售单
     * 
     * @param Array $data 订单号ID
     * @param number $io 默认0出库
     * @param string $type
     * @return boolean|unknown
     */
    public function add_to_sales($data, $io=0, $type=null)
    {
        $iostock_instance   = kernel::service('ome.iostock');
        if (method_exists($iostock_instance, 'set') == false){
            return false;
        }
        
        //存储出入库记录
        $iostock_data   = $data['iostock'];
        if(!$type) {
             eval('$type='.get_class($iostock_instance).'::LIBRARY_SOLD;');
        }
        $iostock_bn     = $iostock_instance->get_iostock_bn($type);
        $rs = $this->data_to_sale($data, $iostock_data, $iostock_bn, $io);
        $allow_commit = $rs['allow_commit'];
        return $allow_commit;
    }
    
    /**
     * [拆单]获取订单对应所有iostock出入库单
     * 
     * @param Array $iostock_data 出入库单
     * @return Array
     */
    public function get_delivery_iostock_data($iostock_data)
    {
        $order_ids  = $delivery_ids = array();
        foreach ($iostock_data as $key => $val)
        {
            $order_ids[$val['order_id']]    = $val['order_id'];
        }
        $in_order_id    = implode(',', $order_ids);
        
        //获取订单对应所有发货单delivery_id
        $sql            = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id in(".$in_order_id.") AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                                            AND d.status NOT IN('failed','cancel','back','return_back')";
        $temp_data      = kernel::database()->select($sql);
        foreach ($temp_data as $key => $val)
        {
            $delivery_ids[]     = $val['delivery_id'];
        }
        
        //读取出库记录
        $result     = array();
        $ioObj      = app::get('ome')->model('iostock');
        $field      = 'iostock_id, iostock_bn, type_id, branch_id, original_bn, original_id, original_item_id, bn';
        $temp_data  = $ioObj->getList($field, array('original_id'=>$delivery_ids, 'type_id'=>3));
        
        foreach ($temp_data as $key => $val)
        {
            $result[$val['original_item_id']]   = $val;
        }
        
        return $result;
    }
    
    /**
     * [拆单]多个发货单累加物流成本
     * 
     * @param Array $order_id 出入库单
     * @return boolean|number
     */
    public function count_delivery_cost_actual($order_id)
    {
        $oDelivery      = app::get('ome')->model('delivery');
        $delivery_ids   = $temp_data = array();
        
        //获取订单对应所有发货单delivery_id
        $sql            = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id='".$order_id."' AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                                            AND d.status NOT IN('failed','cancel','back','return_back')";
        $temp_data      = kernel::database()->select($sql);
        
        //[无拆单]订单只有一个发货单,直接返回false
        if(count($temp_data) < 2){
            return false;
        }
        
        foreach ($temp_data as $key => $val)
        {
            $delivery_ids[]     = $val['delivery_id'];
        }
        
        //累加物流成本
        $dly_data               = $oDelivery->getList('delivery_id, delivery_cost_actual, parent_id, is_bind', array('delivery_id'=>$delivery_ids));
        $delivery_cost_actual   = 0;
        foreach ($dly_data as $key => $val)
        {
            //[合并发货单]重新计算物流运费
            if($val['is_bind'] == 'true'){
                $val['delivery_cost_actual']    = $this->compute_delivery_cost_actual($order_id, $val['delivery_id'], $val['delivery_cost_actual']);
            }
            
            $delivery_cost_actual += floatval($val['delivery_cost_actual']);
        }
        
        return $delivery_cost_actual;
    }
    
    /**
     * [拆单]合并发货单_平摊预估物流运费
     * 
     * @param Array $order_id
     * @param Array $delivery_id
     * @param Array
     */
    public function compute_delivery_cost_actual($order_id, $delivery_id, $delivery_cost_actual)
    {
        $oOrders    = app::get('ome')->model('orders');
        $oDelivery  = app::get('ome')->model('delivery');
        
        $orderIds   = $oDelivery->getOrderIdsByDeliveryIds(array($delivery_id));
        
        $sales_data = $temp_data  = array();
        $temp_data  = $oOrders->getList('order_id, payed', array('order_id'=>$orderIds));
        foreach ($temp_data as $key => $val)
        {
            $val['delivery_cost_actual']    = $delivery_cost_actual;
            $sales_data[$val['order_id']]   = $val;
        }
        
        //平摊预估物流运费，主要处理订单合并发货以及多包裹单的运费问题
        $ome_sales_logistics_feeLib = kernel::single('ome_sales_logistics_fee');
        $ome_sales_logistics_feeLib->calculate($orderIds,$sales_data);
        
        return $sales_data[$order_id]['delivery_cost_actual'];//返回所查订单的平摊物流费用
    }
}