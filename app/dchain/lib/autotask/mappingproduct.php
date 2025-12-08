<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class dchain_autotask_mappingproduct
{
    /**
     * 请求同步翱象商品关系
     * @param $params
     * @param $error_msg
     * @return bool
     */
    public function process($params, &$error_msg='')
    {
        $axInventoryLib = kernel::single('dchain_inventorydepth');
        
        //params
        $shop_id = $params['shop_id'];
        $shop_bn = $params['shop_bn'];
        $task_page = $params['task_page'];
        $product_type = $params['product_type'];
        $product_bns = json_decode($params['product_bns'], true);
        
        //check
        if(empty($shop_id) || empty($product_bns)){
            return true;
        }
        
        if(!is_array($product_bns)){
            return true;
        }
        
        //sdfdata
        $sdfdata = array(
                'shop_id' => $shop_id,
                'product_bns' => $product_bns,
        );
        
        //推送平台商品关系
        $result = $axInventoryLib->reqeustMappingProduct($sdfdata);
        
        return true;
    }
}