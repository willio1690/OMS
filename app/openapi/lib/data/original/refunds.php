<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Class openapi_data_original_refunds
 * 退款单获取Lib类
 * todo：退款单会关联获取对应的商品退款金额明细;
 */
class openapi_data_original_refunds
{
    /**
     * 获取List
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */

    public function getList($start_time, $end_time, $offset = 0, $limit = 100)
    {
        if (empty($start_time) || empty($end_time)) {
            return false;
        }
        
        $orderObj = app::get('ome')->model('orders');
        $shopObj  = app::get('ome')->model('shop');
        
        $pay_type_list = array(
            'online'  => '在线支付',
            'offline' => '线下支付',
            'deposit' => '预存款支付',
        );
        
        $status_list = array(
            'succ'     => '支付成功',
            'failed'   => '支付失败',
            'cancel'   => '未支付',
            'error'    => '处理异常',
            'invalid'  => '非法参数',
            'progress' => '处理中',
            'timeout'  => '超时',
            'ready'    => '准备中',
        );
        
        $refund_refer_list = array(
            0 => 'normal', //普通流程产生的退款单
            1 => 'aftersale', //售后流程产生的退款单
            2 => 'archive', //归档售后产生的退款单
        );
        
        //where
        $where = " WHERE a.t_ready >= " . $start_time . " AND a.t_ready <= " . $end_time;
        
        //count
        $sql      = "SELECT count(*) as count FROM sdb_ome_refunds AS a " . $where;
        $countNum = $orderObj->db->selectrow($sql);
        $countNum = $countNum['count'];
        if ($countNum == 0) {
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }
        
        //list
        $sql      = "SELECT a.*, b.apply_id, b.product_data, b.addon FROM sdb_ome_refunds AS a LEFT JOIN sdb_ome_refund_apply AS b ON a.refund_bn=b.refund_apply_bn ";
        $sql      .= $where . " ORDER BY a.t_ready ASC LIMIT " . $offset . "," . $limit;
        $dataList = $orderObj->db->select($sql);
        
        //data
        $result         = array();
        $order_ids      = array();
        $shop_ids       = array();
        $refund_order   = array();
        $refund_product = array();
        $reship_order   = array();
        foreach ($dataList as $key => $val) {
            $refund_id = $val['refund_id'];
            $order_id  = $val['order_id'];
            $shop_id   = $val['shop_id'];
            
            //支付类型名称
            $val['pay_type_name'] = $pay_type_list[$val['pay_type']];
            
            //支付状态名称
            $val['status_name'] = $status_list[$val['status']];
            
            //退款来源名称
            $val['refund_refer'] = $refund_refer_list[$val['refund_refer']];
            
            //collect
            $order_ids[$order_id] = $order_id;
            $shop_ids[$shop_id]   = $shop_id;
            
            //退款商品明细
            $product_data = (empty($val['product_data']) ? '' : unserialize($val['product_data']));
            if ($product_data && is_array($product_data)) {
                $refund_product[$refund_id] = $product_data; //有退款商品明细的(天猫平台)
            } else {
                //退换货单产生的退款单
                $tempData = $val['addon'] ? unserialize($val['addon']) : array();
                if ($tempData['reship_id']) {
                    $reship_order[$refund_id] = $tempData['reship_id']; //退换货产生的退款单
                } else {
                    $refund_order[$refund_id] = $order_id; //没有退款商品明细
                }
            }
            $val['addon'] = $val['addon'] ? unserialize($val['addon']) : '';//序列化数据双引号和json上引号冲突导致解不开优化
            unset($val['apply_id'], $val['product_data']);
            
            $result[$refund_id] = $val;
        }
        
        //order
        $orderList = array();
        $tempList  = $orderObj->getList('order_id, order_bn', array('order_id' => $order_ids));
        foreach ($tempList as $key => $val) {
            $order_id             = $val['order_id'];
            $orderList[$order_id] = $val['order_bn'];
        }
        
        //shop
        $shopList = array();
        $tempList = $shopObj->getList('shop_id, shop_bn, name', array('shop_id' => $shop_ids));
        foreach ($tempList as $key => $val) {
            $shop_id            = $val['shop_id'];
            $shopList[$shop_id] = array('shop_bn' => $val['shop_bn'], 'shop_name' => $val['name']);
        }
        
        //format
        foreach ($result as $refund_id => $val) {
            $order_id = $val['order_id'];
            $shop_id  = $val['shop_id'];
            
            $val['order_bn']   = $orderList[$order_id];
            $val['shop_bn']    = $shopList[$shop_id]['shop_bn'];
            $val['shop_name']  = $shopList[$shop_id]['shop_name'];
            $val['is_archive'] = 0; //是否归档订单
            
            unset($val['order_id'], $val['shop_id'], $val['disabled'], $val['op_id']);
            
            //[兼容]归档订单
            if (empty($val['order_bn'])) {
                $sql               = "SELECT order_bn FROM sdb_archive_orders WHERE order_id='" . $order_id . "'";
                $archiveRow        = $orderObj->db->selectrow($sql);
                $val['order_bn']   = $archiveRow['order_bn'];
                $val['is_archive'] = 1; //归档订单
            }
            
            $result[$refund_id] = $val;
        }
        
        //退款商品明细
        //todo：天猫平台退款申请单中有退款商品明细的情况
        if ($refund_product) {
            $result = $this->format_refund_product($result, $refund_product);
            
            unset($refund_product);
        }
        
        //退换货产生的退款明细
        if ($reship_order) {
            $result = $this->format_reship_order($result, $reship_order, $refund_order);
            
            unset($reship_order);
        }
        
        //退款单读取销售单明细或订单明细
        //todo：前端店铺没有给退款商品明细,取销售单上的商品明细进行均摊退款金额
        if ($refund_order) {
            $result = $this->format_refund_order($result, $refund_order);
            
            unset($refund_order);
        }
        
        //unset
        unset($dataList, $tempList, $orderList, $shopList, $sql);
        
        //return
        return array(
            'lists' => $result,
            'count' => $countNum,
        );
    }
    
    /**
     * 退款单有退款商品明细的
     * 
     * @param array $result
     * @param array $refund_product
     * @return array
     */
    public function format_refund_product($result, $refund_product)
    {
        foreach ($refund_product as $refund_id => $itemList) {
            $refundItem = array();
            foreach ($itemList as $itemKey => $itemVal) {
                $refundItem[] = array(
                    'bn'           => $itemVal['bn'],
                    'name'         => $itemVal['name'],
                    'nums'         => $itemVal['num'], //退款商品数量
                    'refund_price' => $itemVal['price'], //退款商品金额
                    'item_type'    => 'refund_apply',
                );
            }
            
            $result[$refund_id]['items'] = $refundItem;
        }
        
        unset($refundItem);
        
        return $result;
    }
    
    /**
     * 退款单读取销售单明细或订单明细
     * 
     * @param array $result
     * @param array $refund_order
     * @return array
     */
    public function format_refund_order($result, $refund_order)
    {
        $saleObj = app::get('ome')->model('sales');
        
        $saleList  = array();
        $sale_data = array();
        
        //订单关联的销售单列表
        $tempList = $saleObj->getList('sale_id, sale_bn, order_id, sale_amount', array('order_id' => $refund_order));
        foreach ($tempList as $key => $val) {
            $order_id = $val['order_id'];
            
            $saleList[$order_id] = $val;
        }
        
        //退款单关联的销售单信息
        foreach ($refund_order as $refund_id => $order_id) {
            $sale_id = $saleList[$order_id]['sale_id'];
            if (empty($sale_id)) {
                continue;
            }
            
            //[兼容]有销售单则注销掉,防止订单是未发货&&全额退款的情况
            unset($refund_order[$refund_id]);
            
            
            //销售单信息
            $temp_sales             = $saleList[$order_id];
            $temp_sales['sale_num'] = 0; //销售商品总数量
            
            //销售单明细
            $sql      = "SELECT * FROM sdb_ome_sales_items WHERE sale_id=" . $sale_id;
            $tempList = $saleObj->db->select($sql);
            if (empty($tempList)) {
                continue;
            }
            
            foreach ($tempList as $itemKey => $itemVal) {
                $temp_sales['items'][] = array(
                    'sale_item_id' => $itemVal['item_id'],
                    'product_id'   => $itemVal['product_id'],
                    'bn'           => $itemVal['bn'],
                    'name'         => $itemVal['name'],
                    'num'          => $itemVal['nums'],
                    'sales_amount' => $itemVal['sales_amount'], //实际销售金额(去除所有优惠)
                    'item_type'    => 'refund_sales',
                );
                
                //销售商品总数量
                $temp_sales['sale_num'] += $itemVal['nums'];
            }
            
            $sale_data[$refund_id] = $temp_sales;
        }
        
        
        //[兼容]订单是未发货&&全额退款,没有销售单则读取订单明细
        if ($refund_order) {
            //关联的订单明细
            foreach ($refund_order as $refund_id => $order_id) {
                //check
                if ($sale_data[$refund_id]) {
                    continue;
                }
                
                //是否归档订单
                $is_archive = $result[$refund_id]['is_archive'];
                
                //订单总信息
                $temp_sales                 = array('order_id' => $order_id);
                $temp_sales['sales_amount'] = 0; //订单货品总金额
                $temp_sales['sale_num']     = 0; //订单货品总数量
                
                //订单明细
                if ($is_archive) {
                    //归档订单明细
                    $sql = "SELECT item_id, product_id, bn, name, nums, sale_price FROM sdb_archive_order_items WHERE order_id=" . $order_id . " AND `delete`='false'";
                } else {
                    $sql = "SELECT item_id, product_id, bn, name, nums, divide_order_fee, sale_price FROM sdb_ome_order_items WHERE order_id=" . $order_id . " AND `delete`='false'";
                }
                
                $tempList = $saleObj->db->select($sql);
                if (empty($tempList)) {
                    continue;
                }
                
                foreach ($tempList as $itemKey => $itemVal) {
                    if (empty($itemVal['divide_order_fee'])) {
                        $itemVal['divide_order_fee'] = '';
                    }
                    
                    $itemVal['sales_amount'] = ($itemVal['divide_order_fee'] ? $itemVal['divide_order_fee'] : $itemVal['sale_price']);
                    
                    $temp_sales['items'][] = array(
                        'sale_item_id' => $itemVal['item_id'],
                        'product_id'   => $itemVal['product_id'],
                        'bn'           => $itemVal['bn'],
                        'name'         => $itemVal['name'],
                        'num'          => $itemVal['nums'],
                        'sales_amount' => $itemVal['sales_amount'], //实际支付金额(去除所有优惠)
                        'item_type'    => 'refund_order',
                    );
                    
                    //订单货品总金额
                    $temp_sales['sales_amount'] += $itemVal['sales_amount'];
                    
                    //销售商品总数量
                    $temp_sales['sale_num'] += $itemVal['nums'];
                }
                
                $sale_data[$refund_id] = $temp_sales;
            }
        }
        
        
        //均摊退款金额到销售单商品明细上
        foreach ($sale_data as $refund_id => $sale_info) {
            //销售总金额
            $sale_amount = $sale_info['sale_amount'];
            
            //销售单商品总数量
            $sale_num = $sale_info['sale_num'];
            
            //退款金额
            $refund_money = $result[$refund_id]['cur_money'];
            
            /***
             * //退款金额为0元则跳过(正常不会出现这种情况)
             * if(bccomp($refund_money, 0.000, 3) == 0){
             * continue;
             * }
             ***/
            
            //销售明细
            $itemList          = $sale_info['items'];
            $refundItem        = array();
            $item_count        = count($itemList);
            $item_line         = 0;
            $temp_refund_money = 0;
            foreach ($itemList as $itemKey => $itemVal) {
                $item_line++;
                
                $refund_price = 0;
                $item_num     = $itemVal['num'];
                
                if (bccomp($sale_amount, 0.000, 3) == 0) {
                    //[数量]百分比
                    $percent = bcdiv($item_num, $sale_num, 2);
                    
                    //使用数量进行均摊(场景：销售单总金额为0元)
                    $refund_price = $refund_money * $percent;
                } else {
                    //[金额]百分比
                    $percent = bcdiv($itemVal['sales_amount'], $sale_amount, 2);
                    
                    //使用商品的销售金额进行均摊
                    $refund_price = $refund_money * $percent;
                }
                
                //格式化保留2位小数
                $refund_price = bcdiv($refund_price, 1, 2);
                
                //防止金额分不均
                if ($item_count > 1 && $item_count == $item_line) {
                    $refund_price = bcsub($refund_money, $temp_refund_money, 3);
                } else {
                    $temp_refund_money = bcadd($temp_refund_money, $refund_price, 2);
                }
                
                //组织数据
                $refundItem[] = array(
                    'bn'           => $itemVal['bn'],
                    'name'         => $itemVal['name'],
                    'nums'         => $item_num, //退款商品数量
                    'refund_price' => $refund_price, //退款商品金额
                    'item_type'    => $itemVal['item_type'],
                );
            }
            
            $result[$refund_id]['items'] = $refundItem;
        }
        
        unset($tempList, $saleList, $sale_data, $temp_sales, $refundItem);
        
        return $result;
    }
    
    /**
     * 退换货产生的退款明细
     * 
     * @param array $result
     * @param array $reship_order
     * @return array
     */
    public function format_reship_order($result, $reship_order, &$refund_order)
    {
        $reshipItemObj = app::get('ome')->model('reship_items');
        
        //退货明细
        $reship_items = array();
        $tempList     = $reshipItemObj->getList('*', array('reship_id' => $reship_order, 'return_type' => array('return', 'refuse')));
        foreach ($tempList as $key => $val) {
            $reship_id = $val['reship_id'];
            
            $reship_items[$reship_id][] = array(
                'item_id'    => $val['item_id'],
                'reship_id'  => $val['reship_id'],
                'product_id' => $val['product_id'],
                'bn'         => $val['bn'],
                'name'       => $val['product_name'],
                'num'        => $val['num'],
                'price'      => $val['price'],
                'item_type'  => 'reship_order',
            );
        }
        
        //格式化
        $reship_order = array_flip($reship_order);
        foreach ($reship_order as $reship_id => $refund_id) {
            $tempData = $reship_items[$reship_id];
            
            //如果没有退货明细,则后续读取销售单发货明细
            if (empty($tempData)) {
                $refund_order[$refund_id] = $result[$refund_id]['order_id'];
                
                continue;
            }
            
            //reship items
            $refundItem = array();
            foreach ($tempData as $itemKey => $itemVal) {
                //组织数据
                $refundItem[] = array(
                    'bn'           => $itemVal['bn'],
                    'name'         => $itemVal['name'],
                    'nums'         => $itemVal['num'], //退款商品数量
                    'refund_price' => $itemVal['price'], //退款商品金额
                    'item_type'    => $itemVal['item_type'],
                );
            }
            
            $result[$refund_id]['items'] = $refundItem;
        }
        
        unset($tempList, $reship_items, $tempData, $refundItem);
        
        return $result;
    }

    /**
     * 获取DetailList
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getDetailList($start_time, $end_time, $offset = 0, $limit = 100)
    {
        if (empty($start_time) || empty($end_time)) {
            return false;
        }
        
        $orderObj = app::get('ome')->model('orders');
        $shopObj  = app::get('ome')->model('shop');
        
        $pay_type_list = array(
            'online'  => '在线支付',
            'offline' => '线下支付',
            'deposit' => '预存款支付',
        );
        
        $status_list = array(
            'succ'     => '支付成功',
            'failed'   => '支付失败',
            'cancel'   => '未支付',
            'error'    => '处理异常',
            'invalid'  => '非法参数',
            'progress' => '处理中',
            'timeout'  => '超时',
            'ready'    => '准备中',
        );
        
        $refund_refer_list = array(
            0 => 'normal', //普通流程产生的退款单
            1 => 'aftersale', //售后流程产生的退款单
            2 => 'archive', //归档售后产生的退款单
        );
        
        //where
        $where = " WHERE a.t_ready >= " . $start_time . " AND a.t_ready <= " . $end_time;
        
        //count
        $sql      = "SELECT count(*) as count FROM sdb_ome_refunds AS a " . $where;
        $countNum = $orderObj->db->selectrow($sql);
        $countNum = $countNum['count'];
        if ($countNum == 0) {
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }
        
        //list
        $sql      = "SELECT a.* FROM sdb_ome_refunds AS a ";
        $sql      .= $where . " ORDER BY a.t_ready ASC LIMIT " . $offset . "," . $limit;
        $dataList = $orderObj->db->select($sql);
        
        //data
        $result         = array();
        $order_ids      = array();
        $shop_ids       = array();
        $refund_bn      = array();
        foreach ($dataList as $key => $val) {
            $refund_id = $val['refund_id'];
            $order_id  = $val['order_id'];
            $shop_id   = $val['shop_id'];
            
            //支付类型名称
            $val['pay_type_name'] = $pay_type_list[$val['pay_type']];
            
            //支付状态名称
            $val['status_name'] = $status_list[$val['status']];
            
            //退款来源名称
            $val['refund_refer'] = $refund_refer_list[$val['refund_refer']];
            
            //collect
            $order_ids[$order_id] = $order_id;
            $shop_ids[$shop_id]   = $shop_id;
            $refund_bn[$val['refund_bn']] = $val['refund_bn'];
            $result[$refund_id] = $val;
        }
        
        //order
        $orderList = array();
        $tempList  = $orderObj->getList('order_id, order_bn', array('order_id' => $order_ids));
        foreach ($tempList as $key => $val) {
            $order_id             = $val['order_id'];
            $orderList[$order_id] = $val['order_bn'];
        }
        $objs = app::get('ome')->model('order_objects')->getList('order_id, oid, bn', ['order_id' => $order_ids]);
        $objOidBn = [];
        foreach ($objs as $key => $val) {
            $order_id = $val['order_id'];
            $oid = $val['oid'];
            $bn = $val['bn'];
            $objOidBn[$order_id][$oid] = $bn;
        }
        //refunds
        $refundList = app::get('ome')->model('refund_apply')->getList('refund_apply_bn,product_data', ['refund_apply_bn' => $refund_bn]);
        $refundList = array_column($refundList, 'product_data', 'refund_apply_bn');
        //shop
        $shopList = array();
        $tempList = $shopObj->getList('shop_id, shop_bn, name', array('shop_id' => $shop_ids));
        foreach ($tempList as $key => $val) {
            $shop_id            = $val['shop_id'];
            $shopList[$shop_id] = array('shop_bn' => $val['shop_bn'], 'shop_name' => $val['name']);
        }
        
        //format
        $lists = [];
        foreach ($result as $refund_id => $val) {
            $order_id = $val['order_id'];
            $shop_id  = $val['shop_id'];
            
            $val['order_bn']   = $orderList[$order_id];
            $val['is_archive'] = 0; //是否归档订单
            
            unset($val['order_id'], $val['shop_id'], $val['disabled'], $val['op_id']);
            $oidBn = $objOidBn[$order_id];
            //[兼容]归档订单
            if (empty($val['order_bn'])) {
                $sql               = "SELECT order_bn FROM sdb_archive_orders WHERE order_id='" . $order_id . "'";
                $archiveRow        = $orderObj->db->selectrow($sql);
                $val['order_bn']   = $archiveRow['order_bn'];
                $val['is_archive'] = 1; //归档订单
                $oidBn = app::get('ome')->model('order_objects')->getList('oid, bn', ['order_id' => $order_id]);
                $oidBn = array_column($oidBn, 'bn', 'oid');
            }
            //明细
            $product_data = [];
            if($refundList[$val['refund_bn']]) {
                $product_data = unserialize($refundList[$val['refund_bn']]);
            }
            $tmp = [
                'refund_bn' => $val['refund_bn'],
                'order_bn' => $val['order_bn'],
                'shop_bn' => $shopList[$shop_id]['shop_bn'],
                'shop_name' => $shopList[$shop_id]['shop_name'],
                'refund_money' => $val['money'],
                'paymethod' => $val['paymethod'],
                'refund_time' => date('Y-m-d H:i:s', $val['t_ready']),
                'status' => $val['status_name'],
                'trade_no' => (string)$val['trade_no'],
                'refund_refer' => $val['refund_refer'],
                'memo' => $val['memo'],
                'items' => [],
            ];
            foreach($product_data as $pdKey => $pdVal) {
                $tmp['items'][] = [
                    'material_bn' => $pdVal['bn'],
                    'material_name' => $pdVal['name'],
                    'nums' => $pdVal['num'],
                    'price' => $pdVal['price'],
                    'oid' => $pdVal['oid'],
                    'sales_material_bn' => $oidBn[$pdVal['oid']] ? : '',
                ];
            }
            $lists[] = $tmp;
        }
        
        //return
        return array(
            'lists' => $lists,
            'count' => $countNum,
        );
    }
}