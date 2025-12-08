<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_refund{

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
     * 添加退款单
     * @access public
     * @param int $refund_id 退款单ID
     */
    public function refund($refund_id){
        $refundModel = $this->app->model('refunds');
        $refund = $refundModel->dump($refund_id);
        $res = kernel::single('erpapi_router_request')->set('shop', $refund['shop_id'])->finance_addRefund($refund);
    }
    
    /**
     * 退款单请求
     * @access public
     * @param int $sdf 请求数据
     */
    public function refund_request($sdf){
        $res = kernel::single('erpapi_router_request')->set('shop', $sdf['shop_id'])->finance_addRefund($sdf);
    }
    
    /**
     * 退款单状态更新
     * @access public
     * @param int $refund_id 退款单ID
     */
    public function update_status($refund_id,$status=''){

    }

    public function refuse_refund($refundinfo){
        $rs = array('rsp'=>'fail','msg'=>'失败','data'=>'');
        return $rs;
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

    
    /**
     * 接受申请
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function accept_refundstatus($status,$apply_id)
    {
        $rsp = array('rsp'=>'fail','msg'=>'失败');

        return $rsp;
    }
}