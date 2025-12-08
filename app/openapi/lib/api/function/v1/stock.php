<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_stock extends openapi_api_function_abstract implements openapi_api_function_interface{

    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params,&$code,&$sub_msg){
    }
    
    /**
     * 获取该货品所有仓库下的库存
     * 
     * @param array $params
     * @param string $code
     * @param string $sub_msg
     * @return array
     */
    public function getAll($params, &$code, &$sub_msg)
    {
        $filter = array();
        if (isset($params['goods_bn'])) {
            $filter['goods_bn'] = $params['goods_bn'];
        }
        
        if (isset($params['product_bn'])) {
            $filter['product_bn'] = $params['product_bn'];
        }
        
        if (isset($params['barcode'])) {
            $filter['barcode'] = $params['barcode'];
        }
        
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $page_size = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        $offset = ($page_no-1) * $page_size;
        
        $data = kernel::single('openapi_data_original_stock')->getBnBranchStore($filter,$offset,$page_size);
        
        return $data;
    }
    
    /**
     * 获取DetailList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getDetailList($params,&$code,&$sub_msg){
        $filter = array();
        $filter['product_bn'] = $params['product_bn'];
        $filter['branch_bn']  = $params['branch_bn'];
        $filter['modified_start']  = $params['modified_start'];
        $filter['modified_end']  = $params['modified_end'];

        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $page_size = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        $offset = ($page_no-1) * $page_size;


        $data = kernel::single('openapi_data_original_stock')->getDetailList($filter,$offset,$page_size);

        return $data;
    }

    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params,&$code,&$sub_msg){
        
    }
    
    /**
     * 批量获取条形码对应的商品总库存和冻结库存
     * todo: barcode为条形码,如果是一次查询多个货品则以,逗号分隔;
     * 
     * @param array $params 条件参数
     * @return array
     */
    public function getBarcodeStock($params, &$code, &$sub_msg){
        
        //check
        if(empty($params['barcodes'])){
            return array(); //没有可查询的barcode,则返回空数组
        }
        $barcode_list = explode(',', $params['barcodes']);
        
        /***
        //限制一次性最多查询50条
        if(count($barcode_list) > 50){
            return array();
        }
        ***/
        
        //filter
        $filter = array();
        $filter['barcode'] = $barcode_list;
        
        //page
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $page_size = intval($params['page_size']) > 0 ? intval($params['page_size']): 100;
        $offset = ($page_no - 1) * $page_size;
        
        //select
        $data = kernel::single('openapi_data_original_stock')->getProductBnStock($filter, $offset, $page_size);
        
        return $data;
    }
    
    /**
     * 批量获取货品对应的总库存信息
     * todo: product_bn为货号,如果是一次查询多个货品则以,逗号分隔;
     * 
     * @param array $params 条件参数
     * @return array
     */
    public function getBnStock($params, &$code, &$sub_msg){
        
        //check
        if(empty($params['product_bns'])){
            return array(); //没有可查询的product_bn,则返回空数组
        }
        $product_list = explode(',', $params['product_bns']);
        
        /***
        //限制一次性最多查询50条
        if(count($product_list) > 50){
            return array();
        }
        ***/
        
        //filter
        $filter = array();
        $filter['material_bn'] = $product_list;
        
        //page
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $page_size = intval($params['page_size']) > 0 ? intval($params['page_size']): 100;
        $offset = ($page_no - 1) * $page_size;
        
        //select
        $data = kernel::single('openapi_data_original_stock')->getProductBnStock($filter, $offset, $page_size);
        
        return $data;
    }
}