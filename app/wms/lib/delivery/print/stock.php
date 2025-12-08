<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_delivery_print_stock extends wms_delivery_print_abstract{

    /**
     * format
     * @param mixed $print_data 数据
     * @param mixed $sku sku
     * @param mixed $_err _err
     * @return mixed 返回值
     */
    public function format($print_data, $sku,&$_err){
        //$goodsObj = app::get('ome')->model('goods');
        $branchObj        = app::get('ome')->model('branch');
        $dlyObj           = app::get('wms')->model('delivery');
        $basicMaterialLib = kernel::single('material_basic_material');
        $frontProductName = [];

        $idstr = implode(',', $print_data['ids']);
        //统计商品数量
        $data = $dlyObj->countProduct($idstr);
        $arrPrintStockPrice = $dlyObj->getPrintStockPrice($print_data['ids']);

        $branch_list = array();
        $rows = array();
        $memo = array();
        $mark_text = array();
        $delivery_discount_price = 0;
        foreach($print_data['deliverys'] as $k => $dly) {
            if(!isset($branch_list[$dly['branch_id']])){
                $branch = $branchObj->dump(array('branch_id'=>$dly['branch_id']),'name');
                if($branch){
                    $branch_list[$dly['branch_id']] = $branch['name'];
                }
            }

            $daa = $dlyObj->getProductPosInfo($dly['delivery_id'],$dly['branch_id']);
            if (!$daa) continue;

            //按物料合并相应的保质期批次
            $tmp_expire_bns = array();
            if(isset($dly['expire_bns'])){
                foreach($dly['expire_bns'] as $k => $info){
                    $tmp_expire_bns[$info['bm_id']][] = $info;
                }
            }
            
            //获取发货单关联的订单对象明细
            if($dly['ome_delivery_id'])
            {
                $delivery_detail       = array();
                $sql    = "SELECT a.delivery_item_id, b.bn FROM sdb_ome_delivery_items_detail AS a
                           LEFT JOIN sdb_ome_order_objects AS b ON a.order_obj_id=b.obj_id
                           WHERE a.delivery_id=". $dly['ome_delivery_id'];
                $tempData    = $dlyObj->db->select($sql);
                foreach ($tempData as $obj_val)
                {
                    $delivery_detail[$obj_val['delivery_item_id']]    = $obj_val['bn'];
                }
            }
            
            $has_process_this_pdt = array();
            foreach ($daa as $i) {
                
                //获取销售物料编码
                $i['sales_material_bn']    = $delivery_detail[$i['item_id']];
                
                //处理保质期物料的显示数据
                if(isset($tmp_expire_bns[$i['product_id']])){
                    if(!$has_process_this_pdt || !in_array($i['product_id'],$has_process_this_pdt)){
                        $ei = 0;
                        foreach($tmp_expire_bns[$i['product_id']] as $k => $info){
                            $stockkey = strtoupper($i['bn'].$info['expire_bn']);
                            $rows[$stockkey]['num'] += $info['number'];
                            $rows[$stockkey]['boxs'][] = '<' . $print_data['identInfo']['ids'][$dly['delivery_id']] . '(' . $info['number'] . ')>';
                            $rows[$stockkey]['box'] = implode(' ', $rows[$stockkey]['boxs']);
                            $rows[$stockkey]['box_price'] = isset($arrPrintStockPrice[$i['bn']]) ? ($ei == 0 ? $arrPrintStockPrice[$i['bn']] : '-') : 0;
                            $rows[$stockkey]['bn'] = $i['bn'];
                            $rows[$stockkey]['name'] = $i['name'];
                            $rows[$stockkey]['product_name'] = $i['product_name'];
                            $rows[$stockkey]['product_weight'] = $i['weight'];
                            $rows[$stockkey]['unit'] = $i['unit'];
                            $rows[$stockkey]['specifications'] = $i['specifications'];
                            $rows[$stockkey]['store_position'] = $i['store_position'];
                            $rows[$stockkey]['barcode'] = $basicMaterialLib->getBasicMaterialCode($i['bm_id']);
                            $rows[$stockkey]['expire_bn'] = $info['expire_bn'];
                            
                            #商品编码字段展示和兼容打印模板中spec_info商品规格
                            $rows[$stockkey]['goods_bn']     = $i['sales_material_bn'];
                            $rows[$stockkey]['spec_info']    = $i['specifications'];
                            
                            $ei++;
                        }
                        //标记多条明细有同一个物料，只处理一次，不然数量会累加
                        $has_process_this_pdt[] = $i['product_id'];
                    }
                }else{
                    $stockkey = strtoupper($i['bn']);
                    $rows[$stockkey]['num'] += $i['number'];
                    $rows[$stockkey]['boxs'][] = '<' . $print_data['identInfo']['ids'][$dly['delivery_id']] . '(' . $i['number'] . ')>';
                    $rows[$stockkey]['box'] = implode(' ', $rows[$stockkey]['boxs']);
                    $rows[$stockkey]['box_price'] = isset($arrPrintStockPrice[$stockkey]) ? $arrPrintStockPrice[$stockkey] : 0;
                    $rows[$stockkey]['bn'] = $i['bn'];
                    $rows[$stockkey]['name'] = $i['name'];
                    $rows[$stockkey]['product_name'] = $i['product_name'];
                    $rows[$stockkey]['product_weight'] = $i['weight'];
                    $rows[$stockkey]['unit'] = $i['unit'];
                    $rows[$stockkey]['specifications'] = $i['specifications'];
                    $rows[$stockkey]['store_position'] = $i['store_position'];
                    $rows[$stockkey]['barcode'] = $basicMaterialLib->getBasicMaterialCode($i['bm_id']);
                    $rows[$stockkey]['expire_bn'] = '-';
                    
                    #商品编码字段展示和兼容打印模板中spec_info商品规格
                    $rows[$stockkey]['goods_bn']    = $i['sales_material_bn'];
                    $rows[$stockkey]['spec_info']    = $i['specifications'];
                }
            }

            foreach ($dly['orders'] as $odk => $order){
                $delivery_discount_price += $order['discount'] + $order['pmt_order'] + $order['pmt_goods'];
                if(!empty($order['custom_mark'])){
                    $memo =array_merge($memo,unserialize($order['custom_mark']));
                }

                if(!empty($order['mark_text'])){
                    $mark_text =array_merge($mark_text,unserialize($order['mark_text']));
                }

                if(!empty($order['order_objects'])){
                    foreach ($order['order_objects'] as $frontName){
                        if(!empty($frontName['order_items'])){
                            foreach ($frontName['order_items'] as $frontBn){
                                $frontProductName[$frontBn['bn']]['front_name'] = $frontName['name'];
                            }
                        }
                    }
                }
            }
        }

        // 对usort进行扩展，对多位数组进行值的排序
        function cmp($a, $b) {
            return strcmp($a["store_position"], $b["store_position"]);
        }
        usort($rows, "cmp");

        //商品名称和规格取前台,是合并发货单,取第一个订单的货品名称
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $is_print_front = (1 == $deliCfgLib->getValue('wms_delivery_is_printstock',$sku)) ? true : false ;

        if ($print_data['ids'] && $is_print_front) {
            $arrPrintProductName = $dlyObj->getPrintProductName($print_data['ids']);
            if (!empty($arrPrintProductName)) {
                foreach ($rows as $k => $v) {
                    $rows[$k]['product_name'] = $arrPrintProductName[$v['bn']]['name'];
                    $rows[$k]['name'] = $frontProductName[$v['bn']]['front_name'] ? $frontProductName[$v['bn']]['front_name'] : $arrPrintProductName[$v['bn']]['name'];
                    $rows[$k]['addon'] = $arrPrintProductName[$v['bn']]['addon'];
                    $rows[$k]['spec_info'] = $arrPrintProductName[$v['bn']]['addon'];
                }
            }
        }

        $delivery_total_nums = 0;
        $delivery_total_price = 0;
        foreach ($rows as $k => $v) {
            $delivery_total_nums += $v['num'];
            $delivery_total_price += $v['box_price'];
        }

        return array(
            'rows'=>$rows,
            'delivery_total_nums'=>$delivery_total_nums,
            'delivery_total_price'=>$delivery_total_price - $delivery_discount_price,
            'delivery_discount_price'=>$delivery_discount_price,
            'picking_list_price' => $delivery_total_price,
            'branch_list' => $branch_list,
            'vid' => $idstr,
            'data' => $data,
            'memo' => $memo,
            'mark_text' => $mark_text,
        );
    }

    /**
     * arrayToJson
     * @param mixed $items items
     * @param mixed $idents ID
     * @param mixed $pagedata 数据
     * @return mixed 返回值
     */
    public function arrayToJson($items, $idents,$pagedata) {
        $nbsp = "　";
        $this->covertNullToString($items);
        $delivery_total_nums = 0;
        $delivery_total_price = 0;
        foreach ($items as $k => $v) {
            if (isset($v['boxs'])) {
                unset($items[$k]['boxs']);
            }
        }
        $countDeliveryMsg['num_total'] = "数量总计：" . $pagedata['delivery_total_nums'];
        $countDeliveryMsg['num_money_total'] = "数量总计：" . $pagedata['delivery_total_nums'] . $nbsp . $nbsp .
            "备货金额总计：" . $pagedata['picking_list_price'] . $nbsp . $nbsp .
            "优惠金额总计：" . $pagedata['delivery_discount_price'];
        $countDeliveryMsg['empty'] = '';

        $data = array(
            0 => array(
                'stock_items' => $items,
                'date_y' => date('Y'),
                'date_m' => date('m'),
                'date_d' => date('d'),
                'date_ymd' => date('Ymd'),
                'batch_number' => isset($idents[0]) ? $idents[0] : '',
                'countDeliveryMsg' => $countDeliveryMsg,
            ),
        );
        return json_encode($data);
    }
}