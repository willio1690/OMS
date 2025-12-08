<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_brand extends desktop_controller{

    var $workground = 'goods_manager';

    function index(){
        $this->finder('ome_mdl_brand',array(
            'title'=>'基础物料品牌',
            'actions' => array(
                array('label'=>'添加','href'=>'index.php?app=ome&ctl=admin_brand&act=create','target'=>'dialog::{width:600,height:300,title:\'新建物料品牌\'}'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>true,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
            'orderBy' =>'ordernum DESC'
        ));
    }

    function getCheckboxList(){
        $brand = $this->app->model('brand');
        $this->pagedata['checkboxList'] = $brand->getList('brand_id,brand_name',null,0,-1);
        $this->display('admin/goods/brand/checkbox_list.html');
    }

    function create(){
        $this->pagedata['title'] = '添加商品品牌';
        $this->display('admin/goods/brand/detail.html');
    }

    function save(){
        $this->begin('index.php?app=ome&ctl=admin_brand&act=index');
        $objBrand = $this->app->model('brand');
        $brand_name = addslashes($_POST['brand_name']);

        if($_POST['brand_id']==''){
            $brand = $objBrand->dump(array('brand_name'=>$brand_name),'*');
            if(!empty($brand)){
                $this->end(false,app::get('base')->_('品牌已存在!不可以继续添加'));
            }
        }
        if($objBrand->db_dump(['brand_code'=>$_POST['brand_code'], 'brand_id|noequal'=>$_POST['brand_id']])) {
            $this->end(false,app::get('base')->_('品牌编码已存在!不可以重复'));
        }
        $this->end($objBrand->save($_POST),app::get('base')->_('品牌保存成功'));

    }

    function edit(){
        $brand_id = $_GET['brand_id'];
        $brand_obj = $this->app->model('brand');
        $brands = $brand_obj->dump(array('brand_id' => $brand_id), '*');

        $this->pagedata['brandInfo'] = $brands;
        $this->display('admin/goods/brand/detail.html');
    }

}

