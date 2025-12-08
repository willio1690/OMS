<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
+----------------------------------------------------------
 * Api接口[数据处理]
+----------------------------------------------------------
 *
 * Time: 2014-03-18 $  update 20160608 by wangjianjun
 * [Ecos!] (C)2003-2014 Shopex Inc.
+----------------------------------------------------------
 */


class openapi_data_original_invoice
{

    //获取发票列表
    /**
     * 获取List
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */

    public function getList($filter, $offset = 0, $limit = 100)
    {

        //获取发票列表
        $mdlInOrder = app::get('invoice')->model('order');
        $rs_list    = $mdlInOrder->getList("*", $filter, $offset, $limit, "id desc");

        if (empty($rs_list)) {
            $result = array('list' => []);
            return $result;
        }

        //获取发票内容
        $rs_content = kernel::single('invoice_common')->getInvoiceContent();
        $rl_content = array();
        foreach ($rs_content as $var_content) {
            $rl_content[$var_content["content_id"]] = $var_content["content_name"];
        }

        //获取店铺id
        $shop_ids = array();
        foreach ($rs_list as $var_item) {
            if (!in_array($var_item["shop_id"], $shop_ids)) {
                $shop_ids[] = $var_item["shop_id"];
            }
        }

        $mdlOmeShop = app::get('ome')->model('shop');
        $rs_shops   = $mdlOmeShop->getList("shop_id,name", array("shop_id|in" => $shop_ids));
        $rl_shops   = array();
        foreach ($rs_shops as $var_shop) {
            $rl_shops[$var_shop["shop_id"]]["shop_id"]   = $var_shop["shop_id"];
            $rl_shops[$var_shop["shop_id"]]["shop_name"] = $var_shop["name"];
        }

        //格式化发票列表信息
        foreach ($rs_list as &$var_list) {
            //发票内容
            $var_list["content"] = $rl_content[$var_list["content_id"]];
            //如果有开蓝票开红票成功的明细 则显示
            $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
            $rs_items       = $mdlInOrderElIt->getList("*", array("id" => $var_list["id"]), 0, -1, "item_id asc");
            if (!empty($rs_items)) {
                foreach ($rs_items as $var_item) {
                    //没有发票代码的视作没有开票成功（包括蓝和红）
                    if (!$var_item["invoice_code"]) {
                        continue;
                    }
                    $var_list["electronic_items"][] = $var_item;
                }
            }
            //如发票内容是“商品明细”的 显示关联的商品明细信息
            //             if(intval($var_list['content_id']) == 1){
            $oOrder       = app::get('ome')->model('orders');
            $formatFilter = kernel::single('openapi_format_abstract');
            $item_list    = $oOrder->order_objects($var_list['order_id']);
            foreach ($item_list as $val_j) {
                $temp_arr = array(
                    'obj_id'     => $val_j['obj_id'],
                    'obj_alias'  => $val_j['obj_alias'],
                    'bn'         => $formatFilter->charFilter($val_j['bn']),
                    'name'       => $formatFilter->charFilter($val_j['name']),
                    'price'      => $val_j['price'],
                    'pmt_price'  => $val_j['pmt_price'],
                    'sale_price' => $val_j['sale_price'],
                    'quantity'   => $val_j['quantity'],
                );
                //明细关联商品
                foreach ($val_j['order_items'] as $key_k => $val_k) {
                    $temp_arr['order_items'][$key_k] = array(
                        'item_id'    => $val_k['item_id'],
                        'bn'         => $formatFilter->charFilter($val_k['bn']),
                        'name'       => $formatFilter->charFilter($val_k['name']),
                        'price'      => $val_k['price'],
                        'pmt_price'  => $val_k['pmt_price'],
                        'sale_price' => $val_k['sale_price'],
                        'nums'       => $val_k['nums'],
                        'addon'      => unserialize($val_k['addon']),
                    );
                }
                $var_list["product"][] = $temp_arr;
            }
//             }
            unset($var_list["content_id"]);
            //开票状态
            $var_list["is_status"] = kernel::single('invoice_common')->getIsStatusText($var_list["is_status"]);
            //开票方式
            $var_list["mode"] = kernel::single('invoice_common')->getModeText($var_list["mode"]);
            //地区及地址
            $areaArr               = array();
            $areaArr               = explode(':', $var_list['ship_area']);
            $var_list['ship_area'] = str_replace('/', ' ', $areaArr[1]);
            $var_list['ship_addr'] = $var_list['ship_area'] . $var_list['ship_addr'];
            //店铺id和name
            $cur_shop_id = $var_list["shop_id"];
            unset($var_list["shop_id"]);
            $var_list["shop_name"] = $rl_shops[$cur_shop_id]["shop_name"];
            $var_list["shop_id"]   = $rl_shops[$cur_shop_id]["shop_id"];
        }
        unset($var_list);

        $result = array('list' => $rs_list);
        return $result;

    }

    //更新订单纸质发票的打印状态
    /**
     * 更新
     * @param mixed $rs_info rs_info
     * @param mixed $invoice_no invoice_no
     * @return mixed 返回值
     */
    public function update($rs_info, $invoice_no)
    {

        $mdlInOrder   = app::get('invoice')->model('order');
        $opObj        = app::get('ome')->model('operation_log');
        $mdlOmeOrders = app::get('ome')->model('orders');

        $cur_time = time();
        if (intval($rs_info["is_print"]) == 1) {
            //已打印
            $print_num  = intval($rs_info["print_num"]) + 1;
            $update_arr = array(
                "print_num"   => $print_num,
                "invoice_no"  => $invoice_no,
                "update_time" => $cur_time,
            );
        } else {
            //未打印 开票处理
            $update_arr = array(
                "print_num"   => 1,
                "is_print"    => 1,
                "is_status"   => 1,
                "dateline"    => $cur_time,
                "invoice_no"  => $invoice_no,
                "update_time" => $cur_time,
            );
        }

        $filter = array("id" => $rs_info["id"]);
        $mdlInOrder->update($update_arr, $filter);

        //更新订单表发票号字段
        $filter_orders     = array("order_id" => $rs_info["order_id"]);
        $update_orders_arr = array("tax_no" => $invoice_no);
        $mdlOmeOrders->update($update_orders_arr, $filter_orders);

        $msg = '纸质发票打印成功。';
        $opObj->write_log('invoice_print@invoice', $rs_info['id'], $msg);

        return array(
            'msg'     => 'success',
            'message' => '更新发票的打印状态完成.',
        );

    }

    //获取发票列表
    /**
     * 获取ResultList
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getResultList($filter, $offset = 0, $limit = 100)
    {
        $invEMdl = app::get('invoice')->model('order_electronic_items');
        $invMdl  = app::get('invoice')->model('order');

        $count = $invEMdl->count($filter);

        if (!$count) {
            return ['lists' => [], 'count' => 0];
        }

        $inveList = $invEMdl->getList('*', $filter, $offset, $limit);

        $invList = $invMdl->getList('id,order_bn,type_id,mode,amount,cost_tax,tax_rate', ['id' => array_column($inveList, 'id')]);
        $invList = array_column($invList, null, 'id');

        $lists = [];
        foreach ($inveList as $v) {
            $inv = (array) $invList[$v['id']];

            $content = @json_decode($v['content'],true);

            $l = [];
            $l['order_bn']            = $inv['order_bn'];
            $l['type_id']             = $inv['type_id'];
            $l['mode']                = $inv['mode'];
            $l['amount']              = $inv['amount'];
            $l['cost_tax']            = $inv['cost_tax'];
            $l['tax_rate']            = $inv['tax_rate']/100;
            $l['invoice_code']        = $v['invoice_code'];
            $l['invoice_no']          = $v['invoice_no'];
            $l['serial_no']           = $v['serial_no'];
            $l['billing_type']        = $v['billing_type'];
            $l['create_time']         = date('Y-m-d H:i:s',$v['create_time']);
            $l['url']                 = $v['url'];
            $l['invoice_action_type'] = $v['invoice_action_type'];
            $l['items']               = (array) $content['items'];

            $lists[] = $l;
        }

        return ['lists' => $lists, 'count' => $count];
    }
}
