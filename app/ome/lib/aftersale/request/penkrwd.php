<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request_penkrwd extends ome_aftersale_abstract{
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->_render = app::get('ome')->render();
    }

    function show_aftersale_html(){
        
        $html = '';
        return $html;
    }

    function pre_save_refund($apply_id,$data)
    {
        $rs = array('rsp'=>'succ','msg'=>'成功','data'=>'');
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        if ($data['status'] == '3'||$data['status'] == '2') {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata,$data['status'],'sync');
            return $result;
        }
    }
}
?>