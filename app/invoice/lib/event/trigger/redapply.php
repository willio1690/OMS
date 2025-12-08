<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_event_trigger_redapply extends invoice_event_response
{
    /**
     * 红字申请创建方法
     * @param $shop_id
     * @param $data
     * @return array|false
     */
    public function create($shop_id, $data, $source = 'b2c')
    {
        $dataLib = kernel::single('invoice_event_trigger_data_router')->set_shop_id($shop_id);

        $sdf = $dataLib->getCancelApplyRequestParams($data);

        if (!$sdf) {
            return false;
        }

        $result = kernel::single('erpapi_router_request')->set('invoice', $shop_id)->redapply_create($sdf);

        $this->_createAfter($sdf, $result, $source);

        return $result;
    }

    private function _createAfter($sdf, $result, $source = 'b2c')
    {
        // 因成功不返回单号,故暂时仅在此处更新状态
        $filter     = [
            'id' => $sdf['order']['id']
        ];
        $itemFilter = [
            'item_id' => $sdf['order']['order_electronic_items']['item_id']
        ];

        // 失败处理
        if (!$result || $result['rsp'] != 'succ') {
            $update = [
                'sync'     => 8,     // 冲红确认失败
                'sync_msg' => $result['data']['resultMsg'] ?? '红字申请单创建失败',     // 冲红确认失败
            ];

            $itemUpdate = [
                'red_confirm_status' => '500'
            ];
        } else {
            $update = [
                'sync'     => 7,    // 冲红确认中
                'sync_msg' => ''
            ];

            $itemUpdate = [
                'red_confirm_status' => '99'  // 申请中
            ];
        }
        $invOrderMdl   = app::get('invoice')->model('order');
        $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
        if ($source == 'b2b') {
            $invOrderMdl   = app::get('invoice')->model('b2b_order');
            $invEleItemMdl = app::get('invoice')->model('b2b_order_electronic_items');
        }
        // 更新ITEM
        $invEleItemMdl->update($itemUpdate, $itemFilter);
        $invOrderMdl->update($update, $filter);
    }

    /**
     * 红字确认单查询方法
     *
     * @param string $shop_id 来源店铺ID
     * @param array $data 开电子发票（开蓝票）通知数据信息
     * @param string $error_msg
     * @return array
     */
    public function sync($id, $source = 'b2c')
    {

        if (!$id) {
            return $this->send_error("缺失发票ID");
        }

        $invoice = $this->_getInvoice($id, $source);
        $params  = $this->_formatSyncParmas($invoice);

        $channelId = $invoice['channel_id'];
        $channel   = $this->_getChannel($channelId);
        if (!$channel) {
            return $this->send_error("无法定位开票渠道ID");
        }

        $result = kernel::single('erpapi_router_request')->set('invoice', $invoice['shop_id'])->redapply_query($params);

        if ($result['rsp'] == 'fail') {
            return $this->send_error($result['err_msg']);
        }

        // 没有数据, 则不处理
        if (!$result['data']) {
            return $this->send_error("同步数据缺失");
        }

        // 响应类传入参数
        $data = is_string($result['data']) ? json_decode($result['data'], true) : $result['data'];

        $responseParams = [
            'invoice' => $invoice,
            'data'    => $data,
        ];

        // 模拟接口回传, 触发更新
        $response = kernel::single('erpapi_router_response')->set_node_id($channel['node_id'])->set_api_name('invoice.redapply.status_update')->dispatch($responseParams);

        return $result;
    }

    protected function _formatSyncParmas($invoice)
    {
        return [
            'request_params' => [
                'invoiceDate'    => date('Ymd', $invoice['create_time']),
                'serialNo'       => $invoice["invoice_apply_bn"],
                'taxNo'          => $invoice["tax_no"],
                'sellerTaxNo'    => $invoice["tax_no"],
                'redConfirmUuid' => $invoice["order_electronic_items"]['red_confirm_uuid'],
            ],
            'order'          => $invoice
        ];
    }

    protected function _getInvoice($id, $source = 'b2c')
    {
        $invoiceMdl    = app::get('invoice')->model('order');
        $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
        if ($source == 'b2b') {
            $invoiceMdl    = app::get('invoice')->model('b2b_order');
            $invEleItemMdl = app::get('invoice')->model('b2b_order_electronic_items');
        }
        $invoice = $invoiceMdl->dump($id);

        if (!$invoice) {
            throw new exception('发票不存在');
        }
        $invEleItemFilter = [
            'id'           => $id,
            'billing_type' => 2,
        ];

        $invEleItem = $invEleItemMdl->dump($invEleItemFilter);
        if (!$invEleItem) {
            throw new exception('发票对应红字明细不存在');
        }

        $invoice['order_electronic_items'] = $invEleItem;

        return $invoice;
    }


    protected function _getChannel($channelId)
    {
        $channelMdl = app::get('invoice')->model('channel');
        $channel    = $channelMdl->dump($channelId);
        return $channel;
    }

}
