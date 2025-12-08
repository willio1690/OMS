<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/6/25
 * Time: 11:20
 */
class o2o_order
{

    /**
     * 创建Order
     * @param mixed $data 数据
     * @return mixed 返回值
     */

    public function createOrder(&$data) {
        $shop = app::get('o2o')->model('store')->db_dump(array('shop_id'=>$data['shop_id']));
        if(empty($shop)) {
            return array(false, '没有该门店');
        }

        $data['branch_id'] = $shop['branch_id'];

        $mark_text = [
            [
                'op_name'    => kernel::single('desktop_user')->get_name(),
                'op_time'    => time(),
                'op_content' => $data['mark_text'],
            ]
        ];

        $sdf = array(
            'member_uname'=>$data['name'],
            'pay_bn'=>$data['payment_no'],
            'payinfo'=>array(
                'pay_name'=>$data['payment']
            ),
            'createtime'=>time(),
            'shop_id'=>$data['shop_id'],
            'shop_type'=>$shop['shop_type'],
            'shipping'=>array(
                'shipping_name'=>'',
                'is_cod'=>'false',
                'cost_shipping'=>'0',
                'is_protect'=>'false',
                'cost_protect'=>0,
            ),
            'custom_mark'=>'',
            'mark_text'=>serialize($mark_text),
            'consignee'=>array(
                'name'=>$data['name'],
                'email'=>'',
                'zip'=>'',
                'mobile'=>$data['mobile'],
                'telephone' => '',
                'addr'=>$data['addr'],
                'area'=>$data['area'],
                'r_time'=>'任意日期 任意时间段',
            ),
            'is_tax'=>'false',
            // 'cost_item'=>$data['receivable'],
            'cost_item'=>array_sum($data['original_price']),
            'pmt_goods'=>array_sum($data['discount']),
            'pmt_order'=>$data['pmt_order'],
            'total_amount'=>$data['payed'],
            'order_type'=>'offline',
            'paytime'=>time(),
            'order_source'=>'direct',
            'createway'=>'local',
            'pay_status' => 1,
            'payed'=>$data['payed']
        );
        $sdf['order_bn'] = 'O' . $data['payment_no'];
        if($data['member_id']) {
            $sdf['member_id'] = $data['member_id'];
        } else {
            $memberModel = app::get('ome')->model('members');
            $member = $memberModel->dump(array('uname'=>$sdf['member_uname']),'member_id');
            if (!$member) {
                $member = array(
                    'account' => array('uname'=>$sdf['member_uname']),
                    'contact' => array(
                        'name'=>$sdf['member_uname'],
                        'phone'=>array('mobile'=>$sdf['consignee']['mobile'])
                    ),
                );
                $memberModel->save($member);
            }
            $sdf['member_id'] = $member['member_id'];
        }

        $price = $data['price'];
        $sale_price = $data['sale_price'];
        $discount = $data['discount'];
        $salesMLib = kernel::single('material_sales_material');
        $lib_ome_order = kernel::single('ome_order');
        foreach ($data['num'] as $k => $i){
            $salesMInfo = $salesMLib->getSalesMById($data['shop_id'],$k);
            if($salesMInfo){
                if($salesMInfo['sales_material_type'] == 4){ //福袋
                    $basicMInfos = $salesMLib->get_order_luckybag_bminfo($salesMInfo['sm_id']);
                }elseif($salesMInfo['sales_material_type'] == 5){ //多选一
                    $basicMInfos = $salesMLib->get_order_pickone_bminfo($salesMInfo['sm_id'],$i,$data['shop_id']);
                }else{
                    //获取绑定的基础物料
                    $basicMInfos = $salesMLib->getBasicMBySalesMId($salesMInfo['sm_id']);
                }
                $obj_number = $i;
                //如果是促销类销售物料
                if($salesMInfo['sales_material_type'] == 2){ //促销
                    $obj_type = $item_type = 'pkg';
                    $obj_sale_price = $price[$k]*$obj_number;
                    //item层关联基础物料平摊销售价
                    $salesMLib->calProSaleMPriceByRate($obj_sale_price, $basicMInfos);
                    $return_arr_info = $lib_ome_order->format_order_items_data($item_type,$obj_number,$basicMInfos);
                }elseif($salesMInfo['sales_material_type'] == 4){ //福袋
                    $obj_type = $item_type = 'lkb';
                    $return_arr_info = $lib_ome_order->format_order_items_data($item_type,$obj_number,$basicMInfos);
                }elseif($salesMInfo['sales_material_type'] == 5){ //多选一
                    $obj_type = $item_type = 'pko';
                    foreach($basicMInfos as &$var_basic_info){
                        $var_basic_info["price"] = $price[$k];
                        $var_basic_info["sale_price"] = $sale_price[$k]/$obj_number;
                        $var_basic_info["pmt_price"] = $discount[$k]/$obj_number;
                    }
                    unset($var_basic_info);
                    $return_arr_info = $lib_ome_order->format_order_items_data($item_type,$obj_number,$basicMInfos);
                }else{
                    $sales_material_type = material_sales_material::$sales_material_type;
                    $obj_type = $sales_material_type[$salesMInfo['sales_material_type']]['type'];
                    $obj_type = $obj_type ? $obj_type : 'goods';
                    $item_type = ($obj_type == 'goods') ? 'product' : $obj_type;
                    if($obj_type == 'gift'){
                        $price[$k] = 0.00;
                    }
                    foreach($basicMInfos as &$var_basic_info){
                        $var_basic_info["price"] = $price[$k];
                        $var_basic_info["sale_price"] = $sale_price[$k]/$obj_number;
                        $var_basic_info["pmt_price"] = $discount[$k]/$obj_number;
                    }
                    unset($var_basic_info);
                    $return_arr_info = $lib_ome_order->format_order_items_data($item_type,$obj_number,$basicMInfos);
                }

                $sdf['order_objects'][] = array(
                    'obj_type' => $obj_type,
                    'obj_alias' => $obj_type,
                    'goods_id' => $salesMInfo['sm_id'],
                    'bn' => $salesMInfo['sales_material_bn'],
                    'name' => $salesMInfo['sales_material_name'],
                    'price' => $price[$k],
                    'sale_price'=>$sale_price[$k],
                    'pmt_price'=>$discount[$k],
                    'amount' => $price[$k]*$obj_number,
                    'quantity' => $obj_number,
                    'order_items' => $return_arr_info["order_items"],
                );
                // $item_cost += $price[$k]*$obj_number;
                $tostr[]=array("name"=>$salesMInfo['sales_material_name'],"num"=>$obj_number);
            }
        }
        if (!$sdf['order_objects']) {
            return array(false, '下单商品，该门店无法销售');
        }
        $sdf['title']     = $tostr ? json_encode($tostr):'';
        $sdf['itemnum']   = count($sdf['order_objects']);
        $sdf['currency']  = 'CNY';
        $sdf['source']    = 'local';

        $rs = app::get('ome')->model('orders')->create_order($sdf);
        if(!$rs) {
            return array(false, '该支付单号已经生成过订单');
        }
        $data['order_id'] = $sdf['order_id'];
        return array(true, '新建成功');
    }

    /**
     * doPay
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function doPay($data) {
        $op = kernel::single('ome_func')->getDesktopUser();
        $payment = array(
            'payment_bn'    => $data['payment_no'],
            'shop_id'       => $data['shop_id'],
            'order_id'      => $data['order_id'],
            'currency'       => 'CNY',
            'money'         => (float)$data['payed'],
            'cur_money'     => (float)$data['payed'],
            'paymethod'     => $data['payment'],
            't_begin'       => time(),
            't_end'         => time(),
            'download_time' => time(),
            'status'        => 'succ',
            'trade_no'      => $data['payment_no'],
            'op_id'         => $op['op_id'],
            'op_name'       => $op['op_name']
        );
        app::get('ome')->model('payments')->insert($payment);
    }

    /**
     * 添加ConsignDelivery
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function addConsignDelivery($data) {
        $arrProductId = array();
        foreach ($data['delivery'] as $k => $val) {
            if ($val) {
                $arrProductId[] = $k;
            }
        }
        if(empty($arrProductId)) {
            return;
        }
        $orderId = $data['order_id'];
        $orderObj = app::get('ome')->model('orders');
        $order = $orderObj->db_dump(array('order_id'=>$orderId),
            'order_id,ship_name,ship_time,ship_mobile,ship_zip,ship_area,ship_tel,ship_email,ship_addr,shipping');
        $delivery = array(
            'branch_id' => $data['branch_id'],
            'consignee' => array(
                'name' => $order['ship_name'],
                'r_time' => $order['ship_time'],
                'mobile' => $order['ship_mobile'],
                'zip' => $order['ship_zip'],
                'area' => $order['ship_area'],
                'telephone' => $order['ship_tel'],
                'email' => $order['ship_email'],
                'addr' => $order['ship_addr']
            ),
            'check_store' => false,
            'delivery_items' => array()
        );
        $corpData = kernel::single('logistics_rule')->getSelfFetchCorp();
        $delivery['logi_id'] = $corpData['corp_id'];
        $orderItems = app::get('ome')->model('order_items')->getList('*', array('order_id'=>$orderId, 'delete'=>'false', 'product_id'=>$arrProductId));
        $order_items = array();
        $logObj = app::get('ome')->model('operation_log');
        foreach ($orderItems as $item) {
            $delivery['delivery_items'][] = array(
                'item_type' => $item['item_type'],
                'product_id' => $item['product_id'],
                'shop_product_id' => $item['shop_product_id'],
                'bn' => $item['bn'],
                'number' => $item['nums'],
                'product_name' => $item['name'],
                'spec_info' => $item['addon'],
            );
            $order_items[] = array(
                'item_id' => $item['item_id'],
                'product_id' => $item['product_id'],
                'number' => $item['nums'],
                'bn' => $item['bn'],
                'product_name' => $item['name'],
                'obj_id' => $item['obj_id'],
            );
        }
        $split_status = '';
        $deliveryObj = app::get('ome')->model("delivery");
        $result = $deliveryObj->addDelivery($orderId, $delivery,array(),$order_items, $split_status);
        $delivery_id = $result['data'];
        if ($delivery_id) {
            //更新订单信息
            $sdf = array(
                'order_id'       => $orderId,
                'process_status' => $split_status,
                'confirm'        => 'Y',
                'dispatch_time'  => time(),
                'refund_status'  => 0,
            );
            $orderObj->save($sdf);
            $log_msg    = '订单'.($split_status=='splitting'?'部分':'').'确认';
            $get_dly_bn    = $deliveryObj->getList('delivery_id, delivery_bn', array('delivery_id' => $delivery_id), 0, 1);
            $get_dly_bn    = $get_dly_bn[0];
            $log_msg       .= sprintf('(发货单号：<a href="index.php?app=ome&ctl=admin_receipts_print&act=show_delivery_items&id=%s" target="_blank">%s</a>)', $delivery_id, $get_dly_bn['delivery_bn']);
            $logObj->write_log('order_confirm@ome',$orderId, $log_msg);
            $upData = array();
            $upData['logi_no'] = $get_dly_bn['delivery_bn'];
            $upData['expre_status'] = 'true';
            $upData['status'] = 'progress';
            $deliveryObj->update($upData,array( 'delivery_id' => $delivery_id));
            $logObj->write_log('delivery_expre@ome', $delivery_id, '线下订单已发货打印(模拟)');
            $errmsg = '';
            $consignRs = $deliveryObj->consignDelivery($delivery_id, 0, $errmsg);
            if(!$consignRs) {
                $logObj->write_log('delivery_process@ome', $delivery_id, '发货单发货失败：'.$errmsg);
            }
        } else {
            $logObj->write_log('order_confirm@ome',$orderId,'生成发货单失败');
        }
    }
}