<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_pos_pekon_request_invoice extends erpapi_shop_request_invoice 
{
    /**
     * 电子发票回传平台
     * 
     * @return void
     * @author 
     */
    public function upload($sdf, $sync = false)
    {
        $params = $this->getUploadParams($sdf);

        $callback = array();

        
        $rs = $this->__caller->call('UpdateSalesOrderInvoiceInfo',$params,$callback,'电子发票回传',10,$sdf['invoice']['order_bn']);

       
        $this->uploadCallback($rs, array('electronic_item_id' => $sdf['electronic']['item_id']));
        

        return $rs;
    }

    /**
     * summary
     * 
     * @return void
     * @author 
     */
    public function uploadCallback($ret, $callback_params)
    {
        $status          = $ret['rsp'];

        if ($status == 'succ' && $callback_params['electronic_item_id']){
            app::get('invoice')->model('order_electronic_items')->update(array('upload_tmall_status'=>'2'),array('item_id'=>$callback_params['electronic_item_id']));
        }

        return $this->callback($ret, $callback_params);
    }

    protected function getUploadParams($sdf)
    {
        $invoice    = $sdf['invoice'];
        $electronic = $sdf['electronic'];

        $billing_type = $electronic['billing_type'];

        $status = '';
        switch($billing_type){
            case '1':
                $status = 'InvoicingCompleted';
            break;
            case '2':
                $status = 'InvoiceDiscarded';
            break;

        }
        
        if(empty($status)){
            return true;
        }
        $invoiceModeType = $invoice['mode'] == '0' ? 'Special' : 'General';
        $params = array(
            'thirdpartyOrderNo' =>  $invoice['order_bn'],//第三方生成的订单编号
            'thirdPartPayNo'    =>  '',//支付请求流水号
            'status'            =>  $status,//开票状态IssueApply:申请开票 CancelIssue:取消开票 InvoicingCompleted:开票完成 InvoiceDiscarded:发票作废
            'invoiceDate'       =>  date('Y-m-d H:i:s', $electronic['create_time']),//开票日期 格式"yyyy-MM-dd HH:mm:ss"
            'invoiceNum'        =>  $electronic['invoice_no'],//发票号码
            'invoiceCode'       =>  $electronic['invoice_code'],//发票代码
            'invoiceUrl'        =>  $electronic['url'],//发票下载地址
            'expressDocNo'      =>  $electronic['logi_no'],
            'LogisticsCompanyCode'=>$electronic['logi_name'],
            'invoiceModeType'   =>$invoiceModeType,//
            'taxpayerName'      =>  $invoice['title'],//名称
            'taxpayerIdentityNum'=>$invoice['ship_tax'],//纳税人识别号
            'taxpayerPhone'      => $invoice['ship_company_tel'],//纳税人手机号
            'taxpayerAddress'   =>  $invoice['ship_company_addr'],//注册地址
            'invoiceAccountsBank'   =>  $invoice['ship_bank'],//开户银行
            'invoiceAccount'        =>$invoice['ship_bank_no'],//银行账号
            'invoiceReceiveUrl'     =>  $invoice['ship_email'],//收件人邮箱
            'invoiceReceiveName'    =>$invoice['ship_name'],//收票人 姓名
            'invoiceReceiveAddress'    =>$invoice['ship_addr'],//收票人 地址
            'invoiceAmount'      => $invoice['amount'],
            'preInvoiceNum'      => (string)$electronic['normal_invoice_no'],
        );

        

        return $params;
    }


}
