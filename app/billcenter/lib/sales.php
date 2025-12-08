<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class billcenter_sales
{
    
    /**
     * 保存至AR表
     *
     *
     * @param int $sale_id
     * @return array
     **/
    public function transferAr($sale_id)
    {
        $saleMdl = app::get('billcenter')->model('sales');
        $sale = $saleMdl->db_dump($sale_id);
        
        if (!$sale) {
            return [false, '唯品会销售单不存在'];
        }
        
        $affect_rows = $saleMdl->update(['in_ar' => '1'] , ['id' => $sale_id, 'in_ar' => '0']);
        if (is_bool($affect_rows)) {
            return [false, '唯品会销售单转AR失败：'.$saleMdl->_columns()['in_ar']['type'][$sale['in_ar']]];
        }
        
        $items = app::get('billcenter')->model('sales_items')->getList('*', ['sale_id'  => $sale_id]);
        
        $sdf                    = [];
        $sdf['trade_time']      = $sale['sale_time']; //销售时间
        $sdf['delivery_time']   = $sale['ship_time']; //发货时间
        $sdf['type']            = kernel::single('finance_iostocksales')->get_sales_type($sale['bill_type']); //业务类型 todo
        $sdf['order_bn']        = $sale['order_bn']; //业务单据bn
        $sdf['relate_order_bn'] = $sale['bill_bn']; //关联订单bn，专指售后换货
        $sdf['channel_id']      = $sale['shop_id']; //渠道ID shop_id todo
        $sdf['channel_name']    = $sale['shop_name']; //渠道名称 shop_name
        $sdf['sale_money']      = $sale['settlement_amount']; //商品成交金额
        $sdf['actually_money']  = $sale['settlement_amount']; //客户实付
        $sdf['money']           = $sale['settlement_amount'] ; //商品成交金额+运费
        $sdf['serial_number']   = $sale['sale_bn']; //默认销售单据号，没有自定义规则  todo
        $sdf['charge_status']   = 1; //默认已记账
        $sdf['charge_time']     = time();
        $sdf['unique_id']       = $sale['sale_bn'];
        
        $arItems = [];
        foreach($items as $item) {
            $arItems[] = [
                'bn' => $item['material_bn'],
                'name' => $item['material_name'],
                'num' => $item['nums'],
                'money' => $item['settlement_amount'],
                'actually_money' => $item['settlement_amount'],
            ];
        }
        
        $sdf['items']           = $arItems;
        
        $res = kernel::single("finance_ar")->do_save($sdf);
        
        if ($res['status'] == 'fail') {
            return [false, $res['msg']];
        }
        
        $affect_rows = $saleMdl->update(['in_ar' => '2'] , ['id' => $sale_id]);
        
        return [true];
    }
}