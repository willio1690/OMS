<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request_websited1m extends ome_aftersale_abstract
{
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->_render = app::get('ome')->render();
    }
    
    /**
     * 退款状态保存前扩展
     * @param data msg
     * @return
     * @access  public
     * @author
     */
    function pre_save_refund($apply_id, $data)
    {
        set_time_limit(0);
        
        $oRefund_apply = app::get('ome')->model('refund_apply');
        
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        if ($data['status'] == '2' || $data['status'] == '3') {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata, $data['status'], 'sync');
            return $result;
        }
    }
}