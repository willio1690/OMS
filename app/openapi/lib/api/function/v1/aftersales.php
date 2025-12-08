<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_aftersales extends openapi_api_function_abstract implements openapi_api_function_interface{

    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params,&$code,&$sub_msg){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        $modified_start = $params['modified_start'] ?: '';
        $modified_end   = $params['modified_end'] ?: '';
    
        $filter = [];
    
        if ($start_time && $end_time) {
            $filter['aftersale_time|between'] = [$start_time, $end_time];
        }
    
        if ($modified_start && $modified_end) {
            $filter['up_time|betweenstr'] = [$modified_start, $modified_end];
        }
    
        if (!$filter['up_time|betweenstr'] && !$filter['aftersale_time|between']){
            $sub_msg = '更新时间 或 创建时间 必填';
            return false;
        }
    
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }
        
        //getList
        $original_aftersales_data =  kernel::single('openapi_data_original_aftersales')->getList($filter,$start_time,$end_time,$offset,$limit);
        
        //order_id
        $orderIds = array_column($original_aftersales_data['lists'], 'order_id');
        
        //通过订单号获取销售单信息
        $error_msg = '';
        $salesItemList = $this->getSaleItemsByOrderIds($orderIds, $error_msg);
        
        //list
        $aftersale_arr = array();
        foreach ($original_aftersales_data['lists'] as $k => $aftersale)
        {
            //订单关联的销售明细信息
            $order_id = $aftersale['order_id'];
            $saleItems = $salesItemList[$order_id];
            
            //format
            $aftersale_arr[$k]['shop_code'] = $this->charFilter($aftersale['shop_bn']);
            $aftersale_arr[$k]['shop_name'] = $this->charFilter($aftersale['shop_name']);
            $aftersale_arr[$k]['order_no'] = $this->charFilter($aftersale['order_bn']);
            $aftersale_arr[$k]['change_order_bn'] = $this->charFilter($aftersale['change_order_bn']);
            $aftersale_arr[$k]['relate_order_bn'] = $this->charFilter($aftersale['relate_order_bn']);
            $aftersale_arr[$k]['order_type'] = $this->charFilter($aftersale['order_type']);
            $aftersale_arr[$k]['order_pay_time'] = $aftersale['order_pay_time'] ? date('Y-m-d H:i:s',$aftersale['order_pay_time']) : '0';
            $aftersale_arr[$k]['ship_time'] = $aftersale['ship_time'] ? date('Y-m-d H:i:s',$aftersale['ship_time']) : '0';
            $aftersale_arr[$k]['ship_province'] = $this->charFilter($aftersale['ship_province']);
            $aftersale_arr[$k]['ship_city'] = $this->charFilter($aftersale['ship_city']);
            $aftersale_arr[$k]['ship_district'] = $this->charFilter($aftersale['ship_district']);
            $aftersale_arr[$k]['ship_addr'] = $this->charFilter($aftersale['ship_addr']);
            $aftersale_arr[$k]['ship_zip'] = $this->charFilter($aftersale['ship_zip']);
            $aftersale_arr[$k]['sale_bn'] = $this->charFilter($aftersale['sale_bn']);
            $aftersale_arr[$k]['platform_order_bn'] = $this->charFilter($aftersale['platform_order_bn']);
            $aftersale_arr[$k]['aftersale_no'] = $this->charFilter($aftersale['aftersale_bn']);
            $aftersale_arr[$k]['aftersale_apply_no'] = (string)$aftersale['return_bn'];
            $aftersale_arr[$k]['return_change_no'] = $this->charFilter($aftersale['reship_bn']);
            $aftersale_arr[$k]['return_logi_no'] =  $this->charFilter((string)$aftersale['return_logi_no']);
            $aftersale_arr[$k]['return_logi_name'] =  $this->charFilter((string)$aftersale['return_logi_name']);
            $aftersale_arr[$k]['refund_apply_no'] =  $aftersale['refund_apply_bn'];
            $aftersale_arr[$k]['aftersale_type'] = $aftersale['aftersale_type'];
            $aftersale_arr[$k]['delivery_mode'] = $aftersale['delivery_mode'];
            $aftersale_arr[$k]['pay_method'] = (string)$aftersale['paymethod'];
            $aftersale_arr[$k]['refund_money'] = $aftersale['refundmoney'];
            $aftersale_arr[$k]['member_name'] = $this->charFilter($aftersale['member_name']);
            $aftersale_arr[$k]['member_mobile'] = $this->charFilter($aftersale['ship_mobile']);
            $aftersale_arr[$k]['check_op'] = (string)$aftersale['check_op_name'];
            $aftersale_arr[$k]['quality_inspection_op'] = (string)$aftersale['op_name'];
            $aftersale_arr[$k]['refund_op'] = $this->charFilter($aftersale['refund_op_name']);
            $aftersale_arr[$k]['apply_time'] = $aftersale['add_time'] ? date('Y-m-d H:i:s',$aftersale['add_time']) : '0';
            $aftersale_arr[$k]['check_time'] = $aftersale['check_time'] ? date('Y-m-d H:i:s',$aftersale['check_time']) : '0';
            $aftersale_arr[$k]['quality_inspection_time'] = $aftersale['acttime'] ? date('Y-m-d H:i:s',$aftersale['acttime']) : '0';
            $aftersale_arr[$k]['refund_time'] = $aftersale['refundtime'] ? date('Y-m-d H:i:s',$aftersale['refundtime']) : '0';
            $aftersale_arr[$k]['aftersale_time'] = $aftersale['aftersale_time'] ? date('Y-m-d H:i:s',$aftersale['aftersale_time']) : '0';
            $aftersale_arr[$k]['settlement_amount'] = isset($aftersale['settlement_amount']) ? $aftersale['settlement_amount'] : '';//结算金额
            $aftersale_arr[$k]['platform_amount']   = isset($aftersale['platform_amount']) ? $aftersale['platform_amount'] : '';//平台承担金额
            $aftersale_arr[$k]['receiving_status'] = $aftersale['receiving_status'];//收货状态
            $aftersale_arr[$k]['up_time'] = $aftersale['up_time'];
            $aftersale_arr[$k]['return_category'] = (string)$aftersale['return_category'];//售后退货分类
            if(isset($aftersale['aftersale_items']) && count($aftersale['aftersale_items']) > 0){
                foreach ($aftersale['aftersale_items'] as $key => $aftersale_item)
                {
                    $product_id = $aftersale_item['product_id'];
                    
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['item_id'] = $aftersale_item['item_id'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['bn'] = $this->charFilter($aftersale_item['bn']);
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['sales_material_bn'] = $this->charFilter($aftersale_item['sales_material_bn']);
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['name'] = $this->charFilter($aftersale_item['product_name']);
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['barcode'] = $aftersale_item['barcode'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['price'] = $aftersale_item['price'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['apply_num'] = $aftersale_item['apply_num'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['nums'] = $aftersale_item['num'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['normal_num'] = $aftersale_item['normal_num'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['defective_num'] = $aftersale_item['defective_num'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['amount'] = $aftersale_item['num']*$aftersale_item['price'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['branch_name'] = $aftersale_item['branch_name'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['branch_bn'] = $aftersale_item['branch_bn'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['apply_money'] = $aftersale_item['money'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['refund_money'] = $aftersale_item['refunded'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['cost'] = $aftersale_item['cost'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['cost_amount'] = $aftersale_item['cost_amount'];
                    
                    //销售金额
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['sale_price'] = $aftersale_item['saleprice'];
                    
                    //商品税率
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['cost_tax'] = $aftersale_item['cost_tax'];
                    
                    //other
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['brand_code'] = (string)$aftersale_item['brand_code']; //物料品牌
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['cat_name'] = (string)$aftersale_item['cat_name']; //物料分类
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['goods_type'] = $aftersale_item['goods_type']; //物料属性
                    
                    //基础物料价格
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['retail_price'] = $aftersale_item['retail_price'];
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['order_item_id'] = $aftersale_item['order_item_id'];//订单明细行ID
                    
                    //通过订单获取销售单上明细金额
                    //@todo：birkenstock勃肯客户的逻辑;
                    $saleItemInfo = $saleItems[$product_id];
                    $getSaleItemRow = $this->getSaleItemRow($saleItemInfo, $aftersale_item);
                    if(isset($getSaleItemRow['sale_price']) && isset($getSaleItemRow['sales_amount'])){
                        //[订单]销售单价price
                        $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['order_price'] = $getSaleItemRow['price'];
                        
                        //[订单]销售价sale_price
                        $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['order_sale_price'] = $getSaleItemRow['sale_price'];
                        
                        //[订单]销售金额amount
                        $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['order_amount'] = $getSaleItemRow['amount'];
                        
                        //[订单]商品优惠价pmt_price
                        $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['order_pmt_price'] = $getSaleItemRow['pmt_price'];
                        
                        //[订单]商品实际成交金额sales_amount
                        $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['order_sales_amount'] = $getSaleItemRow['sales_amount'];
                    }
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['shop_goods_id'] = $aftersale_item['shop_goods_id']; //平台商品ID
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['shop_product_id'] = $aftersale_item['shop_product_id']; //平台SkuID
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['settlement_amount'] = isset($aftersale_item['settlement_amount']) ? $aftersale_item['settlement_amount'] : ''; //结算金额
                    $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['platform_amount']   = isset($aftersale_item['platform_amount']) ? $aftersale_item['platform_amount'] : ''; //平台承担金额
                    if($aftersale_item['batchs']) {
                        foreach($aftersale_item['batchs'] as $batch) {
                            $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['batchs'][] = array(
                                'bn' => $batch['bn'],
                                'nums' => $batch['num'],
                                'batch_code' => $batch['purchase_code'],
                                'product_date' => $batch['product_time'],
                                'expire_date' => $batch['expire_time'],
                                'produce_code' => $batch['produce_code'],
                                'inventory_type' => $batch['normal_defective'] == 'normal' ? 'ZP' : 'CC',
                            );
                        }
                    }
                    if($aftersale_item['props']) {
                        $aftersale_arr[$k]['aftersale_items'][$aftersale_item['item_id']]['props'] = $aftersale_item['props'];
                    }
                }
            }
        }

        unset($original_aftersales_data['lists']);
        $original_aftersales_data['lists'] = $aftersale_arr;

        return $original_aftersales_data;
    }

    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params,&$code,&$sub_msg){
    
    }
    
    /**
     * 通过订单号获取销售单信息
     * 
     * @param $orderIds
     * @param $error_msg
     * @return array
     */
    public function getSaleItemsByOrderIds($orderIds, &$error_msg='')
    {
        $orderObj = app::get('ome')->model('orders');
        
        //check
        if(empty($orderIds)){
            return array();
        }
        
        //销售单信息
        $sql = "SELECT sale_id,order_id FROM sdb_ome_sales WHERE order_id IN(". implode(',', $orderIds) .")";
        $saleList = $orderObj->db->select($sql);
        if(empty($saleList)){
            $error_msg = '没有销售单数据!';
            return array();
        }
        
        //sale_id
        $saleIds = array_column($saleList, 'sale_id');
        $saleOrders = array_column($saleList, null, 'sale_id');
        
        //销售单明细
        $fields = 'item_id,sale_id,product_id,bn,pmt_price,orginal_price,price,sales_amount,sale_price,nums';
        $sql = "SELECT ". $fields ." FROM sdb_ome_sales_items WHERE sale_id IN(". implode(',', $saleIds).")";
        $salesItem = $orderObj->db->select($sql);
        if(empty($salesItem)){
            $error_msg = '没有销售单明细数据!';
            return array();
        }
        
        //items
        $returnItems = array();
        foreach ($salesItem as $key => $val)
        {
            $sale_id = $val['sale_id'];
            $product_id = $val['product_id'];
            $item_obj_type = $val['obj_type']; //货品类型(product普通、pkg捆绑、gift赠品)
            
            //order_id
            $order_id = intval($saleOrders[$sale_id]['order_id']);
            
            //PKG捆绑商品
            if($product_id == 0 || $item_obj_type == 'pkg'){
                $obj_sql = "SELECT obj_id,amount,divide_order_fee FROM sdb_ome_order_objects WHERE order_id=". $order_id ." AND bn='". $val['bn'] ."' AND obj_type='pkg'";
                $orderObjInfo  = $orderObj->db->selectrow($obj_sql);
                $obj_id = $orderObjInfo['obj_id'] ?: '0';
                
                //捆绑商品的货品明细
                $item_sql = "SELECT * FROM sdb_ome_order_items WHERE order_id=". $order_id ." AND obj_id=". $obj_id ." AND `delete`='false'";
                $orderItemList = $orderObj->db->select($item_sql);
                
                //重新计算平摊金额
                $item_list = $this->getItemsAveragePrice($orderObjInfo['amount'], $orderItemList);
                foreach ($item_list as $item_val)
                {
                    $item_product_id = $item_val['product_id'];
                    
                    $returnItems[$order_id][$item_product_id] = $item_val;;
                }
            }else{
                $returnItems[$order_id][$product_id] = $val;
            }
        }
        
        return $returnItems;
    }
    
    /**
     * 获取捆绑类型订单明细中的货品平摊价格
     * 
     * @param decimal $obj_amount 捆绑商品销售金额
     * @param array $item_list 订单明细列表
     * @return array
     */
    function getItemsAveragePrice($obj_amount, $item_list)
    {
        $average_item_price = 0;
        $remain_price = 0;
        $count_items = count($item_list);
        if($obj_amount){
            if($obj_amount > $count_items ){
                $average_item_price = floor($obj_amount / $count_items);
                $remain_price = $obj_amount - $average_item_price * $count_items;
            }else{
                $average_item_price = round($obj_amount / $count_items, 3);
            }
        }
        
        //订单明细列表
        $item_i = 0;
        foreach ($item_list as $key => $item)
        {
            $amount = $price = 0;
            
            //amount
            if($item_i == 0){
                $amount = bcadd($average_item_price, $remain_price, 3);
            }else{
                $amount = $average_item_price;
            }
            
            //price
            if ($item['nums']){
                $price = round($amount / $item['nums'], 3);
            }else{
                $price = $amount;
            }
            
            $item_list[$key]['price'] = $price;
            $item_list[$key]['sales_amount'] = $amount;
            
            $item_i++;
        }
        
        return $item_list;
    }
    
    /**
     * 获取售后单上商品关联销售单金额
     * @todo: 重新赋值sale_price、price、amount三个字段值;并新加sales_amount、pmt_price两个字段值
     * 
     * @param $saleItemInfo
     * @param $aftersaleItemInfo
     * @return array|void
     */
    public function getSaleItemRow($saleItemInfo, $aftersaleItemInfo)
    {
        $result = array();
        
        //check
        if(empty($aftersaleItemInfo)){
            return $result;
        }
        
        //sales_amount销售金额
        if($saleItemInfo['sales_amount']){
            //退货数量不等于购买数量,重新计算需退款金额
            if($saleItemInfo['nums'] != $aftersaleItemInfo['num']){
                //货品平均实际成交金额
                $unit_amount = number_format($saleItemInfo['sales_amount'] / $saleItemInfo['nums'], 3, '.', '');
                
                //货品退货的实际金额
                $result['sales_amount'] = $unit_amount * $aftersaleItemInfo['num'];
            }else{
                $result['sales_amount'] = $saleItemInfo['sales_amount'];
            }
        }
        
        //sale_price销售总价
        if($saleItemInfo['sale_price']){
            if($saleItemInfo['nums'] != $aftersaleItemInfo['num']){
                $unit_sale_price = number_format($saleItemInfo['sale_price'] / $saleItemInfo['nums'], 3, '.', '');
                
                //货品退货的实际金额
                $result['sale_price'] = $unit_sale_price * $aftersaleItemInfo['num'];
            }else {
                $result['sale_price'] = $saleItemInfo['sale_price'];
            }
        }
        
        //取销售单明细上的price价格
        $org_price = $saleItemInfo['price'] ? $saleItemInfo['price'] : $aftersaleItemInfo['price'];
        $result['price'] = $org_price;
        
        //销售总价 = 销售单明细上的price * 退货数量
        $result['amount'] = $aftersaleItemInfo['num'] * $org_price;
        
        //商品优惠价
        $result['pmt_price'] = $saleItemInfo['pmt_price'];
        
        //[兼容]迁移数据时,没有销售单明细,sale_amount=单价*退货数量
        if(empty($saleItemInfo)){
            $result['sales_amount'] = $aftersaleItemInfo['num'] * $org_price;
        }
        
        return $result;
    }
    
    /**
     * 获取JIT售后单
     * @param $params
     * @param $code
     * @param $sub_msg
     * @return array
     * @date 2024-11-14 10:17 上午
     */
    public function getGxList($params, &$code, &$sub_msg)
    {
        $start_modified       = $params['start_time'];
        $end_modified         = $params['end_time'];
        $page_no          = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit            = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        
        $filter = [];
        
        $filter['up_time|betweenstr'] = [$start_modified, $end_modified];
        
        $offset = ($page_no - 1) * $limit;
        
        $originalData = kernel::single('openapi_data_original_aftersales')->getGxList($filter, $offset, $limit);
        
        
        $data = [
            'count' => $originalData['count'],
        ];
        foreach ($originalData['lists'] as $k => $v) {
            
            $lists = [
                'aftersale_bn' => $v['aftersale_bn'],
                'bill_bn' => $v['bill_bn'],
                'bill_type' => $v['bill_type'],
                'shop_bn' => $v['shop_bn'],
                'shop_name' => $v['shop_name'],
                'aftersale_time' => date('Y-m-d H:i:s',$v['aftersale_time']),
                'original_bn' => $v['original_bn'],
                'branch_bn' => $v['branch_bn'],
                'branch_name' => $v['branch_name'],
                'logi_name' => $v['logi_name'],
                'logi_code' => $v['logi_code'],
                'logi_no' => $v['logi_no'],
                'order_bn' => $v['order_bn'],
                'at_time' => $v['at_time'],
                'up_time' => $v['up_time'],
                'total_amount' => $v['total_amount'],
                'settlement_amount' => $v['settlement_amount'],
                'total_sale_price'  => $v['total_sale_price'],
            ];
            
            $items = [];
            foreach ($v['items'] as $item) {
                $items[] = [
                    'item_id' => $item['id'],
                    'material_bn' => $item['material_bn'],
                    'barcode' => $item['barcode'],
                    'material_name' => $this->charFilter($item['material_name']),
                    'nums' => $item['nums'],
                    'price' => $item['price'],
                    'retail_price' => $item['retail_price'],
                    'amount' => $item['amount'],
                    'settlement_amount' => $item['settlement_amount'],
                    'sale_price' => $item['sale_price'],
                    'box_no' => $item['box_no'] ?? '',
                ];
            }
            
            $lists['items'] = $items;
            
            $data['lists'][] = $lists;
        }
        
        return $data;
    }
    
}
