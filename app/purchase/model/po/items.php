<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_mdl_po_items extends dbeav_model{

    /**
     * 获取PoIdByPbn
     * @param mixed $product_bn product_bn
     * @return mixed 返回结果
     */
    public function getPoIdByPbn($product_bn){
        $sql = 'SELECT po_id FROM sdb_purchase_po_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }

    /**
     * 获取PoIdByPbarcode
     * @param mixed $product_barcode product_barcode
     * @return mixed 返回结果
     */
    public function getPoIdByPbarcode($product_barcode){
        $sql = 'SELECT po_id FROM sdb_purchase_po_items WHERE barcode like \''.addslashes($product_barcode).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }

}