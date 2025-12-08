<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [百望]电子发票渠道
 */
class invoice_event_trigger_data_baiwang extends invoice_event_trigger_data_common
{
    /**
     * 发票材质映射
     * @var string[]
     * inner: 0 纸票;1 电票
     * outer: PAPER 纸票;ELECTRONIC 电票
     */
    private $invoiceMaterialMapping = [
        '0' => '0',    // 纸票
        '1' => '0',    // 电票
    ];

    /**
     * 发票类型映射
     * @var string[]
     * inner: type_id 0 普通发票;1 专用发票
     * outer: PLAIN 普通发票;VAT 专用发票
     */
    private $invoiceTypeMapping4 = [
        '0' => '02',    // 普通票
        '1' => '01',    // 专用发票
    ];

    private $invoiceTypeMapping3 = [
        '0' => '026',    // 普通票
        '1' => '028',    // 专用发票
    ];

    /**
     * 组织参数
     *
     * @param array $orderInfo
     * @param string $einvoice_type
     * @param string $error_msg
     * @return array
     */
    public function getEinvoiceRequestParams($orderInfo, $einvoice_type = 'blue', $source = 'b2c')
    {
        $rsp = array ('rsp' => 'fail', 'error_msg' => '');
        // 渠道扩展数据
        $channelExtendData = json_decode($orderInfo['channel_extend_data'], true);
        //绑定关系
        $result = kernel::single('erpapi_router_request')->set('bind', 'baiwang')->bind_bind($channelExtendData);
        if (!$result) {
            $error_msg        = '绑定关系失败!';
            $rsp['error_msg'] = $error_msg;

            return $rsp;
        }

        $area = $orderInfo['ship_area'];
        kernel::single('ome_func')->split_area($area);

        $this->__total_je = $this->__total_se = 0;
        $this->tax_rate   = $orderInfo['tax_rate'] / 100;

        //发票明细列表
        $items = $this->getEinvoiceInvoiceItems($orderInfo, $einvoice_type);

        if (!$items) {
            $error_msg        = '没有发票明细';
            $rsp['error_msg'] = $error_msg;

            return $rsp;
        }
        $items              = $this->getEinvoiceInvoiceItemsParams($items);
        $invoiceTypeMapping = $this->invoiceTypeMapping4;
        if (isset($orderInfo['channel_golden_tax_version']) && $orderInfo['channel_golden_tax_version'] == '0') {
            $invoiceTypeMapping = $this->invoiceTypeMapping3;
        }

        $params = [
            'buyerAddress'       => $orderInfo['ship_company_addr'], // 购方单位地址
            'buyerBankName'      => $orderInfo['ship_bank'], // 购方开户行
            'buyerBankNumber'    => $orderInfo['ship_bank_no'], // 购方账户
            'buyerName'          => $orderInfo['title'], // 购方客户名称
            'buyerTaxNo'         => $orderInfo['ship_tax'], // 购方单位代码
            'buyerTelphone'      => $orderInfo['ship_company_tel'], // 电话
            'invoiceTypeCode'    => $invoiceTypeMapping[$orderInfo['type_id']],
            'invoiceType'        => $einvoice_type == 'red' ? 1 : 0, // 开票类型 0:正数发票（蓝票） 1：负数发票（红票）默认0
            'remarks'            => $orderInfo["remarks"], // 备注:长度为230个字符
            'sellerAddress'      => $orderInfo['address'], // 地址
            'sellerBankName'     => $orderInfo["bank"], //开票方银行
            'sellerBankNumber'   => $orderInfo["bank_no"], // 开票方银行帐号
            'sellerTelphone'     => $orderInfo['telephone'], // 开票方电话
            'serialNo'           => $orderInfo["order_electronic_items"]['serial_no'], // 矩阵流水号 - oms开票申请单号 - 银联商户订单号
            'invoiceDetailsList' => $items,
            'outTradeNo'         => $orderInfo["order_bn"],
        ];

        // 推送邮箱
        if ($orderInfo["ship_email"]) {
            $params['notifyEMail'] = $orderInfo["ship_email"];
        }

        // 推送手机
        $mobilePattern = '/^1[3-9]\d{9}$/';
        if ($orderInfo["ship_tel"] && preg_match($mobilePattern, $orderInfo["ship_tel"])) {
            $params['notifyMobileNo'] = $orderInfo["ship_tel"];
        }

        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        $rs_item        = $mdlInOrderElIt->dump(array ("id" => $orderInfo["id"], "billing_type" => '1'));
        if ($rs_item) {
            $params['originalInvoiceCode'] = $rs_item['invoice_code'];
            $params['originalInvoiceNo']   = $rs_item['invoice_no'];
        }


        // 按要求补充备注
        if ($einvoice_type == 'blue' && $orderInfo["order_bn"] && $source == 'b2c') {
            $params['remarks'] .= ' 订单号:' . $orderInfo["order_bn"];
        }

        # 红票补充参数
        if ($einvoice_type == 'red' && $orderInfo['type_id'] == 1) {
            $params['redConfirmUuid'] = $orderInfo["order_electronic_items"]['red_confirm_uuid'];
        }

        // 请求与回调数据分层
        $sdf = [
            'params' => $params,
            'order'  => $orderInfo,
        ];

        return $sdf;
    }

    public function getEinvoiceInvoiceItemsParams($tmpItems = [],$type = 'blue')
    {
        $mapping = [
            'fpmxxh' => 'goodsLineNo',      // 明细行号
            'fphxz'  => 'invoiceLineNature', // 发票行性质,0：正常行 1：折扣行 2：被折扣行
            'spbm'   => 'goodsCode',          // 税收分类编码（末级节点）
            'spmc'   => 'goodsName',          // 商品名称
            'ggxh'   => 'goodsSpecification', // 规格型号
            'dw'     => 'goodsUnit',            // goodsUnit
            'spsl'   => 'goodsQuantity',      // 商品数量
            'je'     => 'goodsTotalPrice',    // 商品金额(不含税)
            'se'     => 'goodsTotalTax',        // 商品税额
            'sl'     => 'goodsTaxRate',         // 税率
        ];

        $invoiceDetailsList = [];
        foreach ($tmpItems as $line => $tmpItem) {
            $item = [];
            foreach ($tmpItem as $key => $value) {
                if (isset($mapping[$key])) {
                    $item[$mapping[$key]] = $value;
                }
                // 税率处理
                if ($key == 'sl') {
                    $item[$mapping[$key]] = $value;
                }
                //折扣行商品单价、数量应该为null
                if ($item['invoiceLineNature'] == '1') {
                    $item['goodsQuantity'] = 0;
                }
            }
            if ($type == 'red') {
                $item['originalInvoiceDetailNo'] = $item['goodsLineNo'];
                $item['goodsSimpleName']         = $item['goodsName'];
                $item['projectName']             = $item['goodsName'];
                unset($item['invoiceLineNature']);
            }
            $invoiceDetailsList[] = $item;
        }

        return $invoiceDetailsList;
    }

    /**
     * 货物板式文件下载地址参数
     *
     * @param array $orderInfo
     * @return array
     */
    public function getEinvoiceGetUrlRequestParams($orderInfo)
    {
        $obj_order_electronic_items = app::get('invoice')->model('order_electronic_items');

        $filter                 = array ('invoice_code' => $orderInfo['invoice_code'], 'invoice_no' => $orderInfo['invoice_no']);
        $order_electronic_items = $obj_order_electronic_items->getList('serial_no', $filter);

        $channel_extend_data = json_decode($orderInfo['channel_extend_data'], true); //银联渠道的一些扩展数据

        $params['nsrsbh']   = $channel_extend_data['bw_user'];#纳税人识别号码
        $params['jrdm']     = $channel_extend_data['bw_key'];#接入代码
        $params['fpqqlsh']  = $order_electronic_items[0]['serial_no'];#qqlx为1时，发票流水号必填
        $params['fpdm']     = $orderInfo['invoice_code'];#qqlx为0时，发票代码号码必填
        $params['fphm']     = $orderInfo['invoice_no'];
        $params['tsbz']     = '0';
        $params['gfkhdh']   = '';#用于接收版式推送短信
        $params['gfkhyx']   = '';#用于接收版式推送邮箱
        $params['order_bn'] = $orderInfo['order_bn'];

        return $params;
    }


    /**
     * 获取红字申请创建参数
     * @param $orderInfo
     * @return array|false
     */
    public function getCancelApplyRequestParams($orderInfo)
    {

        if (!$orderInfo) {
            return false;
        }

        $checkRs = $this->_getCancelApplyRequestParamsCheck($orderInfo);
        if (!$checkRs) {
            return false;
        }
        //发票明细列表
        $items = $this->getEinvoiceInvoiceItems($orderInfo, 'red');

        if (!$items) {
            return false;
        }
        $items = $this->getEinvoiceInvoiceItemsParams($items,'red');

        $requestParams = [
            'taxNo'                         => $orderInfo["tax_no"],  // 机构税号
            'redConfirmSerialNo'            => $orderInfo["order_electronic_items"]['serial_no'],      // 红字确认单流水号,调用方传递
            'entryIdentity'                 => '01',      // 录入方身份 01:销方,02:购方
            'sellerTaxNo'                   => $orderInfo["tax_no"],      //销售方统一社会信用代码/纳税人识别号/身份证件号码
            'sellerTaxName'                 => $orderInfo["payee_name"],      //销售方名称
            'buyerTaxName'                  => $orderInfo["title"],      //购买方名称
            'originInvoiceIsPaper'          => $orderInfo["type_id"] == '0' ? 'N' : 'Y',      //是否纸质发票标志 Y：纸质发票 N：电子发票
            'originInvoiceDate'             => date('Y-m-d H:i:s', $orderInfo['dateline']),  // 蓝字发票开票日期 yyyy-MM-dd HH:mm:ss
            'originalInvoiceNo'             => $orderInfo['invoice_no'],  // 蓝字发票全电发票号码，【发票来源】为2时必填
            'originInvoiceTotalPrice'       => $orderInfo['amount'] - $orderInfo['cost_tax'],  //  蓝字发票合计金额
            'originInvoiceTotalTax'         => $orderInfo['cost_tax'],  // 蓝字发票合计税额
            'originInvoiceType'             => $orderInfo["type_id"] == '0' ? '02' : '01',  // 蓝字发票票种代码 01:增值税专用发票 02:普通发票 03:机动车统一销售发票 04:二手车统一销售发票
            'invoiceTotalPrice'             => -1 * ($orderInfo['amount'] - $orderInfo['cost_tax']),  // 红字冲销金额
            'invoiceTotalTax'               => -1 * $orderInfo['cost_tax'],  // 红字冲销税额
            'redInvoiceLabel'               => '01',  // 红字发票冲红原因代码 01:开票有误 02:销货退回 03:服务中止 04:销售折让
            // 发票来源：全电平台红冲必须要传递的字段
            // 1:增值税发票管理系统：表示此发票是通过原税控系统开具的增值税发票，红冲此类发票时，税控设备需注销后才可以申请全电的红字确认单；
            // 2:电子发票服务平台：表示此发票是通过电子发票服务平台开具的全电发票（包括全电纸质发票），红冲此类发票时需要传递蓝票属性为此；
            'invoiceSource'                 => '2',//【发票来源】
            'redConfirmDetailReqEntityList' => json_encode($items),
            'serialNo'                      => $orderInfo["invoice_apply_bn"],      //
            'autoIssueSwitch'               => 'Y',      //非确认认即开——自动开票 Y:自动开票 N：不自动开票 默认为N
        ];

        return [
            'request_params' => $requestParams,
            'order'          => $orderInfo,
        ];

    }

    private function _getCancelApplyRequestParamsCheck($sdf)
    {
        // 目前不是初始状态都不允许发起
        if (!in_array($sdf['order_electronic_items']['red_confirm_status'], ['0', '500'])) {
            return false;
        }

        return true;
    }

    public function getEinvoiceCreateFileRequestParams($orderInfo)
    {
        $rsp = array ('rsp' => 'fail', 'error_msg' => '');
        // 渠道扩展数据
        $channelExtendData = json_decode($orderInfo['channel_extend_data'], true);
        //绑定关系
        $result = kernel::single('erpapi_router_request')->set('bind', 'baiwang')->bind_bind($channelExtendData);
        if (!$result) {
            $error_msg        = '绑定关系失败!';
            $rsp['error_msg'] = $error_msg;

            return $rsp;
        }

        $params = [
//            'invoiceCode'      => $orderInfo['ship_company_addr'], // 发票代码，税控发票号码、发票代码和发票请求流水号不能同时为空
//            'invoiceNo'        => $orderInfo['ship_bank'], // 发票号码
            'serialNo'         => $orderInfo['order_electronic_items']['serial_no'], // 发票请求流水号
            'invoiceIssueMode' => '1', // 1 全电版式生成 其他代表税控发票生成
        ];

        // 推送邮箱
        if ($orderInfo["ship_email"]) {
            $params['email'] = $orderInfo["ship_email"];
        }

        // 推送手机
        $mobilePattern = '/^1[3-9]\d{9}$/';
        if ($orderInfo["ship_tel"] && preg_match($mobilePattern, $orderInfo["ship_tel"])) {
            $params['phone'] = $orderInfo["ship_tel"];
        }

        // 请求与回调数据分层
        $sdf = [
            'params' => $params,
            'order'  => $orderInfo,
        ];

        return $sdf;
    }

}
