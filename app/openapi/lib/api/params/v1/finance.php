<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 财务账单
 */
class openapi_api_params_v1_finance extends openapi_api_params_abstract implements openapi_api_params_interface
{
    /**
     * 检查Params
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回验证结果
     */

    public function checkParams($method,$params,&$sub_msg)
    {
        if(parent::checkParams($method,$params,$sub_msg)){
            return true;
        }else{
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
                'platform_type'     => array('type'=>'string', 'required'=>'false', 'name'=>'账单类型', 'desc'=>'支付宝：alipay'),
                'shop_bn'           => array('type'=>'string', 'required'=>'false', 'name'=>'店铺编码', 'desc'=>'多个店铺编码之间，用#分隔'),
                'start_time'        => array('type'=>'date', 'required'=>'true', 'name'=>'*创建开始时间,', 'desc'=>'例如：2021-05-01 18:50:30'),
                'end_time'          => array('type'=>'date', 'required'=>'true', 'name'=>'*创建结束时间', 'desc'=>'同上'),
                'trade_start_time'  => array('type'=>'date', 'required'=>'false', 'name'=>'账单开始时间,', 'desc'=>'例如：2021-05-01 18:50:30'),
                'trade_end_time'    => array('type'=>'date', 'required'=>'false', 'name'=>'账单结束时间', 'desc'=>'同上'),
                'order_start_time'  => array('type'=>'date', 'required'=>'false', 'name'=>'订单开始时间', 'desc'=>'例如2021-05-01 18:50:30'),
                'order_end_time'    => array('type'=>'date', 'required'=>'false', 'name'=>'订单结束时间', 'desc'=>'同上'),
                'page_no'           => array('type'=>'number', 'required'=>'false', 'name'=>'*页码', 'desc'=>'默认填写1为第一页'),
                'page_size'         => array('type'=>'number', 'required'=>'false', 'name'=>'*每页数量', 'desc'=>'注意：最大可填写100'),
            ),
            'getJZT' => array(
                'pay_serial_number'     => array('type'=>'string', 'required'=>'false', 'name'=>'流水单号', 'desc'=>'流水单号:pay_serial_number'),
                'account'               => array('type'=>'string', 'required'=>'false', 'name'=>'账号', 'desc'=>'账号：account'),
                'trade_type'            => array('type'=>'string', 'required'=>'false', 'name'=>'交易类型', 'desc'=>'交易类型：trade_type'),
                'plan_id'               => array('type'=>'string', 'required'=>'false', 'name'=>'计划ID', 'desc'=>'计划ID：plan_id'),
                'start_time'            => array('type'=>'date', 'required'=>'true', 'name'=>'*创建开始日期,', 'desc'=>'必填项(例如：2021-11-01 00:00:00)'),
                'end_time'              => array('type'=>'date', 'required'=>'true', 'name'=>'*创建结束时间', 'desc'=>'必填项(例如：2021-11-20 00:00:00)'),
                'launchtime_start_time' => array('type'=>'date', 'required'=>'false', 'name'=>'投放开始日期,', 'desc'=>'例如：2021-11-01 00:00:00'),
                'launchtime_end_time'   => array('type'=>'date', 'required'=>'false', 'name'=>'投放结束时间', 'desc'=>'例如：2021-11-20 00:00:00'),
                'page_no'               => array('type'=>'number', 'required'=>'false', 'name'=>'*页码', 'desc'=>'默认为第一页,填写：1'),
                'page_size'             => array('type'=>'number', 'required'=>'false', 'name'=>'*每页数量', 'desc'=>'注意：最大可填写100'),
            ),
            'getJDbill' => array(
                'member_id'         => array('type'=>'string', 'required'=>'false', 'name'=>'商户号', 'desc'=>'商户号:member_id'),
                'account_no'        => array('type'=>'string', 'required'=>'false', 'name'=>'账户代码', 'desc'=>'账户代码:account_no'),
                'trade_no'          => array('type'=>'string', 'required'=>'false', 'name'=>'商户订单号', 'desc'=>'商户订单号:trade_no'),
                'start_time'        => array('type'=>'date', 'required'=>'true', 'name'=>'*创建开始日期,', 'desc'=>'必填项(例如：2021-11-01 00:00:00)'),
                'end_time'          => array('type'=>'date', 'required'=>'true', 'name'=>'*创建结束时间', 'desc'=>'必填项(例如：2021-11-20 00:00:00)'),
                'bill_start_time'   => array('type'=>'date', 'required'=>'false', 'name'=>'账单开始日期,', 'desc'=>'可选项(例如：2021-11-01 00:00:00)'),
                'bill_end_time'     => array('type'=>'date', 'required'=>'false', 'name'=>'账单结束时间', 'desc'=>'可选项(例如：2021-11-20 00:00:00)'),
                'trade_start_time'  => array('type'=>'date', 'required'=>'false', 'name'=>'交易开始日期,', 'desc'=>'可选项(例如：2021-11-01 00:00:00)'),
                'trade_end_time'    => array('type'=>'date', 'required'=>'false', 'name'=>'交易结束时间', 'desc'=>'可选项(例如：2021-11-20 00:00:00)'),
                'page_no'           => array('type'=>'number', 'required'=>'false', 'name'=>'*页码', 'desc'=>'默认为第一页,填写：1'),
                'page_size'         => array('type'=>'number', 'required'=>'false', 'name'=>'*每页数量', 'desc'=>'注意：最大可填写100'),
            ),
            'getReportItems' => array(
                'order_bn'          => array('type'=>'string', 'required'=>'false', 'name'=>'订单号', 'desc'=>'订单号'),
                'start_time'        => array('type'=>'date', 'required'=>'true', 'name'=>'*更新开始日期,', 'desc'=>'必填项(例如：2021-11-01 00:00:00)'),
                'end_time'          => array('type'=>'date', 'required'=>'true', 'name'=>'*更新结束时间', 'desc'=>'必填项(例如：2021-11-20 00:00:00)'),
                'page_no'           => array('type'=>'number', 'required'=>'false', 'name'=>'*页码', 'desc'=>'默认为第一页,填写：1'),
                'page_size'         => array('type'=>'number', 'required'=>'false', 'name'=>'*每页数量', 'desc'=>'注意：最大可填写100'),
            ),
            'getExpensesSplitList' => array(
                'time_from'  => array('type'=>'date', 'required'=>'true', 'name'=>'*账单开始日期', 'desc'=>'必填项(例如：2021-11-01)'),
                'time_to'    => array('type'=>'date', 'required'=>'true', 'name'=>'*账单结束日期', 'desc'=>'必填项(例如：2021-11-30)'),
                'split_type' => array('type'=>'string', 'required'=>'false', 'name'=>'拆分类型', 'desc'=>'可选值：split(已拆分)、unsplit(不拆仅呈现)，默认为split'),
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
                'getList' => array('name'=>'查询支付宝账单', 'description'=>'批量获取支付宝账单数据'),
                'getJZT' => array('name'=>'查询精准通账单', 'description'=>'批量获取精准通账单数据'),
                'getJDbill' => array('name'=>'查询京东钱包流水', 'description'=>'批量获取京东钱包流水数据'),
                'getReportItems' => array('name'=>'查询账期订单明细', 'description'=>''),
                'getExpensesSplitList' => array('name'=>'获取拆分结果明细', 'description'=>'根据时间范围获取拆分结果明细'),
        );
        
        return $desccription[$method];
    }
}