<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_mdl_store_daliy extends dbeav_model{

    var $has_export_cnf = true;

    public $export_name = '门店每日汇总';

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' createtime >='.strtotime($filter['time_from']);
        }

        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' createtime <'.(strtotime($filter['time_to'])+86400);
        }

        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".implode(' AND ', $where);
    }

    function modifier_distribution_rate($row){
        if($row > 0){
            return ($row*100)."%";
        }else{
            return '0%';
        }
    }

    function modifier_self_pick_rate($row){
        if($row > 0){
            return ($row*100)."%";
        }else{
            return '0%';
        }
    }
}