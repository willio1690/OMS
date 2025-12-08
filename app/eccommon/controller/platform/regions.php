<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class eccommon_ctl_platform_regions extends desktop_controller{

   
    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $actions = array();
        $actions[] = array(
                    'label' => '同步平台地址库',
                    'href' => 'index.php?app=eccommon&ctl=platform_regions&act=syncRegion&shop_id='.$_GET['shop_id'],
                    'target' => "dialog::{width:400,height:200,resizeable:false,title:'同步平台地区'}",
        );
        
        /* $actions[] = array(
            'label' => '获取平台市、区、镇地区库',
            'href' => $this->url .'&act=syncTown&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
            'target' => "dialog::{width:550,height:350,title:'按省级循环获取平台四级地区'}",
        );
        
        $actions[] = array(
                    'label' => '同步平台开普勒地区',
                    'href' => 'index.php?app=eccommon&ctl=platform_regions&act=syncKplRegion',
                    'target' => "dialog::{width:300,height:200,title:'同步平台开普勒地区'}",
        ); */
        $params = array(
            'title'                  => '平台地址库管理',
            'actions'                => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_buildin_filter'=>true,
           
        );
        $this->finder('eccommon_mdl_platform_regions', $params);
    }

    function _views(){
        $regionsObj = $this->app->model('platform_regions');
        $base_filter = array();
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array(),'optional'=>false),
            1 => array('label'=>app::get('base')->_('未匹配'),'filter'=>array('mapping' => array('0')),'optional'=>false),
            2 => array('label'=>app::get('base')->_('已匹配'),'filter'=>array('mapping' => array('1')),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $regionsObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=' . $_GET['app'] . '&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&view=' . $i++;
        }
        return $sub_menu;
    }
    
    /**
     * syncRegion
     * @return mixed 返回值
     */
    public function syncRegion()
    {
        $this->pagedata['select_shop_id'] = $_GET['shop_id'];
        $this->pagedata['platformList'] = kernel::single('eccommon_platform_regions')->platformList();
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('platform/regions.html');
    }

    /**
     * doSyncRegion
     * @return mixed 返回值
     */
    public function doSyncRegion(){

        $this->begin($this->url);
        $shop_id = $_POST['shop_id'];
        if ($shop_id=='') {
            $this->end(false,'请选择店铺');
        }
        $data = array(
            'shop_id' =>  $shop_id,
        );
        list($rs, $rsData) = kernel::single('eccommon_platform_regions')->sync($data);
        if(!$rs) {
            $this->end(false,$rsData['msg']);
        }
        $this->end(true,'获取成功', $this->url, ['data'=>$rsData['data']]);

    }

    /**
     * doSyncArea
     * @return mixed 返回值
     */
    public function doSyncArea() {
        @ini_set('memory_limit','1024M');
        set_time_limit(0);
        $data = $_POST;
        list($rs, $rsData) = kernel::single('eccommon_platform_regions')->syncArea($data);
        if($rs) {
            echo json_encode(['rsp'=>'succ']);
            return;
        }
        echo json_encode(['rsp'=>'fail', 'msg'=>$rsData['msg']]);
    }

    /**
     * syncKplRegion
     * @return mixed 返回值
     */
    public function syncKplRegion(){
        set_time_limit(0);
        $rs = kernel::single('eccommon_platform_regions')->synckpl($data);
        echo '同步成功';
    }
    
    /**
     * 获取平台市、区、镇地区库
     * 
     * @return void
     */
    public function syncTown()
    {
        @ini_set('memory_limit','1024M');
        set_time_limit(0);
        
        //店铺编码
        $this->pagedata['selectListName'] = '店铺编码';
        
        //店铺列表
        $tempList = kernel::single('eccommon_platform_regions')->platformList();
    
        //check
        if(empty($tempList)){
            die('没有可拉取的平台店铺列表');
        }
        
        //format
        $shopList = array();
        foreach ($tempList as $key => $val)
        {
            $shop_bn = $val['shop_bn'];
            $shop_type = $val['shop_type'];
            
            $shopList[$shop_bn] = array(
                'shop_bn' => $shop_bn,
                'shop_name' => '('. $shop_type .')'.$val['name'],
            );
        }
        
        $this->pagedata['shopList'] = $shopList;
        
        //开始时间(默认为昨天)
        //$start_time = date('Y-m-d', strtotime('-1 day'));
        //$this->pagedata['start_time'] = $start_time;
        
        //help帮助信息
        $this->pagedata['help_msg'] = '*温馨提醒：每个平台只需要同步其中一个店铺的地区库。';
        
        //post url
        $this->pagedata['postUrl'] = $this->url .'&act=ajaxSyncTown';
        
        //check
        if(empty($shopList)){
            die('没有可拉取的平台店铺列表');
        }
        
        $this->display('platform/download_datalist.html');
    }
    
    /**
     * Ajax获取平台市、区、镇地区库
     * 
     * @return void
     */
    public function ajaxSyncTown()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        $regionsObj = app::get('eccommon')->model('platform_regions');
        
        if(empty($_POST['shop_bn'])){
            $retArr['err_msg'] = array('请先选择店铺编码');
            echo json_encode($retArr);
            exit;
        }
        
        //setting
        $retArr = array(
            'itotal' => 0,
            'isucc' => 0,
            'ifail' => 0,
            'total' => 0,
            'err_msg' => array(),
        );
        
        //page
        $page = isset($_POST['nextPage']) && $_POST['nextPage'] > 1 ? $_POST['nextPage'] : 1;
        
        //shopInfo
        $shopInfo = app::get('ome')->model('shop')->db_dump(array('shop_bn' => $_POST['shop_bn']));
        if(empty($shopInfo)){
            $retArr['err_msg'] = array('店铺信息不存在');
            echo json_encode($retArr);
            exit;
        }
        
        //filter
        $filter = array(
            'shop_type' => $shopInfo['shop_type'], //店铺类型
            'region_grade' => 1,
        );
        
        //count
        $total_num = $regionsObj->count($filter);
        
        //params
        $params = array(
            'shop_bn' => $_POST['shop_bn'], //店铺编码
            'page_no' => $page,
        );
        
        //request
        $rs = kernel::single('eccommon_platform_regions')->syncTown($params);
        if ($rs['rsp'] == 'succ') {
            $retArr['itotal'] += $rs['current_num']; //本次拉取记录数
            $retArr['isucc'] += $rs['current_succ_num']; //处理成功记录数
            $retArr['ifail'] += $rs['current_fail_num']; //处理失败记录数
            $retArr['total'] = $total_num; //数据总记录数
            $retArr['next_page'] = $rs['next_page']; //下一页页码(如果为0则无需拉取)
        } else {
            $error_msg = $rs['error_msg'];
            $retArr['err_msg'] = array($error_msg);
        }
    
        echo json_encode($retArr);
        exit;
    }
}
