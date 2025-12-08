<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_ctl_admin_delivery_send extends desktop_controller {

    var $name = "通知仓库新建列表";
    var $workground = "console_center";

    function index(){
        $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('ready','progress'),
        );
        $base_filter = array_merge($base_filter,$_GET);
        $_GET['view'] = intval($_GET['view']);
        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if($organization_permissions){
            $base_filter['org_id'] = $organization_permissions;
        }
        
        $user = kernel::single('desktop_user');
        if($user->has_permission('console_process_receipts_print_export'))
        {
            $base_filter_str = http_build_query($base_filter);
            if ($_GET['view'] == '0') {
                $query_status = 'progress';
            }elseif($_GET['view'] == '2'){
                $query_status = 'succ';
            }
            
            $actions[] =  array(
                    'label'=>'导出',
                    'submit'=>'index.php?app=omedlyexport&ctl=ome_delivery&act=index&action=export&status='.$query_status,
                    'target'=>'dialog::{width:600,height:300,title:\'导出\'}'
            );
        }
        
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'title'=>'通知仓库新建列表',
            'base_filter' => $base_filter,
        );

        if (in_array($_GET['view'],array('1','2','3'))){
                $actions[] = array('label' => '发送至第三方',
                            'submit' => 'index.php?app=console&ctl=admin_delivery&act=batch_sync', 
                            'confirm' => '你确定要对勾选的发货单发送至第三方吗？', 
                            'target' => 'refresh',
                ); 
        }
        
        if($_GET['view'] == '3'){
          $actions[] = array('label' => 'OMS强制取消', 'submit' => 'index.php?app=console&ctl=admin_delivery_sync&act=batch_cancel', 'confirm' => "本操作只对非自有仓储,这些发货单认为都是在仓储已经取消发货，请确认这些发货单WMS已经取消！！！\n\n警告：本操作将会直接取消oms发货单并释放库存，并不可恢复，请谨慎使用！！！", 'target' => 'refresh');
        }elseif($_GET['view'] == '5'){
            $actions[] = array(
                   'label' => '通知京东云交易发货',
                   'submit' => 'index.php?app=console&ctl=admin_delivery&act=batch_makedly',
                   'confirm' => '你确定要对勾选的发货单,通知京东云交易平台提前发货吗？',
                   'target' => 'refresh',
            );
        }elseif($_GET['view'] == '2'){
            //发起中状态
            $actions[] = array(
                    'label' => '设置为请求失败状态',
                    'submit' => 'index.php?app=console&ctl=admin_delivery&act=batch_updateFail',
                    'confirm' => '你确定要对勾选的发货单,更新为[请求失败状态]吗？',
                    'target' => 'refresh',
            );
        }
        
        $params['actions'] =$actions;
        $this->finder('console_mdl_delivery', $params);
    }

    function _views(){
        $dlyObj = app::get('console')->model('delivery');

        $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('ready','progress'),
        );

        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if($organization_permissions){
            $base_filter['org_id'] = $organization_permissions;
        }

        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array('sync_status' => array('0','1','2','3')),'optional'=>false),
            1 => array('label'=>app::get('base')->_('未发起'),'filter'=>array('sync_status'=>'0'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('已发起'),'filter'=>array('sync_status'=>'1'),'optional'=>false),
            3 => array('label'=>app::get('base')->_('失败'),'filter'=>array('sync_status'=>'2'),'optional'=>false),
            4 => array('label'=>app::get('base')->_('成功'),'filter'=>array('sync_status'=>'3'),'optional'=>false),
            5 => array('label'=>app::get('base')->_('通知发货失败'),'filter'=>array('sync_status'=>'11'), 'optional'=>false),
            6 => array('label'=>app::get('base')->_('通知发货成功'),'filter'=>array('sync_status'=>'12'), 'optional'=>false),
        );

        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $dlyObj->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=console&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }

        return $sub_menu;
    }

}
