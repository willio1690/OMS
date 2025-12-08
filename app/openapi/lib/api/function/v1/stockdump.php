<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_stockdump extends openapi_api_function_abstract implements openapi_api_function_interface
{

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
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList ($params,&$code,&$sub_msg)
    {
    	$page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
    	$page_size = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        $modified_start = $params['modified_start'] ?: '';
        $modified_end   = $params['modified_end'] ?: '';

        $confirm_time_start = strtotime($params['confirm_time_start']);
        $confirm_time_end   = strtotime($params['confirm_time_end']);
    
        $filter = [];
    
        // 转储单编号
        if ($params['stockdump_bn']) {
            $filter['stockdump_bn'] = $params['stockdump_bn'];
        }

        if ($params['in_status']) {
            $filter['in_status'] = $params['in_status'];
        }

        if ($confirm_time_start && $confirm_time_end) {
            $filter['confirm_time|between'] = [$confirm_time_start, $confirm_time_end];
        }
    
        if ($modified_start && $modified_end) {
            $filter['up_time|betweenstr'] = [$modified_start, $modified_end];
        }

        $limit = $page_size;
        $offset = ($page_no-1)*$limit;

    	$result = kernel::single('openapi_data_original_stockdump')-> getList ($filter,$offset,$limit);

    	return $result;
    }
    
 
}