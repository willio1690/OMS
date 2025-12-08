<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [银联]电子发票渠道
 */
class invoice_event_trigger_data_chinaums extends invoice_event_trigger_data_common
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
    private $invoiceTypeMapping = [
        '0' => '02',    // 普通票
        '1' => '01',    // 专用发票
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
        $result = kernel::single('erpapi_router_request')->set('bind', 'chinaums')->bind_bind($channelExtendData);
        if (!$result) {
            $error_msg        = '绑定关系失败!';
            $rsp['error_msg'] = $error_msg;

            return $rsp;
        }

        $area = $orderInfo['ship_area'];
        kernel::single('ome_func')->split_area($area);


        $ghdwdzdh = $orderInfo['ship_company_addr'] . ' ' . $orderInfo['ship_company_tel'];
        $ghdwyhzh = $orderInfo['ship_bank'] . $orderInfo['ship_bank_no'];

        $this->__total_je = $this->__total_se = 0;
        $this->tax_rate   = $orderInfo['tax_rate'] / 100;

        //发票明细列表
        $items = $this->getEinvoiceInvoiceItems($orderInfo, $einvoice_type);

        if (!$items) {
            $error_msg        = '没有发票明细';
            $rsp['error_msg'] = $error_msg;

            return $rsp;
        }
        $items = $this->getEinvoiceInvoiceItemsParams($items);

        //当是税控盘服务类型时，必填： 税控盘编号、税控盘口令、税务数字证书密码
        if ($orderInfo['eqpttype'] && $orderInfo['skpdata']) {
            $skpdata = unserialize($orderInfo['skpdata']);
        }

        $item_nums = count($items);

        $hjje = $this->__total_je;
        $se   = $this->__total_se;
        $jshj = $this->__total_se + $this->__total_je;

        if ($einvoice_type == 'red') {
            $hjje = -$hjje; //(价税)合计金额  = 价税合计 - 税额,
            $jshj = -$jshj;
            $se   = -$se;
        }

        // todo 用taobao对接,
        if ($einvoice_type != 'red') {
            $params = [
                'buyerAddress'       => $orderInfo['ship_company_addr'], // 购方单位地址
                'buyerBankName'      => $orderInfo['ship_bank'], // 购方开户行
                'buyerBankNumber'    => $orderInfo['ship_bank_no'], // 购方账户
                'buyerName'          => $orderInfo['title'], // 购方客户名称
                'buyerTaxNo'         => $orderInfo['ship_tax'], // 购方单位代码
                'buyerTelphone'      => $orderInfo['ship_company_tel'], // 电话
                'invoiceDate'        => date('Ymd', $orderInfo['create_time']), // 开票日期
                'invoiceTypeCode'    => $this->invoiceTypeMapping[$orderInfo['type_id']], // 发票种类编码, 004：增值税专用发票；007：增值税普通发票；026：增值税电子发票；025：增值税卷式发票；028:增值税电子专用发票 01:全电发票(增值税专用发票) 02:全电发票(普通发票)
                'invoiceType'        => $einvoice_type == 'red' ? 1 : 0, // 开票类型 0:正数发票（蓝票） 1：负数发票（红票）默认0
                'remarks'            => $orderInfo["remarks"], // 备注:长度为230个字符
                'sellerAddress'      => $orderInfo['address'], // 地址
                'sellerBankName'     => $orderInfo["bank"], //开票方银行
                'sellerBankNumber'   => $orderInfo["bank_no"], // 开票方银行帐号
                'sellerTelphone'     => $orderInfo['telephone'], // 开票方电话
                'serialNo'           => $orderInfo["invoice_apply_bn"], // 矩阵流水号 - oms开票申请单号 - 银联商户订单号
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

        } else {
            $params = [
                'serialNo'    => $orderInfo["invoice_apply_bn"],
                'invoiceDate' => date('Ymd', $orderInfo['create_time']),
                'invoiceType' => $einvoice_type == 'red' ? 1 : 0,      // 开票类型 0:正数发票（蓝票） 1：负数发票（红票）默认0
                'outTradeNo'  => $orderInfo["order_bn"],
            ];
        }


        // 按要求补充备注
        if ($einvoice_type == 'blue' && $orderInfo["order_bn"] && $source == 'b2c') {
            $params['remarks'] .= ' 订单号:' . $orderInfo["order_bn"];
        }

        # 红票补充参数 todo 待确认
        if ($einvoice_type == 'red' && $orderInfo['type_id'] == 1) {
            //$params['redConfirmUuid'] = uniqid();
            $params['redConfirmUuid'] = $orderInfo["order_electronic_items"]['red_confirm_uuid'];
        }

        // 请求与回调数据分层
        $sdf = [
            'params' => $params,
            'order'  => $orderInfo,
        ];

        return $sdf;
    }

    public function getEinvoiceInvoiceItemsParams($tmpItems = [])
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
                if ($key == 'fphxz' && $value == '2') {
                    $item['discountIndex'] = $tmpItems[$line + 1]['fpmxxh'];//折扣和被折扣行号
                }
                if ($key == 'fphxz' && $value == '1') {
                    $item['discountIndex'] = $tmpItems[$line - 1]['fpmxxh'];//折扣和被折扣行号
                }
                if (isset($mapping[$key])) {
                    $item[$mapping[$key]] = $value;
                }
                // 税率处理
                if ($key == 'sl') {
                    $item[$mapping[$key]] = $value * 100;
                }
                if ($key == 'spsl') {
                    $item[$mapping[$key]] = abs($value);//折扣商品行的数量也必须是整数
                }
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

        $requestParams = [
            'entryIdentity'   => '01',  // 录入方身份, oms 固定为销方
            'redInvoiceLabel' => '01',      // 冲红原因,
            'invoiceDate'     => date('Ymd', $orderInfo['dateline']),  // 商户订单日期
            'serialNo'        => $orderInfo["invoice_apply_bn"],      // 蓝字专票申请单号
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

}
