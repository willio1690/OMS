<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发票接口响应类
 *
 * @author xiayuanjun<xiayuanjun@shopex.cn>
 * @version 0.1
 */
class erpapi_invoice_response_order extends erpapi_invoice_response_abstract
{
    private $invoice = null;

    // 开票状态 is_status
    // : 0 未开票;1 已开票;2 已作废
    public $isStatusMapping = [
        '00' => '1', // 开具成功
        '03' => '2', // 已作废
        '05' => '2', // 已红冲
        '02' => '2', // 已撤销
        '99' => '0', // 待开具
        '44' => '2', // 已关闭
        '10' => '0', // 开具中
        '20' => '1', // 红冲中
        '30' => '0', // 作废中
        '22' => '0', // 已拆分,未知
    ];

    // sync 状态同步
    // 0 未同步;1 开蓝票中;2 开蓝失败;3 开蓝成功;4 开红票中;5 冲红失败;6 冲红成功;7 冲红确认中;8 冲红确认失败;9 冲红确认成功
    public $syncMapping = [
        '00' => '3', // 开具成功
        '03' => '2', // 已作废
        '05' => '6', // 已红冲
        '02' => '2', // 已撤销
        '99' => '0', // 待开具
        '44' => '2', // 已关闭
        '10' => '1', // 开具中
        '20' => '4', // 红冲中
        '30' => '4', // 作废中
        '22' => '0', // 已拆分,未知
    ];


    // invoice_status 开票状态
    // 0 已开具;2 已撤销;3 已作废; 5 已红冲;10 开具中;20 红冲中;22 已拆分;30 作废中;44 已关闭;99 待开具
    public $invoiceStatusMapping = [
        '00' => '0', // 开具成功
        '03' => '3', // 已作废
        '05' => '5', // 已红冲
        '02' => '2', // 已撤销
        '99' => '0', // 待开具
        '44' => '2', // 已关闭
        '10' => '10', // 开具中
        '20' => '20', // 红冲中
        '30' => '30', // 作废中
        '22' => '22', // 已拆分,未知
    ];

    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params)
    {
        $this->invoice = null;
        // 提取后的响应回参
        $this->invoiceResponse = null;

        $this->__apilog['title'] = '开票结果更新';

        if (is_string($params['response'])) {
            $params['response'] = json_decode($params['response'], true);
        }

        try {
            // 参数校验
            $checkRs = $this->_checkUpdateParams($params);

            // 参数格式化
            $data = $this->_formatUpdateParams($params);
        } catch (Exception $e) {
            $this->__apilog['result']['msg'] = $e->getMessage();
            return false;
        }
        return $data;
    }

    protected function _formatUpdateParams($params)
    {
        $data = [];
        foreach ($this->invoiceResponse['success'] as $key => $invoiceResult) {
            $invOrderMdl = app::get('invoice')->model('order');

            $invOrder                      = $invOrderMdl->dump(['invoice_apply_bn' => $invoiceResult['serialNo']], 'order_bn');
            $this->__apilog['original_bn'] = $invoiceResult['serialNo'] ?: $invOrder['order_bn'];
            $this->__apilog['title']       = '开票结果更新-' . $invoiceResult['serialNo'];

            $data[$key] = array (
                'serial_no'        => $invoiceResult['serialNo'],  // 流水号
                'invoice_apply_bn' => $invoiceResult['serialNo'],  // 流水号
                'invoice_code'     => $invoiceResult['invoiceCode'],         // 发票代码,数电没有
                'invoice_no'       => $invoiceResult['invoiceNo'],    // 发票号码
                'invoice_date'     => strtotime($invoiceResult['invoiceDate']),  // 开票日期
                'invoice_type'     => $invoiceResult['invoiceType'],  // 开票类型 0:蓝，1:红
                'url'              => $invoiceResult['eInvoiceUrl'],   // PDFURL
                'invoice_status'   => $this->invoiceStatusMapping[$invoiceResult['invoiceStatus']],   // PDFURL
                'sync'             => $this->syncMapping[$invoiceResult['invoiceStatus']],   // PDFURL
                'is_status'        => $this->isStatusMapping[$invoiceResult['invoiceStatus']],   // 开票状态
            );

        }

        // 可回传列表为空则直接返回
        if (!$data || empty($data)) {
            throw new exception('发票回传结果为空');
        }

        return $data;
    }

    protected function _checkUpdateParams($params)
    {
        if (is_string($params)) {
            $params = @json_decode($params, true);
        }

        if (is_array($params) && isset($params['response'])) {
            $invoiceResponse = $params["response"];
        }

        if (!$invoiceResponse) {
            throw new exception('回传数据结构不正确');
        }

        $this->invoiceResponse = $invoiceResponse;

    }
}
