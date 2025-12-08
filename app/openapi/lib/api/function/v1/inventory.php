<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/8/26
 * @Describe: 获取盘点单列表
 */
class openapi_api_function_v1_inventory extends openapi_api_function_abstract implements openapi_api_function_interface
{
    
    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */

    public function getList($params, &$code, &$sub_msg)
    {
        $start_time = strtotime($params['start_time']);
        $end_time   = strtotime($params['end_time']);
        $page_no    = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit      = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        
        //盘点单号
        if ($params['inventory_bn']) {
            $filter['inventory_bn'] = trim($params['inventory_bn']);
            $filter['inventory_bn'] = str_replace(array('"', "'"), '', $filter['inventory_bn']);
        }
        //仓库编码
        if ($params['branch_bn']) {
            $filter['branch_bn'] = trim($params['branch_bn']);
            $filter['branch_bn'] = str_replace(array('"', "'"), '', $filter['branch_bn']);
        }
        
        //page
        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }
        
        $original_sales_data = kernel::single('openapi_data_original_inventory')->getList($filter, $start_time, $end_time, $offset, $limit);
        
        return $original_sales_data;
    }
    
    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params, &$code, &$sub_msg)
    {
    }

    /**
     * 获取ApplyDetail
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getApplyDetail($params, &$code, &$sub_msg)
    {
        $page_no    = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit      = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        $offset = ($page_no - 1) * $limit;

        
        return kernel::single('openapi_data_original_inventory')->getApplyDetail($params['inventory_apply_bn'], $offset, $limit);
    }

    /**
     * 获取ApplyList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getApplyList($params, &$code, &$sub_msg)
    {
        $start_time = $params['start_time'];
        $end_time   = $params['end_time'];
        $page_no    = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit      = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        $filter = [
            'up_time|betweenstr' => [$start_time, $end_time],
        ];
        
        // 盘点申请单号
        if ($params['inventory_apply_bn']) {
            $filter['inventory_apply_bn'] = $params['inventory_apply_bn'];
        }


        //仓库编码
        if ($params['status']) {
            $filter['status'] = $params['status'];
        }

        $offset = ($page_no - 1) * $limit;

        return kernel::single('openapi_data_original_inventory')->getApplyList($filter, $offset, $limit);
    }
    /**
     * 获取ShopSkuList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getShopSkuList($params,&$code,&$sub_msg){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $start_time = $params['start_time'];
        $end_time = $params['end_time'];
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        $offset = ($page_no - 1) * $limit;
        $filter = [];
    
        if ($start_time && $end_time) {
            $filter['up_time|betweenstr'] = [$start_time, $end_time];
        }
    
        if (!$filter['up_time|betweenstr']){
            $sub_msg = '更新时间必填';
            return false;
        }
        if ($params['shop_product_bn']) {
            $filter['shop_product_bn'] = $params['shop_product_bn'];
        }
        return kernel::single('openapi_data_original_inventory')->getShopSkuList($filter, $offset, $limit);
    }
    /**
     * 获取ShopStockList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getShopStockList($params,&$code,&$sub_msg){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        $offset = ($page_no - 1) * $limit;
        $filter = [];
    
        if ($start_time && $end_time) {
            $filter['last_modified|between'] = [$start_time, $end_time];
        }
    
        if (!$filter['last_modified|between']){
            $sub_msg = '更新时间必填';
            return false;
        }
        return kernel::single('openapi_data_original_inventory')->getShopStockList($filter, $offset, $limit);
    }
}
