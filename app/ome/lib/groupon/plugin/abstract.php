<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 插件接口类
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */

abstract class ome_groupon_plugin_abstract {

    private $_vaild_field = array ('order_bn' => '订单号', 'product_bn' => '销售物料编码', 'cost_item' => '商品总价', 'total_amount' => '订单总价' );
    private $_extend_vaild_field = array ('consignee' => '收货人信息','product_bn' => '货号' );

    public function getPluginName() {

        return $this->_name;
    }

    /**
     * 处理导入到原始数据
     *
     * @param array $data 原始数据
     * @return Array
     */
    public function process($data, $post) {
        $return = $this->convertToSdf ( $data, $post );
        if($return['rsp'] == 'fail'){
            return $return;
        }
        $orderSdfs = $return['data'];

        $num = 0;
        $groupon_id = 0;
        foreach ( $orderSdfs as $order ) {
            if(!$this->import ( $order)){
                return kernel::single ( 'ome_func' )->getErrorApiResponse ( '导入时发现订单号:' . $order['order_bn'] . ' 创建失败,系统已经存在此订单！在订单'.$order['order_bn'].'之前的订单导入成功，之后的订单需要重新导入。' );
            }

            if ($post ['is_pay'] == 'yes') {
                $payment_sdf = $this->getPaySdf($order,$post);
                $this->doPay ( $payment_sdf );
            }

            if($order['order_id']>0){
                $num++;
                if($num==1){
                    $groupon_id = $this->createOrderGroupon($order,$post);
                    $this->createOrderGrouponItem($groupon_id, $order['order_id']);
                }elseif($num>1 && $groupon_id>0){
                    $this->createOrderGrouponItem($groupon_id, $order['order_id']);
                }
            }
            unset($order);
        }

        return array('rsp'=>'succ');
    }

    public function import(& $orderSdf) {
        $mdl = app::get ( 'ome' )->model ( 'orders' );
        if ($mdl->create_order ( $orderSdf )) {
            return true;
        } else {
            return false;
        }
    }

    public function createOrderGroupon($order_sdf, $post) {
        $mdl_order_groupon = app::get('ome')->model('order_groupon');
        $sdf = array (
            'name' => $post ['groupon_name'],
            'shop_id' => $post ['shop_id'],
            'create_time' => time (),
            'opt_id' => kernel::single ( 'desktop_user' )->get_id (),
            'opt_name' => kernel::single ( 'desktop_user' )->get_name (),
            'org_id' => $order_sdf['org_id'],
        );
        $groupon_id = $mdl_order_groupon->insert ( $sdf );

        return $groupon_id;
    }

    public function createOrderGrouponItem($groupon_id, $order_id) {
        $mdl_order_groupon_items = app::get ('ome')->model('order_groupon_items');
        $order_groupon_items_sdf = array (
            'order_groupon_id' => $groupon_id,
            'order_id' => $order_id
        );
        $item_id = $mdl_order_groupon_items->insert($order_groupon_items_sdf);

        return $item_id;
    }
    
    /**
     * 验证订单数据的完整性
     */
    private function validateOrderData($orderData, &$msg) {
        // 检查是否有重复的商品编码
        $productBns = array();
        foreach ($orderData['products'] as $product) {
            $productBn = $product['product_bn'];
            if (empty($productBn)) {
                $msg = '第' . $product['row_index'] . '行：商品编码为空';
                return false;
            }
            
            if (in_array($productBn, $productBns)) {
                $msg = '第' . $product['row_index'] . '行：商品编码 ' . $productBn . ' 重复';
                return false;
            }
            
            $productBns[] = $productBn;
        }
        
        // 检查商品数量是否大于0
        /*
        foreach ($orderData['products'] as $product) {
            if ($product['product_nums'] <= 0) {
                $msg = '第' . $product['row_index'] . '行：商品数量必须大于0';
                return false;
            }
            
            if ($product['product_price'] < 0) {
                $msg = '第' . $product['row_index'] . '行：商品单价不能为负数';
                return false;
            }
        }
        */
        
        return true;
    }

    public function convertToSdf($data, $post) {
        $orderGroups = array(); // 按订单号分组的订单数据
        $msgList = array();     // 错误信息列表
        $currentOrderBn = '';   // 当前处理的订单号
        
        // 第一步：按订单号分组数据，支持多行订单
        foreach ($data as $k => $row) {
            $orderBn = trim($row[0]); // 第一列是订单号
            
            if (!empty($orderBn)) {
                // 检查是否是新的订单号（与当前订单号不同）
                if ($orderBn !== $currentOrderBn) {
                    // 新订单，创建新组
                    $currentOrderBn = $orderBn;
                    $orderGroups[$currentOrderBn] = array(
                        'order_info' => $row,      // 完整订单信息
                        'products' => array(),     // 商品列表
                        'row_index' => $k + 1     // 行号（用于错误提示）
                    );
                } else {
                    // 订单号相同，视为追加商品到当前订单
                    // 但需要检查这一行是否包含商品信息
                    if (!empty($row[14]) || !empty($row[15]) || !empty($row[16])) {
                        // 包含商品信息，添加到当前订单的商品列表
                        $orderGroups[$currentOrderBn]['products'][] = array(
                            'product_bn' => $row[14],      // 销售物料编码
                            'product_nums' => $row[15],    // 数量
                            'product_price' => $row[16],   // 单价
                            'row_index' => $k + 1         // 行号
                        );
                    } else {
                        // 不包含商品信息，可能是空行或标题行，跳过
                        continue;
                    }
                }
            } else {
                // 第一列为空，视为追加商品到当前订单
                if (empty($currentOrderBn)) {
                    $msgList[] = '第' . ($k + 1) . '行：订单号为空，且前面没有有效的订单信息';
                    continue;
                }
                
                // 添加到当前订单的商品列表
                $orderGroups[$currentOrderBn]['products'][] = array(
                    'product_bn' => $row[14],      // 销售物料编码
                    'product_nums' => $row[15],    // 数量
                    'product_price' => $row[16],   // 单价
                    'row_index' => $k + 1         // 行号
                );
            }
        }
        
        // 第二步：验证追加商品数据的完整性
        foreach ($orderGroups as $orderBn => $orderData) {
            if (!empty($orderData['products'])) {
                $msg = '';
                if (!$this->validateOrderData($orderData, $msg)) {
                    $msgList[] = '订单号:' . $orderBn . ' 追加商品验证失败: ' . $msg;
                }
            }
        }
        
        // 第三步：转换为SDF格式，处理多商品
        $orderSdfs = array();
        foreach ($orderGroups as $orderBn => $orderData) {
            // 先验证主订单行数据（保持原有验证逻辑）
            $row_sdf = $this->convertToRowSdf($orderData['order_info'], $post);
            $msg = '';
            if (!$this->vaildRowSdf($row_sdf, $msg)) {
                $msgList[] = '订单号:' . $row_sdf['order_bn'] . ' 主订单验证失败: ' . $msg;
                continue;
            }
            
            $orderSdf = $row_sdf;
            $orderObjectItem = 0;
            
            // 处理主订单的商品（第一行）
            $productBns = array();
            if (!empty($orderSdf['product_bn'])) {
                $productBns[] = array(
                    'bn' => $orderSdf['product_bn'],
                    'number' => $orderSdf['product_nums'],
                    'price' => $orderSdf['product_price']
                );
            }
            
            // 验证追加的商品（后续行）
            $hasValidationError = false;
            foreach ($orderData['products'] as $product) {
                // 构造临时的row_sdf用于验证
                $temp_row_sdf = array(
                    'product_bn' => $product['product_bn'],
                    'product_nums' => $product['product_nums'],
                    'product_price' => $product['product_price']
                );
                
                // 调用vaildProduct_bn方法验证追加商品
                $msg = '';
                if (!$this->vaildProduct_bn($temp_row_sdf, $msg)) {
                    $msgList[] = '订单号:' . $orderBn . ' 第' . $product['row_index'] . '行追加商品验证失败: ' . $msg;
                    $hasValidationError = true;
                    continue; // 跳过这个商品，继续验证下一个
                }
                
                $productBns[] = array(
                    'bn' => $product['product_bn'],
                    'number' => $product['product_nums'],
                    'price' => $product['product_price']
                );
            }
            
            // 如果追加商品验证有错误，跳过整个订单的处理
            if ($hasValidationError) {
                continue;
            }

            $salesMLib = kernel::single('material_sales_material');
            $lib_ome_order = kernel::single('ome_order');
            foreach($productBns as $item){
                $salesMInfo = $salesMLib->getSalesMByBn($post['shop_id'],$item['bn']);
                if($salesMInfo){
                    //获取绑定的基础物料
                    if($salesMInfo['sales_material_type'] == 4){ //福袋
                        $basicMInfos = $salesMLib->get_order_luckybag_bminfo($salesMInfo['sm_id']);
                    }elseif($salesMInfo['sales_material_type'] == 5){ //多选一
                        $basicMInfos = $salesMLib->get_order_pickone_bminfo($salesMInfo['sm_id'],$item['number'],$post['shop_id']);
                    }else{
                        $basicMInfos = $salesMLib->getBasicMBySalesMId($salesMInfo['sm_id']);
                    }
                    if($basicMInfos){
                        $obj_number = $item['number'];
                        //如果是促销类销售物料
                        if($salesMInfo['sales_material_type'] == 2){
                            $obj_type = $item_type = 'pkg';
                            $obj_sale_price = $item['price']*$obj_number;
                            //item层关联基础物料平摊销售价
                            $salesMLib->calProSaleMPriceByRate($obj_sale_price, $basicMInfos);
                            $return_arr_info = $lib_ome_order->format_order_items_data($item_type,$obj_number,$basicMInfos);
                        }elseif($salesMInfo['sales_material_type'] == 4){ //福袋
                            $obj_type = $item_type = 'lkb';
                            $return_arr_info = $lib_ome_order->format_order_items_data($item_type,$obj_number,$basicMInfos);
                        }elseif($salesMInfo['sales_material_type'] == 5){ //多选一
                            $obj_type = $item_type = 'pko';
                            foreach($basicMInfos as &$var_basic_info){
                                $var_basic_info["price"] = $item['price'];
                                $var_basic_info["sale_price"] = $item['price'];
                            }
                            unset($var_basic_info);
                            $return_arr_info = $lib_ome_order->format_order_items_data($item_type,$obj_number,$basicMInfos);
                        }else{
                            $obj_type = ($salesMInfo['sales_material_type'] == 1) ? 'goods' : 'gift';
                            $item_type = ($obj_type == 'goods') ? 'product' : 'gift';
                            if($obj_type == 'gift'){
                                $row['product_price'] = 0.00;
                            }
                            foreach($basicMInfos as &$var_basic_info){
                                $var_basic_info["price"] = $item['price'];
                                $var_basic_info["sale_price"] = $item['price'];
                            }
                            unset($var_basic_info);
                            $return_arr_info = $lib_ome_order->format_order_items_data($item_type,$obj_number,$basicMInfos);
                        }

                        $orderSdf['order_objects'][] = array(
                            'obj_type' => $obj_type,
                            'obj_alias' => $obj_type,
                            'goods_id' => $salesMInfo['sm_id'],
                            'bn' => $salesMInfo['sales_material_bn'],
                            'name' => $salesMInfo['sales_material_name'],
                            'price' => $item['price'],
                            'sale_price'=>$item['price']*$obj_number,
                            'amount' => $item['price']*$obj_number,
                            'quantity' => $obj_number,
                            'order_items' => $return_arr_info["order_items"],
                            'weight' => $return_arr_info["weight"],
                        );

                        //团购订单特殊直接单价*数量合计cost_item
                        //$item_cost += $row['product_price']*$obj_number;
                        unset($order_items);
                    }
                }
            }
            
            // 重新计算多商品订单的cost_item、total_amount和weight
            $total_cost_item = 0;
            $total_weight = 0;
            foreach ($orderSdf['order_objects'] as $obj) {
                $total_cost_item += $obj['amount']; // amount已经是price * quantity
                // 累加商品重量
                if (isset($obj['weight']) && $obj['weight'] > 0) {
                    $total_weight += $obj['weight'];
                }
            }
            $orderSdf['cost_item'] = $total_cost_item;
            $orderSdf['total_amount'] = $total_cost_item + $orderSdf['shipping']['cost_shipping'];
            $orderSdf["weight"] = $total_weight; // 使用累加的总重量
            
            //处理店铺信息
            $shop = app::get ( 'ome' )->model ( 'shop' )->dump ( array ('shop_id' => $post ['shop_id'] ) );
            $orderSdf ['shop_id'] = $shop ['shop_id'];
            $orderSdf ['shop_type'] = $shop ['shop_type'];
            $orderSdf ['createtime'] = time ();
            $orderSdf ['consignee'] ['area'] = $orderSdf ['consignee'] ['area'] ['province'] . "/" . $orderSdf ['consignee'] ['area'] ['city'] . "/" . $orderSdf ['consignee'] ['area'] ['county'];
            $orderSdf ['shipping'] ['is_cod'] = $orderSdf ['shipping'] ['is_cod'] ? strtolower ( $orderSdf ['shipping'] ['is_cod'] ) : 'false';
            $orderSdf ['is_tax'] = $orderSdf ['is_tax'] ? strtolower ( $orderSdf ['is_tax'] ) : 'false';
            $orderSdf ['cost_tax'] = $orderSdf ['cost_tax'] ? $orderSdf ['cost_tax'] : '0';
            $orderSdf ['discount'] = $orderSdf ['discount'] ? $orderSdf ['discount'] : '0';
            $orderSdf ['score_g'] = $orderSdf ['score_g'] ? $orderSdf ['score_g'] : '0';
            // cost_item和total_amount已经在上面重新计算过了，这里不需要重新赋值
            $orderSdf ['pmt_order'] = $orderSdf ['pmt_order'] ? $orderSdf ['pmt_order'] : '0';
            $orderSdf ['pmt_goods'] = $orderSdf ['pmt_goods'] ? $orderSdf ['pmt_goods'] : '0';
            $orderSdf ['custom_mark'] = kernel::single ( 'ome_func' )->append_memo ( $orderSdf ['custom_mark'] );
            $orderSdf ['mark_text'] = kernel::single ( 'ome_func' )->append_memo ( $orderSdf ['mark_text'] );
            $orderSdf ['order_source'] = 'groupon';
            $orderSdf ['source'] = 'local';
            $orderSdf['createway'] = 'import';

            $orderSdfs [] = $orderSdf;
            unset($productBns,$orderSdf);
        }
        
        // 第四步：统一处理所有验证错误
        if (count($msgList) > 0) {
            $errorMsg[] = "数据验证失败，共发现 " . count($msgList) . " 个错误：";
            foreach($msgList as $key=>$val){
                $errorMsg[] = ($key+1) . "、" . $val;
            }
            return kernel::single('ome_func')->getErrorApiResponse($errorMsg);
        }

        return kernel::single ( 'ome_func' )->getApiResponse ( $orderSdfs );
    }

    public function doPay($payment_sdf) {
        $orderObj = app::get ( 'ome' )->model ( 'orders' );
        $paymentObj = app::get ( 'ome' )->model ( 'payments' );
        $paymentObj->create_payments ( $payment_sdf );

        // 更新订单的支付方式
        $orderObj->update ( array ('payment' => $payment_sdf ['paymethod'] ), array ('order_id' => $payment_sdf ['order_id'] ) );
    }

    public function getPaySdf($order_sdf, $post) {
        $payment_money = $order_sdf ['total_amount'];
        $cur_money = $payment_money ? $payment_money : '0';
        $paymentObj = app::get ( 'ome' )->model ( 'payments' );
        $payment_bn = $paymentObj->gen_id ();
        $paymentCfgObj = app::get ( 'ome' )->model ( 'payment_cfg' );
        $cfg = $paymentCfgObj->dump ( $_POST ['payment'] );
        $sdf = array ('payment_bn' => $payment_bn, 'shop_id' => $order_sdf ['shop_id'], 'order_id' => $order_sdf ['order_id'], 'account' => $post ['account'], 'bank' => $post ['bank'], 'pay_account' => $post ['pay_account'], 'currency' => $order_sdf ['currency'] ? $order_sdf ['currency'] : 'CNY', 'money' => $payment_money ? $payment_money : '0', 'paycost' => $order_sdf ['paycost'] ? $order_sdf ['paycost'] : 0, 'cur_money' => $cur_money, 'pay_type' => $post ['pay_type'] ? $post ['pay_type'] : 'online', 'payment' => $post ['payment'], 'pay_bn' => '', 'paymethod' => $cfg ['custom_name'], 't_begin' => time (), 'download_time' => time (), 't_end' => time (), 'status' => 'succ', 'memo' => $post ['memo'], 'is_orderupdate' => 'true', 'trade_no' => '' );

        return $sdf;
    }

    public function vaildRowSdf(& $row_sdf, & $msg) {
        foreach ( $this->_vaild_field as $field => $des ) {
            // 对于数值字段，允许0值；对于字符串字段，不允许空值
            if (! isset ( $row_sdf [$field] )) {
                $msg = $des . '为空!';
                return false;
            }
            
            // 对于cost_item和total_amount，允许0值，但不允许负值
            if (in_array($field, array('cost_item', 'total_amount'))) {
                if (!is_numeric($row_sdf[$field]) || $row_sdf[$field] < 0) {
                    $msg = $des . '必须是非负数!';
                    return false;
                }
            } else {
                // 对于其他字段，使用原来的empty检查
                if (empty ( $row_sdf [$field] )) {
                    $msg = $des . '为空!';
                    return false;
                }
            }
        }

        foreach ( $this->_extend_vaild_field as $field => $des ) {
            $method = 'vaild' . ucfirst ( $field );
            if (method_exists ( $this, $method )) {
                if (! $this->{$method} ( $row_sdf, $msg )) {
                    $msg = $des . ' ' . $msg;
                    return false;
                }
            }
        }

        return true;
    }

    public function vaildConsignee(& $row_sdf, & $msg) {
        $list = array ();
        $consignee_area_list = array ('province', 'city', 'county' );
        $consignee_list = array ('name', 'addr' );

        /*foreach($consignee_area_list as $k=>$col){
            if(!isset($row_sdf['consignee']['area'][$col]) || empty($row_sdf['consignee']['area'][$col])){
                $msg = $col . '为空!';
                return false;
            }
        }*/

        foreach ( $consignee_list as $k => $col ) {
            if (! isset ( $row_sdf ['consignee'] [$col] ) || empty ( $row_sdf ['consignee'] [$col] )) {
                $msg = $col . '为空!';
                return false;
            }
        }

        $regionLib = kernel::single('eccommon_regions');
        if (is_array ( $row_sdf ['area'] )) {
            foreach ( $row_sdf ['area'] as $k => $v ) {
                $row = $regionLib->getOneByName($v);
                if ($row) {
                    $list [] = $row;
                } else {
                    $list [] = '';
                }
            }
        }

        foreach ( $consignee_area_list as $k => $col ) {
            $row_sdf ['area'] [$col] = $list [$k];
        }

        return true;
    }

    public function vaildProduct_bn(& $row_sdf, & $msg) {
        $post = $_POST;
        // if( preg_match_all('/:\d{1,}\/$/is', $row_sdf['product_bn'],$mathes)){
        //     $products = explode('/',$row_sdf['product_bn']);
        if( preg_match_all('/\W*;\W*/is', $row_sdf['product_bn'],$mathes)){
            $products = explode(';',$row_sdf['product_bn']);
            foreach($products as $key=>$val){
                $product = explode(':',$val);
                if($product[1]>0){
                    $productBns[$key]['bn'] = $product[0];
                    $productBns[$key]['number'] = $product[1];
                    
                    //价格
                    $productBns[$key]['price'] = $product[2];
                }
            }
        }else{
            $tmp = explode(':', $row_sdf['product_bn']);
            $productBns[0]['bn'] = $tmp[0];
            $productBns[0]['number'] = $row_sdf['product_nums'];
            
            //价格
            $productBns[0]['price'] = $row_sdf['product_price'];
        }

        $salesMLib = kernel::single('material_sales_material');
        foreach($productBns as $item){
            $salesMInfo = $salesMLib->getSalesMByBn($post['shop_id'],$item['bn']);
            if($salesMInfo){
                //获取绑定的基础物料
                if($salesMInfo["sales_material_type"] == 4){ //福袋
                    $basicMInfos = $salesMLib->get_order_luckybag_bminfo($salesMInfo['sm_id']);
                }elseif($salesMInfo["sales_material_type"] == 5){ //多选一
                    $basicMInfos = $salesMLib->get_order_pickone_bminfo($salesMInfo['sm_id'],"1",$post['shop_id']);
                }else{
                    $basicMInfos = $salesMLib->getBasicMBySalesMId($salesMInfo['sm_id']);
                }
                if(!$basicMInfos){
                    $msg = '销售物料货号:'.$item['bn']."没有绑定基础物料";
                    return false;
                }
            }else{
                $msg = '销售物料货号:'.$item['bn']."不存在";
                return false;
            }
            
            //判断价格是否填写
            if($item['price'] === ''){
                $msg = '销售物料编码:'.$item['bn']." 没有设置价格";
                return false;
            }

            if($item['number'] === '' || $item['number'] <= 0){
                $msg = '销售物料编码:'.$item['bn']." 没有设置数量";
                return false;
            }
        }
        return true;
    }
}