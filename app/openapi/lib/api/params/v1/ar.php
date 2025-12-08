<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_ar extends openapi_api_params_abstract implements openapi_api_params_interface
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
                'order_bn'         => array('type' => 'string', 'required' => 'false', 'name' => '订单号', 'desc' => '平台订单号'),
                'ar_bn'            => array('type' => 'string', 'required' => 'false', 'name' => '单据编号', 'desc' => 'AR单据编号'),
                'start_time'       => array('type' => 'date', 'required' => 'true', 'name' => '开始时间', 'desc' => '(应收应退单更新时间),例如2012-12-08 18:50:30'),
                'end_time'         => array('type' => 'date', 'required' => 'true', 'name' => '结束时间', 'desc' => '(应收应退单更新时间),例如2012-12-08 18:50:30'),
                'trade_start_time' => array('type' => 'date', 'required' => 'false', 'name' => '账单日期开始时间', 'desc' => '(应收应退账单日期),例如2012-12-08 18:50:30'),
                'trade_end_time'   => array('type' => 'date', 'required' => 'false', 'name' => '账单日期结束时间', 'desc' => '(应收应退账单日期),例如2012-12-08 18:50:30'),
                'status'        => array('type' => 'string', 'required' => 'false', 'name' => '核销状态。可选值：0（未核销），1（部分核销），2（已核销）'),
                'verification_flag'        => array('type' => 'string', 'required' => 'false', 'name' => '是否应收应退对冲。可选值：0（否），1（是）'),
                'page_no'          => array('type' => 'number', 'required' => 'false', 'name' => '页码，默认1 第一页'),
                'page_size'        => array('type' => 'number', 'required' => 'false', 'name' => '每页数量，最大1000'),
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
        $desccription = array('getList' => array('name' => '查询应收应退单信息(根据应收应退单更新时间)', 'description' => '批量获取一个时间段内的应收应退单信息数据'));
        return $desccription[$method];
    }
}