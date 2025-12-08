<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发票-接口响应类
 *
 */
class erpapi_invoice_matrix_baiwang_response_order extends erpapi_invoice_response_order
{
    //发票状态：发票状态默认为空，00开具成功 02空白发票作废 03:已开发票 作废 05:正票已红冲
    public $invoiceStatusMapping = [
        '00' => '0', // 开具成功
        '03' => '3', // 已作废
        '05' => '5', // 已红冲
        '02' => '2', // 已撤销
        '07' => '20', // 红冲中
    ];

    // sync 状态同步
    // 0 未同步;1 开蓝票中;2 开蓝失败;3 开蓝成功;4 开红票中;5 冲红失败;6 冲红成功;7 冲红确认中;8 冲红确认失败;9 冲红确认成功
    public $syncMapping = [
        '00' => '3', // 开具成功
        '03' => '2', // 已作废
        '05' => '6', // 已红冲
        '02' => '2', // 已撤销
        '07' => '7', // 冲红确认中
    ];

    // 开票状态 is_status
    // : 0 未开票;1 已开票;2 已作废
    public $isStatusMapping = [
        '00' => '1', // 开具成功
        '03' => '2', // 已作废
        '05' => '2', // 已红冲
        '02' => '2', // 已撤销
        '07' => '1', // 已撤销
    ];

    //红字确认状态
    private $__syncStatusMapping = [
        1  => '9',    // 无需确认 - 冲红确认成功
        2  => '7',   // 销方录入待购方确认 - 冲红确认中
        3  => '7',   // 购方录入待销方确认 - 冲红确认中
        4  => '9', // 购销双方已确认 - 冲红确认成功
        5  => '3',// 作废（销方录入购方否认）- 开蓝成功
        6  => '3',// 作废（购方录入销方否认）- 开蓝成功
        7  => '3',// 作废（超 72 小时未确认）- 开蓝成功
        8  => '3', // 作废（发起方已撤销）- 开蓝成功
        9  => '3',   // 作废（确认后撤销）- 开蓝成功
        99 => '7',    //申请中 oms中间字段 - 冲红确认中
    ];

    protected function _formatUpdateParams($params)
    {
        $data = [];
        foreach ($this->invoiceResponse as $key => $invoiceResult) {
            $invOrderMdl = app::get('invoice')->model('order');
            $eleMdl = app::get('invoice')->model('order_electronic_items');
            //红冲参数兼容  //冲红成功 状态更新
            if ($invoiceResult['invoiceType'] == '1') {
                $status = $this->__syncStatusMapping[(int)$invoiceResult['confirmState']];
                if ($status == '9') {
                    $invoiceResult['invoiceStatus'] = '05';
                }elseif ($status == '7') {
                    $invoiceResult['invoiceStatus'] = '07';
                }elseif ($status == '3') {
                    $invoiceResult['invoiceStatus'] = '00';
                }
                $invoiceResult['serialNo'] = $invoiceResult['redConfirmSerialNo'];
                $invoiceResult['invoiceNo'] = $invoiceResult['redInvoiceNo'];
                $invoiceResult['invoiceDate'] = $invoiceResult['originInvoiceDate'];
            }

            $invEle                        = $eleMdl->db_dump(['serial_no' => $invoiceResult['serialNo']], 'id');
            $invOrder                      = $invOrderMdl->db_dump(['id' => $invEle['id']], 'order_bn,invoice_apply_bn');
            $this->__apilog['original_bn'] = $invOrder['order_bn'] ?: $invoiceResult['serialNo'];
            $this->__apilog['title']       = '开票结果更新-' . $invoiceResult['serialNo'];

            $data[$key] = array (
                'serial_no'        => $invoiceResult['serialNo'],  // 流水号
                'invoice_apply_bn' => $invOrder['invoice_apply_bn'] ?: $invoiceResult['serialNo'],  // 流水号
                'invoice_code'     => $invoiceResult['invoiceCode'],         // 发票代码,数电没有
                'invoice_no'       => $invoiceResult['invoiceNo'],    // 发票号码
                'invoice_date'     => strtotime($invoiceResult['invoiceDate']),  // 开票日期
                'invoice_type'     => $invoiceResult['invoiceType'],  // 开票类型 0:蓝，1:红
                'url'              => $invoiceResult['eInvoiceUrl'],   // PDFURL
                'invoice_status'   => $this->invoiceStatusMapping[$invoiceResult['invoiceStatus']],   // PDFURL
                'sync'             => $this->syncMapping[$invoiceResult['invoiceStatus']],   // PDFURL
                'is_status'        => $this->isStatusMapping[$invoiceResult['invoiceStatus']],   // 开票状态
                'xml_url'          => $invoiceResult['xmlUrl'],   // XML URL
                'ofd_url'          => $invoiceResult['ofdUrl'],   // OFD URL
            );

        }

        // 可回传列表为空则直接返回
        if (!$data || empty($data)) {
            throw new exception('发票回传结果为空');
        }

        return $data;
    }
}
