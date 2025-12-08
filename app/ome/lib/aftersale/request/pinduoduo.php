<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request_pinduoduo  extends ome_aftersale_abstract{

    function show_aftersale_html(){
        
        $html = '';
        return $html;
    }

    /**
     * 退款状态保存前扩展
     * @param   data msg
     * @return
     * @access  public
     * @author
     */
    function pre_save_refund($apply_id,$data)
    {
        set_time_limit(0);

        $oRefund_apply = app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        if($refunddata['return_id'] > 0) {
            return array();
        }
        if ($data['status'] == '2' || $data['status'] == '3') {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata,$data['status'],'sync');


            return $result;
        }


    }
}