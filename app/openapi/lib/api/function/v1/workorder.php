<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_workorder extends openapi_api_function_abstract implements openapi_api_function_interface
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
        $start_time = date('Y-m-d H:i:s', strtotime($params['start_time']));
        $end_time = date('Y-m-d H:i:s', strtotime($params['end_time']));
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
    
        $filter = [];
        $filter['up_time|betweenstr'] = [$start_time, $end_time];
        if($params['mp_bn']) {
            $filter['mp_bn'] = $params['mp_bn'];
        }
        $dataList = kernel::single('openapi_data_original_workorder')->getList($filter, $page_no, $limit);
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
}