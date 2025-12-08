<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [航信]金税科技电子发票渠道
 */
class invoice_event_trigger_data_jinshui extends invoice_event_trigger_data_common
{
    /**
     * 组织参数
     * 
     * @param array $order
     * @param string $einvoice_type
     * @return array
     */
    public function getEinvoiceRequestParams($orderInfo, $einvoice_type='blue')
    {
        $rsp = array('rsp'=>'fail', 'error_msg'=>'');
        
        //绑定关系
        $result = kernel::single('erpapi_router_request')->set('bind',$orderInfo['channel_type'])->bind_bind();
        if(!$result){
            $error_msg = '绑定关系失败!';
            $rsp['error_msg'] = $error_msg;
            
            return $rsp;
        }
        
        $this->__total_je = $this->__total_se = 0;
        $this->tax_rate   = $orderInfo['tax_rate'] / 100;
        
        //发票明细列表
        $items = $this->getEinvoiceInvoiceItems($orderInfo, $einvoice_type);
        if(!$items){
            $error_msg = '没有发票明细';
            $rsp['error_msg'] = $error_msg;
            
            return $rsp;
        }
    
        if(bccomp($orderInfo['amount'], ($this->__total_je + $this->__total_se)) != 0){
            $rsp['error_msg'] = '开票金额不正确';
            return $rsp;
        }
        
        $hjje = $this->__total_je;
        $se   = $this->__total_se;
        $jshj = $this->__total_se + $this->__total_je;
        
        if($einvoice_type == 'red'){
            $hjje =- $hjje; //(价税)合计金额  = 价税合计 - 税额,
            $jshj =- $jshj;
            $se =- $se;
        }
        
        $orderInfo['items']         = $items;
        $orderInfo['einvoice_type'] = $einvoice_type;
        $orderInfo['hjjeTotal']     = $hjje; # (价税)合计金额  = 价税合计 - 税额,
        $orderInfo['seTotal']       = $se;
        $orderInfo['jshjTotal']     = $jshj; # 价税合计,价税合计 = 金额(含)
        $orderInfo['tid'] = $orderInfo['order_bn'];
        
        //单号需要唯一
        $orderInfo['order_bn'] .= '#'.$orderInfo['id'];
        
        return $orderInfo;
    }
}