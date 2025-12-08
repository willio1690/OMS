<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [阿里淘宝]电子发票渠道
 */
class invoice_event_trigger_data_taobao extends invoice_event_trigger_data_common
{
    /**
     * 组织参数
     *
     * @param array $order
     * @param string $einvoice_type
     * @return array
     */
    function getEinvoiceRequestParams($orderInfo, $einvoice_type='blue')
    {
        $rsp = array('rsp'=>'fail', 'error_msg'=>'');
        
        $this->__total_je = $this->__total_se = 0;
        $this->tax_rate = $orderInfo['tax_rate']/100;
        
        $area = $orderInfo['ship_area'];
        kernel::single('ome_func')->split_area( $area);
        
        $ghdwdzdh = $area[0].$area[1].$area[2].$orderInfo['ship_addr'];
        
        //发票明细列表
        $items = $this->getEinvoiceInvoiceItems($orderInfo,$einvoice_type);
        if(!$items){
            $error_msg = '没有发票明细';
            $rsp['error_msg'] = $error_msg;
            
            return $rsp;
        }
    
        if(bccomp($orderInfo['amount'], ($this->__total_je + $this->__total_se)) != 0){
            $error_msg = '开票金额不正确';
            $rsp['error_msg'] = $error_msg;
    
            return $rsp;
        }
        
        // 不含税金额
        $hjje = $this->__total_je;

        // 税额
        $se =   $this->__total_se;

        // 开票金额
        $jshj = $this->__total_se + $this->__total_je;
        
        if($einvoice_type == 'red'){
            $hjje =- $hjje; //(价税)合计金额  = 价税合计 - 税额,
            $jshj =- $jshj; //合计金额 
            $se =- $se; //税额
        }
        
        //获取platform invoice_amount provider_appkey proxy_appkey
        $shop_info = kernel::single('ome_shop')->getRowByShopId($orderInfo['shop_id']);
        
        $einvoice_shop_type = kernel::single('invoice_common')->returnEinvoiceShopType($shop_info);
        
        $platform = kernel::single('invoice_common')->getPlatformByShopType($einvoice_shop_type);
        
        $invoice_amount = $orderInfo["amount"]; //开票金额（价税合计）
        
        $mdlInOrderSet = app::get('invoice')->model('order_setting');
        $rs_invoice_setting = $mdlInOrderSet->dump(array("shop_id"=>$orderInfo["shop_id"]));
        
        $params = array(
             'id'                => $orderInfo['id'],
             'shop_id'           => $orderInfo['shop_id'],
             'order_bn'          => $orderInfo['order_bn'],
             "business_type"     => "0", //默认：0。对于商家对个人开具，为0;对于商家对企业开具，为1;
             "platform"          => $platform, //电商平台代码
             "tid"               => $orderInfo["order_bn"], //电商平台对应的订单号
             "serial_no"         => $orderInfo["serial_no"], //开票流水号 例子： 20141234123412341
             "payee_address"     => $orderInfo['address'], //开票方地址(新版中为必传)
             "payee_name"        => $orderInfo["payee_name"], //开票方名称，公司名(如:XX商城)
             "payee_operator"    => $orderInfo["payee_operator"], //开票人
             "invoice_amount"    => number_format($jshj,2,".","") , //开票金额
             "invoice_time"      => date("Y-m-d H:i:s",time()), //开票日期
             "invoice_type"      => $einvoice_type, //发票(开票)类型，蓝票blue,红票red，默认blue
             "payee_register_no" => $orderInfo["tax_no"],  //收款方税务登记证号
             "payer_name"        => $orderInfo["title"], //付款方名称, 对应发票台头
             "sum_price"         => number_format($hjje,2,".",""),  //合计金额(新版中为必传) 订单总金额
             "sum_tax"           => number_format($se,2,".","")  , //合计税额
             "items"             => $items, //电子发票明细
             
             "erp_tid"           => '', //erp中唯一单据
             "payee_bankaccount" => $orderInfo["bank"].$orderInfo["bank_no"], //开票方银行及 帐号
             "payer_register_no" => $orderInfo["ship_tax"], //付款方税务登记证号。对企业开具电子发票时必填
             "invoice_memo"      => $orderInfo["remarks"], //发票备注
             "payer_address"     => $orderInfo["ship_company_addr"], //消费者地址
             "payer_bankaccount" => $orderInfo["ship_bank"].$orderInfo["ship_bank_no"], //付款方开票开户银行及账号
             "payer_email"       => '', //消费者电子邮箱
             "payer_phone"       => $orderInfo['ship_company_tel'], //$orderInfo["ship_tel"], //消费者联系电话
             "payee_checker"     => $orderInfo['payee_checker'], //复核人
             "payee_receiver"    => $orderInfo['payee_receiver'], //收款人
             "payee_phone"       => $orderInfo["telephone"], //收款方电话
        );
        
        if($einvoice_type == "red"){
            $params['normal_invoice_code'] = $orderInfo['invoice_code'] ? $orderInfo['invoice_code'] : ''; //被红冲发票代码
            $params['normal_invoice_no'] = $orderInfo['invoice_no'] ? $orderInfo['invoice_no'] : ''; //被红冲发票号码
        }
        
        return $params;
    }
 
    /**
     * 货物板式文件下载地址参数
     *
     * @param array $orderInfo
     * @return array
     */
    public function getEinvoiceGetUrlRequestParams($orderInfo)
    {
        //组打接口获取电子发票url的参数
        $params = kernel::single('invoice_electronic')->getEinvoiceGetUrlRequestParams($orderInfo);
        
        return $params;
    }
}
