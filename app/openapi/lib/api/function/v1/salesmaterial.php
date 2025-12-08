<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_salesmaterial extends openapi_api_function_abstract implements openapi_api_function_interface{

    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params,&$code,&$sub_msg)
    {
        $params = array_filter($params);
        $filter = array();

        if (isset($params['sales_material_bn'])) {
            $filter['sales_material_bn'] = $params['sales_material_bn'];
        }

        if($params['start_time']){
            $filter['last_modify|bthan'] = strtotime($params['start_time']);
        }
        if($params['end_time']) {
            $filter['last_modify|sthan'] = strtotime($params['end_time']);
        }
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit   = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        $data = kernel::single('openapi_data_original_salesmaterial')->getList($filter,($page_no-1)*$limit,$limit);
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
        if($params['sales_material_type'] == 4  || $params['sales_material_type'] == 5){ //福袋、多选一
        }else{
            //格式化参数是促销类还是普通、赠品类
            if(isset($params['bind_info'])){
                if($params['sales_material_type'] == 2){
                    $tmp_basicMInfos = explode('|',$params['bind_info']);
                    foreach($tmp_basicMInfos as $tmp_basicMInfo){
                        $tmp_bnInfo = explode(':',$tmp_basicMInfo);
                        $tmp_bnExtInfo = explode('x',$tmp_bnInfo[1]);
                        $params['at'][$tmp_bnInfo[0]] = $tmp_bnExtInfo[0];
                        $params['pr'][$tmp_bnInfo[0]] = $tmp_bnExtInfo[1];
                    }
                }else{
                    $params['bind_bn'] = $params['bind_info'];
                }
            }
        }
        
        $rs = kernel::single('openapi_data_original_salesmaterial')->add($params,$code,$sub_msg);
        return $rs;
    }

    /**
     * edit
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function edit($params,&$code,&$sub_msg){
        if($params['sales_material_type'] == 4 || $params['sales_material_type'] == 5){ //福袋、多选一
        }else{
            //格式化参数是促销类还是普通、赠品类
            if(isset($params['bind_info'])){
                if($params['sales_material_type'] == 2){
                    $tmp_basicMInfos = explode('|',$params['bind_info']);
                    foreach($tmp_basicMInfos as $tmp_basicMInfo){
                        $tmp_bnInfo = explode(':',$tmp_basicMInfo);
                        $tmp_bnExtInfo = explode('x',$tmp_bnInfo[1]);
                        $params['at'][$tmp_bnInfo[0]] = $tmp_bnExtInfo[0];
                        $params['pr'][$tmp_bnInfo[0]] = $tmp_bnExtInfo[1];
                    }
                }else{
                    $params['bind_bn'] = $params['bind_info'];
                }
            }
        }
        $rs = kernel::single('openapi_data_original_salesmaterial')->edit($params,$code,$sub_msg);
        return $rs;
    }
    
}