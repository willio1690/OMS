<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_mdl_order_golden3CancelExport extends invoice_mdl_order
{
    var $has_export_cnf = false;
    var $export_name = '金3冲红信息';

    protected $export_columns = [
        'invoice_code'        => '蓝票代码',
        'invoice_no'          => '蓝票号码',
        'order_bn'            => '单据号',
        'amount'              => '开票金额',
        'mode'                => '开票方式',
        'cancel_invoice_code' => '红票代码',
        'cancel_invoice_no'   => '红票号码',
        'logi_name'           => '物流公司',
        'logi_no'             => '物流单号',
    ];

    public function __construct($app = '')
    {
        parent::__construct($app);
    }

    public function table_name($real = false)
    {
        return $real ? 'sdb_invoice_order' : 'order';
    }

    //定义列字段
    public function getExportTitle($fields)
    {
        //蓝票代码，蓝票号码，单据号，开票金额，开票方式，开票类型
        //订单发票表 sdb_invoice_order

        $title = [];
        foreach ($this->export_columns as $k => $col) {
            $title[] = $col;
        }
        return mb_convert_encoding(implode(',', $title), 'GBK', 'UTF-8');
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end)
    {
        kernel::log('业务日志:根据查询条件获取导出数据:' . json_encode(['$fields' => $fields, '$filter' => $filter, '$has_detail' => $has_detail]));

        //根据选择的字段定义导出的第一行标题
        if ($curr_sheet == 1) {
            $data['content']['main'][] = $this->getExportTitle('');
        }

        //获取 “订单发票表” 数据
        if (!$invoice_order = $this->getList('*', $filter)) {
            return false;
        }

        foreach ($invoice_order as $value) {    //foreach 发票
            $content                   = [
                'invoice_code'        => $value['invoice_code'],
                'invoice_no'          => $value['invoice_no'],
                'order_bn'            => $value['order_bn'],
                'amount'              => $value['amount'],
                'mode'                => $this->schema['columns']['mode']['type'][$value['mode']],
                'cancel_invoice_code' => '',
                'cancel_invoice_no'   => '',
                'logi_name'           => '',
                'logi_no'             => '',
            ];
            $data['content']['main'][] = mb_convert_encoding(implode(',', $content), 'GBK', 'UTF-8');
        }

        return $data;
    }

    /**
     * 解密
     * @param array $data [
     *    'origin' => '玉**>>sed@hash',
     *    'field_type' => 'ship_name',
     *    'shop_id' => '234123',
     *    'origin_bn' => '表单号',
     *    'type' => 'order'
     * ]
     * @return array
     */
    public function decryptField($data, $shopInfo)
    {
        $res = array ();
        if ($data) {
            foreach ($data as $key => $val) {
                $string     = $val['origin'];
                $is_encrypt = (kernel::single('ome_security_hash')->get_code() == substr($string, -5));

                if ($is_encrypt) {
                    if ($shopInfo['origin_bn'] && $shopInfo['shop_id']) {
                        if (empty($shopInfo['node_type'])) {
                            if ($index = strpos($string, '>>')) {
                                $res[$val['field_type']] = substr($string, 0, $index);
                                continue;
                            }
                            $res[$val['field_type']] = $string;
                            continue;
                        }
                        $decrypt_data = kernel::single('ome_security_router', $shopInfo['node_type'])->decrypt(array (
                            $val['field_type'] => $val['origin'],
                            'shop_id'          => $shopInfo['shop_id'],
                            'order_bn'         => $shopInfo['origin_bn'],
                        ), $shopInfo['type']);
                        if ($decrypt_data[$val['field_type']]) {
                            $string = $decrypt_data[$val['field_type']];
                        }
                    }
                    if ($index = strpos($string, '>>')) {
                        $res[$val['field_type']] = substr($string, 0, $index);
                        continue;
                    }
                    $res[$val['field_type']] = $string;
                }
            }
        }
        return $res;
    }

    function exportTemplate($filter = 'vat_main')
    {
        foreach ($this->io_title($filter) as $v) {
            $title[] = $v;
        }
        return $title;
    }

    function io_title($filter = 'vat_main', $ioType = 'csv')
    {
        switch ($filter) {
            case 'vat_main':
                $this->oSchema['csv'][$filter] = [
                    '*:订单号'              => 'order_bn',
                    '*:开票类型(开蓝/冲红)' => 'billing_type',
                    '*:开票代码'            => 'invoice_code',
                    '*:开票号'              => 'invoice_no',
                    '*:运单号'              => 'logi_no',
                    '*:物流公司'            => 'logi_name',
                ];
                break;
        }

        $this->ioTitle[$ioType][$filter] = array_keys($this->oSchema[$ioType][$filter]);
        return $this->ioTitle[$ioType][$filter];
    }


    function finish_import_csv()
    {
        header("Content-type: text/html; charset=utf-8");

        $data = $this->import_data;
        if (!$data['contents']) {
            return true;
        }
        unset($this->import_data);
        $invoiceOrderMdl = app::get('invoice')->model('order');
        $invEleItemMdl   = app::get('invoice')->model('order_electronic_items');
        $orderInvoiceMdl = app::get('ome')->model('order_invoice');
        $orderMdl        = app::get('ome')->model('orders');
        $opObj           = app::get('ome')->model('operation_log');

        kernel::database()->beginTransaction();

        foreach ($data['contents'] as $key => $row) {

            $rowInfo          = array_combine(array_keys($this->export_columns), $row);
            $billingType      = '2';
            $invoiceCode      = $rowInfo['cancel_invoice_code'];
            $invoiceNo        = $rowInfo['cancel_invoice_no'];
            $logiNo           = $rowInfo['logi_no'];
            $logiName         = $rowInfo['logi_name'];
            $invoiceOrderData = [
                'invoice_code' => $invoiceCode,
                'invoice_no'   => $invoiceNo,
                'dateline'     => time(),
                'update_time'  => time(),
            ];

            $invoiceOrderData['is_status']       = '2';
            $invoiceOrderData['sync']            = '6';
            $filter                              = ['order_bn' => $rowInfo['order_bn'], 'is_status' => '1', 'sync' => ['3', '10']];
            $invoice_action_type                 = '2';
            $type                                = 'invoice_cancel';
            $opMsg                               = '导入冲红成功';
            $invoiceOrderData['is_make_invoice'] = '0';
            $invoiceInfo                         = $invoiceOrderMdl->db_dump($filter);

            // 改成update
            $invoiceItem = [
                'id'                  => $invoiceInfo['id'],
                'invoice_code'        => $invoiceCode,
                'invoice_no'          => $invoiceNo,
                'billing_type'        => $billingType,
                'logi_no'             => $logiNo,
                'logi_name'           => $logiName,
                'invoice_action_type' => $invoice_action_type,
                'invoice_status'      => '5',// 已红冲
                'create_time'         => time(),
                'last_modified'       => time(),
            ];

            // 已有明细则更新
            $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
            // 红票
            $invoiceItemFilter = [
                'id'           => $invoiceInfo['id'],
                'billing_type' => '2',
            ];

            $invEleItem = $invEleItemMdl->db_dump($invoiceItemFilter, 'item_id,invoice_status');

            if ($invEleItem) {
                $invoiceItem['item_id'] = $invEleItem['item_id'];
            }

            $itemRs = $invEleItemMdl->save($invoiceItem);

            if (!$itemRs) {
                kernel::database()->rollBack();
                return false;
            }

            // 更新开票申请主表
            if (!$invoiceOrderMdl->update($invoiceOrderData, ['id' => $invoiceInfo['id'], 'sync' => ['0', '2', '3', '10']])) {
                kernel::database()->rollBack();
                return false;
            }

            if ($invoiceInfo['order_id']) {
                // 更新订单
                $orderMdl->update([
                    'tax_no' => $invoiceNo,
                ], ['order_id' => $invoiceInfo['order_id']]);

                // 更新订单发票信息
                $orderInvoiceMdl->update([
                    'tax_no' => $invoiceNo,
                ], ['order_id' => $invoiceInfo['order_id']]);
            }

            // 记录日志
            $opObj->write_log($type . '@invoice', $invoiceInfo['id'], $opMsg);

            //导入冲红进行改票
            if ($invoice_action_type == '2' && $invoiceInfo['changesdf'] && $invoiceInfo['change_status'] == '1') {
                $params                = array_merge($invoiceInfo, json_decode($invoiceInfo['changesdf'], 1));
                $params['action_type'] = 'doCheckChangeTicket';
                unset($params['is_status'], $params['sync'], $params['is_print'], $invoiceInfo['itemsdf']);
                $sale_data = kernel::single('invoice_sales_data')->generate($invoiceInfo);
                if (!$sale_data['sales_items']) {//缺少销售单明细
                    $params = [];
                }
                if ($params) {
                    kernel::single('invoice_process')->create($params, "invoice_list_add_same");
                    $opObj->write_log($type . '@invoice', $invoiceInfo['id'], '导入冲红进行改票');
                }
            }
        }
        kernel::database()->commit();

        return true;
    }

    function prepared_import_csv_row($row, $title, &$tmpl, &$mark, &$newObjFlag, &$msg)
    {

        $mark = 'contents';

        $fileData = $this->import_data;
        if (!$fileData) {
            $fileData = [];
        }

        if (count(array_intersect($row, $this->export_columns)) == count($this->export_columns)) {
            $mark    = 'title';
            $titleRs = array_flip($row);
            return $titleRs;
        } else {
            if ($row) {
                $rowInfo = array_combine(array_keys($this->export_columns), $row);

                if (!$rowInfo) {
                    $msg['error'] = "导入文件格式不正确";
                    return false;
                }

                $rowInfo['order_bn'] = trim($rowInfo['order_bn']);
                if (empty($rowInfo['order_bn'])) {
                    unset($this->import_data);
                    $msg['error'] = "订单号不能为空";
                    return false;
                }

                if (empty($rowInfo['cancel_invoice_code'])) {
                    unset($this->import_data);
                    $msg['error'] = "红票代码不能为空";
                    return false;
                }
                if (empty($rowInfo['cancel_invoice_no'])) {
                    unset($this->import_data);
                    $msg['error'] = "红票号码不能为空";
                    return false;
                }

                if (!is_numeric($rowInfo['cancel_invoice_code'])) {
                    unset($this->import_data);
                    $msg['error'] = "红票代码格式不正确";
                    return false;
                }

                if (!is_numeric($rowInfo['cancel_invoice_no'])) {
                    unset($this->import_data);
                    $msg['error'] = "红票号码格式不正确";
                    return false;
                }

                $invoiceOrderMdl = app::get('invoice')->model('order');

                //蓝票
                $invoiceFilter = [
                    'order_bn'   => $rowInfo['order_bn'],
                    'invoice_no' => $rowInfo['invoice_no'],
                    'is_status'  => '1',
                    'sync'       => ['3', '10']
                ];
                $errorMsg      = '有多条开票成功记录';
                $emptyMsg      = '没有该订单的已成功开票信息';

                $invoiceCount = $invoiceOrderMdl->count($invoiceFilter);
                $invoiceInfo  = $invoiceOrderMdl->db_dump($invoiceFilter, 'id,is_make_invoice');

                if ($invoiceCount == 0) {
                    unset($this->import_data);
                    $msg['error'] = '订单号：' . $rowInfo['order_bn'] . $emptyMsg;
                    return false;
                }

                if ($invoiceCount > 1) {
                    unset($this->import_data);
                    $msg['error'] = '订单号：' . $rowInfo['order_bn'] . $errorMsg;
                    return false;
                }
                if (!$invoiceInfo['is_make_invoice']) {
                    unset($this->import_data);
                    $msg['error'] = '订单号：' . $rowInfo['order_bn'] . '暂不可红冲';
                    return false;
                }

                $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
                // 红票
                $invoiceItemFilter = [
                    'id'           => $invoiceInfo['id'],
                    'billing_type' => '2',
                ];

                $invEleItem = $invEleItemMdl->db_dump($invoiceItemFilter, 'item_id,invoice_status');

                if ($invEleItem && !in_array($invEleItem['invoice_status'], ['20', '99'])) {
                    unset($this->import_data);
                    $msg['error'] = '订单号：' . $rowInfo['order_bn'] . '当前红冲申请状态不正确';
                    return false;
                }

                $fileData['contents'][] = $row;
            }
            $this->import_data = $fileData;
        }

        return true;
    }

    function prepared_import_csv_obj($data, $mark, $tmpl, &$msg = '')
    {
        return true;
    }
}
