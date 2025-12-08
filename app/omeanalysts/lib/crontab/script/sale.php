<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_crontab_script_sale
{
    
    function __construct()
    {
        $this->db = kernel::database();
        $shs      = app::get('ome')->model('shop')->getlist('*');
        $shops    = [];
        foreach ($shs as $shop) {
            $shops[$shop['shop_id']] = $shop;
        }
        if (!$shops) die;
        $this->shops = $shops;
    }
    
    function statistics($date = '')
    {
        set_time_limit(0);
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($date && strtotime($date)) {
            $yesterday = date('Y-m-d', strtotime($date));
        }
        
        $start_date = $yesterday . ' 00:00:00';
        // $end_date   = $yesterday . ' 23:59:59';
        $end_date   = date('Y-m-d 00:00:00', strtotime($yesterday . ' +1 day'));
        
        
        //先查询发货销售单 更新时间 算出有哪些天发生了变化
        $sql_delivery_order = "SELECT DISTINCT DATE(FROM_UNIXTIME(sale_time)) AS sale_day FROM sdb_sales_delivery_order_item WHERE up_time BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
        
        $upData   = kernel::database()->select($sql_delivery_order);
        $newDates = array_column($upData, 'sale_day');
        
        if (empty($newDates)) {
            return true;
        }
        
        foreach ($newDates as $kDate) {
            $vTime = strtotime($kDate);
            foreach ($this->shops as $shop) {
                list($res, $msg) = $this->saveStatistics($vTime, $shop['shop_id']);
            }
        }
        
        return true;
    }
    
    /**
     * 保存Statistics
     * @param mixed $time time
     * @param mixed $shopId ID
     * @return mixed 返回操作结果
     */
    public function saveStatistics($time, $shopId)
    {
        if (!$time || !$shopId) {
            return [true, '缺少参数'];
        }
        
        $start_time = $time;
        $end_time   = $time + 86399;
        
        //查询发货销售单
        $sql_sdo = "SELECT sum(nums) delivery_num,sum(settlement_amount) delivery_amount,sum(return_num) delivery_return_num,sum(return_amount) delivery_return_amount
                FROM sdb_sales_delivery_order_item
                WHERE shop_id = '" . $shopId . "' AND sale_time BETWEEN '" . $start_time . "' AND '" . $end_time . "'";
        $sdoInfo = kernel::database()->selectrow($sql_sdo);
        
        //查询售后单
        $sql_aftersale = "SELECT sum(ai.num) return_num,sum(ai.refunded) return_amount
                    FROM sdb_sales_aftersale AS a
                    LEFT JOIN sdb_sales_aftersale_items AS ai ON a.aftersale_id = ai.aftersale_id
                    WHERE shop_id = '" . $shopId . "' AND aftersale_time BETWEEN '" . $start_time . "' AND '" . $end_time . "'";
        $aftersaleInfo = kernel::database()->selectrow($sql_aftersale);
        
        //查询是否已生成报表单据
        $omeSalestatisticsMdl  = app::get('omeanalysts')->model('ome_salestatistics');
        $omeSalestatisticsInfo = $omeSalestatisticsMdl->db_dump(['day' => (string)$time, 'shop_id' => $shopId]);
        $shopInfo              = $this->shops[$shopId] ?? [];
        
        $data = [
            'day'                    => $time,
            'shop_id'                => $shopId,
            'shop_bn'                => $shopInfo['shop_bn'] ?? '',
            'shop_name'              => $shopInfo['name'] ?? '',
            'shop_type'              => $shopInfo['shop_type'] ?? '',
            'delivery_num'           => $sdoInfo['delivery_num'] ?? 0,
            'delivery_amount'        => $sdoInfo['delivery_amount'] ?? 0,
            'delivery_return_num'    => $sdoInfo['delivery_return_num'] ?? 0,
            'delivery_return_amount' => $sdoInfo['delivery_return_amount'] ?? 0,
            'return_num'             => $aftersaleInfo['return_num'] ?? 0,
            'return_amount'          => $aftersaleInfo['return_amount'] ?? 0,
        ];
        //新增
        if (!$omeSalestatisticsInfo) {
            //如果不存在 需要查询订单数量
            $sql_orders = "SELECT sum(oi.nums) order_num,sum(oi.amount) order_amount
                        FROM sdb_ome_orders AS o
                        LEFT JOIN sdb_ome_order_items AS oi ON o.order_id = oi.order_id
                        WHERE o.shop_id = '" . $shopId . "' AND o.createtime BETWEEN '" . $start_time . "' AND '" . $end_time . "'";
            $orderInfo  = kernel::database()->selectrow($sql_orders);
            
            $data['order_num']    = $orderInfo['order_num'] ?? 0;
            $data['order_amount'] = $orderInfo['order_amount'] ?? 0;
            $omeSalestatisticsMdl->insert($data);
        } else {
            //更新
            $omeSalestatisticsMdl->update($data, ['day' => $time, 'shop_id' => $shopId]);
        }
        return [true, '成功'];
        
    }
}
