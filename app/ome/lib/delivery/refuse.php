<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_delivery_refuse{

    //发货拒收入库动作
    function do_iostock($reship_id,$io,&$msg){
        $reshipObj = app::get('ome')->model('reship');
        $reshipInfo = $reshipObj->dump($reship_id,'reship_bn,order_id,branch_id');
        
        $reshipLib = kernel::single('siso_receipt_iostock_reship');
        $iostock_data = $this->get_iostock_data($reship_id);
        $reshipLib->_typeId=32;
        $result = $reshipLib->create(array('reship_id'=>$reship_id,'items'=>$iostock_data,'branch_id'=>$reshipInfo['branch_id']), $data, $msg);
        return $result;
    }

    /**
     * 组织入库数据
     * @access public
     * @param String $iso_id 出入库ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($reship_id){
        $reshipObj = app::get('ome')->model('reship');
        $reship_items = $reshipObj->getItemList($reship_id);
        $iostock_data = array();
        if ($reship_items){
            foreach ($reship_items as $k=>$v){
                $iostock_data[$v['item_id']] = array(
                    'item_id' => $v['item_id'],
                    'bn' => $v['bn'],
                    'price' => $v['price'],
                    'normal_num' => $v['num'],
                );
            }
        }
        

        return $iostock_data;
    }

    //拒收退货做负销售单
    function do_sales(){

    }

    //组成销售单数据
    function get_sales_data(){

    }

    
    /**
     * 发送仓库数据.
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function reship_create($reship_id)
    {
        
        $oReship = app::get('ome')->model('reship');
        $oReship_item = app::get('ome')->model('reship_items');
        $oReturn = app::get('ome')->model('return_product');
        $oDelivery_order = app::get('ome')->model('delivery_order');
        $oDelivery = app::get('ome')->model('delivery');
        $oOrder = app::get('ome')->model('orders');
        $reship = $oReship->dump($reship_id,'reship_bn,t_begin,memo,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,return_logi_no,return_logi_name,return_id,order_id,branch_id');

        $reship_item = $oReship_item->getlist('bn,product_name as name,num,price,branch_id',array('reship_id'=>$reship_id),0,-1);
        $branch_id = $reship['branch_id'];
        $iostockdataObj = kernel::single('console_iostockdata');
        $branch = $iostockdataObj->getBranchByid($branch_id);
        $return_id = $reship['return_id'];
        $order_id = $reship['order_id'];
        $delivery_order = $oDelivery_order->dump(array('order_id'=>$order_id),'delivery_id');
        $delivery_id = $delivery_order['delivery_id'];
        $order = $oOrder->dump($order_id,'order_bn,shop_id');
        $delivery = $oDelivery->dump($delivery_id,'delivery_bn');
        $shopObj = app::get('ome')->model('shop');
        $shopInfo = $shopObj->dump($order['shop_id'],'name');
        $ship_area = $reship['ship_area'];
        $ship_area = explode(':',$ship_area);
        $ship_area = explode('/',$ship_area[1]);
        $reship_data = array(
            'reship_bn'=>$reship['reship_bn'],
            'branch_id'=>$branch_id,
            'branch_bn'=>$branch['branch_bn'],
            'create_time'=>$reship['t_begin'],
            'memo'=>$reship['memo'],
            'original_delivery_bn'=>$delivery['delivery_bn'],
            'logi_no'=>$reship['return_logi_no'],
            'logi_name'=>$reship['return_logi_name'],
            'order_bn'=>$order['order_bn'],
            'receiver_name'=>$reship['ship_name'],
            'receiver_zip'=>$reship['ship_zip'],
            'receiver_state'=>$ship_area[0],
            'receiver_city'=>$ship_area[1],
            'receiver_district'=>$ship_area[2],
            'receiver_address'=>$reship['ship_addr'],
            'receiver_phone'=>$reship['ship_tel'],
            'receiver_mobile'=>$reship['ship_mobile'],
            'receiver_email'=>$reship['ship_email'],
            'storage_code'=>$branch['storage_code'],
            'items'=>$reship_item,
            'shop_code'=>$shopInfo['name'],
        );
        return $reship_data;
    } // end func

    /**
     * 更新发货单状态
     * @param  
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function update_orderStatus($order_id)
    {
        $orderObj = app::get('ome')->model('orders');
        
        $order_sum = $orderObj->db->selectrow('SELECT sum(sendnum) as count FROM sdb_ome_order_items WHERE order_id='.$order_id.' AND sendnum != return_num');

        $ship_status = ($order_sum['count'] == 0) ? '4' : '3';
        $orderObj->db->exec("UPDATE sdb_ome_orders SET ship_status='".$ship_status."' WHERE order_id=".$order_id);
        
        
        
    }
    
    /**
     * 处理导入的追回单据
     */
    public function dispose_import_csv(){
        
        $result = array('rsp'=>'fail', 'error_msg'=>'');
        
        //验证导入的文件
        if(!$_FILES['import_file']['name']){
            $result['error_msg'] = '未上传文件!';
            return $result;
        }
        
        $tmpFileHandle = fopen($_FILES['import_file']['tmp_name'], "r");
        
        $oIo = kernel::servicelist('desktop_io');
        foreach( $oIo as $aIo ){
            if($aIo->io_type_name == substr($_FILES['import_file']['name'], -3)){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);
        
        if(!$oImportType){
            $result['error_msg'] = '导入格式不正确!';
            return $result;
        }
        
        $contents = array();
        $oImportType->fgethandle($tmpFileHandle, $contents);
        
        //导入的标题
        $title = $contents[0];
        
        fclose($tmpFileHandle);
        unset($contents[0]);
        
        $tm_contents = array();
        foreach($contents as $row){
            if(!empty($row[0])){
                $tm_contents[] = $row;
            }
        }
        $contents = $tm_contents;
        
        if(empty($contents)){
            $result['error_msg'] = '导入数据项为空!';
            return $result;
        }
        
        if(count($contents) > 1000){
            $result['error_msg'] = '一次性限制最多可导入1000单!';
            return $result;
        }
        
        $result = array('rsp'=>'succ', 'data'=>$contents, 'title'=>$title);
        return $result;
    }
    
    /**
     * 处理发货追回(根据导入的退回物流单号、发货单号)
     * 
     * @param string $type 处理方式(logistics退回物流单号、delivery发货单号)
     * @param array $data
     * @param string $error_msg
     * @return array
     */
    public function dispose_refuse($type, $data, &$error_msg){
        $oQueue = app::get('base')->model('queue');
        
        if(empty($data)){
            $error_msg = '数据不能为空!';
            return false;
        }
        
        if(!in_array($type, array('logistics', 'delivery'))){
            $error_msg = '无效的处理方式!';
            return false;
        }
        
        $page = 0;
        $limit = 100;
        $i = 0;
        $dataList = array();
        foreach ($data as $key => $bill_no){
            
            //根据单据号查找可追回的订单、发货单
            $result = $this->get_refuse_data($type, $bill_no, $error_msg);
            if(!$result){
                return false;
            }
            
            //组织队列数据
            foreach ($result as $iKey => $iVal){
                $i++;
                
                //分页,每页100条
                if($i == $limit){
                    $page ++;
                    $i = 0;
                }
                
                $dataList[$page][] = array('type'=>$type, 'bill_no'=>$bill_no, 'data'=>$iVal);
            }
        }
        
        if(empty($dataList)){
            $error_msg = '没有可执行的单据!';
            return false;
        }
        
        //加入队列
        foreach ($dataList as $pageKey => $val){
            $queueData = array(
                    'queue_title'=>'发货追回导入',
                    'start_time'=>time(),
                    'params'=>array(
                            'sdfdata' => $val,
                            'app' => 'ome',
                            'mdl' => 'reship'
                    ),
                    'worker'=>'ome_delivery_refuse_import.run',
            );
            $oQueue->save($queueData);
        }
        app::get('base')->model('queue')->flush();
        
        return true;
    }
    
    /**
     * 根据(物流单号、发货单号)查找可追回的订单、发货单
     * todo: 导入单据号追回时,如果一个订单拆分多个发货单,是按发货单追回,(对于订单纬度来说)也就是支持部分追回;
     * 
     * @param string $type 查询类型
     * @param string $bill_no 需查询的单据号
     * @param string $error_msg
     * @return array
     */
    public function get_refuse_data($type, $bill_no, &$error_msg){
        $deliveryObj = app::get('ome')->model('delivery');
        $items_detailObj = app::get('ome')->model('delivery_items_detail');
        
        $result = array();
        $delivery_ids = array();
        
        //根据类型获取发货单
        $operation = '';
        if($type == 'logistics')
        {
            $operation = '物流单号:'. $bill_no;
            $deliveryInfo = $deliveryObj->dump(array('logi_no'=>$bill_no, 'status'=>'succ'), 'delivery_id, is_bind, branch_id, logi_no');
            if(empty($deliveryInfo)){
                $error_msg = $operation .', 没有找到已发货的发货单!';
                return false;
            }
        }
        elseif($type == 'delivery')
        {
            $operation = '发货单号:'. $bill_no;
            $deliveryInfo = $deliveryObj->dump(array('delivery_bn'=>$bill_no, 'status'=>'succ'), 'delivery_id, is_bind, branch_id, logi_no');
            if(empty($deliveryInfo)){
                $error_msg =  $operation .', 没有找到已发货的发货单!';
                return false;
            }
        }else{
            $error_msg = '无效的处理方式!';
            return false;
        }
        
        //发货仓库
        $branch_id = $deliveryInfo['branch_id'];
        
        //todo: 有合并发货单的情况
        if($deliveryInfo['is_bind'] == 'true'){
            //合并发货单
            $dlyList = $deliveryObj->getList('delivery_id', array('parent_id'=>$deliveryInfo['delivery_id'], 'status'=>'succ'));
            foreach ($dlyList as $key => $val){
                $delivery_ids[] = $val['delivery_id'];
            }
        }else{
            //普通发货单
            $delivery_ids[] = $deliveryInfo['delivery_id'];
        }
        
        //根据发货单获取关联订单
        $sql = "SELECT a.delivery_id, b.order_id, b.order_bn, b.pay_status, b.ship_status FROM sdb_ome_delivery_order AS a 
                LEFT JOIN sdb_ome_orders AS b ON a.order_id=b.order_id WHERE a.delivery_id IN(". implode(', ', $delivery_ids) .")";
        $data = $deliveryObj->db->select($sql);
        if(empty($data)){
            $error_msg = $operation .', 没有找到对应的订单!';
            return false;
        }
        
        //验证有效性
        foreach ($data as $key => $val){
            $order_id = $val['order_id'];
            $delivery_id = $val['delivery_id'];
            
            //检查订单(导入时,支持按物流单号进行部分退货)
            if(!in_array($val['ship_status'], array('1', '3'))){
                $error_msg = $operation .',关联订单号: '. $val['order_bn'] .' 不是已发货 或 部分退货状态!';
                return false;
            }
            
            //部分退货处理逻辑
            if($val['ship_status'] == '3'){
                $sql = "SELECT reship_id FROM sdb_ome_reship WHERE order_id=". $order_id ." AND logi_no='". $deliveryInfo['logi_no'] ."' AND is_check NOT IN('5')";
                $return = $deliveryObj->db->selectrow($sql);
                if($return) {
                    $error_msg = $operation .',已有售后单据不能重复操作!';
                    return false;
                }
                
                //检查可退货明细
                $dlyItems = $items_detailObj->getlist('order_item_id, bn, product_id, number', array('order_id'=>$order_id, 'delivery_id'=>$delivery_id));
                foreach($dlyItems as $dlyKey => $dlyItemVal){
                    //取订单发货明细信息
                    $orderItemRow = $deliveryObj->db->selectrow("SELECT item_id, sendnum, return_num FROM sdb_ome_order_items WHERE item_id=".$dlyItemVal['order_item_id']);
                    
                    //可退货数量
                    $diff_num = intval($orderItemRow['sendnum']) - intval($orderItemRow['return_num']);
                    if($diff_num < $dlyItemVal['number']){
                        $error_msg = $operation .',货品: '. $dlyItemVal['bn'] .' 可退货数量错误!';
                        return false;
                    }
                }
            }else{
                //检查售后
                $sql = "SELECT return_id FROM sdb_ome_return_product WHERE order_id=". $order_id ." AND `status` NOT IN('5')";
                $return = $deliveryObj->db->selectrow($sql);
                if($return) {
                    $error_msg = $operation .',关联的订单已有售后单据!';
                    return false;
                }
                
                $sql = "SELECT reship_id FROM sdb_ome_reship WHERE order_id=". $order_id ." AND is_check NOT IN('5')";
                $reship = $deliveryObj->db->selectrow($sql);
                if($reship) {
                    $error_msg = $operation .',关联的订单已有退换货单据!';
                    return false;
                }
            }
            
            //返回关联订单ID、发货单ID
            $result[$delivery_id] = array('order_id'=>$order_id, 'delivery_id'=>$delivery_id, 'branch_id'=>$branch_id);
        }
        unset($deliveryInfo, $data, $delivery_ids);
        
        return $result;
    }
    
    /**
     * 根据订单号查找可追回的所有发货单
     * todo: 手工输入订单号追回时,如果一个订单拆分多个发货单时,不支持多次追回,仅支持一次性追回所有;
     * 
     * @param int $order_bn 订单号
     * @param string $error_msg
     * @return boolean
     */
    public function getRefuseByOrderBn($order_bn, &$error_msg){
        $orderObj = app::get('ome')->model('orders');
        $deliveryObj = app::get('ome')->model('delivery');
        $items_detailObj = app::get('ome')->model('delivery_items_detail');
        
        $result = array();
        $delivery_ids = array();
        
        //订单信息
        $orderInfo = $orderObj->dump(array('order_bn'=>$order_bn), 'order_id, order_bn, pay_status, ship_status');
        if(empty($orderInfo)){
            $error_msg = '要追回的订单不存在!';
            return false;
        }
        
        $order_id = $orderInfo['order_id'];
        $operation = '订单号:'. $orderInfo['order_bn'];
        
        //检查订单状态
        if($orderInfo['ship_status'] != '1'){
            $error_msg = $operation .',不是已发货状态无法做拒收处理!';
            return false;
        }
        
        //检查售后
        $sql = "SELECT return_id FROM sdb_ome_return_product WHERE order_id=". $order_id ." AND `status` NOT IN('5')";
        $return = $deliveryObj->db->selectrow($sql);
        if($return) {
            $error_msg = $operation .',订单已有相关售后单据!';
            return false;
        }
        
        $sql = "SELECT reship_id FROM sdb_ome_reship WHERE order_id=". $order_id ." AND is_check NOT IN('5')";
        $reship = $deliveryObj->db->selectrow($sql);
        if($reship) {
            $error_msg = $operation .',订单已有相关退换货单据!';
            return false;
        }
        
        //根据订单获取发货单
        $sql = "SELECT a.order_id, b.delivery_id, b.delivery_bn, b.branch_id FROM sdb_ome_delivery_order AS a 
                LEFT JOIN sdb_ome_delivery AS b ON a.delivery_id=b.delivery_id WHERE a.order_id=". $order_id ." AND b.status='succ' AND b.is_bind='false'";
        $dlyList = $deliveryObj->db->select($sql);
        if(empty($dlyList)){
            $error_msg = $operation .', 没有找到对应的发货单!';
            return false;
        }
        
        //返回关联订单ID、发货单ID
        foreach ($dlyList as $key => $val){
            $delivery_id = $val['delivery_id'];
            $result[$delivery_id] = array('order_id'=>$order_id, 'delivery_id'=>$delivery_id, 'branch_id'=>$val['branch_id']);
        }
        
        return $result;
    }
    
    /**
     * 发货追回最终处理
     * 
     * @param array $params
     * @param string $error_msg
     * @return array
     */
    public function finish_refuse($params, &$error_msg){
        
        $orderObj = app::get('ome')->model('orders');
        $shopObj = app::get('ome')->model('shop');
        $deliveryObj = app::get('ome')->model('delivery');
        $items_detailObj = app::get('ome')->model('delivery_items_detail');
        $reshipObj = app::get('ome')->model('reship');
        $operationLogObj = app::get('ome')->model('operation_log');
        
        $branchLib = kernel::single('ome_branch');
        $channelLib = kernel::single('channel_func');
        
        $type = $params['type']; //执行方式
        $bill_no = $params['bill_no']; //单据号
        $order_id = $params['order_id'];
        $delivery_id = $params['delivery_id'];
        $branch_id = $params['branch_id'];
        
        $operation = '';
        if($type == 'logistics'){
            $operation = '导入退回物流单号';
        }elseif($type == 'delivery'){
            $operation = '导入发货单号';
        }elseif($type == 'order'){
            $operation = '订单号';
        }
        $operation = $operation .': '. $bill_no;
        
        //操作人
        $op_id = kernel::single('desktop_user')->get_id();
        
        //C2C前端店铺列表
        $c2c_shop_type = ome_shop_type::shop_list();
        
        //是否自有仓
        $wms_id = $branchLib->getWmsIdById($branch_id);
        if($wms_id){
            $is_selfWms = $channelLib->isSelfWms($wms_id);
        }
        
        //订单信息
        $orderInfo = $orderObj->dump(array('order_id'=>$order_id), 'order_id, order_bn, shop_id, process_status, status, pay_status, ship_status, shop_type, org_id');
        
        //检查订单(导入时,支持按物流单号进行部分退货)
        if(!in_array($orderInfo['ship_status'], array('1', '3'))){
            $error_msg = $operation .',关联订单号: '. $orderInfo['order_bn'] .' 不是已发货、部分退货状态!';
            return false;
        }
        
        //店铺信息
        $shopInfo = $shopObj->dump(array('shop_id'=>$orderInfo['shop_id']), 'node_type, node_id,delivery_mode');
        
        //发货单
        $deliveryInfo = $deliveryObj->dump(array('delivery_id'=>$delivery_id), '*');
        
        //[合并发货单时]如果是子发货单,需要查询父发货单上的物流单号logi_no
        if($deliveryInfo['parent_id']){
            $parentDlyInfo = $deliveryObj->dump(array('delivery_id'=>$deliveryInfo['parent_id']), 'logi_id, logi_no, logi_name');
            
            $deliveryInfo = array_merge($deliveryInfo, $parentDlyInfo);
        }
        
        //发货单明细
        $dlyItems = $items_detailObj->getlist('order_item_id, order_obj_id, bn, product_id, number', array('order_id'=>$order_id, 'delivery_id'=>$delivery_id));
        
        
        //判断售后单是否已存在(导入时,支持按物流单号进行部分退货)
        $sql = "SELECT reship_id FROM sdb_ome_reship WHERE order_id=". $order_id ." AND logi_no='". $deliveryInfo['logi_no'] ."' AND is_check NOT IN('5')";
        $chkReship = $reshipObj->db->selectrow($sql);
        if($chkReship) {
            $error_msg = $operation .',已有售后单据不能重复操作!';
            return false;
        }
        
        
        //售后reship_bn
        $reship_bn = $reshipObj->gen_id();
        
        //组织数据
        $status = ($is_selfWms ? 'succ' : ''); //第三仓处理成功后,erpapi中会更新status状态
        $is_check = ($is_selfWms ? '7' : '1'); //第三仓处理成功后,erpapi中会更新is_check状态
        $reshipData = array(
                'status' => $status,
                'order_id' => $order_id,
                'member_id' => $deliveryInfo['member_id'],
                'return_logi_name' => $deliveryInfo['logi_id'],
                'return_type' => 'refuse',
                'return_logi_no' => $deliveryInfo['logi_no'],
                'logi_name' => $deliveryInfo['logi_name'],
                'logi_no' => $deliveryInfo['logi_no'],
                'logi_id' => $deliveryInfo['logi_id'],
                'delivery' => $deliveryInfo['delivery'],
                'delivery_id' => $deliveryInfo['delivery_id'],
                'memo' => '',
                'is_check' => $is_check,
                'op_id' => $op_id,
                't_begin' => time(),
                't_end' => 0,
                'shop_id' => $deliveryInfo['shop_id'],
                'reship_bn' => $reship_bn,
                'ship_name' => $deliveryInfo['consignee']['name'],
                'ship_addr' => $deliveryInfo['consignee']['addr'],
                'ship_zip' => $deliveryInfo['consignee']['zip'],
                'ship_tel' => $deliveryInfo['consignee']['telephone'],
                'ship_mobile' => $deliveryInfo['consignee']['mobile'],
                'ship_email' => $deliveryInfo['consignee']['email'],
                'ship_area' => $deliveryInfo['consignee']['area'],
                'branch_id' => $branch_id,
                'check_time' => time(),
                'org_id' => $orderInfo['org_id'],
        );

        // 经销店铺的单据，delivery_mode冗余到售后申请表
        if ($shopInfo['delivery_mode'] == 'jingxiao') {
            $reshipData['delivery_mode'] = $shopInfo['delivery_mode'];
        }
        
        foreach($dlyItems as $k => $itemVal){
            //取订单发货明细信息
            $orderRow = $reshipObj->db->selectrow("SELECT name, sendnum, return_num FROM sdb_ome_order_items WHERE item_id=".$itemVal['order_item_id']);
            
            //可退货数量
            $diff_num = intval($orderRow['sendnum']) - intval($orderRow['return_num']);
            if($diff_num < $itemVal['number']){
                $error_msg = $operation .',货品: '. $itemVal['bn'] .' 可退货数量错误!';
                return false;
            }
            
            $reshipData['reship_items'][$k] = array(
                    'bn' => $itemVal['bn'],
                    'product_name' => $orderRow['name'],
                    'product_id' => $itemVal['product_id'],
                    'num' => $itemVal['number'],
                    'branch_id' => $branch_id,
                    'op_id' => $op_id,
                    'return_type' => 'refuse',
            );
        }

        //开启事务
        //$orderObj->db->beginTransaction();
        
        //生成退货单
        $result = $reshipObj->save($reshipData);
        if(!$result){
            
            //事务回滚
            //$orderObj->db->rollBack();
            
            $error_msg = $operation .',发货拒收确认失败!';
            return false;
        }
        
        //退货单创建 API
        if($shopInfo['node_id'] && !in_array($shopInfo['node_type'], $c2c_shop_type)){
            foreach(kernel::servicelist('service.reship') as $object=>$instance){
                if(method_exists($instance,'reship')){
                    $instance->reship($reshipData['reship_id']);
                }
            }
        }
        
        
        if ($is_selfWms)
        {
            //自有仓储处理流程
            $wmsDelivery = app::get('wms')->model('delivery');
            $dlyItemsSerialObj = app::get('wms')->model('delivery_items_serial');
            
            $storageLifeLib = kernel::single('material_storagelife');
            $dlyItemsSerialLib = kernel::single('wms_receipt_dlyitemsserial');
            $reSerialLib = kernel::single('ome_receipt_dlyitemsserial');
            
            //WMS发货单
            $wms_delivery = $wmsDelivery->dump(array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']), 'delivery_id, delivery_bn, branch_id');
            
            //发货单货品唯一码
            $serialItems = $dlyItemsSerialObj->getList('bn, serial_number', array('delivery_id'=>$wms_delivery['delivery_id']), 0, -1);
            if($serialItems){
                $history_serial = array();
                foreach($serialItems as $sItem){
                    //params
                    $serialParams = array(
                        'serial_number' => $sItem['serial_number'],
                        'reship_id' => $reshipData['reship_id'],
                        'reship_bn' => $reshipData['reship_bn'],
                        'branch_id' => $branch_id,
                        'bn' => $sItem['bn'],
                    );
                    
                    $seria_error_msg = '';
                    $return_serial = array();
                    $rs = $dlyItemsSerialLib->returnProduct($serialParams, $seria_error_msg, $return_serial);
                    if(!$rs){
                        //事务回滚
                        //$orderObj->db->rollBack();
                        
                        $error_msg = $operation .',唯一码退入失败!';
                        return false;
                    }
                    
                    $history_serial[] = $return_serial;
                }
                
                //插入唯一码退货历史记录
                $serial_error_msg = '';
                $rs = $reSerialLib->returnProduct($history_serial, $serial_error_msg);
                if(!$rs){
                    //事务回滚
                    //$orderObj->db->rollBack();
                    
                    $error_msg = $operation .',插入唯一码退货历史记录失败!'. $serial_error_msg;
                    return false;
                }
            }
            
            //保质期部分
            $dlyItemsStorageLifeObj    = app::get('wms')->model('delivery_items_storage_life');
            $dlyItemsStorageLifeLib    = kernel::single('wms_receipt_dlyitemsstoragelife');

            //storagelife info
            $items = $dlyItemsStorageLifeObj->getList('bm_id,expire_bn,number', array('delivery_id'=>$wms_delivery['delivery_id']), 0, -1);
            if($items){
                $history_storagelife = array();
                foreach($items as $item){
                    //params
                    $storagelifeItem = array(
                        'expire_bn' => $item['expire_bn'],
                        'nums' => $item['number'],
                        'bill_id' => $reshipData['reship_id'],
                        'bill_bn' => $reshipData['reship_bn'],
                        'branch_id' => $branch_id,
                        'bm_id' => $item['bm_id'],
                        'old_branch_id' => $wms_delivery['branch_id'],
                        'bill_type' => '32',
                        'bill_io_type' => '1',
                    );

                    $rs = $dlyItemsStorageLifeLib->returnProduct($storagelifeItem, $err_msg, $return_storagelife);
                    if(!$rs){
                        $error_msg = $operation .',保质期批次退入失败!'. $err_msg;
                        return false;
                    }else{
                        $history_storagelife[] = $return_storagelife;
                    }
                }

                //write history storagelife
                kernel::single('ome_receipt_dlyitemsstoragelife')->returnProduct($history_storagelife, $msg);
            }

            //订单明细更新
            foreach($dlyItems as $itemVal)
            {
                //发货单关联订单sendnum扣减
                $sql = "UPDATE sdb_ome_order_items SET return_num=return_num+ ". $itemVal['number'] ." 
                        WHERE order_id=". $order_id ." AND bn='". $itemVal['bn'] ."' AND obj_id=". $itemVal['order_obj_id'];
                $orderObj->db->exec($sql);
            }
            
            //更新售后单明细仓库退回良品数量
            $orderObj->db->exec('UPDATE sdb_ome_reship_items SET normal_num=num WHERE reship_id='.$reshipData['reship_id']);
            
            //订单发货状态变更
            $this->update_orderStatus($order_id);
            
            //增加拒收退货入库明细(生成出入库明细)
            $rs = $this->do_iostock($reshipData['reship_id'], 1, $iostock_error_msg);
            if(!$rs){
                //事务回滚
                //$orderObj->db->rollBack();
                
                $iostock_error_msg = (is_array($iostock_error_msg) ? implode(',', $iostock_error_msg) : $iostock_error_msg); //防止错误信息是数组格式
                
                $error_msg = $operation .',生成退货入库明细失败('. $iostock_error_msg .')!';
                return false;
            }
            
            //负销售单(生成售后单)
            if ($orderInfo['status'] == 'finish') {
                $rs = kernel::single('sales_aftersale')->generate_aftersale($reshipData['reship_id'], 'refuse');
                if(!$rs){
                    //事务回滚
                    //$orderObj->db->rollBack();
                    
                    $error_msg = $operation .',生成负销售单失败!';
                    return false;
                }
            }

            //订单添加相应的操作日志
            $log_msg = $operation .',发货后退回,订单做退货处理!';
            $operationLogObj->write_log('order_refuse@ome', $order_id, $log_msg);
        }
        else
        {
            //第三方仓储处理流程
            //发送至第三方仓
            $refuseData = array('reship_id'=>$reshipData['reship_id']);
            $reship_data = kernel::single('ome_receipt_reship')->reship_create($refuseData);
            
            kernel::single('console_event_trigger_reship')->create($wms_id, $reship_data, false);
        }
        
        //事务确认
        //$orderObj->db->commit();
        
        //发货拒收确认成功
        return true;
    }

}
