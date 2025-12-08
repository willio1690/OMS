<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 +----------------------------------------------------------
 * 基础物料保质期批次明细数据结构
 +----------------------------------------------------------
 * Author: wangbiao@shopex.cn
 * Time: 2015-08-15 $
 * [Ecos!] (C)2003-2015 Shopex Inc.
 +----------------------------------------------------------
 */


class wms_ctl_admin_material_storagelife extends desktop_controller
{
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */

    public function __construct($app)
    {
        parent::__construct($app);
    }
    /*------------------------------------------------------ */
    //-- 列表
    /*------------------------------------------------------ */

    function index()
    {

        $this->title    = '保质期批次列表';
        $base_filter    = array();
        $actions        = array();
        
        #批量修改
        $actions    = array(
                            array(
                                    'label'=>'批量修改保质期',
                                    'submit'=>"index.php?app=wms&ctl=admin_material_storagelife&act=BatchEditExpire&p[0]=".$_GET['view'],
                                    'target'=>'dialog::{width:600,height:300,title:\'批量设置保质期\'}"'
                            ),
                        );
        
        $params    = array(
                        'actions' => $actions,
                        'title' => $this->title,
                        'use_buildin_set_tag'=>true,
                        'use_buildin_filter'=>true,
                        'use_buildin_tagedit'=>true,
                        'use_buildin_import' => false,
                        'use_buildin_export'=>true,
                        'allow_detail_popup'=>true,
                        'use_buildin_recycle'=>false,
                        'use_view_tab'=>true,
                        'base_filter' => $base_filter,
                    );
        
        $this->finder('wms_mdl_basic_material_storage_life', $params);
    }
    
    /*------------------------------------------------------ */
    //-- 分类导航
    /*------------------------------------------------------ */
    function _views()
    {
        $mdl_order    = app::get('material')->model('basic_material_storage_life');
        $sub_menu = array(
                0 => array('label'=>app::get('base')->_('全部'), 'filter'=>array(), 'optional'=>false),
                1 => array('label'=>app::get('base')->_('无剩余数量'), 'filter'=>array('balance_num|sthan'=>0), 'optional'=>false),
                2 => array('label'=>app::get('base')->_('过期自动退出保质期批次'), 'filter'=>array('quit_date|sthan'=>time()), 'optional'=>false),
        );
        
        $i=0;
        foreach($sub_menu as $k => $v)
        {
            $sub_menu[$k]['filter']   = $v['filter'] ? $v['filter']:null;
            $sub_menu[$k]['addon']    = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href']     = 'index.php?app=wms&ctl=admin_material_storagelife&act=index&view='.$i++;
        }
        
        return $sub_menu;
    }
    
    /*
     * 编辑
     * 
     */
    function editor($bmsl_id)
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMaterialObj = app::get('material')->model('basic_material');
        
        $data       = array();
        $data       = $basicMaterialStorageLifeObj->dump(array('bmsl_id'=>$bmsl_id), '*');
        
        if(empty($data))
        {
            die('没有找到相关记录...');
        }
        
        $item_basic_material    = $basicMaterialObj->dump(array('bm_id'=>$data['bm_id']), 'material_name');
        $data    = array_merge($data, $item_basic_material);
        
        $data['production_date']    = date('Y-m-d', $data['production_date']);
        $data['expiring_date']    = date('Y-m-d', $data['expiring_date']);
        $this->pagedata['item']    = $data;
        $this->page('admin/material/editor_storage_life.html');
    }
    
    /*
     * 保存
     *
     */
    function save()
    {
        $this->begin('');
        
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMReceiptStorageLifeLib = kernel::single('material_receipt_storagelife');
        
        $bmsl_id    = $_POST['bmsl_id'];
        if(empty($bmsl_id))
        {
            $this->end(false, '无效操作');
        }
        
        $row    = $basicMaterialStorageLifeObj->dump(array('bmsl_id'=>$bmsl_id), 'bmsl_id');
        if(empty($row))
        {
            $this->end(false, '没有找到相关记录');
        }
        
        $_POST['bmsl_ids'][0]    = $row['bmsl_id'];
        
        unset($_POST['bmsl_id'], $_POST['_DTYPE_DATE']);#注销
        $data    = $_POST;
        
        #更新
        $is_update    = $basicMReceiptStorageLifeLib->updatePeriodValidity($data, $msg);
        
        if($is_update)
        {
            $this->end(true, '修改保质期成功');
        }
        else
        {
            $error_msg = is_array($msg) ? implode('!',$msg) : '修改保质期失败';
            $this->end(false, $error_msg);
        }
    }
    
    /*
     * 关闭 激活状态记录
     */
    function deactive($bmsl_id){
    	$this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
    	
    	$data=array(
    		'bmsl_ids'=>$bmsl_id
    	);
    	
    	#更新
    	$basicMReceiptStorageLifeLib = kernel::single('material_receipt_storagelife');
    	$is_update = $basicMReceiptStorageLifeLib->updateStatusPeriodValidity($data, "deactive", $msg);
    	
    	if($is_update){
    		$this->end(true, '修改状态成功');
    	}else{
    		$error_msg = is_array($msg) ? implode('!',$msg) : '修改状态失败';
    		$this->end(false, $error_msg);
    	}

    }
    
    /*
     * 激活 关闭状态记录
     */
    function active($bmsl_id){
    	$this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
    	 
    	$data=array(
    			'bmsl_ids'=>$bmsl_id
    	);
    	 
    	#更新
    	$basicMReceiptStorageLifeLib = kernel::single('material_receipt_storagelife');
    	$is_update = $basicMReceiptStorageLifeLib->updateStatusPeriodValidity($data, "active", $msg);
    	 
    	if($is_update){
    		$this->end(true, '修改状态成功');
    	}else{
    		$error_msg = is_array($msg) ? implode('!',$msg) : '修改状态失败';
    		$this->end(false, $error_msg);
    	}
    }
    
    /*
     * 批量修改保质期
     */
    function BatchEditExpire($view)
    {
        $sub_menu = $this->_views();
        foreach ($sub_menu as $key => $value) {
            if($key == $view){
                $base_filter = $value['filter'];
            }
        }
        
        $filter = array_merge((array)$_POST,(array)$base_filter);
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMaterialStorageLifeObj->filter_use_like = true;
        $count = $basicMaterialStorageLifeObj->count($filter);

        $this->pagedata['total'] = $count;
        $this->pagedata['filter'] = http_build_query($filter);
        $this->display('admin/material/batch_edit_expire.html');
    }
    
    /*
     * 批量保存
    *
    */
    function batch_save()
    {
        $page_no = intval($_GET['page_no']) ? intval($_GET['page_no']) : 1;
        $page_size = 10;
        $offset = ($page_no-1)*$page_size;
        $total = intval($_GET['total']);
        parse_str($_POST['filter'],$filter);

        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMaterialStorageLifeObj->filter_use_like = true;
        $basicMReceiptStorageLifeLib = kernel::single('material_receipt_storagelife');

        $materialStorageLifeList = $basicMaterialStorageLifeObj->getList('*',$filter,$offset,$page_size);
        $succ_num = $fail_num = 0;
        if ($materialStorageLifeList) {
            foreach ((array) $materialStorageLifeList as $materialStorage_info) {
                $arr_bmsl_id[] = $materialStorage_info['bmsl_id'];
            }
            
            if($arr_bmsl_id){
                $_POST['bmsl_ids'] = $arr_bmsl_id;
                $data    = $_POST;
                $is_update = $basicMReceiptStorageLifeLib->updatePeriodValidity($data, $msg);
                if($is_update){
                    $succ_num = count($materialStorageLifeList);
                }else{
                    $fail_num = count($materialStorageLifeList);
                }
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
    }

}

