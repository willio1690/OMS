<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class billcenter_aftersales
{
    /**
     * 保存至AR表
     * @param $aftersale_id
     * @return array|bool[]
     * @date 2024-11-06 6:08 下午
     */
    public function transferAr($aftersale_id)
    {
        $aftersalesMdl = app::get('billcenter')->model('aftersales');
        $aftersale = $aftersalesMdl->db_dump($aftersale_id);
        
        if (!$aftersale) {
            return [false, '唯品会售后单不存在'];
        }
        
        $shopInfo       = app::get('ome')->model('shop')->db_dump(['shop_id' => $aftersale['shop_id']], 'shop_id,config');
        $shop['config'] = (array)@unserialize($shopInfo['config']);
        if (!isset($shop['config']['aftersales_ar_auto']) || (isset($shop['config']['aftersales_ar_auto']) && $shop['config']['aftersales_ar_auto'] == 'no')) {
            return [false, '前端店铺下店铺配置项中，同步账期未开启！'];
        }
        
        $affect_rows = $aftersalesMdl->update(['in_ar' => '1'] , ['id' => $aftersale_id, 'in_ar' => '0']);
        if (is_bool($affect_rows)) {
            return [false, '唯品会售后单转AR失败：'.$aftersalesMdl->_columns()['in_ar']['type'][$aftersale['in_ar']]];
        }
        
        $items = app::get('billcenter')->model('aftersales_items')->getList('*', ['aftersale_id'  => $aftersale_id]);
        
        $sdf                    = [];
        $sdf['trade_time']      = $aftersale['aftersale_time']; //售后时间
        $sdf['delivery_time']   = strtotime($aftersale['at_time']); //收货时间
        $sdf['type']            = kernel::single('finance_iostocksales')->get_sales_type($aftersale['bill_type']); //业务类型 todo
        $sdf['order_bn']        = $aftersale['order_bn']; //业务单据bn
        $sdf['relate_order_bn'] = $aftersale['bill_bn']; //关联订单bn，专指售后换货
        $sdf['channel_id']      = $aftersale['shop_id']; //渠道ID shop_id todo
        $sdf['channel_name']    = $aftersale['shop_name']; //渠道名称 shop_name
        $sdf['sale_money']      = $aftersale['settlement_amount']; //商品成交金额
        $sdf['actually_money']  = $aftersale['settlement_amount']; //客户实付
        $sdf['money']           = $aftersale['settlement_amount'] ; //商品成交金额+运费
        $sdf['serial_number']   = $aftersale['aftersale_bn']; //默认售后单据号，没有自定义规则  todo
        $sdf['charge_status']   = 1; //默认已记账
        $sdf['charge_time']     = time();
        $sdf['unique_id']       = $aftersale['aftersale_bn'];
        
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
        
        $affect_rows = $aftersalesMdl->update(['in_ar' => '2'] , ['id' => $aftersale_id]);
        
        return [true];
    }
}