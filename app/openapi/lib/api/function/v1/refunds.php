<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 退款单Lib类
 */
class openapi_api_function_v1_refunds extends openapi_api_function_abstract implements openapi_api_function_interface
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
        
        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }
        
        //get list
        $dataList = kernel::single('openapi_data_original_refunds')->getList($start_time, $end_time, $offset, $limit);
    
        return $dataList;
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
        return array();
    }

    /**
     * 获取DetailList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getDetailList($params, &$code, &$sub_msg)
    {
        $start_time = strtotime($params['start_time']);
        $end_time   = strtotime($params['end_time']);
        $page_no    = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit      = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        
        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }
        
        //get list
        $dataList = kernel::single('openapi_data_original_refunds')->getDetailList($start_time, $end_time, $offset, $limit);
        
        return $dataList;
    }
}