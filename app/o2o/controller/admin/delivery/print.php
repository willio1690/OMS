<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2022/6/6 18:32:34
 * @describe 打印
 */
class o2o_ctl_admin_delivery_print extends desktop_controller
{
    function __construct($app)
    {
        if(in_array($_GET['act'], ['toPrintExpre','toPrintMergeNew'])) {
            $this->checkCSRF = false;
        }
        
        parent::__construct($app);
    }

    /**
     * 安装logisticsmanager后使用
     */

    public function toPrintExpre()
    {
        $_err = 'false';

        /* 单品、多品标识 */
        $sku = '';

        $now_print_type = 'ship';

        //获取当前待打印的发货单过滤条件
        $filter_condition = ['delivery_id'=>(int)$_GET['delivery_id']];

        $PrintLib = kernel::single('o2o_delivery_print');
        $print_data = $PrintLib->getPrintDatas($filter_condition,$now_print_type,$sku,false,$msg);
        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->message($msg['error_msg']);
            exit;
        }

        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->message($msg['warn_msg']);
            exit;
        }

        $waybillObj = kernel::single('logisticsmanager_service_waybill');
        $channel_extObj =  app::get("logisticsmanager")->model("waybill_extend");
        $deliveryObj = app::get('wms')->model('delivery');
        $dlyBillObj = app::get('wms')->model('delivery_bill');
        $ids = $print_data['ids'];
        $channelObj = app::get("logisticsmanager")->model("channel");
        //防止并发打印重复获取运单号
        $_inner_key = sprintf("o2o_print_ids_%s", md5(implode(',',$ids)));
        $aData = cachecore::fetch($_inner_key);
        if ($aData === false) {
            cachecore::store($_inner_key, 'printed', 5);
        }else{
            if(!isset($_GET['isdown'])){
                $this->message("选中的发货单已在打印快递单中，请不要重复打印！！！如没有打印完成，请稍后重试！！！");
                exit;
            }

        }

        //发起获取单号
        $expressDelivery = array();
        $existTB = false;
        foreach($print_data['deliverys'] as $val) {
            empty($logiId) && $logiId = $val['logi_id'];
            empty($shopType) && $shopType = $val['shop_type'];
            $orderSource = current($val['orders']);
            if ($val['shop_type'] == 'taobao' && $orderSource['source'] == 'matrix') {
                $existTB = true;
            }
            foreach($val as $dk => $dv) {
                if(!is_array($dv)) {
                    $expressDelivery[$val['delivery_id']][$dk] = $dv;
                }
            }
        }

        $corp = app::get('ome')->model('dly_corp')->dump($logiId);
        app::get('ome')->model('dly_corp_channel')->getChannel($corp, $expressDelivery);
        if(!$corp['channel_id'] && $corp['tmpl_type'] == 'electron') {
            $this->message('对应多个电子面单来源，无法打印');
            exit();
        }
        if($corp['tmpl_type'] != 'electron') {
            $this->message('请使用电子面单！！！');
            exit();
        }
        if(!isset($_GET['isdown']) && $corp['tmpl_type'] == 'electron') {//处理电子面单
            $expressTmpl = app::get("logisticsmanager")->model('express_template')->dump($corp['prt_tmpl_id'],'template_type');
            if($existTB === true && !in_array($expressTmpl['template_type'], ['cainiao_standard','cainiao_user'])) {
                $this->message('淘宝订单请使用菜鸟控件、菜鸟模板');
                exit();
            }
            $eleRet = kernel::single('o2o_delivery_electron')->dealElectron($expressDelivery, $corp['channel_id']);

            if(count($eleRet['id_bn'])) {
                foreach($eleRet['id_bn'] as $k => $val) {
                    unset($print_data['deliverys'][$k]);
                    $print_data['errIds'][] = $k;
                    $print_data['errBns'][$k] = $val['bn'];
                    $print_data['errInfo'][$k] = $val['msg'];
                }
            }
            if(empty($print_data['deliverys'])) {
                $this->message('运单号获取失败：'.$val['msg']);
            }
        }
        foreach($print_data['deliverys'] as $dk=>$dv){
            if($expressDelivery[$dk]['logi_no']) $print_data['deliverys'][$dk]['logi_no'] = $expressDelivery[$dk]['logi_no'];

        }

        $PrintShipLib = kernel::single('o2o_delivery_print_ship');
        $format_data = $PrintShipLib->format($print_data, $sku,$_err);

        $this->pagedata = $format_data;
        $this->pagedata['appCtl'] = 'app=o2o&ctl=admin_delivery_print';

        $express_company_no = strtoupper($corp['type']);
        $objExpress = ome_print_tmpl_express::instance($express_company_no, $this);
        if(!$objExpress->getExpressTpl($corp)){
            $msg = $objExpress->msg ? $objExpress->msg : '获取打印模板失败';
            $this->message($msg);
            exit();
        }
        $printField = $objExpress->printField;
        $printTpl = $objExpress->printTpl;
        $this->pagedata['printTmpl'] = $printTpl;

        $tradeIds = array();
        if ($format_data['delivery']) {
            foreach ($format_data['delivery'] as $val) {
                $val['printTpl']['template_type'] = $printTpl['template_type'];

                //获取快递单打印模板的servivce定义
                $data = array();
                foreach (kernel::servicelist('wms.service.template') as $object => $instance) {
                    if (method_exists($instance, 'getElementContent')) {
                        $tmp = $instance->getElementContent($val);
                    }
                    $data = array_merge($data, $tmp);
                }
                
                //输出所有打印项
                $mydata[] = $data;
                
                // 御城河订单
                $tradeIds = array_merge($tradeIds,explode(',',$val['order_bn']));
            }
        }


        $jsondata = $PrintShipLib->arrayToJson($mydata);

        //组织控件打印数据
        $this->pagedata['jsondata'] = $jsondata;
        $this->pagedata['data'] = addslashes($deliveryObj->array2xml2($mydata, 'data'));
        $this->pagedata['totalPage'] = count($mydata);



        /* 修改的地方 */
        if ($this->pagedata['printTmpl']['file_id']) {
            $this->pagedata['tmpl_bg'] = 'index.php?app=ome&ctl=admin_delivery_print&act=showPicture&p[0]=' . $this->pagedata['printTmpl']['file_id'];
        }

        //获取有问题的单据号
        $this->pagedata['errBns'] = $print_data['errBns'];
        $this->pagedata['err'] = $_err;

        //批次号
        $this->pagedata['idents'] = $print_data['identInfo']['items'];
        $this->pagedata['ident'] = is_array($print_data['identInfo']['idents']) ? implode(',', $print_data['identInfo']['idents']) : $print_data['identInfo']['idents'];
        $this->pagedata['errIds'] = $print_data['errIds'];
        $this->pagedata['errInfo'] = $print_data['errInfo'];
        $items = array();
        foreach ($format_data['delivery'] as $row) {
            $items[$row['delivery_id']] = $row;
        }

        $this->pagedata['items'] = $items;
        $this->pagedata['sku'] = $sku;//单品 多品标识
        $this->pagedata['dpi'] = 96;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['title'] = '快递单打印';
        $this->pagedata['uniqid'] = uniqid();

        //获取打印版本配置信息
        $logicfg = kernel::single('ome_print_logicfg')->getLogiCfg();
        if($logicfg[$express_company_no]){
            $logiVersionFlag = 1;
            $this->pagedata['logicfg'] = $logicfg[$express_company_no];
            $print_logi_version = app::get('ome')->getConf('print_logi_version_'.$this->pagedata['print_logi_id']);

            $this->pagedata['print_logi_version'] = intval($print_logi_version);
        }
        $this->pagedata['logiVersionFlag'] = $logiVersionFlag;
        $params = array('order_bn'=>$this->pagedata['o_bn']);

        // 御城河
        $tradeIds = $this->pagedata['o_bn'];
        $hchsafe = array(
            'operation' => '[控]订单快递单打印',
            'tradeIds'  => $tradeIds,
        );
        kernel::single('base_hchsafe')->order_log($hchsafe);
        // 御城河 END

        $objExpress->setParams($params)->getTmpl();
    }

    private function message($msg) {
        $this->pagedata['err'] = 'true';
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['time'] = date("Y-m-d H:i:s");
        $this->pagedata['msg'] = $msg;
        $this->singlepage('admin/print/message.html');
        exit();
    }

    /**
     * 设置PrintStatus
     * @return mixed 返回操作结果
     */
    public function setPrintStatus() {
        set_time_limit(0);
        $current_otmpl_name = $_POST['current_otmpl_name'] ? $_POST['current_otmpl_name'] : '默认';
        $type = $_POST['type'];
        $str_id = $_POST['str'];
        $dlyObj = app::get('wap')->model('delivery');
        $tmp_id = array_filter(explode(',', $str_id));

        $dlys = $dlyObj->getList('*', array('delivery_id' => $tmp_id), 0, -1);
        $dly = array();
        $arr_s = array(1,2);

        foreach ($dlys as $k => $delivery) {
            if (in_array($delivery['status'], $arr_s)){
                continue;
            }

            //if ($delivery['status'] == 'ready')  $dly[$k]['status'] = 'progress';
            $dly[$k]['delivery_id'] = $delivery['delivery_id'];
            $send_flag = false;
            switch ($type) {
                case 'express':
                    if (($delivery['print_status'] & 4) != 4) {
                        $send_flag = true;
                    }
                    $dly[$k]['print_status'] = $delivery['print_status'] | 4;
                    $dly[$k]['_log_'] = 'delivery_expre@o2o';
                    $dly[$k]['_memo_'] = '快递单打印';
                    //如果是当当物流订单，将订单号更新为物流单号
                    $logi_id = $delivery['logi_id'];
                    $dly_corpObj = app::get('ome')->model('dly_corp');
                    $orderObj = app::get('ome')->model('orders');
                    $dly_corp = $dly_corpObj->dump($logi_id,'type');
                    if ($dly_corp['type'] == 'DANGDANG') {
                        $orderIds = $dlyObj->getOrderIdByDeliveryId($delivery['delivery_id']);
                        if ($orderIds)
                        $ids = implode(',', $orderIds);
                        if ($orderIds)
                        foreach ($orderIds as $oid)
                        {
                            $order = $orderObj->dump($oid,'order_bn');
                            $order_bn= $order['order_bn'];
                        }
                        $dly[$k]['logi_no'] = $order_bn;
                    }
                    break;
                case 'delivery':
                    if (($delivery['print_status'] & 2) != 2) {
                        $send_flag = true;
                    }
                    $dly[$k]['print_status'] = $delivery['print_status'] | 2;
                    $dly[$k]['_log_'] = 'delivery_deliv@o2o';
                    $dly[$k]['_memo_'] = "发货单商品信息打印（打印模板： $current_otmpl_name ）";
                    break;
            }
        }
        $opObj = app::get('ome')->model('operation_log');
        foreach ($dly as $k => $v) {
            $_dly = $v;
            $dlyObj->save($_dly);

            $delivery = $dlyObj->dump($v['delivery_id'], 'outer_delivery_bn,print_status,process_status,branch_id');

            $print_status = true;
            $stock_print_status = true;
            $delie_print_status = true;
            $merge_print_status = true;
            //根据打印单据配置及当前状态判断发货单打印状态
            $deliCfgLib = kernel::single('wms_delivery_cfg');
            /*$checkStock = $deliCfgLib->analyse_btn_status('stock');
            if($checkStock == true && ($delivery['print_status'] & 1) != 1){
                $stock_print_status = false;
            }*/

            $checkDelie = $deliCfgLib->analyse_btn_status('delie');
            if($checkDelie == true && ($delivery['print_status'] & 2) != 2){
                $delie_print_status = false;
            }

            /*$checkMerge = $deliCfgLib->analyse_btn_status('merge');
            if($checkMerge == true && ((($delivery['print_status'] & 1) != 1) || (($delivery['print_status'] & 2) != 2))){
                $merge_print_status = false;
            }*/

            if(($delivery['print_status'] & 4) != 4){
                $print_status = false;
            }

            if($print_status || $stock_print_status || $delie_print_status || $merge_print_status){
                if ($stock_print_status && $delie_print_status && $print_status && $merge_print_status){
                    $tmp_status = $delivery['process_status'] | 1;
                    $data = array('process_status'=>$tmp_status,'delivery_id'=>$v['delivery_id']);
                    $dlyObj->save($data);
                }


                //同步打印状态到oms
                $store_id = kernel::single('ome_branch')->isStoreBranch($delivery['branch_id']);
                $data = array(
                    'delivery_bn' => $delivery['outer_delivery_bn'],
                    'stock_status' => ($delivery['print_status'] & 1) == 1 ? 'true' : 'false',
                    'deliv_status' => ($delivery['print_status'] & 2) == 2 ? 'true' : 'false',
                    'expre_status' => ($delivery['print_status'] & 4) == 4 ? 'true' : 'false',
                );
                kernel::single('wap_event_trigger_delivery')->doPrint($store_id, $data, true);
            }

            $opObj->write_log($v['_log_'], $v['delivery_id'], $v['_memo_']);
        }

        echo 'true';
    }

    /**
     * toPrintMergeNew
     * @return mixed 返回值
     */
    public function toPrintMergeNew() {
        $_err = 'false';

        //多品单品标识
        $sku = '';

        $now_print_type = 'delivery';
        $now_print_mode = 'new';

        //获取当前待打印的发货单过滤条件
        $filter_condition = ['delivery_id'=>(int)$_GET['delivery_id']];

        $PrintLib = kernel::single('o2o_delivery_print');
        $print_data = $PrintLib->getPrintDatas($filter_condition,$now_print_type,$sku,false,$msg);

        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->message($msg['error_msg']);
        }

        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->message($msg['warn_msg']);
            exit;
        }

        $PrintDlyLib = kernel::single('o2o_delivery_print_newdelivery');
        $format_data = $PrintDlyLib->format($print_data, $sku,$_err);

        $deliveryDataObj = kernel::single('wms_print_data_newdelivery');
        foreach ($format_data['items'] as $delivery) {
           $logi_name = $delivery['logi_name'];
            $allItems[] = $deliveryDataObj->getElectronOrder($delivery);
        }
        $jsondata = '';
        if ($allItems) {
            $jsondata = $PrintDlyLib->arrayToJson($allItems);

        }


        $this->pagedata['err'] = $_err;
        $this->pagedata['title'] = '发货单打印';
        //物流公司
        $this->pagedata['appCtl'] = 'app=o2o&ctl=admin_delivery_print';

        $this->pagedata['vid'] = implode(',', $print_data['ids']);
        $this->pagedata['logi_name'] = $logi_name;
        //打印数量
        $this->pagedata['count'] = count($allItems);
        //随机数
        $this->pagedata['uniqid'] = uniqid();
        $this->pagedata['reload'] = 'true';
        //组织控件打印数据
        $this->pagedata['jsondata'] = $jsondata;
        $this->pagedata['totalPage'] = count($allItems);

        // 推送平台日志
        if ($print_data['tradeIds']) kernel::single('base_hchsafe')->order_log(array('operation'=>'[控]发货单打印预览','tradeIds'=>$print_data['tradeIds']));

        ome_print_controltmpl::instance($now_print_type, $this)->printOTmpl($_GET['otmplId']);
    }
}