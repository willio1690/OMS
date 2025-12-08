<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [小红书]店铺退款业务处理Lib类
 */
class ome_aftersale_request_huawei extends ome_aftersale_abstract
{
    function show_aftersale_html()
    {
        $html = '';
        
        return $html;
    }
    
    /**
     * 退款状态保存前扩展
     * 
     * @param int $apply_id
     * @param array $data
     * @return array
     */
    function pre_save_refund($apply_id, $data)
    {
        set_time_limit(0);
        
        $oRefund_apply = app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        if($data['status'] == '2' || $data['status'] == '3')
        {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata, $data['status'], 'sync');
            
            return $result;
        }
    }
    
    /**
     * 售后保存前的扩展
     * 
     * @param array $data
     * @return array
     */
    function pre_save_return($data)
    {
        set_time_limit(0);
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $status = $data['status'];
        
        if ($status == '3' || $status == '5')
        {
            $rsp = kernel::single('ome_service_aftersale')->update_status($return_id, $status, 'sync');
            if ($rsp  && $rsp['rsp'] == 'fail')
            {
                $rs['rsp'] = 'fail';
                $rs['msg'] = $rsp['msg'];
            }
        }
        
        return $rs;
    }
    
    /**
     * 售后服务详情查看页扩展
     * @param   array    $returninfo
     * @return  html
     * @access  public
     * @author
     */
    public function return_product_detail($returninfo)
    {
        return '';
    }
}