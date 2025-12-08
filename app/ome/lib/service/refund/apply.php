<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_refund_apply{
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app)
    {
        $this->app = $app;
    }

    
    
    /**
     * 更新退款申请单状态
     * @param   array refund
     * @param   int   status
     * @return  array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function update_status($refund,$status,$mod = 'async')
    {
        $apply_id = $refund['apply_id'];
        $refundapplyModel = $this->app->model('refund_apply');
        $refundinfo = $refundapplyModel->dump($apply_id, '*');
        $refundinfo = array_merge($refund,$refundinfo);
        
        $rsp = kernel::single('erpapi_router_request')->set('shop', $refundinfo['shop_id'])->finance_updateRefundApplyStatus($refundinfo,$status,$mod);
        return $rsp;
    }

    /**
     * 回写留言和凭证
     */
    function add_refundmemo($data){
        $refundModel = $this->app->model('refund_apply');
        $apply_id = $data['apply_id'];
        $refund = $refundModel->dump($apply_id);
        $shop_id = $refund['shop_id'];
        $data['refund_apply_bn'] = $refund['refund_apply_bn'];
        $data['order_id'] = $refund['order_id'];
        $data['content'] = $data['newmemo']['op_content'];
        $data['image'] = $data['newmemo']['image'];

        kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_addRefundMemo($data);
    }

    
}
?>