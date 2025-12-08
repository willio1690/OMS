<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_ar extends openapi_api_function_abstract implements openapi_api_function_interface
{
    /**
     * 应收应退账单
     * @param $params
     * @param $code
     * @param $sub_msg
     * @return array|bool
     * @author db
     * @date 2024-08-09 12:04 下午
     */
    public function getList($params, &$code, &$sub_msg)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $start_time       = $params['start_time'];
        $end_time         = $params['end_time'];
        $trade_start_time = $params['trade_start_time'];
        $trade_end_time   = $params['trade_end_time'];
        $page_no          = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit            = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        
        $filter = [];

        if ($start_time && $end_time) {
            $filter['up_time|betweenstr'] = [$start_time, $end_time];
        }

        if ($trade_start_time && $trade_end_time) {
            $filter['trade_time|between'] = [strtotime($trade_start_time), strtotime($trade_end_time)];
        }

        if (!$filter['up_time|betweenstr'] && !$filter['trade_time|between']){
            $sub_msg = '更新时间 或 交易时间 必填';
            return false;
        }

        if ($params['order_bn']) {
            $filter['order_bn'] = explode(',', $params['order_bn']);
        }
        
        //单据编号
        if ($params['ar_bn']) {
            $filter['ar_bn'] = explode(',', $params['ar_bn']);
        }

        if ($params['status']) {
            $filter['status'] = $params['status'];
        }

        if ($params['verification_flag']) {
            $filter['verification_flag'] = $params['verification_flag'];
        }
        
        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }
        
        //getList
        $arData = kernel::single('openapi_data_original_ar')->getList($filter, $start_time, $end_time, $trade_start_time, $trade_end_time, $offset, $limit);
        
        //list
        $arArray = array();
        foreach ($arData['lists'] as $k => $ar) {
            $addon = $ar['addon'] ? @unserialize($ar['addon']) : [];

            //format
            $arArray[$k]['ar_bn']             = $this->charFilter($ar['ar_bn']);
            $arArray[$k]['order_bn']          = $this->charFilter($ar['order_bn']);
            $arArray[$k]['channel_name']      = $this->charFilter($ar['channel_name']);
            $arArray[$k]['status']            = $ar['status'];
            $arArray[$k]['verification_time'] = $ar['verification_time'] ? date('Y-m-d H:i:s', $ar['verification_time']) : '';
            $arArray[$k]['type']              = kernel::single('finance_ar')->get_name_by_type($ar['type']);
            $arArray[$k]['monthly_name']      = $this->charFilter($ar['monthly_name']);
            $arArray[$k]['monthly_status']    = $ar['monthly_status'];
            $arArray[$k]['create_time']       = $ar['create_time'] ? date('Y-m-d H:i:s', $ar['create_time']) : '';
            $arArray[$k]['trade_time']        = $ar['trade_time'] ? date('Y-m-d H:i:s', $ar['trade_time']) : '';
            $arArray[$k]['money']             = $ar['money'];
            $arArray[$k]['confirm_money']     = $ar['confirm_money'];
            $arArray[$k]['actually_money']    = $ar['actually_money'];
            $arArray[$k]['serial_number']     = $this->charFilter($ar['serial_number']);
            $arArray[$k]['ar_type']           = $ar['ar_type'];
            $arArray[$k]['up_time']           = $ar['up_time'];
            $arArray[$k]['verification_flag']           = $ar['verification_flag'];
            $arArray[$k]['cost_freight']           = $addon['fee_money'];
            $arArray[$k]['relate_order_bn']   = $ar['relate_order_bn'];
            
            $items = [];
            foreach ($ar['ar_items'] as $key => $ar_item) {
                $items[] = [
                    'item_id' => $ar_item['item_id'],
                    'bn'    => $ar_item['bn'],
                    'nums'  => $ar_item['num'],
                    'money' => $ar_item['money'],
                    'actually_money' => $ar_item['actually_money'],
                    'name'  => $ar_item['name'],
                ];
            }
            $arArray[$k]['ar_items'] = $items;
            // 如果 $v 是 null，则返回空字符串；否则返回原始值
            $arArray[$k] = array_map(function($v) {return is_null($v) ? '' : $v;}, $arArray[$k]);
            
        }
        
        unset($arData['lists']);
        $arData['lists'] = $arArray;
        
        return $arData;
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
    }
    
    /**
     * 核销状态
     * @param $key
     * @return string
     * @author db
     * @date 2024-08-09 11:17 上午
     */
    private function getStatus($key)
    {
        $types = array(
            '0' => '未核销',
            '1' => '部分核销',
            '2' => '已核销',
        );
        return $types[$key];
    }
}