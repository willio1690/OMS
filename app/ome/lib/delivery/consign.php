<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_delivery_consign{

    function __construct($app)
    {
        $this->app = $app;
    }

    function run(&$cursor_id,$params){
        $dly_id = $params['sdfdata'];
        $model = app::get($params['app'])->model($params['mdl']);
        $dly = $model->dump($dly_id,'process');
        if ($dly && $dly['process']=='false'){
            $model->consignDelivery($dly_id);
        }
        return false;
    }

    /**
     * @description 是否允许发货
     * @access public
     * @param void
     * @return void
     * @author chening<chenping@shopex.cn>
     */
    public function deliAllow($logi_no,$branches,&$msg,&$patch) 
    {
        $deliBillModel = $this->app->model('delivery_bill');
        $deliModel = $this->app->model('delivery');
        $delivery = $deliModel->db->select("SELECT delivery_id,branch_id,is_bind,status,verify,process,ship_area,logi_id,logi_number,delivery_logi_number,delivery_bn,type FROM sdb_ome_delivery WHERE logi_no='".$logi_no."'");
        $patch = false;
        if (!$delivery) {
            $delivery = $this->is_patch_logi_no($logi_no,$deliBill);
            
            $patch = true;
        }
        if (!$delivery) { 
            $msg = '快递单号【'.$logi_no.'】不存在！';
            return false;
        }
        $delivery = current($delivery);
        $logi_number = $delivery['logi_number'];
        $delivery_logi_number = $delivery['delivery_logi_number'];
        
        //-- 验证快递单是否已经发货
        if ($patch === true && $deliBill['status'] == 1) {
            $msg = '快递单号【'.$logi_no.'】已经发货！';
            return false;
        }
        
        if ($patch === false) {
            //$deliBillCount1 = $deliBillModel->count(array('delivery_id'=>$delivery['delivery_id'],'status'=>'1'));
            //$deliBillCount0 = $deliBillModel->count(array('delivery_id'=>$delivery['delivery_id'],'status'=>'0'));
            if ($delivery['status'] == 'succ') {
                $msg = '快递单号【'.$logi_no.'】已经发货！';
                return false;
            }
            #多包情况
            elseif($logi_number > 1){
                $deliBillCount = $deliBillModel->count(array('delivery_id'=>$delivery['delivery_id'],'status'=>'1'));
                if($delivery_logi_number > $deliBillCount){
                    $msg = '主单【'.$logi_no.'】不能再重复发货！';
                    return false;
                }  
            }
        }
       


        if (!in_array($delivery['branch_id'],$branches) && $branches[0] != '_ALL_') {
            $msg = '你无权对快递单【'.$logi_no.'】进行发货！';
            return false;
        }

        if (!$deliModel->existOrderStatus($delivery['delivery_id'], $delivery['is_bind'])){
            $msg = '快递单号【'.$logi_no.'】对应发货单不处于可发货状态！';
            return false;
        }

        if (!$deliModel->existOrderPause($delivery['delivery_id'], $delivery['is_bind'])){
            $msg = '快递单号【'.$logi_no.'】对应发货单订单存在异常！';
            return false;
        }

        if ($delivery['status'] == 'back'){
            $msg = '快递单号【'.$logi_no.'】对应发货单已打回！';
            return false;
        }
        if ($delivery['verify'] == 'false'){
            $msg = '快递单号【'.$logi_no.'】对应发货单未校验！';
            return false;
        }

        if ($delivery['process'] == 'true'){
            $msg = '快递单号【'.$logi_no.'】对应发货单已发货！';
            return false;
        }
        
        $deliItemModel = $this->app->model('delivery_items');
        $delivery_items = $deliItemModel->getList('*',array('delivery_id'=>$delivery['delivery_id']));
        foreach ($delivery_items as $item) {
            if ($item['verify'] == 'false'){
                $msg = '快递单号【'.$logi_no.'】对应发货单未校验！';
                return false;
            }
            
            if ($delivery['type'] == 'normal') {
                $re = $deliModel->existStockIsPlus($item['product_id'],$item['number'],$item['item_id'],$delivery['branch_id'],$err,$item['bn']);
                if (!$re){
                   $msg = $err;
                   return false;
                }
            }

             if(app::get('taoguaninventory')->is_installed()){
                 $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($item['product_id'],$delivery['branch_id']);

                if(!$check_inventory){
                   $msg = '正在盘点,请将该货物放回指定区域';
                    return false;
                }
            }
        }


        $orderInfo = $deliModel->getOrderByDeliveryId($delivery['delivery_id']);
        if($orderInfo['pay_status'] == '5'){
            $msg = '对应订单 '.$orderInfo['order_bn'].' 已退款';
            return false;
        }
        
        return $delivery;
    }

    /**
     * @description 判断是否是补打发货单
     * @access public
     * @param void
     * @return void
     */
    public function is_patch_logi_no($logi_no,&$deliBill='') 
    {
        $deliBill = $this->app->model('delivery_bill')->select()->columns('*')->where('logi_no=?',$logi_no)->instance()->fetch_row();
        if (!$deliBill) {
            return false;
        }

        $delivery = $this->app->model('delivery')->getList('delivery_id,branch_id,is_bind,status,verify,process,ship_area,logi_id,logi_number,delivery_logi_number,delivery_bn,type',array('delivery_id'=>$deliBill['delivery_id']),0,1);
        return $delivery;
    }

    /**
     * 发货放队列
     *
     * @return void
     * @author 
     **/
    public function push_queue($params)
    {   
        // 多队列
        if (defined('SAAS_CONSIGN_MQ') && SAAS_CONSIGN_MQ == 'true') {
            $data = array();
            $data['spider_data']['url'] = kernel::openapi_url('openapi.autotask','service');

            $push_params = array(
                'log_text'  => $params['log_text'],
                'log_id'    => $params['log_id'],
                'task_type' => 'autodly',
            );
            $push_params['sign'] =  taskmgr_rpc_sign::gen_sign($push_params);

            $postAttr = array();
            foreach ($push_params as $key => $val) {
                $postAttr[] = $key . '=' . urlencode($val);
            }

            $data['spider_data']['params']    = empty($postAttr) ? '' : join('&', $postAttr);
            $data['relation']['to_node_id']   = base_shopnode::node_id('ome');
            $data['relation']['from_node_id'] = '0';
            $data['relation']['tid']          = $params['log_id'];
            $data['relation']['to_url']       = $data['spider_data']['url'];
            $data['relation']['time']         = time();

            $routerKey = 'tg.order.consign.'.$data['nodeId'];

            $message = json_encode($data);
            $mq = kernel::single('base_queue_mq');
            $mq->connect($GLOBALS['_MQ_CONSIGN_CONFIG'], 'TG_CONSIGN_EXCHANGE', 'TG_CONSIGN_QUEUE');
            $mq->publish($message, $routerKey);
            $mq->disconnect();
        } else {
        // 单队列
            $push_params = array(
                'data' => array(
                    'log_text'  => $params['log_text'],
                    'log_id'    => $params['log_id'],
                    'task_type' => 'autodly',
                ),
                'url' => kernel::openapi_url('openapi.autotask','service')
            );
            kernel::single('taskmgr_interface_connecter')->push($push_params);
        }

        return true;
    }
    /**
     * 保存批次号批量发货至记录队列表中
     * @auth Yaowenzhu
     * @datw 2018-03-27
     */
    public function saveBatchConsign($logi_no){

        // 数据判断
        if(empty($logi_no)){
            return false;
        }
        // 实例化
        $blObj = app::get('ome')->model('batch_log');

        $op = kernel::single('ome_func')->getDesktopUser();
        $bldata = array(
            'op_id'        => $op['op_id'],
            'op_name'      => $op['op_name'],
            'createtime'   => time(),
            'batch_number' => count($logi_no),
            'log_type'     => 'consign',
            'log_text'     => serialize($logi_no),
            'status'       => 0,
            'fail_number'  => 0,
            'succ_number'  => 0,
        );
        // 保存数据
        $blObj->save($bldata);
        // 发货任务加队列
        kernel::single('ome_delivery_consign')->push_queue($bldata);

        $logFormat = array(
            'log_id'       => $bldata['log_id'],
            'op_name'      => $bldata['op_name'],
            'createtime'   => date('Y-m-d H:i:s',$bldata['createtime']),
            'fail_number'  => $bldata['fail_number'],
            'batch_number' => $bldata['batch_number'],
            'status'       => '等待中',
        );

        return $logFormat;
    }
}