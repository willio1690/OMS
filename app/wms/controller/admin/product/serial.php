<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_ctl_admin_product_serial extends desktop_controller{

    //唯一码列表展示
    function index(){
        $action = array();

        //to be continue, add new that ui need design and batch operation
        /*
        $action = array(
                        array(
                                'label' => '新增',
                                'href' => 'index.php?app=wms&ctl=admin_product_serial&act=add',
                                'target'=>"dialog::{width:500,height:191,title:'新增'}",
                        ),
                );
        */

        $this->finder('wms_mdl_product_serial',array(
                'title'=>'唯一码列表',
                'actions' => $action,
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag' => false,
                'use_buildin_export' => true,
                'use_buildin_import' => false,
                'use_buildin_recycle' => false,
                'use_buildin_filter'=>true,
                'use_view_tab'=>true,
                'orderBy' =>'serial_id DESC'
        ));
    }
    
    function _views() {
        //load module
        $prdSerialObj = app::get('wms')->model('product_serial');

        $sub_menu[0] = array('label' => app::get('base')->_('全部'), 'filter' => array(), 'optional' => false);
        $sub_menu[1] = array('label' => app::get('base')->_('已入库'), 'filter' => array('status' => '0'), 'optional' => false);
        $sub_menu[2] = array('label' => app::get('base')->_('已出库'),'filter' => array('status' => '1'),'optional' => false);
        $sub_menu[3] = array('label' => app::get('base')->_('已作废'), 'filter' => array('status' => '2'), 'optional' => false);
        $sub_menu[4] = array('label' => app::get('base')->_('已退入'), 'filter' => array('status' => '3'), 'optional' => false);
        $sub_menu[5] = array('label' => app::get('base')->_('已预占'), 'filter' => array('status' => '4'), 'optional' => false);

        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $prdSerialObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=wms&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&view=' . $k;
        }

        return $sub_menu;
    }

    //唯一码导入展示
    function import(){
        echo $this->page('admin/product/serial/import.html');
    }
    
    //导出模板
    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $oObj = kernel::single('wms_product_serial');
        $title = $oObj->exportTemplate();
        echo '"'.implode('","',$title).'"';
    }
    
    //执行导入操作
    function doImport(){
        //开启事务
        kernel::database()->beginTransaction();
        $result = kernel::single('wms_product_serial')->process($_POST);
        header("content-type:text/html; charset=utf-8");
        if($result['rsp'] == 'succ'){
            kernel::database()->commit();
            echo json_encode(array('result' => 'succ','msg'=>$result["message"]));
        }else{
            kernel::database()->rollBack();
            echo json_encode(array('result' => 'fail', 'msg' =>(array)$result['res']));
        }
    }

    //单个作废
    function cancel($serial_id){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        $res = kernel::single('wms_product_serial')->cancelSerial($serial_id);
        if($res){
            $this->end(true,'作废成功');
        }else{
            $this->end(true,'作废失败');
        }
    }

    //单个上架
    function renew($serial_id){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        $res = kernel::single('wms_product_serial')->renewSerial($serial_id,$message);
        if($res){
            $this->end(true,'上架成功');
        }else{
            $mes = "上架失败 ";
            if($message){
                $mes .= $message;
            }
            $this->end(false,$mes);
        }
    }
    
    //扫码入库展示页面
    function scavenging(){
        $mdl_ome_branch = app::get('ome')->model('branch');
        $mdl_channel_adapter= app::get('channel')->model('adapter');
        //获取自有仓列表
        $rs_adapter = $mdl_channel_adapter->dump(array("adapter"=>"selfwms"));
        $this->pagedata["branch_list"] = $mdl_ome_branch->getList("branch_id,name",array("wms_id"=>$rs_adapter["channel_id"]));
        echo $this->page('admin/product/serial/scavenging.html');
    }
    
    //执行扫码入库
    function doScavenging(){
        $serial_numbers = explode(',',$_POST["batch_value"]);
        $serial_numbers = array_filter($serial_numbers);
        $lib_serial = kernel::single('wms_product_serial');
        $can_import = true;
        foreach($serial_numbers as $var_serial_number){
            $result_arr = $lib_serial->check_serial_import($var_serial_number,$_POST["branch_id"],$_POST["basic_material_bn"]);
            if(!$result_arr["result"]){
                $can_import = false;
                break;
            }
        }
        if(!$can_import){
            echo json_encode(array('result' => 'fail','msg'=>$result_arr["message"]));
        }else{ //执行导入
            $mdl_ome_ps = app::get('wms')->model('product_serial');
            $operationLogObj = app::get('ome')->model('operation_log');
            $product_id = $result_arr["product_id"];
            foreach($serial_numbers as $serial_number){
                $insert_arr = array(
                    "create_time" => time(),
                    "branch_id" => $_POST["branch_id"],
                    "product_id" => $product_id,
                    "bn" => $_POST["basic_material_bn"],
                    "serial_number" => $serial_number,
                );
                $mdl_ome_ps->insert($insert_arr);
                $operationLogObj->write_log('product_serial_import@wms',$insert_arr['serial_id'],'唯一码导入');
            }
            echo json_encode(array('result' => 'succ','msg'=>''));
        }
    }
    
    //扫码入库检查
    function ajax_batchCheck(){
        $result_arr = kernel::single('wms_product_serial')->check_serial_import($_POST["serial_number"],$_POST["branch_id"],$_POST["basic_material_bn"]);
        if($result_arr["result"]){
            echo json_encode(array('result' => 'succ','msg'=>''));
        }else{
            echo json_encode(array('result' => 'fail','msg'=>$result_arr["message"]));
        }
    }
    
}