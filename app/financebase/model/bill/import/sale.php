<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_mdl_bill_import_sale extends dbeav_model{

//    var $defaultOrder = array('id DESC');
    public $filter_use_like = true;

//
//    public function _filter($filter, $tableAlias = NULL, $baseWhere = NULL){
//        $where = '';
//        if(isset($filter['pay_serial_number']))
//        {
//            $where .= " AND io.pay_serial_number like '%{$filter['pay_serial_number']}%'" ;
//            unset($filter['pay_serial_number']);
//        }
//
//        if(isset($filter['import_id']))
//        {
//            $where .= " AND s.import_id = {$filter['import_id']}" ;
//            unset($filter['import_id']);
//        }
//        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
//    }

    public function getRow($cols='*',$filter=array())
    {
        $sql = "SELECT $cols FROM ".$this->table_name(true)." WHERE ".$this->filter($filter);
        return $this->db->selectrow($sql);
    }
//
//    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
//    {
//
//        $datas = array();
//        $sql = "select s.cost_project as cost_project,
//                    du.name,
//                    io.id as id,
//                    io.confirm_status as confirm_status,
//                    io.pay_serial_number as pay_serial_number,
//                    io.expenditure_time as expenditure_time,
//                    io.expenditure_money as expenditure_money,
//                    io.confirm_time as confirm_time
//                    from sdb_financebase_bill_import_summary as s
//                    inner join sdb_financebase_bill_import_sale as io on s.id = io.summary_id
//                    inner join sdb_desktop_users du on io.op_id = du.user_id where ".$this->_filter($filter)." order by io.id desc";
//
//        $datas = $this->db->selectLimit($sql,$limit,$offset);
//        if (!$datas) {
//            return array();
//        }
//        foreach ($datas as $k=>$v) {
////            if ($v['confirm_status'] == 0) {
////                $datas[$k]['confirm_status'] = '未确认';
////            } else {
////                $datas[$k]['confirm_status'] = '已确认';
////            }
//
//            if ($v['confirm_time']) {
//                $datas[$k]['confirm_time'] = $v['confirm_time'];
//            } else {
//                $datas[$k]['confirm_time'] = '';
//            }
//        }
//
//        return $datas;
//    }

}
