<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_360buy_response_jdlvmi_goods extends erpapi_shop_response_goods
{
    

    /**
     * 格式化参数
     * 
     * @param array $params
     * @return array
     */
    protected function _formatAddParams($params)
    {
        


        return true;
        $item = is_string($params['item']) ? @json_decode($params['item'], true) : $params['item'];
        
        $data = array();
        $data['iid']  = $item['spGoodsNo'] ? $item['spGoodsNo'] :'';
        $itemcode = $item['itemCode'];

        $sku = array();
        $sku[] = array(

            'sku_id'            =>  $item['skuId'],
            'outer_id'          =>  $itemcode,
            'properties_name'   =>  $item['itemName'],
        );
        
        $data['skus']['sku'] = $sku;
        return $data;
    }
}