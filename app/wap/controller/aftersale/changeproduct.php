<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店售后 换货单 20170525 by wangjianjun@shopex.cn
 */
class wap_ctl_aftersale_changeproduct extends wap_controller{
    
    function _views_change($curr_view){
        
        $mdl_wap_return = app::get('wap')->model("return");
        
        $page = intval($_POST['page']) ? intval($_POST['page']) : 0;
        $limit = 1; //默认显示1条
        $offset = $limit * $page;
        
        $base_filter = array("return_type"=>"change");
        
        //换货页面以换货门店仓为主
        $is_super = kernel::single('desktop_user')->is_super();
        if(!$is_super){
            $branch_ids = kernel::single('o2o_store_branch')->getO2OBranchByUser(true);
            if(empty($branch_ids)){
                $this->pagedata['link_url'] = $this->delivery_link['order_index'];
                $this->pagedata['error_msg'] = '操作员没有管辖的仓库';
                echo $this->fetch('auth_error.html');
                exit;
            }
            $base_filter['changebranch_id'] = $branch_ids[0];
        }
        
        $wap_router = app::get('wap')->router();
        $sub_menu = array(
                'all' => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'href'=>$wap_router->gen_url(array('ctl'=>'aftersale_changeproduct','act'=>'index'), true)),
                'pending' => array('label'=>app::get('base')->_('待处理'),'filter'=>array_merge($base_filter,array("status"=>1)),'href'=>$wap_router->gen_url(array('ctl'=>'aftersale_changeproduct','act'=>'pending'), true)),
                'refused' => array('label'=>app::get('base')->_('已拒绝'),'filter'=>array_merge($base_filter,array("status"=>2)),'href'=>$wap_router->gen_url(array('ctl'=>'aftersale_changeproduct','act'=>'refused'), true)),
                'already' => array('label'=>app::get('base')->_('已处理'),'filter'=>array_merge($base_filter,array("status"=>3)),'href'=>$wap_router->gen_url(array('ctl'=>'aftersale_changeproduct','act'=>'already'), true)),
        );
        
        foreach($sub_menu as $k=>$v){
            //Ajax加载下一页数据,只处理本页
            if($_POST['flag'] == 'ajax' && $curr_view != $k){
                continue;
            }
            
            $sub_menu[$k]['offset']    = $offset;
            $sub_menu[$k]['limit']     = $limit;
            $sub_menu[$k]['orderby']   = 'return_id desc'; //排序
            
            //搜索条件
            $sel_keywords = htmlspecialchars(trim($_POST['sel_keywords']));
            if($_POST['sel_type'] && $sel_keywords){
                switch ($_POST['sel_type']){
                    case 'order_bn':
                        //先获取order_ids
                        $mdl_ome_orders = app::get('ome')->model("orders");
                        $rs_order = $mdl_ome_orders->getList("order_id",array("order_bn"=>$sel_keywords));
                        if(!empty($rs_order)){
                            $order_ids = array();
                            foreach ($rs_order as $var_o){
                                $order_ids[] = $var_o["order_id"];
                            }
                            $v['filter']['order_id'] = $order_ids;
                        }else{
                            $not_show_datalist = true;
                        }
                        break;
                    case 'ship_mobile':
                        $v['filter']['ship_mobile'] = $sel_keywords;
                        break;
                    case 'aftersale_bn':
                        $mdl_ome_return_product = app::get('ome')->model("return_product");
                        $rs_return_product= $mdl_ome_return_product->dump(array("return_bn"=>$sel_keywords),"return_id");
                        if (!empty($rs_return_product)){
                            $v['filter']['aftersale_id'] = $rs_return_product["return_id"];
                        }else{
                            $not_show_datalist = true;
                        }
                        break;
                    case 'original_reship_bn':
                        $v['filter']['original_reship_bn'] = $sel_keywords;
                        break;
                }
                if ($not_show_datalist){//搜索不满足条件的不显示列表
                    $sub_menu[$k]['not_show_datalist'] = true;
                }else{
                    //搜索并入
                    $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : array();
                }
                $sub_menu[$k]['sel_type'] = $_POST['sel_type'];
                $sub_menu[$k]['sel_keywords'] = $sel_keywords;
            }
            
            $count = $mdl_wap_return->count($v['filter']);
            $sub_menu[$k]['pageSize']  = ceil($count / $limit);
            
            $sub_menu[$k]['curr_view'] = false;
            if($k == $curr_view){//选中状态
                $sub_menu[$k]['curr_view'] = true;
            }
        }
        
        return $sub_menu;
    }
    
    //换货单“全部”的列表显示
    function index(){
        $this->common("all");
    }
    
    //换货单“待处理”的列表显示
    function pending(){
        $this->common("pending");
    }
    
    //换货单“已拒绝”的列表显示
    function refused(){
        $this->common("refused");
    }
    
    //换货单“已处理”的列表显示
    function already(){
        $this->common("already");
    }
    
    //列表公共加载方法
    private function common($menu_type){
        //标签Tabs处理
        $sub_menu = $this->_views_change($menu_type);
        $this->pagedata['sub_menu'] = $sub_menu;
        
        $filter = $sub_menu[$menu_type]["filter"];
        $offset = $sub_menu[$menu_type]['offset'];
        $limit = $sub_menu[$menu_type]['limit'];
        $orderby = $sub_menu[$menu_type]['orderby'];
        
        //ajax点击加载参数
        $this->pagedata['pageSize'] = $sub_menu[$menu_type]['pageSize'];
        $this->pagedata['link_url']    = $sub_menu[$menu_type]['href'];
        
        if ($sub_menu[$menu_type]['not_show_datalist']){//搜索不满足条件的不显示列表
        }else{
            //默认获取列表数据
            $wap_aftersale_lib = kernel::single('wap_aftersale');
            $dataList = $wap_aftersale_lib->getList($filter, $offset, $limit,$orderby);
            if(!empty($dataList)){//有列表数据
                //待处理页面显示 确认 拒单按钮
                if($menu_type == "pending"){
                    foreach ($dataList as &$var_d){
                        $var_d["show_button"] = true;
                    }
                    unset($var_d);
                    //拒绝操作
                    $this->delivery_link['doRefuse'] = app::get('wap')->router()->gen_url(array('ctl'=>'aftersale_changeproduct','act'=>'doRefuse'), true);
                    //确认换货操作
                    $this->delivery_link['doConfirm'] = app::get('wap')->router()->gen_url(array('ctl'=>'aftersale_changeproduct','act'=>'doConfirm'), true);
                }
                $this->pagedata["dataList"] = $dataList;
            }
        }
        
        //搜索选择状态保存
        if ($sub_menu[$menu_type]['sel_type'] && $sub_menu[$menu_type]['sel_keywords']){
            $this->pagedata["sel_type"] = $sub_menu[$menu_type]['sel_type'];
            $this->pagedata["sel_keywords"] = $sub_menu[$menu_type]['sel_keywords'];
        }
        
       
        
        //给公共footer附上link 带上所以的父类构造方法的link
        $this->pagedata['delivery_link'] = $this->delivery_link;
        
        if($offset > 0){//Ajax加载更多
            $this->display('aftersale/change_product_more.html');
        }else{
            $this->display('aftersale/change_product.html');
        }
    }
    
    //换货拒单操作
    function doRefuse(){
        if (!$_POST["return_id"] || !$_POST["refuse_reason_text"]){
            echo json_encode(array('res'=>'error', 'msg'=>'无效操作'));
            exit;
        }
        
        $mdl_ome_operation_log = app::get('ome')->model('operation_log');
        $mdl_wap_return = app::get('wap')->model('return');
        $mdl_ome_reship = app::get('ome')->model('reship');
        
        //获取原始退换货单号
        $rs_wap_return = $mdl_wap_return->dump(array("return_id"=>$_POST["return_id"]),"original_reship_bn");
        //原始退换货单信息
        $rs_ome_reship = $mdl_ome_reship->dump(array("reship_bn"=>$rs_wap_return["original_reship_bn"]),"reship_id,changebranch_id");
        //更新门店退换单为已拒绝状态
        $mdl_wap_return->update(array("status"=>2),array("return_id"=>$_POST["return_id"]));
        //参考oms端收货质检拒绝操作 收货异常
        $refuseMemo = array("refuse"=>$_POST["refuse_reason_text"]);
        $refuse = array(
                'reason' => serialize($refuseMemo),
                'is_check' => 12
        );
        $mdl_ome_reship->update($refuse,array('reship_id'=>$rs_ome_reship["reship_id"]));
        //库存管控 释放换货门店仓库存冻结
        $storeManageLib = kernel::single('ome_store_manage');
        $storeManageLib->loadBranch(array('branch_id'=>$rs_ome_reship['changebranch_id']));
        $params_stock = array(
            "params" => $rs_ome_reship,
            "node_type" => "refuseChangeReship",
        );
        $storeManageLib->processBranchStore($params_stock, $err_msg);
        //获取主键记录原始换货货单日志
        $mdl_ome_operation_log->write_log('reship@ome',$rs_ome_reship["reship_id"],'门店拒绝换货');
        echo json_encode(array('res'=>'succ', 'status'=>'已拒绝', 'msg'=>'已拒绝成功'));
        exit;
    }
    
    //确认换货操作
    function doConfirm(){
        if (!$_POST["return_id"]){
            echo json_encode(array('res'=>'error', 'msg'=>'无效操作'));
            exit;
        }
        //获取当前的换货单数据
        $mdl_wap_return = app::get('wap')->model('return');
        $mdl_wap_return_items = app::get('wap')->model('return_items');
        $rs_wap_return = $mdl_wap_return->dump(array("return_id"=>$_POST["return_id"],"return_type"=>"change"),"*");
        $rs_wap_return_items = $mdl_wap_return_items->getList("*",array("return_id"=>$_POST["return_id"]));
        if (empty($rs_wap_return) || empty($rs_wap_return_items)){
            echo json_encode(array('res'=>'error', 'msg'=>'换货单数据缺失'));
            exit;
        }
        //获取原始换货单数据
        $mdl_ome_reship = app::get('ome')->model('reship');
        $rs_ome_reship = $mdl_ome_reship->dump(array("reship_bn"=>$rs_wap_return["original_reship_bn"],"is_check"=>1),"*");
        if(empty($rs_ome_reship)){
            echo json_encode(array('res'=>'error', 'msg'=>'原始换货单必须是已审核状态'));
            exit;
        }
        //先更新原始换货单状态 做并发处理
        if ($rs_ome_reship['status'] == "succ") {
            echo json_encode(array('res'=>'error', 'msg'=>'请不要重复点击'));
            exit;
        }
        $mdl_ome_reship->update(array("status"=>"succ"),array('reship_id'=>$rs_ome_reship["reship_id"]));
        //必走流程：生成一张相应的门店订单、付款、生成发货单、发货
        $change_order_sdf = kernel::single('wap_aftersale')->create_order_pay_store_delivery($rs_ome_reship);
        if (empty($change_order_sdf)) {
            echo json_encode(array('res'=>'error', 'msg'=>'生成换货订单发货流程失败'));
            exit;
        }
        //换货订单发货流程走后 默认memo
        $memo = ' 生成了1张换货订单【'.$change_order_sdf['order_bn'].'】';
        //获取退货部分的明细数据 更新原始退货单 normal_num良品字段数据
        $rs_wap_return_items_part = array(); 
        foreach($rs_wap_return_items as $var_w_r_i){
            if ($var_w_r_i["return_type"] == "return"){
                $rs_wap_return_items_part[] = $var_w_r_i;
            }
        }
        $mdl_ome_reship_items = app::get('ome')->model('reship_items');
        foreach ($rs_wap_return_items_part as $var_w_r_i_t){
            $mdl_ome_reship_items->update(array("normal_num"=>$var_w_r_i_t["num"]),array("reship_id"=>$rs_ome_reship["reship_id"],"bn"=>$var_w_r_i_t["bn"]));
        }
        //库存管控 
        $storeManageLib = kernel::single('ome_store_manage');
        //退货部分：门店货品实际库存增加
        $storeManageLib->loadBranch(array('branch_id'=>$rs_ome_reship['branch_id']));
        $params_stock = array(
                "params" => $rs_ome_reship,
                "node_type" => "confirmReshipReturn",
        );
        $storeManageLib->processBranchStore($params_stock, $err_msg);
        //释放换货门店仓库存冻结
        $storeManageLib->loadBranch(array('branch_id'=>$rs_ome_reship['changebranch_id']));
        $params_stock = array(
                "params" => $rs_ome_reship,
                "node_type" => "confirmReshipChange",
        );
        $storeManageLib->processBranchStore($params_stock, $err_msg);
        //添加换货单api请求
        $mdl_ome_reship->request_reship_creat_api($rs_ome_reship["shop_id"],$rs_ome_reship["reship_id"]);
        //是否归档
        $is_archive = kernel::single('archive_order')->is_archive($rs_ome_reship['source']);
        //订单明细退货处理
        $orders = $mdl_ome_reship->do_order_items_return($rs_ome_reship,$is_archive);
        //更新上换货订单的关联订单号
        app::get('ome')->model('orders')->update(array('relate_order_bn'=>$orders['order_bn']),array('order_bn'=>$change_order_sdf['order_bn']));
        //判断是否要生成一张支付单
        if ($rs_ome_reship['diff_order_bn']) {//新增补差订单 发货状态改为已发货 并把状态回打给前端。
            kernel::single('ome_reship')->updatediffOrder($rs_ome_reship['diff_order_bn']);
        }
        //生成退款申请单（换货都会生成退款相关的流水）
        $mdl_refund_apply = app::get('ome')->model('refund_apply');
        $refund_apply_bn = $mdl_refund_apply->gen_id();
        //新建退款申请单时的申请退款金额
        $money = (float)$rs_ome_reship['tmoney']+(float)$rs_ome_reship['diff_money']+(float)$rs_ome_reship['bcmoney']-(float)$rs_ome_reship['bmoney'];
        $refund_sdf = $mdl_ome_reship->create_refund_apply_record($refund_apply_bn,$rs_ome_reship,$money,$is_archive);
        //完成退款申请和退款单处理 命名类
        $reshipLib = kernel::single('ome_reship');
        if ($is_archive) {
            $reshipLib = kernel::single('archive_reship');
        }
        $is_generate_aftersale = true; //是否生成售后单
        $totalmoney = (float)$rs_ome_reship['totalmoney']; //实际需要退款的金额
        if ($totalmoney == 0 || $totalmoney < 0) {//换货 如果实际退款金额为零 或者 负数： 需客户再补钱的 都是走个已退款的流水 退款申请完成，并产生退款单
            $reshipLib->createRefund($refund_sdf,$orders);
        }elseif($totalmoney > 0){
            $is_generate_aftersale = false;
            //更新为实际退款金额 需要退款
            $memo .= $refund_sdf['memo'].'总退款金额大于换货订单总额，进行多余费用退款!';
            $mdl_refund_apply->update(array("money"=>$totalmoney,"memo"=>$memo),array('refund_apply_bn'=>$refund_apply_bn));
            //生成退款申请单（换出的订单金额） 后产生退款单 状态更新为已退款
            $refund_apply_bn = $mdl_refund_apply->gen_id();
            $refund_sdf = $mdl_ome_reship->create_refund_apply_record($refund_apply_bn,$rs_ome_reship,$change_order_sdf['total_amount'],$is_archive);
            $reshipLib->createRefund($refund_sdf,$orders);//退款申请完成，并产生退款单
        }
        //更新当前换货单状态及原始换货单状态
        $mdl_wap_return->update(array("status"=>3),array("return_id"=>$rs_wap_return["return_id"]));
        $mdl_ome_reship->update(array('is_check'=>'7','t_end'=>time()),array('reship_id'=>$rs_ome_reship["reship_id"]));
        $memo .= '操作完成。';
        $mdl_operation_log = app::get('ome')->model('operation_log');
        if($rs_ome_reship['return_id']){//存在售后申请单的，更新为完成状态、是否收货和是否质检的字段。
            $mdl_return_product = app::get('ome')->model('return_product');
            $mdl_return_product->update(array('status'=>'4','money'=>$totalmoney,'recieved'=>'true','verify'=>'true'),array('return_id'=>$rs_ome_reship['return_id']));
            $mdl_operation_log->write_log('return@ome',$rs_ome_reship['return_id'],$memo);
            //退货完成回写
            if ($change_order_sdf) {
                $newmemo =' 生成了1张换货订单【'.$change_order_sdf['order_bn'].'】';
            }
            kernel::single('ome_service_aftersale')->update_status($rs_ome_reship['return_id'],'','async',$newmemo);
        }
        $mdl_operation_log->write_log('reship@ome',$rs_ome_reship["reship_id"],$memo);
        //生成售后单
        if($is_generate_aftersale){
            kernel::single('sales_aftersale')->generate_aftersale($rs_ome_reship["reship_id"],$rs_ome_reship['return_type']);
        }
        echo json_encode(array('res'=>'succ', 'status'=>'已确认', 'msg'=>'已确认换货'));
    }
   
}