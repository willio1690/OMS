<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoguanallocate_mdl_appropriation_items extends dbeav_model{

    /**
     * 获取OrderIdByPbn
     * @param mixed $product_bn product_bn
     * @return mixed 返回结果
     */
    public function getOrderIdByPbn($product_bn){
        $sql = 'SELECT appropriation_id FROM sdb_taoguanallocate_appropriation_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }

    /**
     * 获取OrderIdByPbarcode
     * @param mixed $product_barcode product_barcode
     * @return mixed 返回结果
     */
    public function getOrderIdByPbarcode($product_barcode)
    {
        $sql = "SELECT I.appropriation_id 
                FROM sdb_taoguanallocate_appropriation_items as I 
                LEFT JOIN sdb_material_codebase as c ON I.product_id=c.bm_id 
                WHERE c.code like '". addslashes($product_barcode) ."%' AND c.type=1";
        $rows = $this->db->select($sql);
        return $rows;
    }
   
}