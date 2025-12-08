<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_warehouse extends erpapi_store_response_abstract
{
   
    /**
     * 补货时获取商品信息
     * @param 
     * @return 
     */
    public function listing($filter){
        $offset = $filter['offset'];
        $limit  = $filter['limit'];
        $is_store_sale = $filter['is_store_sale'];
        $bpMdl = app::get('ome')->model('branch_product');
        if($is_store_sale==1){//门店可售库存


            $bps = $this->o2olisting($filter);

            $count = $bps['count'];
            $list = $bps['list'];
        }else{
            $count = $bpMdl->count($filter);
            $list = $bpMdl->getList('*', $filter, $offset, $limit);
        }
        
        

        if (!$list) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $branch_id = array_column($list, 'branch_id');
        $bm_id     = array_column($list, 'product_id');

        $branchMdl = app::get('ome')->model('branch');
        $bmMdl     = app::get('material')->model('basic_material');

        $branch_list = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => $branch_id, 'check_permission' => 'false'));
        $branch_list = array_column($branch_list, null, 'branch_id');

        $bm_list = $bmMdl->getList('*', array('bm_id' => $bm_id));
        $bm_list = array_column($bm_list, null, 'bm_id');

        $data = array();
        foreach ($list as $l) {
            $bm     = $bm_list[$l['product_id']];
            $material_bn = $bm['material_bn'];
            $barcode = kernel::single('material_codebase')->getBarcodeBybn($material_bn);
            $branch = $branch_list[$l['branch_id']];
           
            $data[] = array(
                'material_name'  => $bm['material_name'],
                'bn'             => $bm['material_bn'],
                'barcode'        => $barcode,
                'store'          => $l['store']-$l['store_freeze'],
                'store_freeze'   => $l['store_freeze'],
                'last_modified'  => date('Y-m-d H:i:s', $l['last_modified']),
                'branch_bn'      => $branch['branch_bn'],
                'branch_name'    => $branch['name'],
            );
        }

        return array('rsp' => 'succ','data' => array('lists' => $data, 'count' => $count));
    }


    /**
     * o2olisting
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function o2olisting($filter){
        $offset = $filter['offset'];
        $limit  = $filter['limit'];
        $bpMdl = app::get('ome')->model('branch_product');
        $bps = $bpMdl->db->selectrow("SELECT count(id) as _count FROM sdb_ome_branch_product where branch_id=".$filter['branch_id']." and product_id in(select bm_id from sdb_o2o_syncproduct)");

        $count = $bps['_count'];

        $list = $bpMdl->db->select("SELECT * FROM sdb_ome_branch_product where branch_id=".$filter['branch_id']." and product_id in(select bm_id from sdb_o2o_syncproduct) limit ".$offset.",".$limit."");

        return array('list'=>$list,'count'=>$count);



    }

}

?>