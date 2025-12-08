<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 基础物料处理类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:56:04+08:00
 */
class erpapi_front_response_process_material
{
    /**
     *
     *
     * @return void
     * @author
     **/

    public function get($filter)
    {
        $bmMdl    = app::get('material')->model('basic_material');
        $bmExtMdl = app::get('material')->model('basic_material_ext');
        $catMdl   = app::get('material')->model('basic_material_cat');
        $brandMdl = app::get('ome')->model('brand');

        $bm = $bmMdl->db_dump(array('material_bn' => $filter['bn']));
        if (!$bm) {
            return array('rsp' => 'succ', 'data' => array());
        }

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
        ));

        return array('rsp' => 'succ', 'data' => $data);
    }

}
