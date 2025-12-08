<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 阿里淘宝电子发票业务
 */
class erpapi_invoice_matrix_taobao_request_invoice extends erpapi_invoice_request_invoice
{
    private $_platform = array(
        "taobao"    => "TB",
        "tmall"     => "TM",
        "360buy"    => "JD",
        "dangdang"  => "DD",
        "paipai"    => "PP",
        "qq_buy"    => "QQ",
        "amazon"    => "AMAZON",
        "suning"    => "SN",
        "gome"      => "GM",
        "guomei"    => "GM",
        "vop"       => "WPH",
        "mogujie"   => "MGJ",
        "yintai"    => "YT",
        "yihaodian" => "YHD",
        "vjia"      => "VANCL",
        "alibaba"   => "1688",
    );

    /**
     * 发票创建接口请求
     * 
     * @param array $sdf 请求参数
     */

    public function create($sdf)
    {

        $items = array();
        foreach ($sdf['items'] as $invoiceItems) {
            $items[] = array(
                "item_name"     => $invoiceItems['spmc'], #发票项目名称（或商品名称）
                "item_no"       => $invoiceItems['spbm'], #可选参数 发票项目编号（或商品编号）
                'price'         => number_format($invoiceItems['spdj'], 6, ".", ""), #单价
                'quantity'      => $invoiceItems['spsl'], #数量
                "row_type"      => $invoiceItems['fphxz'],
                "sum_price"     => number_format($invoiceItems['je'], 2, ".", ""), #总价，格式：100.00
                "tax"           => number_format($invoiceItems['se'], 2, ".", ""), #税额
                "tax_rate"      => number_format($invoiceItems['sl'], 2, ".", ""), #税率
                "amount"        => number_format($invoiceItems['jshj'], 2, ".", ""), #价税合计
                "unit"          => $invoiceItems['dw'], #单位。
                "specification" => $invoiceItems['ggxh'], #可选参数 规格型号 目前给空
            );
        }

        $sdf['invoice_items'] = json_encode($items);unset($sdf['items']);

        //开票发起前 标记开电子发票同步状态为 开蓝票中
        $mdlInOrder      = app::get('invoice')->model('order');
        $filter_arr      = array("id" => $sdf["id"]);
        $update_sync_arr = array("sync" => "1"); //标记开蓝票中的状态

        $mdlInOrder->update($update_sync_arr, $filter_arr);

        $title    = "阿里电子发票开蓝票";
        $callback = array(
            'class'  => get_class($this),
            'method' => 'create_callback',
            'params' => array(
                "id"         => $sdf["id"],
                "order_bn"   => $sdf["order_bn"],
                "shop_id"    => $sdf["shop_id"],
                "payee_name" => $sdf["payee_name"],
                "telephone"  => $sdf["payer_phone"], #消费者电话
                'platform'   => $sdf['platform'], #平台
                'amount'     => $sdf["invoice_amount"], #价税合计，就是开票金额
                'cost_tax'   => $sdf["sum_tax"], #价金
            ),
        );

        $gateway = '';
        if ($sdf['is_encrypt']) {
            $gateway = $sdf['s_node_type'];
        }

        $rs = $this->__caller->call(EINVOICE_CREATEREQ, $sdf, $callback, $title, 10, $sdf["order_bn"], true, $gateway);
        if ($rs['rsp'] == 'fail' && $sdf['id']) {
            $invOrderMdl = app::get('invoice')->model('order');
            $optMdl      = app::get('ome')->model('operation_log');

            $invOrderMdl->update(array('sync' => '2', 'is_status' => '0'), array('id' => $sdf['id']));
            $optMdl->write_log('invoice_billing@invoice', $sdf['id'], sprintf('开票失败：%s', $rs['msg']));
        }

        return $rs;
    }

    /**
     * 开蓝回调
     * 
     * @return void
     * @author
     * */
    public function create_callback($response, $callback_params)
    {
        $rsp     = $response['rsp'];
        $res     = $response['res'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'] ? @json_decode($response['data'], true) : array();

        $id = $callback_params['id'];

        $optMdl        = app::get('ome')->model('operation_log');
        $invOrderMdl   = app::get('invoice')->model('order');
        $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
        $orderMdl      = app::get('ome')->model('orders');

        $status = '';
        if ($rsp == 'succ' && $data['invoice_result']) {
            $invoice_result = array_shift($data['invoice_result']);

            $status = $invoice_result['status'];
        }

        $invoice = $invOrderMdl->db_dump($id);

        if (!$invoice) {
            return $this->callback($response, $callback_params);
        }

        if ($res == 'isv.invoice-success' && preg_match('/参数错误：发票已经开具成功，serialNo=(.*)/', $err_msg, $matches)) {
            $invEleItemMdl->update(array('serial_no' => $matches[1]), array('id' => $id, 'billing_type' => '1'));

            $invOrderMdl->update(array('sync' => '1'), array('id' => $id, 'sync' => array('0', '1', '2')));

            return $this->callback($response, $callback_params);
        }

        //开蓝失败
        if ($rsp != 'succ' || $status != 'create_success') {
            $invOrderMdl->update(array('sync' => '2', 'is_status' => '0'), array('id' => $id, 'sync' => array('0', '1', '2')));

            $optMdl->write_log('invoice_billing@invoice', $id, sprintf('开票失败：%s', $err_msg));

            return $this->callback($response, $callback_params);
        }

        $shop       = app::get('ome')->model('shop')->db_dump($callback_params['shop_id'], 'shop_id,node_id,node_type');
        $url_params = array(
            'node_id'    => $shop['node_id'],
            'platform'   => $callback_params['platform'],
            'tid'        => $callback_params['order_bn'],
            'order_bn'   => $callback_params['order_bn'],
            'invoice_no' => $invoice_result['invoice_no'],
            'expire'     => 1296000, // 默认保存3个月
        );
        $invoice_url      = kernel::single('erpapi_router_request')->set('invoice', $shop['shop_id'])->invoice_get_url($url_params);
        $invoice_url_data = @json_decode($invoice_url['data'], true);
        $file_id = 0;
        if ('on' == app::get('ome')->getConf('ome.invoice.local.storage')) {
            $file_id = kernel::single('invoice_order')->save_base_file(['url' => $invoice_url_data['url'], 'order_bn' => $invoice['order_bn']]);
        }

        // 更新ITEM
        $invEleItemMdl->update(array(
            'invoice_code'        => $invoice_result['invoice_code'],
            'invoice_no'          => $invoice_result['invoice_no'],
            'serial_no'           => $invoice_result['serial_no'],
            'create_time'         => strtotime($invoice_result['invoice_date']),
            'url'                 => $invoice_url_data['url'] ? $invoice_url_data['url'] . '#expire=' . date('Y-m-d', strtotime('+15 day')) : '',
            'upload_tmall_status' => $shop['node_type'] == 'taobao' ? '2' : '1',
            'file_id'             => $file_id,
        ), array('id' => $id, 'billing_type' => '1'));

        // 更新INVOICE ORDER
        $invOrderMdl->update(array(
            'invoice_code' => $invoice_result['invoice_code'],
            'invoice_no'   => $invoice_result['invoice_no'],
            'dateline'     => strtotime($invoice_result['invoice_date']),
            'update_time'  => time(),
            'sync'         => '3',
            'is_status'    => '1',
            'is_make_invoice' => '0',
            'cost_tax'     => $callback_params['cost_tax'],
        ), array('id' => $id, 'sync' => array('0', '1', '2')));

        // 更新ORDER
        if ($invoice['order_id']) {
            $orderMdl->update(array(
                'tax_no' => $invoice_result['invoice_no'],
            ), array('order_id' => $invoice['order_id']));
        }

        // 发短信
        $optMdl->write_log('invoice_billing@invoice', $id, '开票成功');

        // 开票上传
        if ('on' == app::get('ome')->getConf('ome.invoice.autoupload') 
            && in_array($shop['node_type'], array('360buy','luban'))) {

            $apiFailMdl    = app::get('erpapi')->model('api_fail');
            $apiFailMdl->saveTriggerRequest($invoice_result['invoice_no'], 'upload_invoice');

            $optMdl->write_log('einvoice_upload@invoice', $id, '准备上传蓝票');
        }

        if (defined('APP_TOKEN') && defined('APP_SOURCE')) {
            //kernel::single('taoexlib_sms_send_router', 'einvoice')->sendmsg($id);
        }

        return $this->callback($response, $callback_params);
    }

    /**
     * 发票取消接口请求
     * @param array $sdf 请求参数
     */
    public function cancel($sdf)
    {
        $items = array();
        foreach ($sdf['items'] as $invoiceItems) {
            $items[] = array(
                "item_name"     => $invoiceItems['spmc'], #发票项目名称（或商品名称）
                "item_no"       => $invoiceItems['spbm'], #可选参数 发票项目编号（或商品编号）
                'price'         => number_format($invoiceItems['spdj'], 6, ".", ""), #单价
                'quantity'      => $invoiceItems['spsl'], #数量
                "row_type"      => $invoiceItems['fphxz'],
                "sum_price"     => number_format($invoiceItems['je'], 2, ".", ""), #总价，格式：100.00
                "tax"           => number_format($invoiceItems['se'], 2, ".", ""), #税额
                "tax_rate"      => number_format($invoiceItems['sl'], 2, ".", ""), #税率
                "amount"        => number_format($invoiceItems['jshj'], 2, ".", ""), #价税合计
                "unit"          => $invoiceItems['dw'], #单位。
                "specification" => $invoiceItems['ggxh'], #可选参数 规格型号 目前给空
            );
        }

        $sdf['invoice_items'] = json_encode($items);unset($sdf['items']);

        //开票发起前 标记开电子发票同步状态为 开红票中
        $mdlInOrder = app::get('invoice')->model('order');

        $filter_arr      = array("id" => $sdf["id"]);
        $update_sync_arr = array("sync" => "4"); //标记开红票中的状态
        $mdlInOrder->update($update_sync_arr, $filter_arr);

        $title    = "阿里电子发票冲红";
        $callback = array(
            'class'  => get_class($this),
            'method' => 'cancel_callback',
            'params' => array(
                "id"       => $sdf["id"],
                "order_bn" => $sdf["order_bn"],
                "shop_id"  => $sdf["shop_id"],
                "payee_name" => $sdf["payee_name"],
                "telephone"  => $sdf["payer_phone"], #消费者电话
                'platform'   => $sdf['platform'], #平台
                'amount'     => $sdf["invoice_amount"], #价税合计，就是开票金额
                'cost_tax'   => $sdf["sum_tax"], #价金
            ),
        );

        $gateway = '';
        if ($sdf['is_encrypt']) {
            $gateway = $sdf['s_node_type'];
        }

        $rs = $this->__caller->call(EINVOICE_CREATEREQ, $sdf, $callback, $title, 10, $sdf["order_bn"], true, $gateway);
        if ($rs['rsp'] == 'fail' && $sdf['id']) {
            $invOrderMdl = app::get('invoice')->model('order');
            $optMdl      = app::get('ome')->model('operation_log');

            $invOrderMdl->update(array('sync' => '5', 'is_status' => '1'), array('id' => $sdf['id']));
            $optMdl->write_log('invoice_billing@invoice', $sdf['id'], sprintf('开票冲红失败：%s', $rs['msg']));
        }

        return $rs;
    }

    /**
     * 开蓝回调
     * 
     * @return void
     * @author
     * */
    public function cancel_callback($response, $callback_params)
    {
        $rsp     = $response['rsp'];
        $res     = $response['res'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'] ? @json_decode($response['data'], true) : array();

        $id = $callback_params['id'];

        $optMdl        = app::get('ome')->model('operation_log');
        $invOrderMdl   = app::get('invoice')->model('order');
        $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
        $orderMdl      = app::get('ome')->model('orders');

        $status = '';
        if ($rsp == 'succ' && $data['invoice_result']) {
            $invoice_result = array_shift($data['invoice_result']);

            $status = $invoice_result['status'];
        }

        $invoice = $invOrderMdl->db_dump($id);
        if (!$invoice) {
            return $this->callback($response, $callback_params);
        }

        if ($res == 'isv.invoice-success' && preg_match('/参数错误：发票已经开具成功，serialNo=(.*)/', $err_msg, $matches)) {
            $invEleItemMdl->update(array('serial_no' => $matches[1]), array('id' => $id, 'billing_type' => '1'));

            $invOrderMdl->update(array('sync' => '4'), array('id' => $id, 'sync' => array('3', '4', '5')));

            return $this->callback($response, $callback_params);
        }

        // 开蓝失败
        if ($rsp != 'succ' || $status != 'create_success') {
            $invOrderMdl->update(array('sync' => '5', 'is_status' => '1'), array('id' => $id, 'sync' => array('3', '4', '5')));

            $optMdl->write_log('invoice_billing@invoice', $id, sprintf('冲红失败：%s', $err_msg));

            return $this->callback($response, $callback_params);
        }

        $shop       = app::get('ome')->model('shop')->db_dump($callback_params['shop_id'], 'shop_id,node_id,node_type');
        $url_params = array(
            'node_id'    => $shop['node_id'],
            'platform'   => $callback_params['platform'],
            'tid'        => $callback_params['order_bn'],
            'order_bn'   => $callback_params['order_bn'],
            'invoice_no' => $invoice_result['invoice_no'],
            'expire'     => 1296000, // 默认保存3个月
        );
        $invoice_url      = kernel::single('erpapi_router_request')->set('invoice', $shop['shop_id'])->invoice_get_url($url_params);
        $invoice_url_data = @json_decode($invoice_url['data'], true);
        $file_id = 0;
        if ('on' == app::get('ome')->getConf('ome.invoice.local.storage')) {
            $file_id = kernel::single('invoice_order')->save_base_file(['url' => $invoice_url_data['url'], 'order_bn' => $invoice['order_bn']]);
        }
        // 更新ITEM
        $invEleItemMdl->update(array(
            'invoice_code'        => $invoice_result['invoice_code'],
            'invoice_no'          => $invoice_result['invoice_no'],
            'serial_no'           => $invoice_result['serial_no'],
            'create_time'         => strtotime($invoice_result['invoice_date']),
            'url'                 => $invoice_url_data['url'] ? $invoice_url_data['url'] . '#expire=' . date('Y-m-d', strtotime('+15 day')) : '',
            'upload_tmall_status' => $shop['node_type'] == 'taobao' ? '2' : '1',
            'file_id'             => $file_id,
        ), array('id' => $id, 'billing_type' => '2'));

        // 更新INVOICE ORDER
        $invOrderMdl->update(array(
            'invoice_code' => $invoice_result['invoice_code'],
            'invoice_no'   => $invoice_result['invoice_no'],
            'dateline'     => strtotime($invoice_result['invoice_date']),
            'update_time'  => time(),
            'sync'         => '6',
            'is_status'    => '2',
        ), array('id' => $id, 'sync' => array('3', '4', '5')));

        // 更新ORDER
        if ($invoice['order_id']) {
            $orderMdl->update(array(
                'tax_no' => $invoice_result['invoice_no'],
            ), array('order_id' => $invoice['order_id']));
        }

        // 发短信
        $optMdl->write_log('invoice_billing@invoice', $id, '冲红成功');

        // 开票上传
        if ('on' == app::get('ome')->getConf('ome.invoice.autoupload') 
            && in_array($shop['node_type'], array('360buy','luban'))) {

            $apiFailMdl    = app::get('erpapi')->model('api_fail');
            $apiFailMdl->saveTriggerRequest($invoice_result['invoice_no'], 'upload_invoice');

            $optMdl->write_log('einvoice_upload@invoice', $id, '准备上传红票');
        }


        //售后冲红电票 自动重写开票
        if ($invoice['changesdf'] && $invoice['change_status'] == '1') {
            $params = array_merge($invoice,json_decode($invoice['changesdf'],1));
            unset($params['is_status'],$params['itemsdf']);
            $type = 'change_ticket';
            $params['is_edit'] = 'true';
        }
        if ($invoice['action_type'] != 'create_order') {
            //自动重新开票
            $params = !empty($params) ? $params : $invoice;
            unset($params['is_status'],$params['sync']);
            $type = $invoice['action_type'];
        }
        
        if ($params) {
            $data = kernel::single('invoice_order')->formatAddData($params);
            list($res,$msg) = kernel::single('invoice_process')->newCreate($data,$type);
        }
    
        //冲红成功修改合票发票
        kernel::single('invoice_order')->cancelSuccessRes($invoice['id']);
        


        return $this->callback($response, $callback_params);
    }

    /**
     * 电子发票获取url
     * @param array $sdf 请求参数
     */
    public function get_url($sdf)
    {
        $title = "获取电子发票url地址";
        return $this->__caller->call(EINVOICE_URL_GET, $sdf, null, $title, 10, $sdf["order_bn"]);
    }

    /**
     * 查询已回传淘宝的电子发票
     * @param array $sdf 请求参数
     */
    public function get_info($sdf)
    {
        //组接口参数
        $params = array("tid" => $sdf["order_bn"]);
        $title  = "查询已回传淘宝的电子发票";
        return $this->__caller->call(EINVOICE_INVOICE_GET, $params, null, $title, 10, $sdf["order_bn"]);
    }

    /**
     * 回流天猫前 电子发票状态更新
     * 
     * @param array $sdf
     */
    public function einvoice_prepare($sdf)
    {
        $invoice_type = 1; //默认蓝票
        if ($sdf["einvoice_type"] == "red") {
            //已作废并且冲红成功
            $invoice_type = 2; //红票
        }
        //组接口参数
        $params = array(
            "tid"                 => $sdf["order_bn"],
            "invoice_action_type" => $sdf["invoice_action_type"],
            "invoice_type"        => $invoice_type,
            "serial_no"           => $sdf["serial_no"],
            "invoice_title"       => $sdf["title"] ? $sdf["title"] : $sdf["tax_company"],
        );
        $title = "天猫电子发票状态更新";
        return $this->__caller->call(EINVOICE_INVOICE_PREPARE, $params, null, $title, 10, $sdf["order_bn"]);
    }

    /**
     * 电子发票回流天猫
     * @param array $sdf 请求参数
     */
    public function upload_tmall($sdf)
    {
        //组打接口电子发票回流天猫的参数
        $params = kernel::single('invoice_electronic')->getEinvoiceUploadTmallRequestParams($sdf);
        $title  = "电子发票回流天猫";
        return $this->__caller->call(EINVOICE_DETAIL_UPLOAD, $params, null, $title, 25, $sdf["order_bn"]);
    }

    public function get_result_params($sdf)
    {
        $params                      = array();
        $params['platform']          = $this->_platform[$sdf['node_type']] ? $this->_platform[$sdf['node_type']] : 'OTHER';
        //如果合单或者换货订单请求平台other
        if (strpos($sdf['order_bn'],',') || substr($sdf['order_bn'],0,1) == 'C') {
            $params['platform'] = 'OTHER';
        }
        $params['serial_no']         = $sdf['serial_no'];
        $params['tid']               = $sdf['order_bn'];
        $params['payee_register_no'] = $sdf['payee_register_no'];

        return $params;
    }

    /**
     * 创建_result_callback
     * @param mixed $result result
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function create_result_callback($result, $callback_params)
    {
        if ($result['rsp'] == 'fail') {
            return $this->error($result['err_msg'], $result['res']);
        }

        $result['data'] = @json_decode($result['data'], true);

        $data = array();
        foreach ($result['data']['invoice_result'] as $key => $value) {
            if ($value['status'] != 'create_success') {
                continue;
            }

            $url_params = array(
                'node_id'    => $callback_params['node_id'],
                'platform'   => $callback_params['platform'],
                'tid'        => $callback_params['order_bn'],
                'order_bn'   => $callback_params['order_bn'],
                'invoice_no' => $value['invoice_no'],
                'expire'     => 1296000, // 默认保存3个月
            );
            $invoice_url      = kernel::single('erpapi_router_request')->set('invoice', $callback_params['shop_id'])->invoice_get_url($url_params);
            $invoice_url_data = @json_decode($invoice_url['data'], true);

            $data[$key] = array(
                'c_fpqqlsh' => $value['serial_no'],
                'c_fpdm'    => $value['invoice_code'],
                'c_fphm'    => $value['invoice_no'],
                'c_kprq'    => strtotime($value['invoice_date']) . '000',
                'c_url'     => $invoice_url_data['url'] ? $invoice_url_data['url'] . '#expire=' . date('Y-m-d', strtotime('+15 day')) : 'no',
                'c_status'  => '2',
            );
        }

        if (!$data) {
            return $this->error('同步失败', 'error');
        }

        $rs = array('rsp' => 'succ', 'data' => json_encode($data));
        return parent::create_result_callback($rs, $callback_params);
    }
}
