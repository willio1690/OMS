<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_workorder extends openapi_api_params_abstract implements openapi_api_params_interface
{
    /**
     * 检查Params
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回验证结果
     */
    public function checkParams($method, $params, &$sub_msg)
    {
        if (parent::checkParams($method, $params, $sub_msg)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 获取AppParams
     * @param mixed $method method
     * @return mixed 返回结果
     */
    public function getAppParams($method)
    {
        $params = array(
            'getList' => array(
                'start_time' => array('type' => 'date', 'required' => 'true', 'name' => '开始时间(更新时间)', 'desc' => '例如2012-12-08 00:00:00'),
                'end_time'   => array('type' => 'date', 'required' => 'true', 'name' => '结束时间(更新时间)', 'desc' => '例如2012-12-08 23:59:59'),
                'page_no'    => array('type' => 'number', 'required' => 'false', 'name' => '页码', 'desc' => '默认1,第一页'),
                'page_size'  => array('type' => 'number', 'required' => 'false', 'name' => '每页数量', 'desc' => '最大100'),
                'mp_bn'      => array('type'=>'string','required'=>'false','name'=>'加工单号','desc'=>''),
            ),
        );
        
        return $params[$method];
    }
    
    /**
     * description
     * @param mixed $method method
     * @return mixed 返回值
     */
    public function description($method)
    {
        $desccription = array(
            'getList' => array('name' => '查询加工单列表', 'description' => '')
        );
        
        return $desccription[$method];
    }
    
}