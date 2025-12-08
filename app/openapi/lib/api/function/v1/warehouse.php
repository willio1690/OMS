<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_warehouse extends openapi_api_function_abstract implements openapi_api_function_interface{
    
    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params,&$code,&$sub_msg){
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }
        $warehouse_data = kernel::single('openapi_data_original_warehouse')->getList($start_time,$end_time,$offset,$limit);
        return $warehouse_data;
    }
    
    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params,&$code,&$sub_msg){
        $data = array();
        $data['name'] = $this->charFilter($params['name']);
        $data['vendor'] = $params['vendor'];
        $data['type'] = $params['t_type'];
        $data['extrabranch_name'] = $params['extrabranch_name'];
        $data['branch_bn'] = $params['branch_bn'];
        $data['delivery_cost'] = $params['delivery_cost'];
        $data['operator'] = $params['operator'];
        $data['memo'] = $params['memo'];
        $data['original_iso_bn'] = $params['original_iso_bn'];
        $data['items'] = json_decode($params['items'],true);
        $rs = kernel::single('openapi_data_original_warehouse')->add($data);
        return $rs;
    }
    
}