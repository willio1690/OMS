<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_finder_monthly_report{

    var $column_edit = "操作";
    var $column_edit_width = "240";
    var $column_edit_order=5;
    function column_edit($row){
        $confhref = '';

        

        if($row['status'] == 2)
        {
            $confhref .= '<a href="index.php?app=finance&ctl=monthend_detail&act=index&p[0]='.$row['monthly_id'].'">账期明细</a>';
        }elseif ($row['status'] == 1) {
            $now_time = time();

            if($now_time > $row['end_time'])
            {
                $confhref = '<a target="dialog::{title:\'关账确认页面\',width:400,height:400}" href="index.php?app=finance&ctl=monthend&act=closebook&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['monthly_id'].'">关账</a>&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            $confhref .= '<a href="index.php?app=finance&ctl=monthend_verification&act=index&p[0]='.$row['monthly_id'].'&finder_vid='.$_GET['finder_vid'].'">核销列表</a>&nbsp;&nbsp;&nbsp;&nbsp;';
            // $confhref .= '<a href="index.php?app=finance&ctl=monthend_uncharge&act=index&p[0]='.$row['monthly_id'].'&finder_vid='.$_GET['finder_vid'].'">往期单据</a>&nbsp;&nbsp;&nbsp;&nbsp;';//核销更改， 该功能不可用 

            // $confhref .= '<a href="index.php?app=finance&ctl=monthend&act=reverify&p[0]='.$row['monthly_id'].'">重新核销</a>';//核销更改， 该功能不可用 

        }

        return $confhref;
    }
}