<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 物料处理类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:56:04+08:00
 */
class erpapi_front_response_process_o2o_material
{
    /**
     * 门店列表查询
     *
     * @return void
     * @author
     **/

    public function listing($filter)
    {
        $offset = $filter['offset'];
        $limit  = $filter['limit'];

        $list = app::get('o2o')->model('branch_product')->getList('*', $filter, $offset, $limit);
        if (!$list) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $branch_id = array_column($list, 'branch_id');
        $bm_id     = array_column($list, 'bm_id');

        $branchMdl = app::get('ome')->model('branch');
        $bmMdl     = app::get('material')->model('basic_material');
        $bmExtMdl  = app::get('material')->model('basic_material_ext');
        $bpModel       = app::get('ome')->model('branch_product');
        $catMdl    = app::get('material')->model('basic_material_cat');
        $brandMdl  = app::get('ome')->model('brand');

        $branch_list = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => $branch_id, 'check_permission' => 'false'));
        $branch_list = array_column($branch_list, null, 'branch_id');

        $bm_list = $bmMdl->getList('*', array('bm_id' => $bm_id));
        $bm_list = array_column($bm_list, null, 'bm_id');

        $bm_ext_list = $bmExtMdl->getList('*', array('bm_id' => $bm_id));
        $bm_ext_list = array_column($bm_ext_list, null, 'bm_id');

        $cat_id   = array_column($bm_list, 'cat_id');
        $cat_list = $catMdl->getList('*', array('cat_id' => $cat_id ? $cat_id : 0));
        $cat_list = array_column($cat_list, null, 'cat_id');

        $brand_id   = array_column($bm_ext_list, 'brand_id');
        $brand_list = $brandMdl->getList('*', array('brand_id' => $brand_id ? $brand_id : 0));
        $brand_list = array_column($brand_list, null, 'brand_id');

        $pstore_list = array();
        foreach ($bpModel->getList('*', array('branch_id' => $branch_id, 'product_id' => $bm_id)) as $value) {
            $pstore_list[$value['branch_id']][$value['product_id']] = $value;
        }

        $data = array('materials' => array());
        foreach ($list as $l) {
            $bm     = $bm_list[$l['bm_id']];
            $cat    = $cat_list[$bm['cat_id']];
            $bm_ext = $bm_ext_list[$l['bm_id']];
            $brand  = $brand_list[$bm_ext['brand_id']];
            $branch = $branch_list[$l['branch_id']];
            $pstore = $pstore_list[$l['branch_id']][$l['bm_id']];

            $data['materials'][] = array(
                'name'           => $bm['material_name'],
                'bn'             => $bm['material_bn'],
                'spu'            => $bm['material_spu'],
                'type'           => $bm['type'],
                'unit'           => $bm_ext['unit'],
                'specifications' => $bm_ext['specifications'],
                'unit'           => $bm_ext['unit'],
                'cat_name'       => $cat['cat_name'],
                'brand_name'     => $brand['brand_name'],
                'store'          => $pstore['store'],
                'store_freeze'   => $pstore['store_freeze'],
                'share_store'    => $pstore['share_store'],
                'share_freeze'   => $pstore['share_freeze'],
                'last_modified'  => date('Y-m-d H:i:s', $pstore['last_modified']),
                'store_bn'       => $branch['branch_bn'],
                'store_name'     => $branch['name'],
            );
        }

        return array('rsp' => 'succ', 'data' => $data);
    }

    /**
     * 门店列表查询
     *
     * @return void
     * @author
     **/
    public function count($filter)
    {
        $count = app::get('o2o')->model('branch_product')->count($filter);

        return array('rsp' => 'succ', 'data' => array('count' => $count));
    }

    /**
     *
     *
     * @return void
     * @author
     **/
    public function get($filter)
    {
        $bmMdl     = app::get('material')->model('basic_material');
        $bmExtMdl  = app::get('material')->model('basic_material_ext');
        $catMdl    = app::get('material')->model('basic_material_cat');
        $brandMdl  = app::get('ome')->model('brand');
        

        $bm = $bmMdl->db_dump(array('material_bn' => $filter['bn']));
        if (!$bm) {
            return array('rsp' => 'succ', 'data' => array());
        }
        $branch_id = $filter['branch_id'];

        $bm_ext = $bmExtMdl->db_dump(array('bm_id' => $bm['bm_id']));
        $cat    = $catMdl->db_dump(array('cat_id' => $bm['cat_id']));
        $brand  = $brandMdl->db_dump(array('brand_id' => $bm_ext['brand_id']));
       
        $data = array('material' => array(
            'name'           => $bm['material_name'],
            'bn'             => $bm['material_bn'],
            'spu'            => $bm['material_spu'],
            'type'           => $bm['type'],
            'unit'           => $bm_ext['unit'],
            'specifications' => $bm_ext['specifications'],
            'unit'           => $bm_ext['unit'],
            'cat_name'       => $cat['cat_name'],
            'brand_name'     => $brand['brand_name'],
            'store'          => (int) $pstore['store'],
            'store_freeze'   => (int) $pstore['store_freeze'],
            'share_store'    => (int) $pstore['share_store'],
            'share_freeze'   => (int) $pstore['share_freeze'],
        ));

        return array('rsp' => 'succ', 'data' => $data);
    }
}
