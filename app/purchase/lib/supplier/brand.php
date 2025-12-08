<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 品牌app
 */
class purchase_supplier_brand{
    
    function getBrand($brand_id=null){

        $oBrand = app::get('ome')->model('brand');
        if ($brand_id) $filter = array('brand_id'=>$brand_id);
        $brand_list = $oBrand->getList('brand_id,brand_name',$filter);
        
        return  $brand_list;
        
    }

}
