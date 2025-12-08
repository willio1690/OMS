<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 抖音店铺退款业务处理Lib类
 */
class ome_aftersale_request_luban extends ome_aftersale_abstract
{
    private $_render;

    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct()
    {
        $this->_render = app::get('ome')->render();
    }

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
        $applyInfo = $oRefund_apply->refund_apply_detail($apply_id);
        
        //拒绝退款
        if ($data['status'] == '3'){
            //$result = kernel::single('ome_service_refund_apply')->update_status($applyInfo, $data['status'], 'sync');
            //if($applyInfo['refund_refer'] != '1'){
            //    return array('rsp'=>'fail', 'msg'=>'抖音不支持售前拒绝退款');
            //}
            
            //提示错误信息,否则调用finance_addRefund()方法会默认同意退货退款
            if($applyInfo['return_id'] && $applyInfo['refund_refer'] == '1'){
                return array('rsp'=>'fail', 'msg'=>'不允许手工拒绝抖音退货退款的请求!');
            }
            
            //订单信息
            $orderObj = app::get('ome')->model('orders');
            $orderInfo = $orderObj->dump(array('order_id'=>$applyInfo['order_id']), '*');
            
            $applyInfo['order_bn'] = $orderInfo['order_bn'];
            $applyInfo['logi_id'] = $orderInfo['logi_id'];
            $applyInfo['logistics_no'] = $orderInfo['logi_no'];
            
            //转换物流公司编码
            if($applyInfo['logi_id']){
                $sql = "SELECT corp_id,type FROM sdb_ome_dly_corp WHERE corp_id=".$applyInfo['logi_id'];
                $corpInfo = $oRefund_apply->db->selectrow($sql);
                
                $applyInfo['company_code'] = $corpInfo['type'];
            }
            
            //@todo：抖音不支持售前拒绝退款
            $applyInfo['cancel_dly_status'] = 'FAIL';
            $applyInfo['refund_bn'] = $applyInfo['refund_apply_bn'];
            $applyInfo['trigger_event'] = '手工操作';
            
            //request
            $result = kernel::single('erpapi_router_request')->set('shop', $applyInfo['shop_id'])->finance_addRefund($applyInfo);
            
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
    public function return_product_detail($returninfo){
        # $html = $this->_render->fetch('admin/return_product/plugin/detail_taobao.html');
        # return $html;
    }

    /**
     * 售后拒绝时弹出的页面.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function return_button($return_id,$status){
        $rs = array('rsp'=>'default','msg'=>'','data'=>'');
        if ($status == '5') {
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_return&act=refuse_message&p[0]='.$return_id.'&p[1]=luban');
        }
        return $rs;
    }


    /**
     * 仅退款拒绝弹出页面
     * 
     * @param $refund_id
     * @param $status
     * @return array
     */
    public function refund_button($refund_id,$status){
        $rs = array('rsp'=>'default','msg'=>'','data'=>'');
        if ($status == '3') {
            return array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_refund_apply&act=refuse_message&p[0]='.$refund_id.'&p[1]=luban');

        }
        return $rs;
    }

}