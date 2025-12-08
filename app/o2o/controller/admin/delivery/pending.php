<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/6/6 10:17:55
 * @describe: 控制器
 * ============================
 */
class o2o_ctl_admin_delivery_pending extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $base_filter = ['status'=>['0']];
        $is_super    = kernel::single('desktop_user')->is_super();
        if(!$is_super)
        {
            $branchObj     = kernel::single('o2o_store_branch');
            $branch_ids    = $branchObj->getO2OBranchByUser(true);
            if(empty($branch_ids))
            {
                header("Content-type: text/html; charset=utf-8");
                echo '操作员没有管辖的仓库';
                exit;
            }
            $base_filter['branch_id']    = $branch_ids;
        }
        $actions = array();
        $params = array(
                'title'=>'待处理单据',
                'base_filter'=>$base_filter,
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy'=>'delivery_id desc',
        );
        
        $this->finder('o2o_mdl_delivery', $params);
    }

    /**
     * edit
     * @param mixed $dly_id ID
     * @return mixed 返回值
     */
    public function edit($dly_id) {
        $dlyObj = app::get('wap')->model('delivery');
        $delivery = $dlyObj->db_dump($dly_id);// 加密
        $omedly = app::get('ome')->model('delivery')->db_dump(['delivery_bn'=>$delivery['outer_delivery_bn']], 'delivery_id');
        $dlyorderObj = app::get('ome')->model('delivery_order');
        $orderObj = app::get('ome')->model('orders');
        $dly_order = $dlyorderObj->getlist('*',array('delivery_id'=>$omedly['delivery_id']),0,-1);
        $order_ids = array();
        foreach ($dly_order as $id){
            $order_ids[] = $id['order_id'];
        }
        $orders = $orderObj->getList('order_id,order_bn,shipping,shop_type,paytime',array('order_id|in'=>$order_ids));
        $items = app::get('wap')->model('delivery_items')->getList('*', ['delivery_id'=>$dly_id]);
        if ($items)
            foreach ($items as $key => $item){
                $items[$key]['barcode'] = kernel::single('material_basic_material')->getBasicMaterialCode($item['product_id']);

            }
        $delivery['is_encrypt'] = kernel::single('ome_security_router',$delivery['shop_type'])->show_encrypt($delivery, 'delivery');
        if(!in_array($delivery['status'], array('0'))) {
            $msg = $delivery['status'] == 'back' ? '拒绝成功, ' :
                ($delivery['status'] == 'succ' ? '发货成功, ' : '');
            header("Content-type: text/html; charset=utf-8");
            exit($msg . $delivery['delivery_bn'] . '发货单不可操作');
        }
        $dlyPrintUrl = 'index.php?app=o2o&ctl=admin_delivery_print&act=toPrintMergeNew&delivery_id='.$dly_id.'&finder_id='.$_GET['finder_id'];
        $expressPrintUrl = 'index.php?app=o2o&ctl=admin_delivery_print&act=toPrintExpre&delivery_id='.$dly_id.'&finder_id='.$_GET['finder_id'];
        $split_barcode_setting = [];//kernel::single('ome_func')->get_split_barcode_setting();
        $this->pagedata['split_barcode_setting'] = json_encode($split_barcode_setting);
        $this->pagedata['orders'] = $orders;
        $this->pagedata['items'] = $items;
        $this->pagedata['dly']   = $delivery;
        $this->pagedata['print_dly_url'] = $dlyPrintUrl;
        $this->pagedata['print_express_url'] = $expressPrintUrl;
        $this->singlepage('admin/delivery/check.html');
    }

    /**
     * showSensitiveData
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function showSensitiveData($delivery_id) {
        $deliveryMdl = app::get('wap')->model('delivery');

        $delivery = $deliveryMdl->db_dump($delivery_id,'*');

        $order_bns = kernel::single('ome_extint_order')->getOrderBns($delivery['outer_delivery_bn']);
        $delivery['order_bn'] = current($order_bns);
        // 处理加密
        $delivery['encrypt_body'] = kernel::single('ome_security_router',$delivery['shop_type'])->get_encrypt_body($delivery, 'delivery');

        $this->splash('success',null,null,'redirect',$delivery);
    }

    /**
     * 立即接单
     * 
     * @return json
     */
    function doConfirm()
    {
        $delivery_id    = intval($_POST['delivery_id']);
        $redirect_url   = ($_POST['backUrl'] ? $_POST['backUrl'] : $this->delivery_link['order_confirm']);
        if(empty($delivery_id))
        {
            echo json_encode(array('res'=>'error', 'msg'=>'无效操作'));
            exit;
        }
        
        $wapDeliveryObj    = app::get('wap')->model('delivery');
        $deliveryInfo      = $wapDeliveryObj->dump(array('delivery_id'=>$delivery_id), '*');
        if(empty($deliveryInfo))
        {
            echo json_encode(array('res'=>'error', 'msg'=>'没有相关发货单'));
            exit;
        }
        elseif($deliveryInfo['status'] > 0 || $deliveryInfo['confirm'] != 3)
        {
            echo json_encode(array('res'=>'error', 'msg'=>'该发货单无法继续操作'));
            exit;
        }

        $dlyProcessLib = kernel::single('wap_delivery_process');
        
        //组织参数
        $params = array_merge(array('delivery_id'=>$delivery_id), $deliveryInfo);
        
        if($dlyProcessLib->accept($params)){
            app::get('ome')->model('operation_log')->write_log('delivery@o2o',$deliveryInfo['delivery_id'],"确认接单");
            
            //task任务更新统计数据
            $wapDeliveryLib    = kernel::single('wap_delivery');
            $wapDeliveryLib->taskmgr_statistic('confirm');
            
            echo json_encode(array('res'=>'succ', 'status'=>'已确认', 'msg'=>'订单已接收', 'delivery_bn'=>$deliveryInfo['delivery_bn']));
            exit;
        }else{
            echo json_encode(array('res'=>'error', 'msg'=>'门店确认失败'));
            exit;
        }
    }

    /**
     * refuse
     * @return mixed 返回值
     */
    public function refuse() {
        $reasonObj    = app::get('o2o')->model('refuse_reason');
        $refuse_reasons  = $reasonObj->getList('*', array('disabled'=>'false'), 0, 100);
        $this->pagedata['refuse_reasons']    = $refuse_reasons;
        $this->pagedata['delivery_id']    = (int)$_POST['delivery_id'];
        
        $this->display('admin/delivery/refuse.html');
    }

    /**
     * doRefuse
     * @return mixed 返回值
     */
    public function doRefuse() {
        $delivery_id    = intval($_POST['delivery_id']);
        if(empty($delivery_id))
        {
            $this->splash('error', $this->url, '无效操作');
        }
        
        $wapDeliveryObj    = app::get('wap')->model('delivery');
        $deliveryInfo      = $wapDeliveryObj->dump(array('delivery_id'=>$delivery_id), '*');
        if(empty($deliveryInfo))
        {
            $this->splash('error', $this->url, '没有相关发货单');
        }
        if($deliveryInfo['status'] > 0 || $deliveryInfo['confirm'] != 3)
        {
           $this->splash('error', $this->url, '该发货单无法继续操作');
        }

        $dlyProcessLib = kernel::single('wap_delivery_process');
        
        //组织参数
        $params = array_merge(array('delivery_id'=>$delivery_id), $deliveryInfo);
        
        $refuse_reason_id    = intval($_POST['refuse_reason_id']);
        if(empty($refuse_reason_id))
        {
            $this->splash('error', $this->url, '请选择拒单理由');
        }
        
        //拒绝原因
        $params['reason_id']   = $refuse_reason_id;
        
        if($dlyProcessLib->refuse($params)){
            $reasonObj    = app::get('o2o')->model('refuse_reason');
            $reasonInfo         = $reasonObj->dump(array('reason_id'=>$params['reason_id']), '*');
            app::get('ome')->model('operation_log')->write_log('delivery@o2o',$deliveryInfo['delivery_id'],"拒绝接单,".$reasonInfo['reason_name']);
            
            //task任务更新统计数据
            $wapDeliveryLib    = kernel::single('wap_delivery');
            $wapDeliveryLib->taskmgr_statistic('refuse');
            $this->splash('success', $this->url, '已拒绝成功');
        }else{
            $this->splash('error', $this->url, '门店拒绝失败');
        }
    }

    function doConsign()
    {
        $delivery_id    = intval($_POST['delivery_id']);
        if(empty($delivery_id))
        {
            echo json_encode(array('res'=>'error', 'msg'=>'无效操作'));
            exit;
        }
        $filter    = array('delivery_id'=>$delivery_id);
        
        #管理员对应仓库
        $is_super = kernel::single('desktop_user')->is_super();
        if(!$is_super){
            $branchObj     = kernel::single('o2o_store_branch');
            $branch_ids    = $branchObj->getO2OBranchByUser(true);
            if(empty($branch_ids))
            {
                $error_msg    = '操作员没有管辖的仓库';
                
                echo json_encode(array('res'=>'error', 'msg'=>$error_msg));
                exit;
            }
            $filter['branch_id'] = $branch_ids;
        }
        
        $wapDeliveryObj    = app::get('wap')->model('delivery');
        $deliveryInfo      = $wapDeliveryObj->dump($filter, '*');
        $deliveryInfo['status']    = intval($deliveryInfo['status']);
        
        if(empty($deliveryInfo))
        {
            $error_msg    = '没有此发货单或没仓库权限,请检查';
        }
        elseif($deliveryInfo['confirm'] != 1)
        {
            $error_msg = "该发货单还未确认,不能进行操作";
            
            if($deliveryInfo['confirm'] == 2){
                $error_msg = "该发货单已被拒绝,不能进行操作";
            }
        }
        elseif($deliveryInfo['status'] !== 0)
        {
            $error_msg    = '该发货单状态不正确,不能进行操作';
            
            if($deliveryInfo['status'] == 3){
                $error_msg    = '该发货单已发货,不能进行操作';
            }
        }
        if($deliveryInfo['process_status'] & 1 != 1) {
            $error_msg = '该发货单未打印完成';
        }

        //错误提示
        if($error_msg)
        {
            echo json_encode(array('res'=>'error', 'msg'=>$error_msg));
            exit;
        }
        $bill = app::get('wap')->model('delivery_bill')->db_dump(['delivery_id'=>$deliveryInfo['delivery_id'], 'type'=>'1'], 'logi_no');
        $deliveryInfo['logi_no'] = $bill['logi_no'];
        if(empty($deliveryInfo['logi_no'])) {
            echo json_encode(array('res'=>'error', 'msg'=>'缺少运单号'));exit();
        }
        $deliveryInfo['order_number']  = 1;
        
        //执行发货
        $dlyProcessLib  = kernel::single('wap_delivery_process');
        $res            = $dlyProcessLib->consign($deliveryInfo);
        if($res){
            app::get('ome')->model('operation_log')->write_log('delivery@o2o',$deliveryInfo['delivery_id'],"确认发货");
            //task任务更新统计数据
            $wapDeliveryLib    = kernel::single('wap_delivery');
            $wapDeliveryLib->taskmgr_statistic('consign');
            
            echo json_encode(array('res'=>'succ', 'msg'=>'发货成功'));
            exit;
        }else {
            echo json_encode(array('res'=>'error', 'msg'=>'发货失败'));
            exit;
        }
    }
}