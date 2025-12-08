<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_bill_verification_error {



    var $column_edit = "操作";
    var $column_edit_width = "150";

    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];

        $ret = '<a href="index.php?app=financebase&ctl=admin_shop_settlement_verification&act=setVerificationError&p[0]='.$row['id'].'&_finder[finder_id]=' . $finder_id . '&finder_id=' . $finder_id . '" target="dialog::{width:550,height:400,resizeable:false,title:\'核销规则编辑\'}">编辑</a>';

        return $ret;
    }



    var $detail_oplog = "操作日志";
    function detail_oplog($id){
        $render = app::get('financebase')->render();
        $mdlOpLog = app::get('financebase')->model("bill_verification_error");


        $info= $mdlOpLog->getRow('memo',array('id'=>$id));
        
        $list = @unserialize($info['memo']);
        $list = $list ?: [];
        
 		$render->pagedata['list'] = array_reverse($list);

        return $render->fetch("admin/verification/op_logs.html");
    }





}

