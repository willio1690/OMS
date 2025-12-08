<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 从ome_ctl_admin_return_rchange复制而来
 * 这里只是做了process_list的质检单据的列表展示
 */
class wms_ctl_admin_return_rchange extends desktop_controller {
    
    var $name = "退换货单";
    var $workground = "wms_center";

    function index(){
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
        );
        if($_GET['flt'] == 'process_list'){
            //质检单据的列表展示
            $oBranch = app::get('ome')->model('branch');
            $params['title'] = '售后收货';
            //$params['base_filter'] = array('status|noequal'=>'succ');
            if(!$_GET['view']){
              $params['base_filter']['is_check'] = array('1','3','4','7','8','9','13');
            }
            $params['use_buildin_export'] = false;
            #过滤自有仓储退货单
            $wms_id = kernel::single('wms_branch')->getBranchByselfwms();
            $branch_list = $oBranch->getList('branch_id', array('wms_id'=>$wms_id), 0, -1);
            $branch_list[] = 0;
            if ($branch_list)
            $branch_ids = array();
            foreach ($branch_list as $branch_list) {
                $branch_ids[] = $branch_list['branch_id'];
            }
            $params['base_filter']['branch_id'] = $branch_ids;
        }else{
            //不会走到这里 代码先保留
            $params['use_buildin_export'] = true;
            $params['title'] = '退换货单';
            //$params['base_filter'] = array('status|noequal'=>'succ');
            $params['actions'] = array(
                  array(
                    'label' => '新建退换货单',
                    'href' => 'index.php?app=ome&ctl=admin_return_rchange&act=rchange',
                    'target' => "dialog::{width:1200,height:546,title:'新建退换货单'}",
                  ),
            );
        }
        if(isset($_POST['return_type'])){
            $params['base_filter']['return_type'] = $_POST['return_type'];
        }else{
            //过滤拒收退货
            $params['base_filter']['return_type'] = array('return','change');
        }
        # 权限判定 （和ome_ctl_admin_return_rchange的区别 做了权限管理）
        if(!$this->user->is_super()){
           $returnLib = kernel::single('ome_return');
           foreach ($params['actions'] as $key=>$action) {
               $url = parse_url($action['href']);
               parse_str($url['query'],$url_params);
                $has_permission = $returnLib->chkground($this->workground,$url_params);
                if (!$has_permission) {
                    unset($params['actions'][$key]);
                }
           }
        }
        $this->finder ( 'ome_mdl_reship' , $params );
    }

    //由于本身app wms 不等于 finder的app ome 所以没走到这里 代码先保留
    function _views(){
        if($_GET['flt'] == 'process_list'){
            #$this->workground = "wms_center";
            $sub_menu = $this->_view_process();
        }else{
            #$this->workground = "aftersale_center";
            $sub_menu = $this->_view_all();
        }
        return $sub_menu;
    }

    //由于本身app wms 不等于 finder的app ome 所以没走到这里 代码先保留
    function _view_all(){
        $mdl_reship = app::get('ome')->model('reship');
        $sub_menu = array(
            0 => array('label'=>__('全部'),'optional'=>false),
            1 => array('label'=>__('未审核'),'filter'=>array('is_check'=>'0'),'optional'=>false),
            2 => array('label'=>__('审核成功'),'filter'=>array('is_check'=>'1'),'optional'=>false),
            3 => array('label'=>__('审核失败'),'filter'=>array('is_check'=>'2'),'optional'=>false),
            //4 => array('label'=>__('收货成功'),'filter'=>array('is_check'=>'3'),'optional'=>false),
            //5 => array('label'=>__('拒绝收货'),'filter'=>array('is_check'=>'4'),'optional'=>false),
            6 => array('label'=>__('拒绝'),'filter'=>array('is_check'=>'5'),'optional'=>false),
            7 => array('label'=>__('补差价'),'filter'=>array('is_check'=>'6'),'optional'=>false),
            8 => array('label'=>__('完成'),'filter'=>array('is_check'=>'7'),'optional'=>false),
            9 => array('label'=>__('质检通过'),'filter'=>array('is_check'=>'8'),'optional'=>false),
            10 => array('label'=>__('拒绝质检'),'filter'=>array('is_check'=>'9'),'optional'=>false),
            11=> array('label'=>__('未录入退回物流号'),'filter'=>array('filter_sql'=>'({table}return_logi_no is null or {table}return_logi_no="")'),'optional'=>false),
            12 => array('label'=>__('质检异常'),'filter'=>array('filter_sql'=>'({table}is_check="10" or ({table}need_sv="false" and {table}is_check="0"))','optional'=>false)),
        );
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_reship->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_return_rchange&act=index&view='.$k;
        }
        return $sub_menu;
    }
    
    //由于本身app wms 不等于 finder的app ome 所以没走到这里 代码先保留
    function _view_process(){
        $mdl_reship = app::get('ome')->model('reship');
        $is_check = array('1','3','4','7','8','9');
        $sub_menu = array(
            0 => array('label'=>__('全部'),'filter'=>array('is_check'=>$is_check),'optional'=>false),
            1 => array('label'=>__('审核成功'),'filter'=>array('is_check'=>'1'),'optional'=>false),
            //2 => array('label'=>__('收货成功'),'filter'=>array('is_check'=>'3'),'optional'=>false),
            //3 => array('label'=>__('拒绝收货'),'filter'=>array('is_check'=>'4'),'optional'=>false),
            4 => array('label'=>__('完成'),'filter'=>array('is_check'=>'7'),'optional'=>false),
            5 => array('label'=>__('质检通过'),'filter'=>array('is_check'=>'8'),'optional'=>false),
            6 => array('label'=>__('拒绝质检'),'filter'=>array('is_check'=>'9'),'optional'=>false),
        );
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_reship->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=wms&ctl=admin_return_rchange&act=index&flt=process_list&view='.$k;
        }
        return $sub_menu;
    }

}