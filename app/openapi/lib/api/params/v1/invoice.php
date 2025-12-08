<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
+----------------------------------------------------------
 * Api接口[参数判断]
+----------------------------------------------------------
 *
 * Time: 2014-03-18 $   update 20160608 by wangjianjun
 * [Ecos!] (C)2003-2014 Shopex Inc.
+----------------------------------------------------------
 */


class openapi_api_params_v1_invoice extends openapi_api_params_abstract implements openapi_api_params_interface
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
            'getList'       => array(
                'mode'                   => array('type' => 'string', 'required' => 'false', 'name' => '开票方式', 'desc' => '1：电子发票 ； 0：纸质发票 ；不填不区分'),
                'is_status'              => array('type' => 'string', 'required' => 'false', 'name' => '开票状态', 'desc' => '1：已开票； 0：未发票 ； 2：已作废 ；不填不区分'),
                'start_time'             => array('type' => 'date', 'required' => 'false', 'name' => '开始时间', 'desc' => '例如2012-12-08 18:50:30'),
                'end_time'               => array('type' => 'date', 'required' => 'false', 'name' => '结束时间', 'desc' => '例如2012-12-08 18:50:30'),
                'last_modify_start_time' => array('type' => 'string', 'require' => 'false', 'name' => '最后更新开始时间', 'desc' => '例如2012-12-08 18:50:30'),
                'last_modify_end_time'   => array('type' => 'string', 'require' => 'false', 'name' => '最后更新结束时间', 'desc' => '例如2012-12-08 18:50:30'),
                'page_no'                => array('type' => 'number', 'require' => 'false', 'name' => '页码', 'desc' => '默认1,第一页'),
                'page_size'              => array('type' => 'number', 'require' => 'false', 'name' => '每页最大数量', 'desc' => '最大100'),
            ),
            'update'        => array(
                'order_bn'   => array('type' => 'string', 'required' => 'true', 'name' => '订单号', 'desc' => '必填'),
                'shop_id'    => array('type' => 'string', 'required' => 'true', 'name' => '前端店铺ID', 'desc' => '必填 参考shop_id字段'),
                'invoice_no' => array('type' => 'string', 'required' => 'true', 'name' => '发票号', 'desc' => '必填'),
            ),
            'getResultList' => array(
                'start_time' => array('type' => 'date', 'required' => 'true', 'name' => '开始时间', 'desc' => '例如2012-12-08 18:50:30'),
                'end_time'   => array('type' => 'date', 'required' => 'true', 'name' => '结束时间', 'desc' => '例如2012-12-08 18:50:30'),
                'order_bn'   => array('type' => 'string', 'require' => 'false', 'name' => '订单号', 'desc' => '交易订单号'),
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
            'getList'       => array('name' => '获取订单发票列表', 'description' => '获取订单发票的信息列表'),
            'update'        => array('name' => '更新纸质发票的打印信息', 'description' => '更新纸质发票的打印信息'),
            'getResultList' => array('name' => '获取开票结果列表', 'description' => '获取开票结果列表'),
        );
        return $desccription[$method];
    }

}
