<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_mdl_ome_orderDiscounts extends dbeav_model
{
    
    var $has_export_cnf = true;
    
    var $export_name = '订单优惠明细统计';
    
    /**
     * 获取_order_count
     * @param mixed $filter filter
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function get_order_count($filter = null, $type = 0)
    {
        if ($type == 0) {
            $sql = 'SELECT count(DISTINCT order_id) as count FROM sdb_omeanalysts_ome_orderDiscounts WHERE ' . $this->_rFilter($filter);
        } else {
            $sql = 'SELECT
                        sum(sale_money) AS count
                    FROM (SELECT sale_money FROM sdb_omeanalysts_ome_orderDiscounts WHERE ' . $this->_rFilter($filter) . ' GROUP BY order_id) AS sale_money';
        }
        $row = $this->db->select($sql);
        
        return $row[0]['count'];
    }
    
    /**
     * 获取_orderDiscount
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function get_orderDiscount($filter = null)
    {
        $sql = 'SELECT sum(discount_money) as amount FROM sdb_omeanalysts_ome_orderDiscounts where ' . $this->_rFilter($filter);
        $row = $this->db->select($sql);
        return $row[0]['amount'];
    }
    
    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter = null)
    {
        $sql = 'SELECT count(*) as _count FROM sdb_omeanalysts_ome_orderDiscounts WHERE ' . $this->_rFilter($filter);
        $row = $this->db->select($sql);
        return intval($row[0]['_count']);
    }
    
    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderType = null)
    {
        if ($cols != '*') {
            $field = $cols;
        }else{
            $field = 'order_bn,shop_id,shop_type,original_money,sale_money,pay_money,discount_type,discount_name,discount_money,order_createtime,paytime,pay_status,createtime,last_modified';
        }
        $sql = 'SELECT '. $field .' FROM sdb_omeanalysts_ome_orderDiscounts WHERE ' . $this->_rFilter($filter);
        if ($orderType == null) {
            $orderType = 'paytime desc';
        }
        if ($orderType) {
            $sql .= ' ORDER BY ' . (is_array($orderType) ? implode($orderType, ' ') : $orderType);
        }

        $data = $rows = $this->db->selectLimit($sql, $limit, $offset);
        return $data;
    }
    
    /**
     * _rFilter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _rFilter($filter, $tableAlias = null, $baseWhere = null)
    {
        $where = array(1);
        if (isset($filter['time_from']) && $filter['time_from']) {
            $where[] = ' paytime >=' . strtotime($filter['time_from']);
        }
        if (isset($filter['time_to']) && $filter['time_to']) {
            $where[] = ' paytime <' . (strtotime($filter['time_to']) + 86400);
        }
        if (isset($filter['order_bn']) && $filter['order_bn']) {
            $where[] = ' order_bn =\'' . addslashes($filter['order_bn']) . '\'';
        }
        if(isset($filter['type_id']) && $filter['type_id']){
            if(is_array($filter['type_id'])) {
                $shopIds = array_filter($filter['type_id']);
                if($shopIds){
                    // 对数组中每个元素进行 addslashes 转义
                    $escapedShopIds = array_map('addslashes', $shopIds);
                    $where[] = ' shop_id IN (\'' . implode("','", $escapedShopIds) . '\')';
                }
            } else {
                $where[] = ' shop_id =\''.addslashes($filter['type_id']).'\'';
            }
        }
        if (isset($filter['shop_type']) && $filter['shop_type']) {
            $where[] = ' shop_type =\'' . addslashes($filter['shop_type']) . '\'';
        }
        if(isset($filter['org_id']) && $filter['org_id']){
            $where[] = " org_id in ('".implode('\',\'',$filter['org_id'])."')";
        }
        
        return implode(' AND ', $where);
    }
    
    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams)
    {
        $type    = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        } elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams)
    {
        $params = $logParams['params'];
        $type   = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_purchaseReport_orderDiscountsAnalysis';
        }
        $type .= '_export';
        return $type;
    }
    
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams)
    {
        $params = $logParams['params'];
        $type   = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_purchaseReport_orderDiscountsAnalysis';
        }
        $type .= '_import';
        return $type;
    }
    
    public function exportName(&$data)
    {
        $data['name'] = $_POST['time_from'] . '到' . $_POST['time_to'] . '订单优惠明细统计';
    }
    
    public function io_title($filter = null, $ioType = 'csv')
    {
        switch ($ioType) {
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
                    '*:订单号'  => 'order_bn',
                    '*:来源店铺' => 'shop_id',
                    '*:平台类型' => 'shop_type',
                    '*:原价金额' => 'original_money',
                    '*:销售金额' => 'sale_money',
                    '*:支付金额' => 'pay_money',
                    '*:优惠类型' => 'discount_type',
                    '*:优惠名称' => 'discount_name',
                    '*:优惠金额' => 'discount_money',
                    '*:下单时间' => 'order_createtime',
                    '*:付款时间' => 'paytime',
                    '*:付款状态' => 'pay_status',
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys($this->oSchema[$ioType]['main']);
        return $this->ioTitle[$ioType][$filter];
    }
    
    function export_csv($data, $exportType = 1)
    {
        $output   = array();
        $output[] = $data['title']['orderDiscounts'] . "\n" . implode("\n", (array)$data['content']['orderDiscounts']);
        echo implode("\n", $output);
    }
    
    /**
     * fgetlist_csv
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $exportType exportType
     * @return mixed 返回值
     */
    public function fgetlist_csv(&$data, $filter, $offset, $exportType = 1)
    {
        @ini_set('memory_limit', '1024M');
        if (!$data['title']) {
            $title = array();
            foreach ($this->io_title() as $k => $v) {
                $title[] = $v;
            }
            $data['title']['orderDiscounts'] = mb_convert_encoding('"' . implode('","', $title) . '"', 'GBK', 'UTF-8');
        }
    
        $oShop = app::get('ome')->model('shop');
        $rs    = $oShop->getList('shop_id,name');
        foreach ($rs as $v) {
            $shops[$v['shop_id']] = $v;
        }
        
        $limit = 100;
        if (!$orderDiscounts = $this->getList('*', $filter, $offset * $limit, $limit)) {
            return false;
        }
        
        $discounts_type = array(
            'pay'       => '支付优惠',
            'promotion' => '促销优惠',
        );
        $pay_status = array(
            0 => '未支付',
            1 => '已支付',
            2 => '处理中',
            3 => '部分付款',
            4 => '部分退款',
            5 => '全额退款',
            6 => '退款申请中',
            7 => '退款中',
            8 => '支付中',
        );
        foreach ($orderDiscounts as $aFilter) {
            $discountsRow['*:订单号']  = $aFilter['order_bn'] . "\t";
            $discountsRow['*:来源店铺'] = $shops[$aFilter['shop_id']]['name'];
            $discountsRow['*:平台类型'] = $aFilter['shop_type'];
            $discountsRow['*:原价金额'] = $aFilter['original_money'];
            $discountsRow['*:销售金额'] = $aFilter['sale_money'];
            $discountsRow['*:支付金额'] = $aFilter['pay_money'];
            $discountsRow['*:优惠类型'] = $discounts_type[$aFilter['discount_type']];
            $discountsRow['*:优惠名称'] = $aFilter['discount_name'];
            $discountsRow['*:优惠金额'] = $aFilter['discount_money'];
            $discountsRow['*:下单时间'] = date('Y-m-d H:i:s', $aFilter['order_createtime']);
            $discountsRow['*:付款时间'] = date('Y-m-d H:i:s', $aFilter['paytime']);
            $discountsRow['*:付款状态'] = $pay_status[$aFilter['pay_status']];
            
            $data['content']['orderDiscounts'][] = mb_convert_encoding('"' . implode('","', $discountsRow) . '"', 'GBK',
                'UTF-8');
        }
        
        return true;
    }
    
    //根据查询条件获取导出数据
    /**
     * 获取ExportDataByCustom
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $has_detail has_detail
     * @param mixed $curr_sheet curr_sheet
     * @param mixed $start start
     * @param mixed $end end
     * @param mixed $op_id ID
     * @return mixed 返回结果
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id)
    {
        //根据选择的字段定义导出的第一行标题
        if ($curr_sheet == 1) {
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        $oShop = app::get('ome')->model('shop');
        $rs    = $oShop->getList('shop_id,name');
        foreach ($rs as $v) {
            $shops[$v['shop_id']] = $v;
        }
        
        $this->io_title();
        
        if (!$orderDiscounts = $this->getList('*', $filter, $start, $end)) {
            return false;
        }
        
        $discounts_type = array(
            'pay'       => '支付优惠',
            'promotion' => '促销优惠',
        );
        $pay_status = array(
            0 => '未支付',
            1 => '已支付',
            2 => '处理中',
            3 => '部分付款',
            4 => '部分退款',
            5 => '全额退款',
            6 => '退款申请中',
            7 => '退款中',
            8 => '支付中',
        );
        foreach ($orderDiscounts as $aFilter) {
            $orderDiscountsRow['order_bn']         = $aFilter['order_bn']. "\t";
            $orderDiscountsRow['shop_id']          = $aFilter['shop_id'];
            $orderDiscountsRow['shop_type']        = $aFilter['shop_type'];
            $orderDiscountsRow['shop_id']          = $shops[$aFilter['shop_id']]['name'];
            $orderDiscountsRow['original_money']   = $aFilter['original_money'];
            $orderDiscountsRow['sale_money']       = $aFilter['sale_money'];
            $orderDiscountsRow['pay_money']        = $aFilter['pay_money'];
            $orderDiscountsRow['discount_type']    = $discounts_type[$aFilter['discount_type']];
            $orderDiscountsRow['discount_name']    = $aFilter['discount_name'];
            $orderDiscountsRow['discount_money']   = $aFilter['discount_money'];
            $orderDiscountsRow['pay_status']       = $pay_status[$aFilter['pay_status']];
            $orderDiscountsRow['order_createtime'] = date('Y-m-d H:i:s', $aFilter['order_createtime']);
            $orderDiscountsRow['paytime']          = date('Y-m-d H:i:s', $aFilter['paytime']);
            
            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if (isset($orderDiscountsRow[$col])) {
                    $orderDiscountsRow[$col] = mb_convert_encoding($orderDiscountsRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[]           = $orderDiscountsRow[$col];
                } else {
                    $exptmp_data[] = '';
                }
            }
            
            $data['content']['main'][] = implode(',', $exptmp_data);
        }
        
        return $data;
    }
}
