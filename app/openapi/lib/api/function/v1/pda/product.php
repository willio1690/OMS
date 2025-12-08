<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_pda_product extends openapi_api_function_v1_pda_abstract {
    #按仓库，货位整理(注意：需要事先在OMS系统中事先添加货位)
    /**
     * position
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function position($params,&$code,&$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $data = kernel::single('openapi_api_function_v1_branch')->position($params,$code,$sub_msg);
        return $data;
    }
    #pda获取货品
    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params,&$code,&$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        $params = array_filter($params);
        $filter = array();

        if (isset($params['brand_name'])) {
            $brandModel = app::get('ome')->model('brand');
            $brand = $brandModel->dump(array('brand_name' => $params['brand_name']));

            if (!$brand) return array('list' => array(),'count' => '0');

            $filter['brand_id'] = $brand['brand_id'];
        }

        if (isset($params['type_name'])) {
            $goodsTypeModel = app::get('ome')->model('goods_type');
            $gtype = $goodsTypeModel->dump(array('name' => $params['type_name']));

            if (!$gtype) return array('list' => array(), 'count' => '0');

            $filter['type_id'] = $gtype['type_id'];
        }

        $basicMaterialObj = app::get('material')->model('basic_material');
        $salesBasicMaterialObj = app::get('material')->model('sales_basic_material');
        if(isset($params['product_bn'])){
            $basicMaterialData = $basicMaterialObj->dump(array('material_bn' => $params['product_bn']), 'bm_id');
            if (empty($basicMaterialData)) {
                return array('list' => array(), 'count' => '0');
            }
            $filter['bm_id'][] = $basicMaterialData['bm_id'];
        }
        if(isset($params['product_barcode'])) {
            $_filter = array();
            if (!empty($filter['bm_id'])) {
                $_filter['bm_id'] = $filter['bm_id'];
            }
            if($params['product_barcode']){
                 $_filter['code'] = $params['product_barcode'];
            }
            $basicMaterialCodeObj = app::get('material')->model('codebase');
            // 要限制code类型吗？ （条码、批次）
            $goods_info = $basicMaterialCodeObj->dump($_filter, 'bm_id');
            if (empty($goods_info)){
                return array('list' => array(), 'count' => '0');
            }
            $filter['bm_id'][] = $goods_info['bm_id'];

        }

        if (isset($params['start_lastmodify'])) $params['start_lastmodify'] = strtotime($params['start_lastmodify']);
        if (isset($params['end_lastmodify'])) $params['end_lastmodify'] = strtotime($params['end_lastmodify']);

        if (isset($params['start_lastmodify']) && isset($params['end_lastmodify'])) {
            $filter['last_modified|between'] = array($params['start_lastmodify'],$params['end_lastmodify']);
        } elseif (isset($params['start_lastmodify']) && !isset($params['end_lastmodify'])) {
            $filter['last_modified|bthan'] = $params['start_lastmodify'];
        } elseif (!isset($params['start_lastmodify']) && isset($params['end_lastmodify'])) {
            $filter['last_modified|sthan'] = $params['end_lastmodify'];
        }

        if (!empty($filter)) {
            $salesBasicMaterialData = $salesBasicMaterialObj->getList('sm_id', $filter);
            if (empty($salesBasicMaterialData)){
                return array('list' => array(), 'count' => '0');
            }

            $filter = array();
            foreach ($salesBasicMaterialData as $salesBasicMaterial) {
                $filter['sm_id'][] = $salesBasicMaterial['sm_id'];
            }
        }

        if (isset($params['goods_bn'])) {
            // goods_bn 作为sales_material_bn 和openapi_data_original_stock保持一致
            // $sales_material = app::get('material')->model('sales_material')->db->select("SELECT bm.bm_id FROM `sdb_material_sales_material` m LEFT JOIN sdb_material_sales_basic_material bm on m.sm_id=bm.sm_id WHERE m.`sales_material_bn` ='" . $params['goods_bn'] . "'");
            // if (empty($sales_material)) {
            //     return array('list' => array(), 'count' => '0');
            // }
            // foreach ($sales_material as $material) {
            //     $filter['bm_id'][] = $material['bm_id'];
            // }
            $filter['sales_material_bn'] = $params['goods_bn'];
        }

        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit   = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 1000 : intval($params['page_size']);

        $data = kernel::single('openapi_data_original_salesmaterial')->getList($filter, ($page_no - 1) * $limit, $limit);
        return $data;
    }
}