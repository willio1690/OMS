<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_stock extends erpapi_store_response_abstract
{
    

    /**
     * 
     * @param  $params [参数] method store.stock.listing
     * @return array
     */
    public function listing($params){
        $this->__apilog['title']       = '查询商品库存';
        $this->__apilog['original_bn'] = '';

        if (!$params['material_bn']) {
            //$this->__apilog['result']['msg'] = '缺少物料编码';
            //return false;
        }
        $filter = array();

        if ($params['material_bn']) {
            $material = app::get('material')->model('basic_material')->db_dump(array('material_bn' => $params['material_bn']), 'bm_id');

            $filter['bm_id'] = $material ? $material['bm_id'] : 0;
        }

        if ($params['store_bn']) {
          
            $store_list = app::get('o2o')->model('store')->getList('branch_id', array('store_bn' => $params['store_bn']));
            $branch_id  = array_column($store_list, 'branch_id');

            $filter['branch_id'] = $branch_id;
        }

        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }
        $filter['offset'] = $offset;
        $filter['limit'] = $limit;

        return $filter;
    }

     /**
     * 
     * @param  $params [参数] method store.stock.count
     * @return array
     */
    public function count($params){
        $this->__apilog['title']       = '查询商品库存总数';
        $this->__apilog['original_bn'] = '';

       
        $filter = array('search'=>'all');

        if ($params['material_bn']) {
            $material = app::get('material')->model('basic_material')->db_dump(array('material_bn' => $params['material_bn']), 'bm_id');

            $filter['bm_id'] = $material ? $material['bm_id'] : 0;
        }

        if ($params['store_bn']) {
          
            $store_list = app::get('o2o')->model('store')->getList('branch_id', array('store_bn' => $store_bn));
            $branch_id  = array_column($store_list, 'branch_id');

            $filter['branch_id'] = $branch_id;
        }

        return $filter;
    }
}

?>