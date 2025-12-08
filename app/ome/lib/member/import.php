<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_member_import {

    function run(&$cursor_id,$params,&$errmsg){
        $memberObj = app::get('ome')->model('members');

        foreach($params['sdfdata'] as $v){
			
            $uname = $v['uname'];
            $shopex_shop_type = ome_shop_type::shopex_shop_type();
            if(in_array($v['shop_type'],$shopex_shop_type)){
                $member_detail = $memberObj->dump(array('uname'=>$uname,'shop_id'=>$v['shop_id']),'member_id');
            }else{
                $member_detail = $memberObj->dump(array('uname'=>$uname,'shop_type'=>$v['shop_type']),'member_id');
            }
            
            if(!$member_detail['member_id']){
                kernel::single('ome_member_func')->save($v,$v['shop_id']);
            }
        }
        return false;
    }
}