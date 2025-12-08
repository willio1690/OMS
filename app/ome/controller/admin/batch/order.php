<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_batch_order extends desktop_controller{
    var $name = "订单中心";
    var $workground = "order_center";

    /**
     * 批量审核订单
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function batchConfirm(){

        $fltId = $_GET['fltId'];
        if ($fltId) {
            $filterRow = app::get('desktop')->model('filter')->dump($fltId, 'filter_query');

            parse_str($filterRow['filter_query'], $filterQuery);

            if ($_POST['isSelectedAll'] == '_ALL_' && is_array($filterQuery)) {
                $_POST = array_merge($_POST, $filterQuery);
            }
        } else {

        }

        // 查询订单
        $_POST['process_status'] = array('unconfirmed','confirmed','splitting');
        $_POST['assigned']       = 'assigned';
        $_POST['abnormal']       = 'false';
        $_POST['is_fail']        = 'false';
        $_POST['pause']          = 'false';
        $_POST['is_auto']        = 'false';
        $_POST['status']         = 'active';
        $_POST['archive']        = '0';
        $_POST['ship_status']    = array('0','2');
        $_POST['pay_status']     = array('0','1');

        if(!kernel::single('desktop_user')->is_super()){
            $_POST['op_id'] = kernel::single('desktop_user')->get_id();
        }

        $object = app::get('ome')->model('orders');
        $object->filter_use_like = true;
        $orders = $object->getList('order_id,order_combine_hash,order_combine_idx,order_bool_type,shop_type',$_POST,0,5000);

        $this->pagedata['splitSeting'] = kernel::single('ome_order_split')->get_delivery_seting();
        $this->pagedata['combineSeting'] = app::get('ome')->getConf('ome.combine.select');

        $branchMdl = app::get('ome')->model('branch');

        $branchFilter = array('disabled'=>'false','is_deliv_branch'=>'true','check_permission'=>'false');

        $this->pagedata['branchList']  = $branchMdl->getList('branch_id,name',$branchFilter,0,-1,'weight desc');
        $splitFilter = array(
            'split_type' => app::get('omeauto')->model('order_split')->getBatchConfirmSplitType()
        );
        $this->pagedata['order_split']  = app::get('omeauto')->model('order_split')->getList('sid,name',$splitFilter,0,-1);
        //天猫物流升级服务增加提示信息
        $order_id = $orders ? array_column($orders, 'order_id') : [0];

        $orderExt = app::get('ome')->model('order_extend')->getList('cpup_service,promise_service,extend_field',array('order_id'=>$order_id));
        $cpup = array();
        $promise_service = [];
        $is_duocang = false;
        if ($orderExt) {
            foreach ($orderExt as $val) {
                $val['extend_field'] && $val['extend_field'] = json_decode($val['extend_field'], 1);
                $cpup = array_merge($cpup,explode(',',$val['cpup_service']));
                if ($val['promise_service']) {
                    // 将promise_service按英文逗号分割后合并到数组中
                    $services = explode(',', $val['promise_service']);
                    $promise_service = array_merge($promise_service, $services);
                }
                if ($val['extend_field']['performance_type'] == '3') {
                    $is_duocang = true;
                }
            }
            // 对promise_service数组去重
            $promise_service = array_unique(array_filter($promise_service));
        }
        $cpup_service = 'false';
        $dewuBrandDuocang = false; // 得物品牌直发多仓发货订单

        foreach ($orders as $orderVal) {
            if (kernel::single('ome_order_bool_type')->isCPUP($orderVal['order_bool_type'])) {
                $cpup_service = 'true';
                // break;
            }
            if (strtolower($orderVal['shop_type']) == 'dewu' && kernel::single('ome_order_bool_type')->isDWBrand($orderVal['order_bool_type']) && $is_duocang) {
                $dewuBrandDuocang = true;
            }
        }

        $branchList = $branchMdl->getList('branch_id,name,branch_bn',$branchFilter,0,-1,'weight desc');
        if ($dewuBrandDuocang) {
            // 获取得物多仓的发货仓列表
            $oAddress = app::get('ome')->model('return_address');
            $dewuBrandList = $oAddress->getList('distinct branch_bn', ['shop_type'=>'dewu']);
            $dewuBrandList = array_column($dewuBrandList, 'branch_bn');
            foreach ($branchList as $k => $v) {
                if (!in_array($v['branch_bn'], $dewuBrandList)) {
                    unset($branchList[$k]);
                }
            }
        }
        // todo maxiaochen 如果含有得物品牌直发的多仓发货订单，branchList只展示得物的发货仓列表
        $this->pagedata['branchList']  = $branchList;
        $this->pagedata['cpup'] = $cpup;
        $this->pagedata['cpup_service'] = $cpup_service;
        $this->pagedata['promise_service'] = implode(',', $promise_service);


        $orderGroup = array();
        foreach ($orders as $value) {
            $idx = sprintf('%s||%s', $value['order_combine_hash'], $value['order_combine_idx']);
            $orderGroup[$idx][] = $value['order_id'];
        }

        $order_id = array();
        $countGroup = 0;
        $countOrder = 0;
        $countOrders = count($orders);
        foreach ($orderGroup as $k => $v) {
            if(count($v) > 1) {
                $countGroup += 1;
                $countOrder += count($v);
            }
            $order_id[] = implode('|', $v);
        }
        $_POST['order_id'] = $order_id;
        if ($_POST['isSelectedAll'] == '_ALL_') {
            unset($_POST['isSelectedAll']);
        }

        $pageData =[
            'billName' => "订单组，共计{$countOrders}个订单，其中{$countOrder}个订单可以合并成{$countGroup}个发货单",
            'maxProcessNum' => 1,
            'queueNum' => 5,
            // 'close' => true,
            'custom_html' => $this->fetch('admin/batch/confirm.html'),
            'request_url' => 'index.php?app=ome&ctl=admin_batch_order&act=ajaxDoAuto'
        ];
        parent::selectToPageRequest($object, $pageData);
    }

    /**
     * 获取CorpByBarnch
     * @return mixed 返回结果
     */
    public function getCorpByBarnch(){

        if(!isset($_POST['branch_id']) || empty($_POST['branch_id'])){
            $this->splash('error','','请选择仓库信息');
        }
        $branchId = (int)$_POST['branch_id'];
        $filter = array(
            'disabled'   => 'false',
            'filter_sql' => "( FIND_IN_SET ( $branchId, branch_id ) OR all_branch='true')",
        );
        // todo maxiaochen 得物品牌直发 物流公司限制 https://open.dewu.com/#/api/body?apiId=1020&id=1&title=%E8%AE%A2%E5%8D%95%E6%9C%8D%E5%8A%A1
        $corps = app::get('ome')->model('dly_corp')->getList('branch_id, all_branch, corp_id, name, type, is_cod, weight, channel_id, shop_id, tmpl_type',$filter,0, -1, 'weight DESC');

        if(!$corps){
            $this->splash('error','','仓库没有设置物流公司');
        }
        $this->splash('success','','请求成功','',array('data'=>$corps));
    }
    
    /**
     * 自动审核订单.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function ajaxDoAuto(){
        set_time_limit(0);
        $order_id  = array_filter(explode(';', $_POST['ajaxParams']));
        $corp_id   = $_POST['corp_id'];
        $branch_id = $_POST['branch_id'];
        $is_split  = $_POST['combine_or_split'] == '1' ? true : false;
        $is_combine  = $_POST['combine_or_split'] == '2' ? true : false;
        $splitId = $_POST['split_id'];
        $retArr = array(
            'total'  => count($order_id),
            'succ'   => 0,
            'fail'   => 0,
            'err_msg' => array(),
        );
        if (!$order_id)  { 
            $retArr['fail'] = $retArr['total'];
            $retArr['fail_msg'][] = ['msg'=>'请选择订单'];
            echo json_encode($retArr);exit;
        }
        if (!$corp_id)   { 
            $retArr['fail'] = $retArr['total'];
            $retArr['fail_msg'][] = ['msg'=>'请选择物流公司'];
            echo json_encode($retArr);exit;
        }
        if (!$branch_id) { 
            $retArr['fail'] = $retArr['total'];
            $retArr['fail_msg'][] = ['msg'=>'请选择仓库'];
            echo json_encode($retArr);exit;
        }

        $corpMdl    = app::get('ome')->model('dly_corp');
        $branchMdl  = app::get('ome')->model('branch');
        if($corp_id == 'auto') {
            $corp = ['corp_id'=>'auto'];
        } else {
            $corp = $corpMdl->dump($corp_id, 'corp_id, name, type, is_cod, weight, channel_id');
        }
        if (!$corp) {
            $retArr['fail'] = $retArr['total'];
            $retArr['fail_msg'][] = ['msg'=>'选择物流不存在'];
            echo json_encode($retArr);exit;
        }
        if($branch_id == 'auto') {
            $branch = ['branch_id'=>'auto'];
        } else {
            $branch = $branchMdl->dump(array('branch_id'=>$branch_id,'check_permission'=>'false'),'branch_id,name,area,address,branch_bn,wms_id');
        }
        if (!$branch) {
            $retArr['fail'] = $retArr['total'];
            $retArr['fail_msg'][] = ['msg'=>'选择仓库不存在'];
            echo json_encode($retArr);exit;
        }
        foreach ($order_id as $v) {
            $data = array(
                'is_combine' => $is_combine,
                'is_split' => $is_split,
                'branch' => $branch,
                'corp' => $corp,
                'split_id' => $splitId,
            );
            $tmpArr = kernel::single('ome_batch_order')->ajaxDoAutoOne(explode('|', $v), $data);
            if($tmpArr['ifail']){
                $retArr['fail'] += 1;
                foreach ($tmpArr['err_msg'] as $k => $v) {
                    $retArr['fail_msg'][] = ['msg'=>$v];
                }
            }
        }
        $retArr['succ'] = $retArr['total'] - $retArr['fail'];
        echo json_encode($retArr);exit;
    }
    /**
     * 批量取消订单.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function batchCancel(){
       $order_ids = $_POST['order_id'];
       $orderObj    = app::get('ome')->model('orders');
       $orders = $orderObj->getList('order_id',array('order_id'=>$order_ids,'payed'=>0,'status|notin'=>array('dead')));
       $orderGroup = array();
       foreach ( $orders as $order ) {
           $orderGroup[] = $order['order_id'];
       }
       $this->pagedata['finder_id'] = $_GET['finder_id'];
       $this->pagedata['ordercount'] = count($orderGroup);
       $this->pagedata['orderGroup'] = json_encode($orderGroup);
       $this->pagedata['currentTime'] = time();
       $this->pagedata['ordertotal'] = count($order_ids);

       unset($orderGroup);
       $this->display("admin/batch/cancel.html");
    }

    /**
     * 批量取消订单.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function doBatchCancel(){
        $oOrder = app::get('ome')->model('orders');
        $oShop = $this->app->model('shop');
        $result = array('total' => 0, 'succ' => 0, 'fail' => 0);
        $ajaxParams = explode(';',$_POST['ajaxParams']);
        $rows = $oOrder->getList('shop_id,order_id,source', array('order_id' => $ajaxParams,'payed'=>0,'status|notin'=>array('dead')));
        $orders = array();
        foreach ($rows as $order) {
            $orders[$order['order_id']] = $order;

        }
        unset($rows);
        foreach ( $orders as $order ) {
        $order_id = $order['order_id'];
        $result['total'] ++;

        $memo = "批量取消订单: ".$_POST['memo'];
        $mod = 'sync';

        $c2c_shop_list = ome_shop_type::shop_list();
        $shop_detail = $oShop->dump(array('shop_id'=>$order['shop_id']),'node_id,node_type');
        if(!$shop_detail['node_id'] || in_array($shop_detail['node_type'],$c2c_shop_list) || $order['source'] == 'local'){
            $mod = 'async';
        }

        $sync_rs = $oOrder->cancel($order_id,$memo,true,$mod, false);
        if($sync_rs['rsp'] == 'success')
        {
            $result['succ'] ++;
        }else{
            $result['fail']++;
        }



        }
        echo json_encode($result,true);
    }
   
    /**
     * 批量开发票.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function BatchTax(){
        $this->_request = kernel::single('base_component_request');
        $obj_order = app::get('ome')->model('orders');
        $all_order_ids = array();
        if($_GET['is_tax'] == 'no'){
            $is_tax = 'true';
        }elseif($_GET['is_tax'] == 'yes'){
            $is_tax = 'false';
        }
      
        if(isset($_POST['isSelectedAll']) && $_POST['isSelectedAll']){
           
            if($_GET['type'] == 'active'){
                $filter = array(
                    'disabled' => 'false',
                    'is_fail' => 'false',
                    'status' => 'active',
                    'archive' => 0,
                    'process_status|noequal' => 'is_declare',
                );
            }elseif($_GET['type'] == 'unmyown'){
            
                $filter = array (
                    'assigned' => 'assigned',
                    'abnormal' => 'false',
                    'is_fail' => 'false',
                    'status' => 'active',
                    'process_status' =>array (
                        0 => 'unconfirmed',
                        1 => 'confirmed',
                        2 => 'splitting',
                    ),
                    'archive' => 0,
                );
            }
            $_order_ids = $obj_order->getList('order_id',$filter);
            foreach($_order_ids as $val){
                $all_order_ids[] = $val['order_id'];
            }
        }else{
            $order_ids = $_POST['order_id'];
            foreach($order_ids as $order_id){
                $all_order_ids[] = $order_id;
            }
        }
        if(app::get('invoice')->is_installed()){
            $this->pagedata['invoice'] =  'true'; 
        }else{
            $this->pagedata['invoice'] =  'false';
        }
        $this->pagedata['is_tax'] =  $is_tax;
    
        $this->pagedata['order_num'] = count($all_order_ids);
        $this->pagedata['order_id'] = $all_order_ids;
        $this->pagedata['all_order_ids'] = json_encode($all_order_ids);
        $this->display('admin/order/batch/tax.html');
    }
    
    /**
     * 批量开发票.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function doBatchTax(){
        $order_id = $_POST['order_id'];
        $is_tax = $_POST['is_tax'];

        $orderObj = app::get('ome')->model('orders');
        $oOperation_log = app::get('ome')->model('operation_log');
        if(app::get('invoice')->is_installed()){
            $order_is_tax_part = $is_tax == 'true' ? "开发票" : "不开发票";
            //更新订单is_tax字段 并记下log
            $update_arr = array("is_tax"=>$is_tax);
            $filter_arr = array("order_id"=>$order_id);
            $rs_is_tax = $orderObj->update($update_arr,$filter_arr);
            $order_is_tax_log = "批量设置订单更新为".$order_is_tax_part;
            if(!is_bool($rs_is_tax)){
                $oOperation_log->write_log('order_modify@ome',$order_id,$order_is_tax_log);
                $arr_create_invoice = array(
                    'order_id'=>$order_id,
                    'is_tax' => $is_tax
                );
                kernel::single('invoice_order_front_router', 'b2c')->operateTax($arr_create_invoice);
            }
        }else{
            //未安装app:invoice的 只对ome_orders表做相应字段更新
            $data = array(
                'order_id' => $order_id,
                'is_tax' => $_POST['is_tax'],
            );
            $orderObj->save($data);
            $oOperation_log->write_log('order_modify@ome',$order_id,'录入及变更发票号');
        }
        
        echo json_encode(array('status'=>'success')); die;
    }

    /**
     * 批量操作对话框
     *
     * @return void
     * @author 
     **/
    public function batchDialog($act='',$flt='',$view=0)
    {
        if ($act == 'renew') {
            $this->pagedata['dailogform'] = false;
        }
        
        $orderModel = app::get('ome')->model('orders');
        
        $orderCtl = kernel::single('ome_ctl_admin_order');
        
        // 条件
        switch ($act) {
            case 'dopause':
                $base_filter = array('pause'=>'false','archive'=>'0');
                if ($flt == 'unmyown') {
                    $base_filter['op_id']          = kernel::single('desktop_user')->get_id();
                    $base_filter['assigned']       = 'assigned';
                    $base_filter['abnormal']       = "false";
                    $base_filter['is_fail']        = 'false';
                    $base_filter['status']         = 'active';
                    $base_filter['process_status'] = array('unconfirmed', 'confirmed', 'splitting');

                    if ($view == 989898) {
                        $base_filter['is_cod'] = 'true';
                    } elseif ($view && $view<999000) {
                        $base_filter['order_confirm_filter'] = sprintf("(sdb_ome_orders.auto_status & %s = %s)", $view, $view);
                    }

                } elseif ($flt == 'active') {
                    $sub_menu = $orderCtl->_view_active();
                    foreach ($sub_menu as $key => $value) {
                        if($view == $key){
                            $base_filter = array_merge((array)$value['filter'], $base_filter);
                        }
                    }
                }

                if(kernel::single('desktop_user')->is_super()){
                    unset($base_filter['op_id']);
                }

                $filter = array_merge((array)$_POST , (array)$base_filter);

                break;
            case 'renew':
                // 一单一单处理
                $base_filter = array('pause'=>'true','archive'=>'0');
                if ($flt == 'unmyown') {
                    $sub_menu = $orderCtl->_views_unmyown();
                    foreach ($sub_menu as $key => $value) {
                        if($view == $key){
                            $base_filter = array_merge((array)$value['filter'], $base_filter);
                        }
                    }

                } elseif ($flt == 'active') {
                    $sub_menu = $orderCtl->_view_active();
                    foreach ($sub_menu as $key => $value) {
                        if($view == $key){
                            $base_filter = array_merge((array)$value['filter'], $base_filter);
                        }
                    }
                }

                if(kernel::single('desktop_user')->is_super()){
                    unset($base_filter['op_id']);
                }

                $filter = array_merge((array)$_POST , (array)$base_filter);

                break;
            case 'doabnormal': //批量设置为异常
                $base_filter = array('archive'=>'0');
                if ($flt == 'unmyown') {
                    $base_filter['op_id']          = kernel::single('desktop_user')->get_id();
                    $base_filter['assigned']       = 'assigned';
                    $base_filter['abnormal']       = "false";
                    $base_filter['is_fail']        = 'false';
                    $base_filter['status']         = 'active';
                    $base_filter['process_status'] = array('unconfirmed', 'confirmed', 'splitting');

                    if($view>=999000){
                          //app::get('desktop')->model('filter')->getFilter() 方法不存在 会报错
//                        $filter_parames = array('model'=>'ome_mdl_orders','user_id'=>$base_filter['op_id'],'app'=>'ome','ctl'=>'admin_order','act'=>'confirm');
//                        $filterobj=app::get('desktop')->model('filter');
//                        $confirm_filter = $filterobj->getFilter($filter_parames,$view);
//                        $base_filter = array_merge((array)$base_filter,(array)$confirm_filter);
                    }else{
                        $sub_menu = $orderCtl->_views_unmyown();
                        foreach ($sub_menu as $key => $value) {
                            if($view == $key){
                                $base_filter = array_merge((array)$value['filter'], $base_filter);
                            }
                        }
                    }
                     
                } elseif ($flt == 'active') { 
                    $base_filter['disabled']       = 'false';
                    $base_filter['is_fail']        = 'false';
                    $base_filter['status']         = 'active';
                    if($view>=999000){
//                        $filter_parames = array('model'=>'ome_mdl_orders','user_id'=>$base_filter['op_id'],'app'=>'ome','ctl'=>'admin_order','act'=>'active');
//                        $filterobj=app::get('desktop')->model('filter');
//                        $confirm_filter = $filterobj->getFilter($filter_parames,$view);
//                        $base_filter = array_merge((array)$base_filter,(array)$confirm_filter);
                    }else{
                        $sub_menu = $orderCtl->_view_active();
                        foreach ($sub_menu as $key => $value) {
                            if($view == $key){
                                $base_filter = array_merge((array)$value['filter'], $base_filter);
                            }
                        }
                    }
                }
                $op_id = kernel::single('desktop_user')->get_id();
                $groupObj = $this->app->model("groups");
                $op_group = $groupObj->get_group($op_id);
                if(kernel::single('desktop_user')->is_super()){
                    unset($base_filter['op_id']);
                    unset($op_group['group_id']);
                    unset($op_id);
                }
                $filter = array_merge((array)$_POST,(array)$base_filter);
                $this->pagedata['dialogform'] = true;
                $abnormalTypeModel = $this->app->model('abnormal_type');
                $abnormal_type = $abnormalTypeModel->getList("*");
                $this->pagedata['abnormal_type'] = $abnormal_type;
                break;
            case 'dispose_abnormal':
                $base_filter = array('archive'=>'0');
                $base_filter['op_id'] = kernel::single('desktop_user')->get_id();
                $base_filter['abnormal'] = 'true';
                $base_filter['is_fail'] = 'false';
                $base_filter['status'] = 'active';
                $base_filter['process_status'] = array('unconfirmed', 'confirmed', 'splitting');
                
                $op_id = kernel::single('desktop_user')->get_id();
                
                $groupObj = $this->app->model("groups");
                $op_group = $groupObj->get_group($op_id);
                if(kernel::single('desktop_user')->is_super()){
                    unset($base_filter['op_id']);
                    unset($op_group['group_id']);
                    unset($op_id);
                }
                
                $filter = array_merge((array)$_POST,(array)$base_filter);
                
                $this->pagedata['dialogform'] = true;
                
                //abnormal_type
                $abnormalTypeModel = $this->app->model('abnormal_type');
                $abnormal_type = $abnormalTypeModel->getList("*");
                $this->pagedata['abnormal_type'] = $abnormal_type;
                
                //取第一个订单信息
                $orderInfo = $orderModel->dump($filter, '*');
                $this->pagedata['orderInfo'] = $orderInfo;
                
                //取第一个订单异常信息
                $sql = "SELECT * FROM sdb_ome_abnormal WHERE order_id='". $orderInfo['order_id']."'";
                $abnoramlInfo = $orderModel->db->selectrow($sql);
                $this->pagedata['abnoramlInfo'] = $abnoramlInfo;
                
            break;
            default:
                echo "系统无法识别你的批量操作";exit;
                break;
        }

        //count
        $count = $orderModel->count($filter);

        $this->pagedata['total'] = $count;
        $this->pagedata['filter'] = http_build_query($filter);
        $this->pagedata['act']  = $act;
        $this->pagedata['flt']  = $flt;
        $this->pagedata['view'] = $view;

        $this->display('admin/order/batch/dialog.html');
    }


    /**
     * 针对订单
     * 所有的批量操作都应该放这里
     *
     * @return void
     * @author 
     **/
    public function batchopt($act,$flt,$view)
    {
       
        $result = true; $msg = '操作成功';
        // 订单
        $orderModel = app::get('ome')->model('orders');
        $filter = array();
        switch ($act) {
            //批量暂停
            case 'dopause':
                $page_no = intval($_GET['page_no']) ? intval($_GET['page_no']) : 1;
                $page_size = 10;
                $offset = 0;
                $total = intval($_GET['total']);
                parse_str($_POST['filter'],$filter);

                $orders = $orderModel->getList('order_id',$filter, $offset, $page_size);

                $succ_num = $fail_num = 0;
                if ($orders) {
                    foreach ((array) $orders as $order) {
                        $rs = $orderModel->pauseOrder($order['order_id'], false, '');

                        if ($rs) $succ_num++; else $fail_num++;
                    }
                }

                $result = array('status'=>'running','data'=>array('succ_num'=>$succ_num,'fail_num'=>$fail_num));

                if ( ($page_size * $page_no) >= $total) {
                    $result['status'] = 'complete';
                    $result['data']['rate'] = '100';
                } else {
                    $result['data']['rate'] =  $page_no * $page_size / $total * 100;
                }

                echo json_encode($result);exit;

                break;
            //批量恢复
            case 'renew':
                $page_no = intval($_GET['page_no']) ? intval($_GET['page_no']) : 1;
                $page_size = 10;
                $offset = 0;
                $total = intval($_GET['total']);
                parse_str($_POST['filter'],$filter);

                $orders = $orderModel->getList('order_id',$filter,$offset,$page_size);

                $succ_num = $fail_num = 0;
                if ($orders) {
                    foreach ((array) $orders as $order) {
                        $rs = $orderModel->renewOrder($order['order_id']);
                        if ($rs) $succ_num++; else $fail_num++;
                    } 
                }

                $result = array('status'=>'running','data'=>array('succ_num'=>$succ_num,'fail_num'=>$fail_num));

                if ( ($page_size * $page_no) >= $total) {
                    $result['status'] = 'complete';
                    $result['data']['rate'] = '100';
                } else {
                    $result['data']['rate'] =  $page_no * $page_size / $total * 100;
                }

                echo json_encode($result);exit;

                break;
            //批量设置为异常
            case 'doabnormal':
                $page_no = intval($_GET['page_no']) ? intval($_GET['page_no']) : 1;
                $page_size = 10;
                if($flt == 'unmyown'){
                    $offset = 0;
                }elseif ($flt == 'active'){
                    $offset = ($page_no-1)*$page_size;
                }
                $total = intval($_GET['total']);
                parse_str($_POST['filter'],$filter);
                $abnormal_data = $_POST['abnormal'];
                $orders = $orderModel->getList('order_id',$filter,$offset,$page_size);
                $succ_num = $fail_num = 0;
                $abnormalModel = $this->app->model('abnormal');
                if ($orders) {
                    foreach ((array) $orders as $order) {
                        $abnormal_data['order_id'] = $order['order_id'];
                        $abnormal = $abnormalModel->dump(array("order_id"=>$order['order_id']),"abnormal_id");
                        if(!empty($abnormal)) $abnormal_data['abnormal_id'] = $abnormal['abnormal_id'];
                        $orderModel->set_abnormal($abnormal_data);
                        unset($abnormal_data['order_id']);
                        unset($abnormal_data['abnormal_id']);
                        //取消发货单
                        define('FRST_TRIGGER_OBJECT_TYPE','发货单：订单异常取消发货单');
                        define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：do_abnormal');
                        $orderModel->cancel_delivery($order['order_id']);
                        $succ_num++;
                    } 
                }

                $result = array('status'=>'running','data'=>array('succ_num'=>$succ_num,'fail_num'=>$fail_num));

                if ( ($page_size * $page_no) >= $total) {
                    $result['status'] = 'complete';
                    $result['data']['rate'] = '100';
                } else {
                    $result['data']['rate'] =  $page_no * $page_size / $total * 100;
                }

                echo json_encode($result);exit;

            break;
            case 'dispose_abnormal':
                //批量处理异常
                $abnormalModel = $this->app->model('abnormal');
                
                $page_no = intval($_GET['page_no']) ? intval($_GET['page_no']) : 1;
                $page_size = 10;
                //$offset = ($page_no-1)*$page_size;
                $offset = 0; //每次处理后,都要从0开始
                
                $total = intval($_GET['total']);
                
                parse_str($_POST['filter'], $filter);
                
                $abnormal_data = $_POST['abnormal'];
                $orders = $orderModel->getList('order_id',$filter,$offset,$page_size);
                $succ_num = $fail_num = 0;
                
                //开始处理
                if ($orders){
                    foreach ((array) $orders as $order)
                    {
                        $abnormal_data['order_id'] = $order['order_id'];
                        $abnormal = $abnormalModel->dump(array("order_id"=>$order['order_id']), "abnormal_id");
                        
                        if(!empty($abnormal)) $abnormal_data['abnormal_id'] = $abnormal['abnormal_id'];
                        
                        $orderModel->set_abnormal($abnormal_data);
                        
                        unset($abnormal_data['order_id']);
                        unset($abnormal_data['abnormal_id']);
                        
                        //取消发货单
                        //define('FRST_TRIGGER_OBJECT_TYPE','发货单：订单异常取消发货单');
                        //define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：do_abnormal');
                        //$orderModel->cancel_delivery($order['order_id']);
                        
                        $succ_num++;
                    }
                }
                
                //result
                $result = array('status'=>'running', 'data'=>array('succ_num'=>$succ_num, 'fail_num'=>$fail_num));
                
                if(($page_size * $page_no) >= $total){
                    $result['status'] = 'complete';
                    $result['data']['rate'] = '100';
                } else {
                    $result['data']['rate'] =  $page_no * $page_size / $total * 100;
                }
                
                echo json_encode($result);
                exit;
            default:
                # code...
                break;
        }

    }
}
?>
