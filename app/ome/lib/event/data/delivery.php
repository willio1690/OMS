<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_data_delivery
{

    /**
     *
     * 发货通知单请求数据生成
     * @param array $delivery_id 本地发货通知单(合并后的)id
     */
    public function generate($delivery_id)
    {
        $deliveryObj      = app::get('ome')->model('delivery');
        $deliveryOrderObj = app::get('ome')->model('delivery_order');
        $orderObj         = app::get('ome')->model('orders');
        $shopObj          = app::get('ome')->model('shop');
        $branchObj        = app::get('ome')->model('branch');
        $memberObj        = app::get('ome')->model('members');
        $dlyCorpObj       = app::get('ome')->model('dly_corp');
        $orderExtendObj   = app::get('ome')->model('order_extend');
        $didObj           = app::get('ome')->model('delivery_items_detail');
        $checkMdl         = app::get('ome')->model('order_objects_check_items');
        $data             = $deliveryObj->dump($delivery_id, '*', array('delivery_items' => array('*'), 'delivery_order' => array('*')));
        if ($data['parent_id'] > 0) {
            return array();
        }

        //判断是否是平台发货订单如果是不允许
        if ($data['bool_type'] & ome_delivery_bool_type::__PLATFORM_CODE) {
            return array();
        }

        //重组发货单明细上的金额信息 单价、平摊优惠价、平摊优惠金额
        $dly_order = $deliveryOrderObj->getlist('*', array('delivery_id' => $delivery_id), 0, -1);

        $pmt_orders       = $deliveryObj->getPmt_price($dly_order);
        $sale_orders      = $deliveryObj->getsale_price($dly_order);
        $tbdx_flag        = false;
        $is_order_invoice = 'false';
        $invoice_items    = array();
        if ($data) {
            foreach ($data['delivery_items'] as $key => $item) {
                foreach ($data['delivery_order'] as $dk => $order) {
                    $order_id = $order;
                    break;
                }
                $order_detail = $orderObj->dump($order_id, 'order_source,shop_type,status');
                if($order_detail['status'] != 'active'){
                    return array();
                }
                if ($order_detail['order_source'] == 'tbdx' && $order_detail['shop_type'] == 'taobao') {
                    $tbdx_flag = true;
                    $didArrs   = $didObj->dump(array('delivery_id' => $item['delivery_id'], 'delivery_item_id' => $item['item_id']), 'item_type,order_obj_id,order_item_id');

                    $tbitemObj = app::get('ome')->model('tbfx_order_items');

                    $tbfx_filter = array('obj_id' => $didArrs['order_obj_id'], 'item_id' => $didArrs['order_item_id']);

                    $ext_item_info                         = $tbitemObj->getOrderByOrderId($tbfx_filter);
                    $data['delivery_items'][$key]['price'] = round(($ext_item_info[0]['buyer_payment'] / $item['number']), 2);

                    $data['delivery_items'][$key]['sale_price'] = $ext_item_info[0]['buyer_payment'];
                } else {
                    $data['delivery_items'][$key]['price']      = $sale_orders[$item['bn']];
                    $data['delivery_items'][$key]['sale_price'] = $sale_orders[$item['bn']] * $item['number'];
                }

                $data['delivery_items'][$key]['pmt_price'] = $pmt_orders[$item['bn']]['pmt_price'];

                //回传奇门平台时需要qimen_delivery_id
                $data['delivery_items'][$key]['qimen_delivery_id'] = $data['delivery_items'][$key]['delivery_id'];
                $data['delivery_items'][$key]['delivery_items_id'] = $item['item_id'];
                unset($data['delivery_items'][$key]['delivery_id']);
                //unset($data['delivery_items'][$key]['item_id']);
                //$items[$key]['sale_price'] = ($sale_orders[$items[$key]['bn']]*$item['number'])-$pmt_order[$items[$key]['bn']]['pmt_price'];
            }

            $order_bns = array();
            foreach ($data['delivery_order'] as $dk => $order) {
                $row = $orderObj->dump($order['order_id'], '*', array('order_objects' => array('*', array('order_items' => array('*')))));

                if ($tbdx_flag) {
                    $tbfxorders       = $orderObj->db->selectrow("SELECT SUM(buyer_payment) as total_amount FROM sdb_ome_tbfx_order_items WHERE order_id=" . $order['order_id'] . "");
                    $row['cost_item'] = $row['total_amount'] = $tbfxorders['total_amount'];

                }

                if ($row['is_tax'] == 'true') {
                    $data['is_order_invoice']        = 'true';
                    $data['invoice']['invoice_desc'] = $row['tax_title'];
                    $data['invoice_money'] += ($row['total_amount'] - $row['service_price']);
                }
                
                // 订单扩展信息
                $orderextend    = $orderExtendObj->dump(array('order_id' => $order['order_id']), '*');
                $order_bns[]    = $row['order_bn'];
                $data['is_cod'] = $row['shipping']['is_cod'];
                $data['total_amount'] += $row['total_amount'];
                $data['discount_fee'] += ($row['pmt_order'] - $row['discount']);
                $data['total_goods_amount'] += $row['cost_item'];
                $data['goods_discount_fee'] += $row['pmt_goods'];
                $data['cost_tax']           += $row['cost_tax'];
                
                $data['shop_type']       = $row['shop_type'];
                $data['relate_order_bn'] = $row['relate_order_bn'];
                $data['cert_id']         = $orderextend['cert_id'];
                $data['extend_field']    = @json_decode($orderextend['extend_field'], 1);
                $data['extend_status']   = $orderextend['extend_status'];
                $data['order_bool_type'] = $row['order_bool_type'];
                //
            
                $orderReceiverMdl = app::get('ome')->model('order_receiver');
                $orderReceivers = $orderReceiverMdl->db_dump(array('order_id'=>$order['order_id']),'encrypt_source_data');

                if($orderReceivers['encrypt_source_data']){
                    $data['encrypt_source_data'] = json_decode($orderReceivers['encrypt_source_data'],true);
                }
                
                //平台订单信息
                $data['platform_createtime'] = ($row['createtime'] ? date('Y-m-d H:i:s', $row['createtime']) : ''); //顾客下单时间
                $data['platform_download_time'] = ($row['download_time'] ? date('Y-m-d H:i:s', $row['download_time']) : ''); //订单下载时间
                $data['platform_paytime'] = ($row['paytime'] ? date('Y-m-d H:i:s', $row['paytime']) : ''); //付款时间

                // 如果是唯品会，检测是否有重点检测的明细
                if (kernel::single('ome_order_bool_type')->isJITX($data['order_bool_type'])){
                    $checkList = $checkMdl->getList('bn',['order_id'=>$order['order_id']]);
                    if ($checkList) {
                        $data['quality_check'] = $checkList;
                    }
                }
                
                //支付时间
                $data['pay_time']  = $data['is_cod'] == 'false' ? date('Y-m-d H:i:s', $row['paytime']) : '';
                
                //店铺信息
                $shopInfo          = $shopObj->dump($row['shop_id'], 'shop_bn,name,node_id,addon,business_category');
                $data['shop_code'] = isset($shopInfo['shop_bn']) ? $shopInfo['shop_bn'] : '';
                $data['shop_name'] = isset($shopInfo['name']) ? $shopInfo['name'] : '';
                $data['node_id']   = $shopInfo['node_id'];
                $data['business_category'] = $shopInfo['business_category'];
                

                if($shopInfo['addon']){
                    // 平台店铺的shop_id
                    $data['platform_shop_id'] = $shopInfo['addon']['user_id'];

                    // 平台店铺唯一标识
                    $data['platform_shop_unikey'] = $shopInfo['addon']['unikey'];
                }


                $data['createway'] = $row['createway'];
                $dlyCorpInfo       = $dlyCorpObj->dump($data['logi_id'], 'type, protect');
                $data['logi_code'] = isset($dlyCorpInfo['type']) ? $dlyCorpInfo['type'] : '';
                $data['logi_protect'] = $dlyCorpInfo['protect'];

                if ($orderextend['platform_logi_no']) {
                    $waybill_arr          = explode(',', $orderextend['platform_logi_no']);
                    $data['sub_logi_nos'] = count($waybill_arr) > 1 ? array_slice($waybill_arr, 1) : '';
                }

                $data['logistics_costs'] += $row['shipping']['cost_shipping'];
                $receivable = $orderextend['receivable'] > 0 ? $orderextend['receivable'] : $row['total_amount'];
                $data['cod_fee'] += ($row['shipping']['is_cod'] == 'true' ? $receivable : 0.00);

                $branchInfo           = $branchObj->db->selectrow('SELECT branch_bn,storage_code,owner_code FROM sdb_ome_branch WHERE branch_id=' . $data['branch_id']);
                $data['storage_code'] = $branchInfo['storage_code'];
                $data['branch_bn']    = $branchInfo['branch_bn'];
                $data['owner_code']    = $branchInfo['owner_code'];
                $data['order_type']   = $row['order_type'];
                $data['order_source']   = $row['order_source'];
    
                // 优化：一次性获取所有扩展字段，然后在循环中判断取值
                $arr_props = app::get('ome')->model('branch_props')->getPropsByBranchId($data['branch_id']);
                foreach ($arr_props as $k => $v) {
                    //仓库自定义字段-活动号
                    if ($k == 'activity_no' && $v) {
                        $data['activity_no'] = $v;
                    }
                }
                //平台订单号(换货生成新订单的场景)
                if($row['platform_order_bn']){
                    $data['platform_order_bn'] = $row['platform_order_bn'];
                }
                
                $member_uname         = $memberObj->db_dump(array('member_id' => $row['member_id']), 'uname,name');
                if ($member_uname) {
                    $data['member_name'] = $member_uname['uname'];
                }

                $data['member'] = $member_uname;


                if ($row['mark_text']) {
                    $mark_text_arr = @unserialize($row['mark_text']);
                    $tmp_memo      = is_array($mark_text_arr) ? array_pop($mark_text_arr) : [];
                }
                $data['memo'] = isset($tmp_memo['op_content']) ? $tmp_memo['op_content'] : '';
                $custom_mark  = '';
                if ($row['custom_mark']) {
                    $custom_mark = kernel::single('ome_func')->format_memo($row['custom_mark']);
                    // 取最后一条
                    $mark = array_pop($custom_mark);
                    $custom_mark = $mark['op_content'];
                }
                $data['custom_mark'] = $custom_mark;
                
                foreach ($row['order_objects'] as $ok => $obj) {
                    $data['order_objects'][$ok] = array(
                        'order_id'          => $row['order_id'],
                        'order_bn'          => $row['order_bn'],
                        'order_type'        => $row['order_type'], //订单类型
                        'relate_order_bn'   => $row['relate_order_bn'], //关联订单号
                        'obj_id'            => $obj['obj_id'],
                        'obj_type'          => $obj['obj_type'],
                        'shop_goods_id'     => $obj['shop_goods_id'],
                        'goods_id'          => $obj['goods_id'],
                        'bn'                => $obj['bn'],
                        'name'              => $obj['name'],
                        'quantity'          => $obj['quantity'],
                        'price'             => $obj['price'],
                        'pmt_price'         => $obj['pmt_price'],
                        'sale_price'        => $obj['sale_price'],
                        'divide_order_fee'  => $obj['divide_order_fee'], //分摊之后的实付金额
                        'part_mjz_discount' => $obj['part_mjz_discount'], //优惠分摊
                        'author_id'         => $obj['author_id'], //活动主播ID
                        'author_name'       => $obj['author_name'], //活动主播名
                        'oid'               => $obj['oid'],
                    );

                    foreach ($obj['order_items'] as $ik => $item) {
                        $data['order_objects'][$ok]['order_items'][$ik] = array(
                            'order_id'          => $row['order_id'],
                            'obj_id'            => $obj['obj_id'],
                            'item_id'           => $item['item_id'],
                            'item_type'         => $item['item_type'],
                            'product_id'        => $item['product_id'],
                            'bn'                => $item['bn'],
                            'name'              => $item['name'],
                            'nums'              => $item['quantity'],
                            'price'             => $item['price'],
                            'pmt_price'         => $item['pmt_price'],
                            'sale_price'        => $item['sale_price'],
                            'divide_order_fee'  => $item['divide_order_fee'], //分摊之后的实付金额
                            'part_mjz_discount' => $item['part_mjz_discount'], //优惠分摊
                            'oid'               => $obj['oid'],
                        );
                        if ($row['is_tax'] == 'true' && $item['sale_price'] > 0) {
                            $invoice_items[] = array(
                                'item_name' => $item['name'],
                                'spec'      => $item['spec'],
                                'nums'      => $item['quantity'],
                                'price'     => $item['sale_price'],

                            );
                        }
                    }
                }

                unset($row);
            }
            
            //订单号(合并发货单以'|'竖线分隔多个订单号)
            $data['order_bn'] = implode('|', $order_bns);
            // 如果是唯品会，如果是合单，取第一个订单号，保证oms获取唯品会接口和给到wms的订单号一致
            if (kernel::single('ome_order_bool_type')->isJITX($data['order_bool_type']) && count($order_bns)>1) {
                $data['is_vop_merge'] = true;
                $data['order_bn']     = $order_bns[0];
            }
        }
        if ($invoice_items) {
            $data['invoice']['invoice_items'] = $invoice_items;
        }
        if ($data['pause'] == 'true') {
            $data['pause'] = 'true';
        }
        $data['outer_delivery_bn'] = $data['delivery_bn'];
        //是否预打包
        if(kernel::single('ome_bill_label_delivery')->isPrepackage($delivery_id)){
            $data['prepackage'] = 'true';
        }
        // 订单支付单
        if ($data['delivery_order']) {
            $paymentMdl       = app::get('ome')->model('payments');
            $data['payments'] = $paymentMdl->getList('payment_bn,trade_no,paymethod', ['order_id' => array_column($data['delivery_order'], 'order_id')]);
        }

        unset($data['delivery_bn'], $data['delivery_order']);

        return $data;
    }

}
