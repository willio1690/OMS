<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */



/**
 * 供应商关联品牌
 */
class purchase_mdl_supplier_brand extends dbeav_model {

    function saveSupplierBrand($datas){
        
        if ($this->save($datas)) return true;
        else return false;
        
    }
    
}
?>