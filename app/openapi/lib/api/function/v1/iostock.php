<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_iostock extends openapi_api_function_abstract implements openapi_api_function_interface{

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
        $iostock_bn = trim($params['iostock_bn']);
        $branch_bn = trim($params['branch_bn']);
        $original_bn = trim($params['original_bn']);
        $bn = trim($params['bn']);
        $type = trim($params['io_type']);

        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }

        $iostock_data = kernel::single('openapi_data_original_iostock')->getList($start_time,$end_time,$iostock_bn,$original_bn,$branch_bn,$bn,$type,$offset,$limit);

        $iostock_arr = array();
        foreach ($iostock_data['lists'] as $k => $iostock) {
            $iostock_arr[$k]['iostock_id'] = $iostock['iostock_id'];
        
            $iostock_arr[$k]['iostock_bn']       = $this->charFilter($iostock['iostock_bn']);
            $iostock_arr[$k]['branch_bn']        = $this->charFilter($iostock['branch_bn']);
            $iostock_arr[$k]['branch_name']      = $this->charFilter($iostock['branch_name']);
            $iostock_arr[$k]['bn']               = $this->charFilter($iostock['bn']);
            $iostock_arr[$k]['name']             = $this->charFilter($iostock['name']);
            $iostock_arr[$k]['barcode']          = $iostock['barcode'];
            $iostock_arr[$k]['nums']             = $iostock['nums'];
            $iostock_arr[$k]['balance_nums']     = $iostock['balance_nums'];
            $iostock_arr[$k]['type']             = $this->charFilter($iostock['type_name']);
            $iostock_arr[$k]['iostock_time']     = date('Y-m-d H:i:s', $iostock['create_time']);
            $iostock_arr[$k]['memo']             = $this->charFilter($iostock['memo']);
            $iostock_arr[$k]['original_bn']      = $this->charFilter($iostock['original_bn']);
            $iostock_arr[$k]['iostock_price']    = $iostock['iostock_price'];
            $iostock_arr[$k]['unit_cost']        = $iostock['unit_cost'];
            $iostock_arr[$k]['original_name']    = (string)$iostock['original_name'];
            $iostock_arr[$k]['appropriation_no'] = (string)$iostock['appropriation_no'];
            $iostock_arr[$k]['arrival_no']       = $iostock['arrival_no'];
            $iostock_arr[$k]['now_unit_cost']    = $iostock['now_unit_cost'];
            $iostock_arr[$k]['inventory_cost']   = $iostock['inventory_cost'];
            $iostock_arr[$k]['now_num']          = $iostock['now_num'];
            $iostock_arr[$k]['now_inventory_cost']= $iostock['now_inventory_cost'];
            $iostock_arr[$k]['operator']         = $iostock['operator'];
            $iostock_arr[$k]['oper']             = $iostock['oper'];
            $iostock_arr[$k]['supplier_name']    = $iostock['supplier_name'];
            $iostock_arr[$k]['entity_unit_cost']    = $iostock['entity_unit_cost'];
            $iostock_price_num += $iostock['iostock_price'] * $iostock['nums'];
            $unit_cost_num     += $iostock['unit_cost'] * $iostock['nums'];
        }

        unset($iostock_data['lists']);
        $iostock_data['iostock_price_num'] = sprintf("%.3f", $iostock_price_num);
        $iostock_data['unit_cost_num'] = sprintf("%.3f", $unit_cost_num);
        $iostock_data['lists'] = $iostock_arr;
        return $iostock_data;
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
}