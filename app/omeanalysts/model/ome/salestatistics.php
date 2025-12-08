<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_mdl_ome_salestatistics extends dbeav_model
{
    
    var $export_name = '退货率统计';
    
    /**
     * exportName
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function exportName(&$data)
    {
        $data['name'] = $_POST['time_from'] . '到' . $_POST['time_to'] . $this->export_name;
    }
    
    /**
     * 获取_count
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function get_count($filter = null)
    {
        
        $sql = 'select sum(order_num) as total_order_num,
        sum(order_amount) as total_order_amount,
        sum(delivery_num) as total_delivery_num,
        sum(delivery_amount) total_delivery_amount,
        sum(delivery_return_num) total_delivery_return_num,
        sum(delivery_return_amount) total_delivery_return_amount,
        sum(return_num) total_return_num,
        sum(return_amount) total_return_amount
        from sdb_omeanalysts_ome_salestatistics where ' . $this->_filter($filter);
        
        $rows                      = $this->db->select($sql);
        $data                      = $rows[0];
        $total_delivery_num        = (int)$data['total_delivery_num'] ?? 0;
        $total_delivery_return_num = (int)$data['total_delivery_return_num'] ?? 0;
        
        $total_delivery_amount        = (float)$data['total_delivery_amount'] ?? 0;
        $total_delivery_return_amount = (float)$data['total_delivery_return_amount'] ?? 0;
        
        $data['total_return_num_rate']    = '0.00%';
        $data['total_return_amount_rate'] = '0.00%';
        if (!empty($total_delivery_return_num) && !empty($total_delivery_num)) {
            $data['total_return_num_rate'] = bcmul(bcdiv($total_delivery_return_num, $total_delivery_num, 2), 100,2) . '%';
        }
        
        if (!empty($total_delivery_return_amount) && !empty($total_delivery_amount)) {
            $data['total_return_amount_rate'] = bcmul(bcdiv($total_delivery_return_amount, $total_delivery_amount, 2), 100,2) . '%';
        }
        
        return $data;
    }
    
    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter = null)
    {
        $sql = "select count(*) as _count from sdb_omeanalysts_ome_salestatistics where " . $this->_filter($filter);
        
        $rows = $this->db->select($sql);
        return $rows[0]['_count'];
    }
    
    public function getlist($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderType = null)
    {
        $sql = "select * from sdb_omeanalysts_ome_salestatistics where " . $this->_filter($filter);
        if ($orderType) $sql .= ' order by ' . (is_array($orderType) ? implode($orderType, ' ') : $orderType);
        $rows         = $this->db->selectLimit($sql, $limit, $offset);
        $shopTypeList = ome_shop_type::get_shop_type();
        foreach ($rows as $key => $val) {
            $delivery_num        = (int)$val['delivery_num'] ?? 0;
            $delivery_return_num = (int)$val['delivery_return_num'] ?? 0;
            
            $delivery_amount        = (float)$val['delivery_amount'] ?? 0;
            $delivery_return_amount = (float)$val['delivery_return_amount'] ?? 0;
            
            $rows[$key]['return_num_rate']    = '0.00%';
            $rows[$key]['return_amount_rate'] = '0.00%';
            if (!empty($delivery_return_num) && !empty($delivery_num)) {
                $rows[$key]['return_num_rate'] = bcmul(bcdiv($delivery_return_num, $delivery_num, 2), 100,2) . '%';
            }
            
            if (!empty($delivery_return_amount) && !empty($delivery_amount)) {
                $rows[$key]['return_amount_rate'] = bcmul(bcdiv($delivery_return_amount, $delivery_amount, 2), 100,2) . '%';
            }
            $rows[$key]['shop_type'] = $shopTypeList[$val['shop_type']] ?? '';
            
        }
        return $rows;
    }
    
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        $where = [1];
        if (isset($filter['time_from']) && $filter['time_from']) {
            $where[] = ' day >= ' . strtotime($filter['time_from']);
        }
        if (isset($filter['time_to']) && $filter['time_to']) {
            $where[] = ' day < ' . (strtotime($filter['time_to']) + 86400);
        }
        unset($filter['time_from'], $filter['time_to']);
        
        if(isset($filter['shop_id']) && $filter['shop_id']){
            if(is_array($filter['shop_id'])) {
                $shopIds = array_filter($filter['shop_id']);
                if($shopIds){
                    // 对数组中每个元素进行 addslashes 转义
                    $escapedShopIds = array_map('addslashes', $shopIds);
                    $where[] = ' shop_id IN (\'' . implode("','", $escapedShopIds) . '\')';
                }
            } else {
                $where[] = ' shop_id =\''.addslashes($filter['shop_id']).'\'';
            }
        }
        unset($filter['shop_id']);
        
        if (isset($filter['day'])) {
            $where[] = ' day = ' . $filter['day'];
            unset($filter['day']);
        }
        
        return parent::_filter($filter, $tableAlias, $baseWhere) . " AND " . implode(' AND ', $where);
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        $schema                                  = parent::get_schema();
        $schema['columns']['return_num_rate']    = ['type' => 'decimal(10,4)', 'default' => '0.00', 'label' => '当日数量退货率', 'in_list' => true, 'default_in_list' => true, 'order' => 115];
        $schema['columns']['return_amount_rate'] = ['type' => 'decimal(10,4)', 'default' => '0.00', 'label' => '当日金额退货率', 'in_list' => true, 'default_in_list' => true, 'order' => 125];
        
        foreach ($schema['columns'] as $col => $detail) {
            if ($detail['in_list']) {
                $schema['in_list'][] = $col;
            }
            if ($detail['default_in_list']) {
                $schema['default_in_list'][] = $col;
            }
        }
        return $schema;
    }
    
    
    //商品销量统计title
    /**
     * 获取_export_main_title
     * @return mixed 返回结果
     */
    public function get_export_main_title()
    {
        $title = array(
            'col:日期',
            'col:店铺',
            'col:下单量',
            'col:发货量',
            'col:销售额',
            'col:负销售额',
            'col:售后量',
            'col:完成售后量',
        );
        return $title;
    }
    
    //商品销量统计
    /**
     * 获取_export_main
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function get_export_main($filter, $offset, $limit, &$data)
    {
        $data = $this->getlist('*', $filter, $offset, $limit);
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
        @ini_set('memory_limit', '512M');
        
        $limit = 100;
        
        $salestatistics = $this->getList('*', $filter, $offset * $limit, $limit);
        if (!$salestatistics) return false;
        
        
        if (!$data['title']) {
            $title = array();
            foreach ($this->io_title() as $k => $v) {
                $title[] = $v;
            }
            
            $data['title'] = mb_convert_encoding('"' . implode('","', $title) . '"', 'GBK', 'UTF-8');
        }
        
        foreach ($salestatistics as $k => $aFilter) {
            $aFilter['day'] = $aFilter['day'] ? date('Y-m-d', $aFilter['day']) : '';
            foreach ($this->oSchema['csv']['main'] as $kk => $v) {
                $statisticsRow[$kk] = $this->charset->utf2local($aFilter[$v]);
                
            }
            $data['contents'][] = '"' . implode('","', $statisticsRow) . '"';
        }
        
        return true;
    }
    
    /**
     * export_csv
     * @param mixed $data 数据
     * @param mixed $exportType exportType
     * @return mixed 返回值
     */
    public function export_csv($data, $exportType = 1)
    {
        
        $output   = array();
        $output[] = $data['title'] . "\n" . implode("\n", (array)$data['contents']);
        
        echo implode("\n", $output);
    }
    
    /**
     * io_title
     * @param mixed $filter filter
     * @param mixed $ioType ioType
     * @return mixed 返回值
     */
    public function io_title($filter = null, $ioType = 'csv')
    {
        switch ($ioType) {
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
                    '*:店铺编码'    => 'shop_bn',
                    '*:店铺名称'    => 'shop_name',
                    '*:店铺类型'    => 'shop_type',
                    '*:发货时间'    => 'day',
                    '*:当日下单量'   => 'order_num',
                    '*:当日下单金额'  => 'order_amount',
                    '*:当日发货量'   => 'delivery_num',
                    '*:当日发货金额'  => 'delivery_amount',
                    '*:发货退货量'   => 'delivery_return_num',
                    '*:发货退货金额'  => 'delivery_return_amount',
                    '*:当日退货量'   => 'return_num',
                    '*:当日数量退货率' => 'return_num_rate',
                    '*:当日退货金额'  => 'return_amount',
                    '*:当日金额退货率' => 'return_amount_rate',
                    '*:更新时间'    => 'up_time',
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys($this->oSchema[$ioType]['main']);
        return $this->ioTitle[$ioType][$filter];
    }
}
