<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_po extends openapi_api_function_abstract implements openapi_api_function_interface{


    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params,&$code,&$sub_msg){
        $data = array();

        $data['name'] = $params['name'];
        $data['po_bn'] = $params['po_bn'];
        $data['vendor'] = $params['vendor'];
        $data['vendor_bn'] = $params['vendor_bn'];
        $data['type'] = $params['po_type'];
        $data['branch_bn'] = $params['branch_bn'];
        $data['delivery_cost'] = $params['delivery_cost'];
        $data['deposit_balance'] = $params['deposit_balance'];
        $data['arrive_time'] = $params['arrive_time'];
        $data['operator'] = $params['operator'];
        $data['memo'] = $params['memo'];
        $data['confirm'] = $params['confirm'];
        $data['items'] = json_decode($params['items'],true);

        $rs = kernel::single('openapi_data_original_po')->add($data);

        return $rs;
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
    	$offset = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
    	$limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        $start_time= $params['start_time'] ? strtotime($params['start_time']) : 0;
        $end_time= $params['end_time'] ? strtotime($params['end_time']) : time();

    	unset($params['page_no']);
    	unset($params['page_size']);
        if(isset($params['start_time'])) unset($params['start_time']);
        if(isset($params['end_time'])) unset($params['end_time']);
    	
    	
    	$filter = array(
            'purchase_time|bthan' => $start_time,
            'purchase_time|sthan' => $end_time,
        );
        
    	foreach ($params as $k=>$param){
    		if(empty($param)) continue;
    		switch ($k){
    			case 'statement_status':
    				$filter['statement'] = $param;
    				break;
                case 'last_modify_start_time':
                    $filter['last_modify|bthan'] = strtotime($params['last_modify_start_time']);
                    break;
                case 'last_modify_end_time':
                    $filter['last_modify|sthan'] = strtotime($params['last_modify_end_time']);
                    break;
    			default:
    				$filter[$k] = $param;
    				break;
    		}
    	}
    	$result = kernel::single('openapi_data_original_po')-> getList ($filter,$offset,$limit);
    
    	// todo定义返回结构
    
    
    	return $result;
    }
    
 
}