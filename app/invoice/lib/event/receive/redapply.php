<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_event_receive_redapply extends invoice_event_response
{

    private $_invoice_apply_bn = '';

    private $_invoiceMdl = null;

    private $_invEleItemMdl = null;

    private $_orderMdl = null;
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

    public function __construct($constructParams)
    {
        parent::__construct();
        $this->_invoice_apply_bn = $constructParams['invoice_apply_bn'];
        $this->_invoiceMdl       = app::get('invoice')->model('order');
        $this->_invEleItemMdl    = app::get('invoice')->model('order_electronic_items');
        $this->_orderMdl         = app::get('ome')->model('orders');

    }

    /**
     *
     * 发货通知单处理总入口
     * @param array $data
     */
    public function update($data)
    {
        //参数检查
        if (!isset($data['status'])) {
            return $this->send_error('必要参数缺失');
        }

        // 明细表更新
        $this->_updateItem($data);
        // 主表更新
        $this->_updateInvoice($data);


        // 特殊状态(存在事件)
        switch ($data['status']) {
            case 1:
            case 4:
                $this->_cancel($data);
                break;
        }

        return $this->send_succ('更新成功');
    }

    private function _cancel($data)
    {
        $channelMdl = app::get('invoice')->model('channel');
        $channel    = $channelMdl->dump($data['channel_id']);
        if ($channel['channel_type'] == 'baiwang') {
            kernel::single('invoice_event_trigger_einvoice')->getEinvoiceCreateResult($data['item_id']);
            return true;
        }
        // 推红冲
        $rs_invoice                        = kernel::single('invoice_check')->checkInvoiceCancel($data['id']);
        $rs_invoice['invoice_action_type'] = 2;
        $rs_invoice                        = kernel::single('invoice_electronic')->getEinvoiceSerialNo($rs_invoice, "2");
        kernel::single('invoice_event_trigger_einvoice')->cancel($rs_invoice['shop_id'], $rs_invoice);
    }

    private function _updateItem($data)
    {
        // 更新明细表
        $itemUpdate = [
            'red_confirm_status' => (string)$data['status'],    // 当前传入与dbschema完全一致
        ];

        if (isset($data['red_confirm_no']) && $data['red_confirm_no']) {
            $itemUpdate['red_confirm_no'] = $data['red_confirm_no'];
        }

        if (isset($data['red_confirm_uuid']) && $data['red_confirm_uuid']) {
            $itemUpdate['red_confirm_uuid'] = $data['red_confirm_uuid'];
        }

        if (isset($data['red_invoice_no']) && $data['red_invoice_no']) {
            $itemUpdate['invoice_no'] = $data['red_invoice_no'];
        }


        $itemFilter = [
            'item_id' => $data['item_id']
        ];

        $invEleItemMdl = $this->_invEleItemMdl;
        // 更新ITEM
        $invEleItemMdl->update($itemUpdate, $itemFilter);
    }

    private function _updateInvoice($data)
    {
        // 更新明细表
        $update = [
            'sync'        => $this->__syncStatusMapping[$data['status']],    // mapping
            'update_time' => time(),
        ];

        $itemFilter = [
            'id' => $data['id']
        ];

        $invOrderMdl = $this->_invoiceMdl;

        $invOrderMdl->update($update, $itemFilter);
    }

}
