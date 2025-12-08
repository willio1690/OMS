<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_members{
    
    var $detail_address = '地址库';
    
    
    function detail_address($member_id){
        $render = app::get('ome')->render();
        $addressObj = app::get('ome')->model('member_address');
        $address_detail = $addressObj->getList('*',array('member_id'=>$member_id));
        foreach($address_detail as &$address){
            $ship_area = $address['ship_area'] ? explode(':',$address['ship_area']) : '';
            
            if($ship_area){
                $address['ship_area'] = $ship_area[1];
                
            }

        }
        $render->pagedata['address_detail'] = $address_detail;

        $finder_id = $_GET['_finder']['finder_id'];
        $render->pagedata['finder_id'] = $finder_id;
        unset($address_detail);
        return $render->fetch('admin/member/address.html');
    }

    var $column_edit = "操作";
    var $column_edit_width = "200";
    var $addon_cols = "member_id";
    function column_edit($row){
        $member_id = $row[$this->col_prefix.'member_id'];
        $finder_id = $_GET['_finder']['finder_id'];
        $button = <<<EOF
            <a href="index.php?app=ome&ctl=admin_customer&act=add&p[0]=$member_id&type=copy&finder_id=$finder_id" target="dialog::{width:800,height:500,title:'复制会员'}">复制</a>
EOF;
        $button1 = <<<EOF
            <a href="index.php?app=ome&ctl=admin_customer&act=add&p[0]=$member_id&type=edit&finder_id=$finder_id" target="dialog::{width:800,height:500,title:'编辑会员'}">编辑</a>
EOF;
        $button2 = <<<EOF
            <a href="index.php?app=ome&ctl=admin_customer&act=edit_address&member_id=$member_id&finder_id=$finder_id" target="_blank">地址库</a>
EOF;
        $string = $button.' | '.$button1.' | '.$button2;
        return $string;
    }
}
?>