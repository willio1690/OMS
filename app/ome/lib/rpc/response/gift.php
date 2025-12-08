<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_rpc_response_gift extends ome_rpc_response
{
    //获取赠品列表CRM
    /**
     * 获取list
     * @param mixed $result result
     * @return mixed 返回结果
     */
    public function getlist($result){
        $offset = $result['page']?($result['page']-1):0;
        $limit = $result['limit']?$result['limit']:500;
        $cols = 'gift_bn,gift_name';
        
        $data = array();
        $giftObj = app::get('crm')->model('gift');
        $i = 0;
        $filter = array('status'=>1);
        while($offset >= $i){
            $gifts = $giftObj->getList($cols,$filter,$offset*$limit,$limit);
            $data = array_merge($data,$gifts);
            $i++;
        }

        $salesMaterialExtObj = app::get('material')->model('sales_material_ext');
        $salesMStockLib = kernel::single('material_sales_material_stock');

        foreach ($data as $k => $giftInfo) {
            $store = $salesMStockLib->getSalesMStockById($giftInfo['product_id']);
            $ExtInfo = $salesMaterialExtObj->dump($giftInfo['product_id'], 'retail_price');

            $data[$k]['gift_num'] = $store;
            $data[$k]['price'] = $ExtInfo['retail_price'] ? $ExtInfo['retail_price'] : 0.00;
            $data[$k]['cost'] = 0.00;
        }

        return $data;
    }
}