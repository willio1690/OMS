<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_mdl_order_vatInvoiceExport extends invoice_mdl_order
{
    var $has_export_cnf = false;
    var $export_name    = '专票信息';

    public function __construct($app='')
    {
        parent::__construct($app);
        if ($_GET['view'] == 8){
            $this->export_name = '金3冲红';
        }
    }

    public function table_name($real = false)
    {
        return $real ? 'sdb_invoice_order' : 'order';
    }

    //定义列字段
    public function getExportTitle($fields)
    {
        //订单发票表 sdb_invoice_order
        $export_columns = [
            'order_bn'               => '单据号',
            'invoice_no'             => '购方编号',
            'tax_company'            => '购方单位名称',
            'ship_tax'               => '购方税号',
            'ship_addr_and_ship_tel' => '购方地址电话',
            'ship_bank_no'           => '购方银行账号',
            'remarks'                => '备注',
            'goods_bn'               => '商品编号',
            'goods_name'             => '商品名称',
            'specifications'         => '规格型号',
            'unit'                   => '计量单位',
            'nums'                   => '数量',
            'sale_price'             => '单价',
            'invoice_amount'         => '客户实付',
            'have_tax_price'         => '含税金额',
            'not_have_tax_price'     => '不含税金额',
            'tax_price∑'             => '税额',
            'tax_rate'               => '税率',
            'mode'                   => '发票类型',
            'discount'               => '折扣金额',
            'tax_code'               => '税收分类编码',
            'payee_receiver'         => '收款人',
            'payee_checker'          => '复核人',
            'payee_operator'         => '开票人',
            'ship_name'              => '收件人',
            'ship_tel'               => '电话',
            'ship_addr'              => '地址',
        ];

        $title = [];
        foreach ($export_columns as $k => $col) {
            $title[] = $col;
        }
        return mb_convert_encoding(implode(',', $title), 'GBK', 'UTF-8');
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end)
    {
        // todo.XueDing:校验，预警
        kernel::log('业务日志:根据查询条件获取导出数据:' . json_encode(['$fields' => $fields, '$filter' => $filter, '$has_detail' => $has_detail]));

        //订单 model
        $model_order      = app::get('ome')->model('orders');
        $model_order_obj  = app::get('ome')->model('order_objects');
        $basicMaterialLib = kernel::single('material_basic_material');
        $basicSalesMaterialMdl = app::get('material')->model('sales_material');

        $smExtMdl = app::get('material')->model('sales_material_ext');
    
        $invoiceItemsMdl      = app::get('invoice')->model('order_items');
        //根据选择的字段定义导出的第一行标题
        if ($curr_sheet == 1) {
            $data['content']['main'][] = $this->getExportTitle('');
        }

        //获取 “订单发票表” 数据
        if (!$invoice_order = $this->getList('*', $filter)) {
            return false;
        }
        $ids = array_column($invoice_order,'id');
        $invoiceItemsList = $invoiceItemsMdl->getList('*,amount as sale_price,amount as sales_amount,quantity as nums',['id'=>$ids,'is_delete'=>'false']);
        if ($invoiceItemsList) {
            $invoiceItemsList = ome_func::filter_by_value($invoiceItemsList,'id');
        }
        $invoiceSetting = kernel::single('invoice_func')->get_order_setting($invoice_order[0]['shop_id'], $invoice_order[0]['mode'])[0];
    
        foreach ($invoice_order as $value) {
            $tax_rate = $value['tax_rate'];
            //获取发货单数据
//            $Objdly   = app::get('ome')->model('delivery');
//            $delivery = $Objdly->getDeliveryByOrderBn($value['order_bn']);    //根据订单bn获取发货单信息

            //获取订单数据
            $order = app::get('ome')->model('orders')->db_dump($value['order_id'], 'cost_freight,discount');
            if(!$order) {
                $order = app::get('archive')->model('orders')->db_dump($value['order_id'], 'cost_freight,discount');
            }

            //获取订单明细列表
            $orderInfo = @json_decode($value['itemsdf'],true);
            $itemList = $orderInfo['sales_items'];
            if ($invoiceItemsList) {
                $itemList = $invoiceItemsList[$value['id']];
                $itemList = kernel::single('invoice_order')->showMergeInvoiceItems($itemList);
            }
            foreach ($itemList as $row) {
                if ($row['sale_price'] <= 0) continue;
    
                if (isset($row['tax_rate'])) {
                    $tax_rate           = $row['tax_rate'];
                    $smInfo['tax_name'] = str_replace(',', '，', $row['item_name']);
                    $smExt['unit']      = $row['unit'];
                    $spec               = $row['specification'];
                    $smInfo['tax_code'] = $row['tax_code'];
                } else {
                    $bMaterialRow   = $basicMaterialLib->getBasicMaterialExt($row['product_id']);
                    $smInfo         = $basicSalesMaterialMdl->db_dump(['sales_material_bn' => $row['sales_material_bn']]);
                    $smExt          = $smExtMdl->db_dump($smInfo['sm_id']);
                    $objRow         = app::get('ome')->model('order_objects')->db_dump(['obj_id' => $row['obj_id']]);
                    $objRow['name'] = str_replace(',', '，', $objRow['name']);
                    $spec           = $row['sales_material_bn'] ?: $row['bn'];
                }
                $decrypData = [
                    [
                        'origin'     => $value['ship_tel'],
                        'field_type' => 'ship_tel',
                    ],
                    [
                        'origin'     => $value['ship_tel'],
                        'field_type' => 'ship_mobile',
                    ],
                    [
                        'origin'     => $value['ship_addr'],
                        'field_type' => 'ship_addr',
                    ],
                    [
                        'origin'     => $value['ship_name'],
                        'field_type' => 'ship_name',
                    ],
                ];
                $shopInfo = [
                    'shop_id'    => $value['shop_id'],
                    'origin_bn'  => $value['order_bn'],
                    'type'       => 'order',
                    'node_type'  => $value['shop_type'],
                ];
                $decryRes = $this->decryptField($decrypData,$shopInfo);
                $content                   = [
                    'order_bn'               => str_replace(',', '，',  $value['order_bn']),
                    'invoice_no'             => str_replace(',', '，',  $value['order_bn']),
                    'tax_company'            => $value['title'] ?? $value['tax_company'],
                    'ship_tax'               => $value['ship_tax'],
                    
                    'ship_addr_and_ship_tel' => str_replace(',', '，',  $value['ship_company_addr'] . ' ' .  $value['ship_company_tel']),
                    'ship_bank_no'           => $value['ship_bank'].$value['ship_bank_no'],
                    'remarks'                => '平台订单号：' .str_replace(',', '，',  $value['order_bn']),

                    'goods_bn'           => $row['sales_material_bn'] ?: $row['bn'],
                    'goods_name'         => $smInfo['tax_name'] ?: $objRow['name'],
                    'specifications'     => $spec,
                    'unit'               => $smExt['unit'] ?: $bMaterialRow['unit'],
                    'nums'               => $row['nums'],
                    'sale_price'         => $row['sale_price'],
                    'invoice_amount'     => $value['amount'],
                    'have_tax_price'     => $row['sale_price'],
                    'not_have_tax_price' => round($row['sale_price'] / (($tax_rate / 100) + 1),3),
                    'tax_price'          => round($row['sale_price'] / (($tax_rate / 100) +1 ) * ($tax_rate / 100), 3),
                    'tax_rate'           => $tax_rate . '%',
                    'mode'               => $value['mode'] == '0' ? '0' : self::MODE[$value['mode']] ?? '未知',
                    'discount'           => 0,
                    'tax_code'           => !empty($smInfo['tax_code']) ? $smInfo['tax_code'] : $bMaterialRow['tax_code'],

                    //发票 sdb_invoice_order 表数据
                    'payee_receiver'     => $value['payee_receiver'] ?: $invoiceSetting['payee_receiver'],
                    'payee_checker'      => $value['payee_checker'] ?: $invoiceSetting['payee_checker'],
                    'payee_operator'     => $value['payee_operator'] ?: $invoiceSetting['payee_operator'],
                ];
//                $decrypData = [
//                    [
//                        'origin'     => $delivery['ship_name'],
//                        'field_type' => 'ship_name',
//                    ],
//                    [
//                        'origin'     => $delivery['ship_mobile'],
//                        'field_type' => 'ship_mobile',
//                    ],
//                    [
//                        'origin'     => $delivery['ship_addr'],
//                        'field_type' => 'ship_addr',
//                    ]
//                ];
//                $shopInfo = [
//                    'shop_id'    => $delivery['shop_id'],
//                    'origin_bn'  => $value['order_bn'],
//                    'type'       => 'delivery',
//                    'node_type'  => $delivery['shop_type'],
//                ];
//                $decryRes = $this->decryptField($decrypData,$shopInfo);
                //发货单数据
                $ship_tel = $value['ship_tel'];
                if (!empty($decryRes['ship_tel'])) {
                    if (strpos($decryRes['ship_tel'],'*') !== false) {
                        if (!empty($decryRes['ship_mobile'])) {
                            $ship_tel = $decryRes['ship_mobile'];
                        }
                    }else{
                        $ship_tel = $decryRes['ship_tel'];
                    }
                }
                $content['ship_name']   = $decryRes['ship_name'] ?? $value['ship_name'];
                $content['ship_tel'] = $ship_tel;
                $content['ship_addr']   = $decryRes['ship_addr'] ? $decryRes['ship_addr'] : $value['ship_addr'];
                if ($content['tax_code'] == material_sales_material::$sale_invoice_bn['咖啡胶囊']['code']) {
                    $infreightCode = $content['tax_code'];
                }
                $data['content']['main'][] = mb_convert_encoding(implode(',', $content), 'GBK', 'UTF-8');
            }
            if($order['cost_freight'] > 0 && !isset($row['tax_rate'])) {
                $cost_freight_sl = app::get('ome')->getConf('ome.invoice.infreight.rate');
                $infreightCode = $infreightCode ?: material_sales_material::$sale_invoice_bn['咖啡机']['code'];
                $cost_freight_sl = '0.13';
                $content                   = [
                    'order_bn'               => str_replace(',', '，',  $value['order_bn']),
                    'invoice_no'             => str_replace(',', '，',  $value['order_bn']),
                    'tax_company'            => $value['title'] ?? $value['tax_company'],
                    'ship_tax'               => $value['ship_tax'],
                    
                    'ship_addr_and_ship_tel' =>str_replace(',', '，',  $value['ship_company_addr'] . ' ' .  $value['ship_company_tel']),
                    'ship_bank_no'           => $value['ship_bank'].$value['ship_bank_no'],
                    'remarks'                => '平台订单号：' .str_replace(',', '，',  $value['order_bn']),

                    'goods_bn'           => '',
                    'goods_name'         => app::get('ome')->getConf('ome.invoice.infreight.name'),
                    'specifications'     => '',
                    'unit'               => '',
                    'nums'               => 1,
                    'sale_price'         => $order['cost_freight'],
                    'invoice_amount'     => $value['amount'],
                    'have_tax_price'     => $order['cost_freight'],
                    'not_have_tax_price' => round($order['cost_freight'] / ($cost_freight_sl + 1), 3),
                    'tax_price'          => round($order['cost_freight'] / ($cost_freight_sl + 1) * $cost_freight_sl, 3),
                    'tax_rate'           => ($cost_freight_sl*100).'%',
                    'mode'               => $value['mode'] == '0' ? '0' : self::MODE[$value['mode']] ?? '未知',
                    'discount'           => 0,
                    'tax_code'           => $infreightCode,

                    //发票 sdb_invoice_order 表数据
                    'payee_receiver'     => $value['payee_receiver'] ?: $invoiceSetting['payee_receiver'],
                    'payee_checker'      => $value['payee_checker'] ?: $invoiceSetting['payee_checker'],
                    'payee_operator'     => $value['payee_operator'] ?: $invoiceSetting['payee_operator'],
                ];
                $content['ship_name']   = $decryRes['ship_name'] ?? $value['ship_name'];
                $content['ship_tel'] = $ship_tel;
                $content['ship_addr']   = $decryRes['ship_addr'] ? $decryRes['ship_addr'] : $value['ship_addr'];
                
                $data['content']['main'][] = mb_convert_encoding(implode(',', $content), 'GBK', 'UTF-8');
            }
        }
        return $data;
    }
    
    /**
     * 解密
     * @param  array $data [
     *    'origin' => '玉**>>sed@hash',
     *    'field_type' => 'ship_name',
     *    'shop_id' => '234123',
     *    'origin_bn' => '表单号',
     *    'type' => 'order'
     * ]
     * @return array
     */
    public function decryptField($data,$shopInfo)
    {
        $res = array();
        if ($data) {
            foreach ($data as $key => $val) {
                $string = $val['origin'];
                $is_encrypt = (kernel::single('ome_security_hash')->get_code() == substr($string, -5));
    
                if ($is_encrypt) {
                    if($shopInfo['origin_bn'] && $shopInfo['shop_id']) {
                        if(empty($shopInfo['node_type'])) {
                            if($index = strpos($string, '>>')) {
                                $res[$val['field_type']] = substr($string, 0, $index);
                                continue;
                            }
                            $res[$val['field_type']] =  $string;
                            continue;
                        }
                        $decrypt_data = kernel::single('ome_security_router',$shopInfo['node_type'])->decrypt(array (
                            $val['field_type']    => $val['origin'],
                            'shop_id'     => $shopInfo['shop_id'],
                            'order_bn'    => $shopInfo['origin_bn'],
                        ), $shopInfo['type']);
                        if($decrypt_data[$val['field_type']]) {
                            $string = $decrypt_data[$val['field_type']];
                        }
                    }
                    if($index = strpos($string, '>>')) {
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
                    '*:订单号'         => 'order_bn',
                    '*:开票类型(开蓝/冲红)' => 'billing_type',
                    '*:开票代码'        => 'invoice_code',
                    '*:开票号'         => 'invoice_no',
                    '*:运单号'         => 'logi_no',
                    '*:物流公司'        => 'logi_name',
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
        $opObj  = app::get('ome')->model('operation_log');

        kernel::database()->beginTransaction();

        foreach ($data['contents'] as $key => $row) {
            $billingType      = $row[1] == '开蓝' ? '1' : '2';
            $invoiceCode      = $row[2];
            $invoiceNo        = $row[3];
            $logiNo           = $row[4];
            $logiName         = $row[5];
            $invoiceOrderData = [
                'invoice_code' => $invoiceCode,
                'invoice_no'   => $invoiceNo,
                'dateline'     => time(),
                'update_time'  => time(),
            ];
            //开蓝
            if ($billingType == '1') {
                $invoiceOrderData['is_status'] = '1';
                $invoiceOrderData['sync']      = '3';
                $filter                        = " order_bn = '" .$row[0] ."' and is_status = '0' and (sync = '0' || sync = '2')";
                $invoice_action_type           = '1';
                $type = 'invoice_billing';
                $opMsg = '导入开蓝成功';
            } else {
                $invoiceOrderData['is_status'] = '2';
                $invoiceOrderData['sync']      = '6';
                $filter                        = " order_bn = '" .$row[0] ."' and is_status = '1' and (sync = '3' or sync = '10')";
                $invoice_action_type           = '2';
                $type = 'invoice_cancel';
                $opMsg = '导入冲红成功';
            }
            $invoiceOrderData['is_make_invoice'] = '0';
            $db = kernel::database();
            $sql = "select * from sdb_invoice_order where " . $filter;
            $invoiceInfo = $db->selectrow($sql);
            $opObj->write_log($type.'@invoice', $invoiceInfo['id'], $opMsg);

            $invoiceItem = [
                'id'                  => $invoiceInfo['id'],
                'invoice_code'        => $invoiceCode,
                'invoice_no'          => $invoiceNo,
                'billing_type'        => $billingType,
                'logi_no'             => $logiNo,
                'logi_name'           => $logiName,
                'invoice_action_type' => $invoice_action_type,
                'create_time'         => time(),
                'last_modified'       => time(),
            ];
            $invEleItemMdl->insert($invoiceItem);

            // 更新订单
            if ($invoiceInfo['order_id']) {
                $orderMdl->update([
                    'tax_no' => $invoiceNo,
                ], ['order_id' => $invoiceInfo['order_id']]);

                $orderInvoiceMdl->update([
                    'tax_no' => $invoiceNo,
                ], ['order_id' => $invoiceInfo['order_id']]);
            }
            if ($invoice_action_type == '1') {
                // 开票上传
                if ('on' == app::get('ome')->getConf('ome.invoice.autoupload') && in_array($invoiceInfo['node_type'], array('wesite','pekon'))) {
                    $apiFailMdl = app::get('erpapi')->model('api_fail');
                    $apiFailMdl->saveTriggerRequest($invoiceNo, 'upload_invoice');
                    $opObj->write_log('einvoice_upload@invoice', $invoiceInfo['id'], '准备上传蓝票');
                }
            }
            
            if (!$invoiceOrderMdl->update(
                $invoiceOrderData,
                ['id' => $invoiceInfo['id'], 'sync' => ['0', '2', '3', '10']]
            )) {
                kernel::database()->rollBack();
                return false;
            }
            //导入冲红进行改票
            if ($invoice_action_type == '2') {
                $params = $invoiceInfo;
                if ($invoiceInfo['changesdf'] && $invoiceInfo['change_status'] == '1') {
                    $params = array_merge($invoiceInfo,json_decode($invoiceInfo['changesdf'],1));
                    $params['action_type'] = 'doCheckChangeTicket';
                }
                unset($params['is_status'],$params['sync'],$params['is_print'],$invoiceInfo['itemsdf']);
                if ($params) {
                    $data = kernel::single('invoice_order')->formatAddData($params);
                    list($res,$msg) = kernel::single('invoice_process')->newCreate($data,$type);
                    $opObj->write_log($type.'@invoice', $invoiceInfo['id'], '导入冲红进行改票');
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

        if (substr($row[0], 0, 1) == '*') {
            $mark    = 'title';
            $titleRs = array_flip($row);
            return $titleRs;
        } else {
            if ($row) {
                $row[0] = trim($row[0]);
                if (empty($row[0])) {
                    unset($this->import_data);
                    $msg['error'] = "订单号不能为空";
                    return false;
                }
                if (empty($row[1]) || !in_array($row[1], ['开蓝', '冲红'])) {
                    unset($this->import_data);
                    $msg['error'] = "开票类型不能为空，内容必须为开蓝或者冲红";
                    return false;
                }
                if (empty($row[2])) {
                    unset($this->import_data);
                    $msg['error'] = "开票代码不能为空";
                    return false;
                }
                if (empty($row[3])) {
                    unset($this->import_data);
                    $msg['error'] = "开票号不能为空";
                    return false;
                }
                if ($row[1] == '开蓝') {
                    if (empty($row[4]) || empty($row[5])) {
                        $msg['error'] = "开蓝时物流公司与物流单号必填";
                        return false;
                    }
                }
                $invoiceOrderMdl = app::get('invoice')->model('order');

                //蓝票
                $row[0] = str_replace('，', ',', $row[0]);
                if ($row[1] == '开蓝') {
                    $invoiceFilter = " order_bn = '" .$row[0] ."' and is_status = '0' and sync <= '3'";
                    $errorMsg      = '有多条未开票记录或者已开票成功';
                    $emptyMsg      = '没有该订单的未开票信息';
                } else {
                    $invoiceFilter = " order_bn = '" .$row[0] ."' and is_status = '1' and (sync = '3' OR sync = '10')";
                    $errorMsg      = '有多条开票成功记录';
                    $emptyMsg      = '没有该订单的已成功开票信息';
                }
                $db = kernel::database();
                $sql = "select is_make_invoice from sdb_invoice_order where " . $invoiceFilter;
                $invoiceCount = $db->count($sql);
                $invoiceInfo = $db->selectrow($sql);
                if ($invoiceCount == 0) {
                    unset($this->import_data);
                    $msg['error'] = '订单号：' . $row[0] . $emptyMsg;
                    return false;
                }

                if ($invoiceCount > 1) {
                    unset($this->import_data);
                    $msg['error'] = '订单号：' . $row[0] . $errorMsg;
                    return false;
                }
                if (!$invoiceInfo['is_make_invoice']) {
                    unset($this->import_data);
                    $msg['error'] = '订单号：' . $row[0] . '暂不可'.$row[1];
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
