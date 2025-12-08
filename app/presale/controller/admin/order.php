<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class presale_ctl_admin_order extends desktop_controller
{

    var $name = "订单中心";
    var $workground = "order_center";

    function _views(){
        $mdl_order = $this->app->model('orders');
        $base_filter = array('order_type'=>'presale','createtime|than'=>mktime(0,0,0,1,1,date('Y')));

        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if($organization_permissions){
            $base_filter['org_id'] = $organization_permissions;
        }

        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'optional'=>false),
            1 => array('label'=>app::get('base')->_('前端未付尾款'),'filter'=>array('shop_pay'=>'1'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('前端已付尾款'),'filter'=>array('shop_pay'=>'2'),'optional'=>false),
            3 => array('label'=>app::get('base')->_('退款'),'filter'=>array('pay_status' => array('4','5','6','7')),'optional'=>false),
            4 => array('label'=>app::get('base')->_('已发货'),'filter'=>array('ship_status'=>'1'),'optional'=>false),
        );

        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app='.$_GET['app'].'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }

    function index(){
        $filter = array('order_type'=>'presale','createtime|than'=>mktime(0,0,0,1,1,date('Y')));

        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if($organization_permissions){
            $filter['org_id'] = $organization_permissions;
        }

        $params = array(
            'title'=>'预售订单',
            'actions'=>$actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            'use_buildin_import'=>false,
            'use_view_tab'=>true,
            'use_buildin_filter'=>true,
            'base_filter' => $filter,
            'finder_cols' => 'order_bn,shop_id,total_amount,column_print_status,process_status,is_cod,pay_status,ship_status,payment,shipping,logi_id,logi_no,createtime,paytime,mark_type',
            'finder_aliasname' => 'order_view',
        );
        $this->finder('presale_mdl_orders',$params);
    }

    
}




?>