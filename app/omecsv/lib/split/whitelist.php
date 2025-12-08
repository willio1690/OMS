<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omecsv_split_whitelist
{
    private $bill_type = [
        'normal' => [
            'name'  => '导入任务',//任务名称
            'class' => 'omecsv_split_import',//处理类
        ],
        'platform' => [
            'name'  => '导入任务',//任务名称
            'class' => 'ome_order_import',//平台自发订单导入
        ],
        'iostock' => [
            'name'  => '出入库单导入任务',//任务名称
            'class' => 'taoguaniostockorder_iso_to_import',
        ],
        'order' => [
            'name'  => '导入任务',//任务名称
            'class' => 'ome_order_importV2',//订单导入
        ],
        'material_add' => [
            'name'  => '导入任务',//任务名称
            'class' => 'material_basic_material_importAddV2',//基础物料导入
        ],
        'material_update' => [
            'name'  => '导入任务',//任务名称
            'class' => 'material_basic_material_importUpV2',//基础物料导入
        ],
        'material_props_update' => [
            'name'  => '导入任务',//任务名称
            'class' => 'material_basic_material_importPropsUpV2',//基础物料导入
        ],
        'material_sales_add' => [
            'name'  =>  '导入任务',
            'class' => 'material_sales_material_importAddV2',//销售物料导入
        ],
        'region_relation_import' => [
            'name'  => '地区关联导入任务',
            'class' => 'tongyioil_region_relation_to_import',
        ],
        'goods_price' => [
            'name'  => '经销商品价格导入任务',
            'class' => 'dealer_goods_price_importV2',
        ],
    ];
    
    public function getBillType($type)
    {
        return $this->bill_type[$type];
    }
}
