<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_base{


	var $detail_basic = "原始数据";
    
    function detail_basic($id){

        $render = app::get('financebase')->render();
        $mdlBill = app::get('financebase')->model("bill");
        $mdlBillBase = app::get('financebase')->model("bill_base");

        $bill_row = $mdlBill->getList('unique_id,shop_id,platform_type',array('id'=>$id));
        $base_bill_row = $mdlBillBase->getList('content',array('shop_id'=>$bill_row[0]['shop_id'],'unique_id'=>$bill_row[0]['unique_id']));

        $class_name = 'financebase_data_bill_'.$bill_row[0]['platform_type'];
        if (!ome_func::class_exists($class_name)){
        	die($bill_row[0]['platform_type'].'无此类型的方法');
        }

        $instance = kernel::single($class_name);

        $array_title = $instance->getTitle();

        $array_content = json_decode($base_bill_row[0]['content'],1);

        $info = array();

        foreach ($array_title as $key => $value) {
        	$info[$key] = array('title'=>$value,'content'=>$array_content[$key]);
        }

 		$render->pagedata['info'] = $info;

        return $render->fetch("admin/bill/detail.html");
    }

    public $detail_expenses = '费用拆分';
    /**
     * detail_expenses
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_expenses($id) {
        $render = app::get('financebase')->render();
        $rows = app::get('financebase')->model('expenses_split')->getList('*', array('bill_id'=>$id));
        foreach ($rows as $k => $v) {
            $bm = app::get('material')->model('basic_material')->db_dump(array('bm_id'=>$v['bm_id']), 'material_bn,material_name');
            $rows[$k]['material_bn'] = $bm['material_bn'];
            $rows[$k]['material_name'] = $bm['material_name'];
            $rows[$k]['split_status'] = $v['split_status'] == '2' ? '红冲项' : ($v['split_status'] == '1' ? "调整项" : '拆分项');
        }
        $render->pagedata['expenses'] = $rows;
        return $render->fetch("admin/bill/expenses_split.html");
    }

    var $column_edit = "操作";
    var $column_edit_width = "80";
    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];

        $ret = '<a href="index.php?app=financebase&ctl=admin_shop_settlement_bill&act=edit&p[0]='.$row['id'].'&finder_id=' . $finder_id . '" target="dialog::{width:690,height:200,title:\'编辑\'}">编辑</a>';

        return $ret;
    }

}

