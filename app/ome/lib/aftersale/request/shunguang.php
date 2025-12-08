<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request_shunguang extends ome_aftersale_abstract{
    function show_aftersale_html(){

        $html = '';
        return $html;
    }
    function pre_save_refund($apply_id,$data){
        $oRefund_apply = app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        $result = kernel::single('ome_service_refund_apply')->update_status($refunddata,$data['status'],'sync');
        return $result;
    }

    function pre_save_return($data){
        set_time_limit(0);
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,$data['status'],'sync');
        if ($rsp  && $rsp['rsp'] == 'fail') {
            $rs['rsp'] = 'fail';
            $rs['msg'] = $rsp['msg'];
        }
        return $rs;
    }
}