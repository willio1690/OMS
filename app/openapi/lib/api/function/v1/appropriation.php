<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_appropriation extends openapi_api_function_abstract implements openapi_api_function_interface{

    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params,&$code,&$sub_msg){
        $sub_msg = kernel::single('openapi_data_original_appropriation')->add($params);
        return $sub_msg;
    }
    
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
        $appropriation_no = trim($params['appropriation_no']);
    
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }
    
        $appt_data = kernel::single('openapi_data_original_appropriation')->getList($start_time,$end_time,$appropriation_no,$offset,$limit);
        $appt_arr = array();
        foreach ($appt_data['lists'] as $k => $iostock){
            $appt_arr[$k]["appropriation_no"] = $iostock["appropriation_no"];
            foreach ($iostock["products"] as $k_inner => $product){
                $appt_arr[$k]["products"][$k_inner]["bn"] = $this->charFilter($product["bn"]);
                $appt_arr[$k]["products"][$k_inner]["name"] = $this->charFilter($product["name"]);
                $appt_arr[$k]["products"][$k_inner]["barcode"] = $this->charFilter($product["barcode"]);
                $appt_arr[$k]["products"][$k_inner]["nums"] = $this->charFilter($product["nums"]);
            }
            foreach ($iostock["iostock"] as $k_inner => $iostock){
                $appt_arr[$k]["iostock"][$k_inner]["iostock_id"] = $iostock["iostock_id"];
                $appt_arr[$k]["iostock"][$k_inner]["iostock_bn"] = $this->charFilter($iostock["iostock_bn"]);
                $appt_arr[$k]["iostock"][$k_inner]["branch_bn"] = $this->charFilter($iostock["branch_bn"]);
                $appt_arr[$k]["iostock"][$k_inner]["branch_name"] = $this->charFilter($iostock["branch_name"]);
                $appt_arr[$k]["iostock"][$k_inner]["type"] = $this->charFilter($iostock["type"]);
                $appt_arr[$k]["iostock"][$k_inner]["iostock_time"] = $iostock["iostock_time"];
                $appt_arr[$k]["iostock"][$k_inner]["memo"] = $this->charFilter($iostock["memo"]);
                $appt_arr[$k]["iostock"][$k_inner]["original_bn"] = $this->charFilter($iostock["original_bn"]);
                $appt_arr[$k]["iostock"][$k_inner]["iostock_price"] = $iostock["iostock_price"];
                $appt_arr[$k]["iostock"][$k_inner]["unit_cost"] = $iostock["unit_cost"];
            }
        }
        unset($appt_data['lists']);
    
        $appt_data['lists'] = $appt_arr;
    
        return $appt_data;
    
    }
    
}