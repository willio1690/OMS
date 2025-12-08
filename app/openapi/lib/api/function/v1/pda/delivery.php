<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2017/1/13
 * @describe pda 发货单查询
 */
class openapi_api_function_v1_pda_delivery extends openapi_api_function_v1_pda_abstract 
{

    protected function _getFilter($params) {
        $filter = array('type'=>'normal', 'status' => 0, 'disabled' => 'false');
        if($params['delivery_bn']) {
            $filter['delivery_bn'] = $params['delivery_bn'];
        }

        #因打印状态和拣货、校验状态先后顺序没有做严格，需要使用按位与操作
        if($params['status'] == 'ready') {
            $filter['process_status'] = array(0, 1);
            $filter['pick_status'] = array(0, 1);
            #可拣货中，不能包含已打印的
        } elseif($params['status'] == 'picked') {
            $filter['process_status'] = array(0, 1);
        } elseif($params['status'] == 'checked') {
            $filter['process_status'] = '3';
        }
        if($params['delivery_ident']) {
            $filter['delivery_ident'] = $params['delivery_ident'];
        }
        if($params['delivery_panier']) {
            $filter['delivery_panier'] = $params['delivery_panier'];
        }
        return $filter;
    }

    protected function _getOrderInfo($arrDeliveryId) {
        $dlyOrderId = app::get('ome')->model('delivery_order')->getList("*", array('delivery_id'=>$arrDeliveryId));
        $arrOrderId = array();
        foreach($dlyOrderId as $val) {
            $arrOrderId[$val['order_id']] = $val['order_id'];
        }
        $order = app::get('ome')->model('orders')->getList('order_id, custom_mark, mark_text,order_bn', array('order_id'=>$arrOrderId));
        $arrOrder = array();
        foreach($order as $val) {
            $customMark = '';
            $markText = '';
            $custom = kernel::single('ome_func')->format_memo($val['custom_mark']);
            if($custom){
                // 取最后一条
                $custom = array_pop($custom);
                $customMark = $custom['op_content'].'；'."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp";
            }
            $mark = kernel::single('ome_func')->format_memo($val['mark_text']);
            if($mark){
                // 取最后一条
                $mark = array_pop($mark);
                $markText = $mark['op_content'].'；'."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp";
            }

            $arrOrder[$val['order_id']]['custom_mark'] = $customMark;
            $arrOrder[$val['order_id']]['mark_text']   = $markText;
            $arrOrder[$val['order_id']]['order_bn']    = $val['order_bn'];
        }
        $arrDlyOrderInfo = array();
        foreach($dlyOrderId as $val) {
            $arrDlyOrderInfo[$val['delivery_id']]['custom_mark'] .= $arrOrder[$val['order_id']]['custom_mark'];
            $arrDlyOrderInfo[$val['delivery_id']]['mark_text']   .= $arrOrder[$val['order_id']]['mark_text'];
            $arrDlyOrderInfo[$val['delivery_id']]['order_bn']    .= $arrOrder[$val['order_id']]['order_bn'].'、';
        }
        return $arrDlyOrderInfo;
    }

    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */

    public function getList($params, &$code, &$sub_msg) {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
           $sub_msg = '设备未授权';
           return false;
        }
        #获取操作员管辖所有自建仓库
        $all_owner_branch = $this->get_all_branchs('1');
        if(empty($all_owner_branch)){
            $sub_msg = '操作员无仓库管辖权限,请到OMS设置';
            return false;
        }
        foreach ( $all_owner_branch as $k => $branch){
            $branch_filter['branch_id'][] = $branch['branch_id'];
        }
        
        $filter = $this->_getFilter($params);
        $filter = array_merge($filter,$branch_filter);

        if (isset($params['logi_no']) && !empty($params['logi_no'])) {
            $dlyBillLib = kernel::single('wms_delivery_bill');
            $delivery_id = $dlyBillLib->getDeliveryIdByPrimaryLogi($params['logi_no']);
            if(!$delivery_id){
                $sub_msg = $logi_no . '快递单号不存在!';
                return false;
            }
            $filter['delivery_id'] = $delivery_id;
        }

        if(empty($filter)) {
            $sub_msg = '没有检索条件';
            return false;
        }
        $pageNo = intval($params['page_no']);
        $pageSize = intval($params['page_size']);
        $offset = $pageNo > 0 ? $pageNo - 1 : 0;
        $limit = $pageSize && $pageSize < 1000 ? $pageSize : 1000;
        $field = 'delivery_id, delivery_bn,create_time,branch_id,ship_name';
        $deliveryModel = app::get('wms')->model('delivery');
        $delivery = $deliveryModel->getList($field, $filter, $offset * $limit, $limit);
        if(empty($delivery)) {
            $sub_msg = '没有符合条件的待发货单';
            return false;
        }
        $arrDelivery = array();
        foreach($delivery as $val) {
            $arrDelivery[$val['delivery_id']] = $val;
        }
        $arrDeliveryId = array_keys($arrDelivery);
        $dlyOrder = $this->_getOrderInfo($arrDeliveryId);
        $branch_ids = array();
        foreach($dlyOrder as $dlyId => $val) {
            $arrDelivery[$dlyId] = array_merge($arrDelivery[$dlyId], $val);
            $branch_ids[] = $arrDelivery[$dlyId]['branch_id'];
        }
        $dlyItems = app::get('wms')->model('delivery_items')->getList('*', array('delivery_id'=>$arrDeliveryId));
        $product_ids = array();
        foreach($dlyItems as $val) {
            $val['product_name'] = $this->charFilter($val['product_name']);

            $arrDelivery[$val['delivery_id']]['delivery_items'][] = $val;
            $product_ids[] = $val['product_id'];
        }

        // 盒子号
        $pdaPickList = array ();
        $pdaPickMdl = app::get('wms')->model('pda_pick');
        foreach ($pdaPickMdl->getList('*', array ('delivery_id' => $arrDeliveryId)) as $key => $value) {
            $pdaPickList[$value['delivery_id']] = $value;
        }

        $basicMaterialLib = kernel::single('material_basic_material');
        foreach (array_unique($product_ids) as $pid) {
            $all_product_infos[$pid] = $basicMaterialLib->getBasicMaterialExt($pid);
        }

        #一次分仓库获取获取每个货品的货位
        $obj_branch_product_pos = app::get('ome')->model('branch_product_pos');
        $branch_product_pos  = array();
        foreach($branch_ids as $branch_id){
            $rows = $obj_branch_product_pos->get_products_pos($branch_id,$product_ids);
            if(!$rows)continue;
            foreach($rows as $v){
                $branch_id  = $v['branch_id'];
                $product_id = $v['product_id'];
                $branch_product_pos[$branch_id][$product_id] = $v['store_position'];
            }
        }
        #遍历每个发货单，处理货位和条码
        foreach($arrDelivery as $id=>$Delivery){
            $arrDelivery[$id]['box'] = $pdaPickList[$Delivery['delivery_id']]['box'];

            $branch_id =  $Delivery['branch_id'];
            foreach($Delivery['delivery_items'] as $p=>$items){
                $product_id = $items['product_id'];
                $store_position = $branch_product_pos[$branch_id][$product_id];
                $barcode = $all_product_infos[$product_id]['barcode'];
                
                $arrDelivery[$id]['delivery_items'][$p]['store_position'] = $store_position?$store_position:'';
                $arrDelivery[$id]['delivery_items'][$p]['barcode']        = $barcode?$barcode:'';
                $arrDelivery[$id]['delivery_items'][$p]['serial_number']  = $all_product_infos[$product_id]['serial_number'];
            }
        }
        unset($all_product_info,$branch_product_pos);
        $count = $deliveryModel->count($filter);
        return array('count'=>$count, 'list'=>array_values($arrDelivery));
    }

    /**
     * 更新Status
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function updateStatus($params, &$code, &$sub_msg) {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        if($params['delivery_bn']){
            $deliveryBn = explode(';', $params['delivery_bn']);
            $arrDelivery = app::get('wms')->model('delivery')->getList('*', array('delivery_bn'=>$deliveryBn));
        }elseif($params['batch_no']){
            $batch_nos = explode(';', trim($params['batch_no']));
            $printqModel = app::get('ome')->model("print_queue_items");
            $delivery_ids = $printqModel->getList('delivery_id',array('ident'=>$batch_nos));
            $print_delivery_ids = array_map('current', $delivery_ids);
            if(empty($print_delivery_ids)){
                $sub_msg = '没有符合条件的发货单';
                return false;
            }
            $arrDelivery = app::get('wms')->model('delivery')->getList('*', array('delivery_id'=>$print_delivery_ids));
        }else{
            $sub_msg = '缺少发货单号或批次号';
            return false;
        }
        if($params['status'] == 'picked') {
            return $this->_dealPickedDelivery($arrDelivery);
        } else {
            $sub_msg = '未知的发货单状态';
            return false;
        }
    }

    protected function _dealPickedDelivery($arrDelivery) {
        $ret = array(
            'picked'=>array('msg'=>'拣货成功', 'delivery_bn'=>array()),
            'none'=>array('msg'=>'无需拣货或已经拣货成功', 'delivery_bn'=>array()),
            'fail'=>array('msg'=>'发货单状态有变化，拣货失败', 'delivery_bn'=>array()),
        );
        $updateDeliveryId = array();
        $deliveryModel = app::get('wms')->model('delivery');
        foreach($arrDelivery as $delivery) {
            if(($delivery['pick_status'] == '2') || ($delivery['bool_status'] & 2) || ($delivery['verify'] == 'true')) {
                $ret['none']['delivery_bn'][] = $delivery['delivery_bn'];
            } else {
                $update = array(
                    'pick_status' => '2', // 已拣货 
                );
                $rs = $deliveryModel->update($update, array('delivery_id'=>$delivery['delivery_id'], 'bool_status'=>$delivery['bool_status']));
                if(is_bool($rs)) {
                    $ret['fail']['delivery_bn'][] = $delivery['delivery_bn'];
                } else {
                    $ret['picked']['delivery_bn'][] = $delivery['delivery_bn'];
                    $updateDeliveryId[] = $delivery['delivery_id'];
                }
            }
        }
        app::get('ome')->model('operation_log')->batch_write_log('delivery_modify@ome', 'pda拣货已拣货', time(), array('delivery_id'=>$updateDeliveryId));
        return $ret;
    }
    /**
     * 检查
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回验证结果
     */
    public function check($params, &$code, &$sub_msg){
       if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        if (!$params['logi_no']) {
            if($params['delivery_bn']) {
                $dly = app::get('ome')->model('delivery')->db_dump(['delivery_bn'=>$params['delivery_bn']], 'logi_no');
                $params['logi_no'] = $dly['logi_no'];
            }
        }
        if (!$params['logi_no']) {
            $sub_msg = '缺少物流单号';
            return false;
        }

        $status = kernel::database()->beginTransaction();
        $res = kernel::single('openapi_data_original_pda_delivery')->check($params);
        if($res['rsp'] == 'succ'){
            kernel::database()->commit($status);
            return array(
                'message' => 'success',
                'logi_no' => $params['logi_no'],
                'msg' => $res['msg'],
            );
        }
        $sub_msg = $res['msg'] ? $res['msg'] : '校验失败';
        kernel::database()->rollBack();
        return false;
    }

    /**
     * batchCheck
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function batchCheck($params, &$code, &$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $fail_mun = 0;
        $delivery_info = @json_decode($params['delivery_info'],true);
        foreach ($delivery_info as $info){
            $delivery_params['pda_token'] = $params['pda_token'];
            $delivery_params['device_code'] = $params['device_code'];
            $delivery_params['delivery_bn'] = $info['delivery_bn'];
            $delivery_params['status'] = $info['status'];
            $delivery_params['serial_data'] = @serialize($info['serial_data']?$info['serial_data']:array());
            $delivery_params['verify_items'] = @json_encode($info['verify_items']?$info['verify_items']:array());
            if(!$this->check($delivery_params, $code, $sub_msg_temp)){
                $sub_msg = $sub_msg_temp;
                $fail_mun++;
            }
        }

        $delivery_num = count($delivery_info);
        $succ_num = $delivery_num - $fail_mun;

        return array('msg'=>'成功:'.$succ_num.' 失败:'.$fail_mun,'message'=>'原因:'.json_encode($sub_msg));
    }

    /**
     * PDA发货
     *
     * @return void
     * @author 
     **/
    public function consign($params, &$code, &$sub_msg)
    {
        // 验证登陆
        if(!$params['pda_token'] || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }

        // 验证设备
       if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        $logi_no     = $params['logi_no'];
        $weight      = sprintf('%.2f',$params['weight']);

        if (!$logi_no) {
            $sub_msg = '运单号不能为空';
            return false;
        }

        $minWeight = app::get('ome')->getConf('ome.delivery.minWeight');
        $maxWeight = app::get('ome')->getConf('ome.delivery.maxWeight');
        if('on' == app::get('ome')->getConf('ome.delivery.weight') && ($weight<$minWeight || $weight>$maxWeight)){

            $sub_msg = '提交重量:'.$weight.',最小'.$minWeight.',最大'.$maxWeight.'包裹重量超出系统设置范围';
            return false;
        }

        $status = kernel::database()->beginTransaction();
        $res = kernel::single('openapi_data_original_pda_delivery')->consign($params);
        if($res['rsp'] == 'succ'){
            kernel::database()->commit($status);
            return array(
                'message' => 'success',
                'logi_no' => $params['logi_no'],
                'msg' => $res['msg'],
            );
        }
        $sub_msg = $res['msg'] ? $res['msg'] : '发货失败';
        kernel::database()->rollBack();
        return false;
    }

    /**
     * PDA打印
     *
     * @return void
     * @author 
     **/
    public function printCPCL($params, &$code, &$sub_msg)
    {
        // 验证登陆
        if(!$params['pda_token'] || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }

        // 验证设备
       if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        $delivery_bn  = $params['delivery_bn'];
        $printer_name = $params['printer_name'];

        if (!$delivery_bn) {
            $sub_msg = '发货单号不能同时为空';
            return false;
        }

        if (!$printer_name) {
            $sub_msg = '打印机名称不能为空'; 
            return false;
        }

        $deliveryMdl = app::get('wms')->model('delivery');
        $delivery = $deliveryMdl->db_dump(array ('delivery_bn' => $delivery_bn),'delivery_id, logi_id, shop_id');
        if (!$delivery) {
            $sub_msg = '发货单['.$delivery_bn.']不存在';
            return false;
        }

        $corpMdl = app::get('ome')->model('dly_corp');
        $corp = $corpMdl->db_dump($delivery['logi_id'],'channel_id,corp_id');
        app::get('ome')->model('dly_corp_channel')->getChannel($corp, array($delivery));
        if (!$corp['channel_id']) {
            $sub_msg = '发货单['.$delivery_bn.']非电子面单不能打印';
            return false;
        }

        $result = kernel::single('ome_event_trigger_logistics_electron')->directGetWaybill($delivery['delivery_id'], $corp['channel_id']);
        if ($result['fail']) {
            $sub_msg = '发货单['.$delivery_bn.']获取运单号失败:'.$result[0]['msg'];

            return false;
        }

        $logi_no = '';
        foreach ($result['succ'] as $key => $value) {
            if ($value['delivery_bn'] == $delivery_bn) {
                $logi_no = $value['logi_no'];
            }
        }
        if (!$logi_no) {
            $sub_msg = '发货单['.$delivery_bn.']未返回相关运单号';

            return false;
        }

        $printer = array (
            'printer_name' => $printer_name,
            'client_id'    => $params['device_code'],
        );

        // 获取打印指令
        list ($res, $msg) = kernel::single('ome_event_trigger_logistics_electron')->getPrintCPCL($delivery['delivery_id'], $printer);

        if (!$res) {
            $sub_msg = $msg;

            return false;
        }

        return array('message'=>'success', 'data' => $msg);
    }

    
}