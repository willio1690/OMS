<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logistics_ctl_admin_area_router extends desktop_controller {
    var $workground = 'setting_tools';
    var $defaultWorkground = 'setting_tools';


    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        if($_GET['action'] == 'import') {
            echo '<form method="post" action="index.php?app=logistics&ctl=admin_area_router&act=exportTemplate" class="tableform" target="download" enctype="multipart/form-data">
    模板下载：
    <button class="btn btn-primary" type="submit"><span><span>下载</span></span></button>
  </form>';
        }
        $action=array(
            array(
                'label'  => '添加地区',
                'href'   => 'index.php?app=logistics&ctl=admin_area_router&act=addArea',
                'target'=>'dialog::{width:600,height:600,title:\'添加地区\'}',
            ),
        );
        $row = app::get('logistics')->model('area_router')->getList('area_id', array(), 0, 1);
        if(!$row) {
            $action[] = array(
                'label' => '初始化',
                'icon' => 'upload.gif',
                'href' => $this->url . '&act=index&action=import',
                'target' => 'dialog::{width:450,height:210,title:\'初始化\'}'
            );
        }
        $params=array(
            'title' => '物流就近设置',
            'actions'=>$action,
            'use_buildin_recycle' => true,
        );
        $this->finder('logistics_mdl_area_router',$params);
    }

    /**
     * exportTemplate
     * @return mixed 返回值
     */
    public function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $templateColumn = app::get('logistics')->model('area_router')->getTemplateColumn();
        $yxj = kernel::single('base_charset')->utf2local('优先级');
        $title = array($yxj);
        foreach ($templateColumn as $k => $v) {
            $title[] = kernel::single('base_charset')->utf2local($k);
        }
        $endVal = end($title);
        foreach ($title as $val) {
            if($val == $yxj) {
                echo '"' . implode('","', $title) . '"'."\n";
            } else {
                echo '"'.$val.'"' . "\n";
            }
        }
    }

    /**
     * 添加Area
     * @return mixed 返回值
     */
    public function addArea(){
        $area_id = intval($_GET['area_id']);
        $area = array();
        if($area_id){
            $area = app::get('logistics')->model('area_router')->db_dump($area_id);
            $area['router_area'] = unserialize($area['router_area']);
            $this->pagedata['area'] = $area;
        }

        $region = app::get('eccommon')->model('regions')->getList('region_id, local_name', array('filter_sql'=>'p_region_id is null'));
        foreach ($region as $key => $value) {
            $region[$key]['weight'] = (int) $area['router_area'][$value['region_id']]['weight'];
        }

        uasort($region,function($a,$b){
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return $a['weight'] > $b['weight'] ? -1 : 1 ;
        });

        $this->pagedata['region'] = $region;
        $this->display('admin/addAreaRouter.html');
    }

    /**
     * 保存Area
     * @return mixed 返回操作结果
     */
    public function saveArea() {
        $data = $_POST;
        $areaRouterModel = app::get('logistics')->model('area_router');
        $this->begin('index.php?app=logistics&ctl=admin_area_router&act=index');
        $region = app::get('eccommon')->model('regions')->db_dump(array('region_id'=>$data['area_id']),'region_id, local_name');
        $data['area_name'] = $region['local_name'];
        $areaRouterModel->saveRouterArea($data);
        $this->end(true, '操作成功');
    }
}
