<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class crm_finder_gift_rule_base{
    
    var $addon_cols = "disabled,gift_list,filter_arr";
    
    var $column_edit = "操作";
    var $column_edit_width = 100;
    var $column_edit_order = COLUMN_IN_HEAD;
    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $id = $row['id'];
        $button = '';
      
        $button .= '<a href="index.php?app=crm&ctl=admin_gift_rulebase&act=editRuleBase&id='.$id.'&finder_id='.$finder_id . '">编辑</a>';
        $button .= ' | <a href="index.php?app=crm&ctl=admin_gift_rulebase&act=copy_rulebase&id='.$id.'&finder_id='.$finder_id . '">复制</a>';
        return $button;
    }

    var $column_limit = '是否限量';
    var $column_limit_width = 200;
    /**
     * column_limit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_limit($row){
        $db = kernel::database();
        $filter_arr = $row[$this->col_prefix.'filter_arr'];
        $filter_arr = json_decode($filter_arr,true);
        $id = $row['id'];
        if($filter_arr['buy_goods']['limit_type'] == '1'){
            $sql = "select count(distinct order_bn) as total_orders from sdb_ome_gift_logs where rule_base_id=".$id."";
            $gift_logs = $db->selectRow($sql);
            $show_limit = '限量赠送:'.$filter_arr['buy_goods']['limit_orders'];
            $show_limit.='已送:'.$gift_logs['total_orders'].'';

            return sprintf("<div onmouseover='bindFinderColTip(event)' rel='%s' style='height:20px;background-color:%s;float:left;color:#ffffff;'>&nbsp;%s&nbsp;</div>", $show_limit, 'green', $show_limit);
        }else{
            return '-';
        }
    }



    var $detail_snapshot = '快照';
    /**
     * detail_snapshot
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_snapshot($id){
        $render = app::get('crm')->render();
        $mdl_snapshot = app::get('crm')->model("snapshot");
        
        $logs = $mdl_snapshot->getList('*', array('task_id'=>$id, 'type'=>3),0,-1,'id desc');
        
        $render->pagedata['logs'] = $logs;
        return $render->fetch("admin/snapshot/gift_rule.html");
    }
    
}
