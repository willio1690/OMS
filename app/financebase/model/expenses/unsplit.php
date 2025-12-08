<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/27 16:41:57
 * @describe: model层
 * ============================
 */
class financebase_mdl_expenses_unsplit extends dbeav_model {
    public $has_export_cnf = true;
    public $export_name = '未拆单据';

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real=false)
    {
        $table_name = 'bill';
        if($real){
            return DB_PREFIX.'financebase_'.$table_name;
        }else{
            return $table_name;
        }
    }

    /**
     * modifier_bill_category
     * @param mixed $col col
     * @return mixed 返回值
     */
    public function modifier_bill_category($col) {
        return $col ? : '未识别类型';
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = NULL, $baseWhere = NULL){
        $baseWhere[] = ($tableAlias ? $tableAlias . '.' : '') . 'split_status in("2","4")';
        if(isset($filter['time_from']) && $filter['time_from']){
            $baseWhere[] = ' `trade_time` >='.strtotime($filter['time_from']);
        }
        
        if(isset($filter['time_to']) && $filter['time_to']){
            $baseWhere[] = ' `trade_time` <'.(strtotime($filter['time_to'])+86400);
        }
        if(isset($filter['split_time_from'])) { 
            if($filter['split_time_from']){
                $baseWhere[] = ' `split_time` >='.strtotime($filter['split_time_from']);
            }
            unset($filter['split_time_from']);
        }
        
        if(isset($filter['split_time_to'])) { 
            if($filter['split_time_to']){
                $baseWhere[] = ' `split_time` <'.(strtotime($filter['split_time_to'])+86400);
            }
            unset($filter['split_time_to']);
        }
        if(isset($filter['split_fail_msg'])) { 
            if($filter['split_fail_msg']){
                $fm = trim($filter['split_fail_msg']);
                $baseWhere[] = ' (`split_msg` like "%'.$fm.'%" or `confirm_fail_msg` like "%'.$fm.'%")';
            }
            unset($filter['split_fail_msg']);
        }
        if($filter['bill_category'] == '未识别类型') {
            $filter['bill_category'] = '';
        }
        return parent::_filter($filter, $tableAlias, $baseWhere);
    }

    /**
     * 获取PrimaryIdsByCustom
     * @param mixed $filter filter
     * @param mixed $opId ID
     * @return mixed 返回结果
     */
    public function getPrimaryIdsByCustom($filter, $opId) {
        if($filter['id']) {
            if($filter['time_from']) {
                unset($filter['time_from']);
            }
            if($filter['time_to']) {
                unset($filter['time_to']);
            }
        }
        $primary_ids = array();
        $primary_info = $this->getList('id', $filter, 0, -1);
        if($primary_info){
            foreach($primary_info as $info){
                $primary_ids[] = $info['id'];
            }
            $inLogData = array(
                'export_type' => 'items',
                'filter' => json_encode($filter, JSON_UNESCAPED_UNICODE),
                'export_time' => time(),
                'op_id' => $opId,
            );
            app::get('financebase')->model('expenses_export_log')->insert($inLogData);
        }
        return $primary_ids;
    }
}