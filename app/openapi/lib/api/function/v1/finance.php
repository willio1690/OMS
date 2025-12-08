<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 财务账单
 */
class openapi_api_function_v1_finance extends openapi_api_function_abstract implements openapi_api_function_interface
{
    /**
     * 获取账单列表
     * 
     * @param array $params
     * @param string $code
     * @param string $sub_msg
     * @return array
     */

    public function getList($params, &$code, &$sub_msg)
    {
        $start_time = $params['start_time'] ? strtotime($params['start_time']) : 0;
        $end_time = $params['end_time'] ? strtotime($params['end_time']) : 0;
        
        $trade_start_time = $params['trade_start_time'] ? strtotime($params['trade_start_time']) : 0;
        $trade_end_time = $params['trade_end_time'] ? strtotime($params['trade_end_time']) : 0;
        
        $order_start_time = $params['order_start_time'] ? strtotime($params['order_start_time']) : 0;
        $order_end_time = $params['order_end_time'] ? strtotime($params['order_end_time']) : 0;
        
        $params['platform_type'] = trim($params['platform_type']);
        $params['shop_bn'] = trim($params['shop_bn']);
        $params['page_no'] = intval($params['page_no']);
        $params['page_size'] = intval($params['page_size']);
        
        //分页
        $page_no = $params['page_no'] > 0 ? $params['page_no'] : 1;
        $limit = $params['page_size'] > 100 || $params['page_size'] <= 0 ? 100 : $params['page_size'];
        $offset = 0;
        if($page_no > 1){
            $offset = ($page_no - 1) * $limit;
        }
        
        //店铺编码
        $shopBns = array();
        if($params['shop_bn']){
            $tempData = explode('#', $params['shop_bn']);
            foreach ($tempData as $key => $shop_bn){
                $shopBns[] = $shop_bn;
            }
        }
        
        //filter
        $filter = array(
            'shop_bns'          => $shopBns, //店铺编码
            'create_time'       => array($start_time, $end_time), //创建时间范围
            'trade_time'        => array($trade_start_time, $trade_end_time), //账单时间范围
            'platform_type'     => $params['platform_type'], //平台类型
            'order_create_date' => array($order_start_time, $order_end_time), //订单创建时间范围
        );
        
        //获取数据列表
        $originalLib = kernel::single('openapi_data_original_finance');
        $dataList = $originalLib->getList($filter, $offset, $limit);
        if(empty($dataList)){
            return array();
        }
        
        //list
        foreach ($dataList['lists'] as $key => $val)
        {
            //隐藏shop_id
            unset($val['id'], $val['shop_id'], $val['unique_id']);
            
            //时间
            $val['trade_time'] = date('Y-m-d H:i:s', $val['trade_time']);
            $val['order_create_date'] = date('Y-m-d H:i:s', $val['order_create_date']);
            $val['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
            
            $dataList['lists'][$key] = $val;
        }
        
        return $dataList;
    }
    
    function add($params, &$code, &$sub_msg){
        //==
    }
    
    /**
     * 获取精准通数据接口
     */
    public function getJZT($params, &$code, &$sub_msg)
    {
        //投放日期范围
        $start_time = $params['start_time'] ? strtotime($params['start_time']) : 0;
        $end_time = $params['end_time'] ? strtotime($params['end_time']) : 0;
    
        $launchtime_start_time = $params['launchtime_start_time'] ? strtotime($params['launchtime_start_time']) : 0;
        $launchtime_end_time = $params['launchtime_end_time'] ? strtotime($params['launchtime_end_time']) : 0;
        
        //查询条件
        $params['pay_serial_number'] = trim($params['pay_serial_number']);
        $params['account'] = trim($params['account']);
        $params['trade_type'] = trim($params['trade_type']);
        $params['plan_id'] = trim($params['plan_id']);
        
        //页码
        $params['page_no'] = intval($params['page_no']);
        $params['page_size'] = intval($params['page_size']);
        
        //分页
        $page_no = $params['page_no'] > 0 ? $params['page_no'] : 1;
        $limit = $params['page_size'] > 100 || $params['page_size'] <= 0 ? 100 : $params['page_size'];
        $offset = 0;
        if($page_no > 1){
            $offset = ($page_no - 1) * $limit;
        }
        
        //filter
        $filter = array(
            'at_time' => array($start_time, $end_time), //创建日期
            'launchtime' => array($launchtime_start_time, $launchtime_end_time), //投放日期
        );
        $filter = array_merge($filter, $params);
        
        //获取数据列表
        $originalLib = kernel::single('openapi_data_original_finance');
        $dataList = $originalLib->getJztList($filter, $offset, $limit);
        if(empty($dataList)){
            return array();
        }
        
        //list
        foreach ($dataList['lists'] as $key => $val)
        {
            //格式化时间
            $val['launchtime'] = date('Y-m-d H:i:s', $val['launchtime']);
            $val['at_time'] = date('Y-m-d H:i:s', $val['at_time']);
            $val['up_time'] = date('Y-m-d H:i:s', $val['up_time']);
            
            $dataList['lists'][$key] = $val;
        }
        
        return $dataList;
    }
    
    /**
     * 获取精准通数据接口
     */
    public function getJDbill($params, &$code, &$sub_msg)
    {
        //创建日期范围
        $start_time = $params['start_time'] ? strtotime($params['start_time']) : 0;
        $end_time = $params['end_time'] ? strtotime($params['end_time']) : 0;
    
        //账单日期范围
        $bill_start_time = $params['bill_start_time'] ? strtotime($params['bill_start_time']) : 0;
        $bill_end_time = $params['bill_end_time'] ? strtotime($params['bill_end_time']) : 0;
        
        //交易日期范围
        $params['trade_start_time'] = $params['trade_start_time'] ? strtotime($params['trade_start_time']) : 0;
        $params['trade_end_time'] = $params['trade_end_time'] ? strtotime($params['trade_end_time']) : 0;
        
        //查询条件
        $params['member_id'] = trim($params['member_id']);
        $params['account_no'] = trim($params['account_no']);
        $params['trade_no'] = trim($params['trade_no']);
        
        //页码
        $params['page_no'] = intval($params['page_no']);
        $params['page_size'] = intval($params['page_size']);
        
        //分页
        $page_no = $params['page_no'] > 0 ? $params['page_no'] : 1;
        $limit = $params['page_size'] > 100 || $params['page_size'] <= 0 ? 100 : $params['page_size'];
        $offset = 0;
        if($page_no > 1){
            $offset = ($page_no - 1) * $limit;
        }
        
        //filter
        $filter = array(
                'at_time' => array($start_time, $end_time), //创建日期
                'bill_time' => array($bill_start_time, $bill_end_time), //账单日期
        );
        $filter = array_merge($filter, $params);
        
        //获取数据列表
        $originalLib = kernel::single('openapi_data_original_finance');
        $dataList = $originalLib->getJdBillList($filter, $offset, $limit);
        if(empty($dataList)){
            return array();
        }
        
        //list
        foreach ($dataList['lists'] as $key => $val)
        {
            //格式化时间
            $val['trade_time'] = date('Y-m-d H:i:s', $val['trade_time']);
            $val['bill_time'] = date('Y-m-d H:i:s', $val['bill_time']);
            $val['at_time'] = date('Y-m-d H:i:s', $val['at_time']);
            $val['up_time'] = date('Y-m-d H:i:s', $val['up_time']);
            $val['remark'] = $this->charFilter($val['remark']);
            
            $dataList['lists'][$key] = $val;
        }
        
        return $dataList;
    }

    /**
     * 获取ReportItems
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getReportItems($params, &$code, &$sub_msg){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $start_time       = $params['start_time'];
        $end_time         = $params['end_time'];
        $page_no          = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit            = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        
        $filter = [];

        if ($start_time && $end_time) {
            $filter['up_time|betweenstr'] = [$start_time, $end_time];
        }

        if (!$filter['up_time|betweenstr']){
            $sub_msg = '更新时间 必填';
            return false;
        }

        if ($params['order_bn']) {
            $filter['order_bn'] = explode(',', $params['order_bn']);
        }
        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }
        
        $data = kernel::single('openapi_data_original_finance')->getReportItems($filter, $offset, $limit);
 
        return $data;
    }

    /**
     * 获取拆分结果明细
     * 
     * @param array $params
     * @param string $code
     * @param string $sub_msg
     * @return array
     */
    public function getExpensesSplitList($params, &$code, &$sub_msg)
    {
        $time_from = isset($params['time_from']) ? strtotime($params['time_from']) : 0;
        $time_to = isset($params['time_to']) ? strtotime($params['time_to']) : 0;

        if (!$time_from || !$time_to) {
            $sub_msg = '时间范围必填';
            return false;
        }

        // 根据拆分类型确定使用的时间字段
        $split_type = isset($params['split_type']) ? $params['split_type'] : 'split';
        
        // 两个表都使用 trade_time 作为账单时间字段
        $filter = array(
            'trade_time|between' => array($time_from, $time_to),
            'split_type' => $split_type,
        );

        $originalLib = kernel::single('openapi_data_original_finance');
        $dataList = $originalLib->getExpensesSplitList($filter);

        if (empty($dataList['lists'])) {
            return array();
        }
        return $dataList;
    }
}