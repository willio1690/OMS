<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_mdl_delivery_items extends dbeav_model{
    /**
     * 获取DeliveryIdByPbn
     * @param mixed $product_bn product_bn
     * @return mixed 返回结果
     */
    public function getDeliveryIdByPbn($product_bn){
        $sql = 'SELECT count(1) as _c FROM sdb_wms_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >=10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT delivery_id FROM sdb_wms_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }
        $sql = 'SELECT delivery_id FROM sdb_wms_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }
    
    //按条形码搜索
    /**
     * 获取DeliveryIdByPbarcode
     * @param mixed $product_barcode product_barcode
     * @return mixed 返回结果
     */
    public function getDeliveryIdByPbarcode($product_barcode){
        $sql = 'SELECT count(1) as _c FROM sdb_wms_delivery_items as I LEFT JOIN '.
                'sdb_material_codebase as c ON I.product_id=c.bm_id WHERE c.code like \''.addslashes($product_barcode).'%\'';
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >= 10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT delivery_id FROM sdb_wms_delivery_items as I LEFT JOIN '.
                    'sdb_material_codebase as c ON I.product_id=c.bm_id WHERE c.code like \''.addslashes($product_barcode).'%\'';
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }
        
        $sql = 'SELECT delivery_id FROM sdb_wms_delivery_items as I LEFT JOIN '.
                'sdb_material_codebase as c ON I.product_id=c.bm_id WHERE c.code like \''.addslashes($product_barcode).'%\'';
        $rows = $this->db->select($sql);
        
        return $rows;
    }
}
