<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_ctl_admin_shop_products extends desktop_controller {

    var $name = '淘宝后端商品';
    var $workground = 'tbo2o_center';

    function index()
    {
        $base_filter = array();
        $params = array(
                'title'=>'淘宝后端商品管理',
                'actions' => array(
                        array(
                                'label' => '获取本地后端商品',
                                'href' => 'index.php?app=tbo2o&ctl=admin_shop_products&act=downloadLocal&finder_id='.$_GET['finder_id'],
                                'target' => 'dialog::{title:\'获取本地基础物料\'}',
                        ),
                        array(
                                'label' => '推送后端商品至淘宝',
                                'submit' => 'index.php?app=tbo2o&ctl=admin_shop_products&act=syncTaobaoItems&finder_id='.$_GET['finder_id'],
                                'target' => 'dialog::{title:\'推送后端商品至淘宝\'}',
                        ),
                        array(
                                'label' => '同步淘宝后端商品',
                                'href' => 'index.php?app=tbo2o&ctl=admin_shop_products&act=queryTaobaoPage&finder_id='.$_GET['finder_id'],
                                'target' => 'dialog::{title:\'同步淘宝后端商品\'}',
                        ),
                ),
                'base_filter' => $base_filter,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
        );
        
        $this->finder('tbo2o_mdl_shop_products', $params);
    }
    
    /**
     * 列表分栏菜单
     * 
     * @return Array
     */
    function _views()
    {
        $shopProductObj      = app::get('tbo2o')->model('shop_products');
        
        $sub_menu = array(
                0 => array('label'=>app::get('base')->_('全部'),'optional'=>false),
                1 => array('label'=>app::get('base')->_('已同步'),'filter'=>array('is_sync'=>1),'optional'=>false),
        );
        
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $shopProductObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=tbo2o&ctl=admin_shop_products&act=index&view='.$k;
        }
        
        return $sub_menu;
    }
    
    /**
     * @description 弹窗选择后端商品列表
     * @access public
     * @param void
     * @return void
     */
    function finder_common(){
        $params = array(
                'title'=>app::get('desktop')->_('后端商品列表'),
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_setcol'=>true,
                'use_buildin_refresh'=>true,
                'finder_aliasname'=>'finder_common',
                'alertpage_finder'=>true,
                'use_buildin_tagedit'=>false,
        );
        
        $this->finder($_GET['app_id'].'_mdl_'.$_GET['object'],$params);
    }
    
    /**
     * 编辑
     * 
     * @return Boolean
     */
    function edit($id)
    {
        $this->begin('index.php?app=tbo2o&ctl=admin_shop_products&act=index');
        
        if (empty($id)){
            $this->end(false,'操作出错，请重新操作');
        }
        
        $shopProductObj      = app::get('tbo2o')->model('shop_products');
        $productExtObj       = app::get('tbo2o')->model('shop_product_ext');
        
        $row    = $shopProductObj->dump($id);
        if(empty($row))
        {
            $this->end(false,'操作出错，请重新操作');
        }
        
        $row_ext    = $productExtObj->dump($id);
        $data       = array_merge($row, $row_ext);
        
        //品牌列表
        $brandObj    = app::get('ome')->model('brand');
        $brandList   = $brandObj->getList('brand_id,brand_name', array());
        
        $this->pagedata['brandList'] = $brandList;
        $this->pagedata['data'] = $data;
        $this->singlepage('admin/shop/product_edit.html');
    }
    
    function toEdit()
    {
        $this->begin('index.php?app=tbo2o&ctl=admin_shop_products&act=index');
        
        $id    = $_POST['id'];
        if (empty($id)){
            $this->end(false,'操作出错，请重新操作');
        }
        
        $shopProductObj      = app::get('tbo2o')->model('shop_products');
        $productExtObj       = app::get('tbo2o')->model('shop_product_ext');
        
        $row    = $shopProductObj->dump($id);
        if(empty($row))
        {
            $this->end(false,'操作出错，请重新操作');
        }
        
        //品牌名称
        $brand_id    = intval($_POST['brand_id']);
        $brand_name  = '';
        if($brand_id)
        {
            $brandObj    = app::get('ome')->model('brand');
            $brandRow    = $brandObj->dump(array('brand_id'=>$brand_id), 'brand_name');
            $brand_name  = $brandRow['brand_name'];
        }
        
        //sdf
        $data    = array(
                        'is_fragile'=>intval($_POST['is_fragile']),
                        'is_dangerous'=>intval($_POST['is_dangerous']),
                        'is_costly'=>intval($_POST['is_costly']),
                        'is_warranty'=>intval($_POST['is_warranty']),
                        'price'=>floatval($_POST['price']),
                        'weight'=>intval($_POST['weight']),
                        'length'=>$_POST['length'],
                        'width'=>$_POST['width'],
                        'height'=>$_POST['height'],
                        'volume'=>$_POST['volume'],
                        'matter_status'=>$_POST['matter_status'],
                        'brand_id'=>$brand_id,
                        'brand_name'=>$brand_name,
                        'is_area_sale'=>intval($_POST['is_area_sale']),
                        'item_type'=>intval($_POST['item_type']),
        );
        $is_save    = $productExtObj->update($data, array('id'=>$row['id']));
        if($is_save)
        {
            //已同步的商品编辑后显示“同步失败”
            $shopProductObj->update(array('is_sync'=>2), array('id'=>$row['id']));
            
            $this->end(true, '保存成功');
        }
        
        $this->end(false, '保存失败');
    }
    
    /**
     * 获取本地后端商品
     * 
     * @return Boolean
     */
    function downloadLocal()
    {
        $loadList[]    = array('name'=>'本地','flag'=>'all');
        
        #同步页面
        $url    = 'index.php?app=tbo2o&ctl=admin_shop_products&act=downloadByLocal';
        if ($_GET['redirectUrl'])
        {
            $this->pagedata['redirectUrl']    = 'index.php?'.http_build_query($_GET['redirectUrl']);
        }
        
        $this->pagedata['url']          = $url;
        $this->pagedata['loadList']     = $loadList;
        
        $_POST['time'] = time();
        if($_POST)
        {
            $inputhtml = '';
            $post = http_build_query($_POST);
            $post = explode('&', $post);
            foreach ($post as $p) {
                list($name,$value) = explode('=', $p);
                $params = array(
                        'type' => 'hidden',
                        'name' => $name,
                        'value' => $value
                );
                $inputhtml .= utils::buildTag($params,'input');
            }
            $this->pagedata['inputhtml'] = $inputhtml;
        }
        
        $this->display('admin/shop/download_page.html');
    }
    
    function downloadByLocal()
    {
        $basicMaterialObj    = app::get('material')->model('basic_material');
        $shopProductLib      = kernel::single('tbo2o_shop_products');
        
        #页码
        $page    = intval($_GET['page']);
        $page    = ($page > 0 ? $page : 1);
        $flag    = $_GET['flag'];
        
        #同步
        $totalResults    = $basicMaterialObj->count(array('disabled'=>'false'));
        $limit           = 50;
        
        $result        = $shopProductLib->syncMaterial(($page - 1), $limit, $errormsg);
        if($result === false)
        {
            $this->splash('error', null, $errormsg);
        }
        elseif($result['status'] == 'finish')
        {
            $msg        = '同步完成';
            $msgData    = array('errormsg'=>$errormsg,'totalResults'=>$totalResults,'downloadRate'=>100,'downloadStatus'=>$result['status']);
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
        else
        {
            $msg           = '正在同步中...';
            $downloadRate  = ($page * $limit / $totalResults) * 100;
            
            $msgData    = array('errormsg'=>$errormsg,'totalResults'=>$totalResults,'downloadRate'=>intval($downloadRate),'downloadStatus'=>$result['status']);
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
    }
    
    /**
     * 下载淘宝后端商品
     * 
     * @return Boolean
     */
    function downloadTaobao()
    {
        $loadList[]    = array('name'=>'淘宝','flag'=>'all');
        
        #同步页面
        $url    = 'index.php?app=tbo2o&ctl=admin_shop_products&act=downloadByTaobao';
        if ($_GET['redirectUrl'])
        {
            $this->pagedata['redirectUrl']    = 'index.php?'.http_build_query($_GET['redirectUrl']);
        }
        
        $this->pagedata['url']          = $url;
        $this->pagedata['loadList']     = $loadList;
        
        $_POST['time'] = time();
        if($_POST)
        {
            $inputhtml = '';
            $post = http_build_query($_POST);
            $post = explode('&', $post);
            foreach ($post as $p) {
                list($name,$value) = explode('=', $p);
                $params = array(
                        'type' => 'hidden',
                        'name' => $name,
                        'value' => $value
                );
                $inputhtml .= utils::buildTag($params,'input');
            }
            $this->pagedata['inputhtml'] = $inputhtml;
        }
        
        $this->display('admin/shop/download_page.html');
    }
    
    function downloadByTaobao()
    {
        $this->splash('error', null, '暂时无法同步...');
    }
    
    /**
     * [单个]推送后端商品至淘宝
     * 
     * @return Boolean
     */
    function syncTaobaoSingle($id)
    {
        $finder_id    = $_GET['finder_id'];
        
        $shopProductLib    = kernel::single('tbo2o_shop_products');
        
        $result    = $shopProductLib->syncTaobaoScitem($id, $error_msg);
        
        if($result === false)
        {
            echo "<script>parent.MessageBox.success('同步失败(". $error_msg .")！');parent.finderGroup['{$finder_id}'].refresh();</script>";
            exit;
        }
        
        echo "<script>parent.MessageBox.success('同步成功！');parent.finderGroup['{$finder_id}'].refresh();</script>";
        exit;
    }
    
    /**
     * [批量]推送后端商品至淘宝
     * 
     * @return Boolean
     */
    function syncTaobaoItems()
    {
        $this->_request    = kernel::single('base_component_request');
        
        $data   = $this->_request->get_post();
        $ids    = $data['id'];
        if(empty($ids))
        {
            echo '请选择同步的后端商品!';
            exit;
        }
        
        //获取有效的数据
        $shopProductObj    = app::get('tbo2o')->model('shop_products');
        $dataList          = $shopProductObj->getList('id', array('id'=>$ids, 'is_sync'=>0));
        if(empty($dataList))
        {
            echo '没有需要同步的后端商品!';
            exit;
        }
        
        $ids    = array();
        foreach ($dataList as $key => $val)
        {
            $ids[]    = $val['id'];
        }
        
        //每次最多执行50条记录
        if(count($ids) > 50)
        {
            echo '批量操作每次最多可以执行50条记录!';
            exit;
        }
        
        //加载批量模板
        $loadList[]    = array('name'=>'后端商品至淘宝','flag'=>'all');
        
        #同步页面
        $url    = 'index.php?app=tbo2o&ctl=admin_shop_products&act=execSyncTaobao';
        if ($_GET['redirectUrl'])
        {
            $this->pagedata['redirectUrl']    = 'index.php?'.http_build_query($_GET['redirectUrl']);
        }
        
        $this->pagedata['url']          = $url;
        $this->pagedata['loadList']     = $loadList;
        
        $_POST         = array();
        $_POST['time'] = time();
        $_POST['ids']  = json_encode($ids);
        
        if($_POST)
        {
            $inputhtml = '';
            
            foreach ($_POST as $key => $val)
            {
                $params = array(
                        'type' => 'hidden',
                        'name' => $key,
                        'value' => $val,
                );
                $inputhtml .= utils::buildTag($params,'input');
            }
            
            $this->pagedata['inputhtml'] = $inputhtml;
        }
        
        $this->display('admin/shop/sync_taobao_page.html');
    }
    
    function execSyncTaobao()
    {
        #页码
        $page    = intval($_GET['page']);
        $page    = ($page > 0 ? $page : 1);
        $flag    = $_GET['flag'];
        
        #Post
        $id_list       = ($_POST['ids'] ? json_decode($_POST['ids'], true) : '');
        $totalResults  = count($id_list);
        if(empty($id_list))
        {
            $this->splash('error', null, '没有可执行的数据');
        }
        
        #已完成同步
        if($page > $totalResults)
        {
            $msg        = '同步完成';
            $msgData    = array('errormsg'=>'', 'totalResults'=>$totalResults, 'downloadRate'=>100, 'downloadStatus'=>'finish');
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
        
        #正在同步
        $shopProductLib    = kernel::single('tbo2o_shop_products');
        
        $id        = $id_list[$page - 1];
        $result    = $shopProductLib->syncTaobaoScitem($id, $error_msg);
        if($result === false)
        {
            $this->splash('error', null, $error_msg);
        }
        else
        {
            $msg           = '正在同步中...';
            $downloadRate  = ($page / $totalResults) * 100;
            
            $msgData    = array('errormsg'=>$error_msg, 'totalResults'=>$totalResults, 'downloadRate'=>intval($downloadRate), 'downloadStatus'=>'running');
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
    }
    
    /**
     * [单个]编辑商品后重新更新推送数据至淘宝
     * 
     * @return Boolean
     */
    function updateSyncTaobao($id)
    {
        $finder_id    = $_GET['finder_id'];
        
        $shopProductLib    = kernel::single('tbo2o_shop_products');
        
        $result    = $shopProductLib->updateSyncTaobaoScitem($id, $error_msg);
        
        if($result === false)
        {
            echo "<script>parent.MessageBox.success('更新失败(". $error_msg .")！');parent.finderGroup['{$finder_id}'].refresh();</script>";
            exit;
        }
        
        echo "<script>parent.MessageBox.success('更新成功！');parent.finderGroup['{$finder_id}'].refresh();</script>";
        exit;
    }
    
    /**
     * 查询淘宝全部后端商品
     * 
     * @return Boolean
     */
    function queryTaobaoPage()
    {
        $loadList[]    = array('name'=>'淘宝','flag'=>'all');
        
        #同步页面
        $url    = 'index.php?app=tbo2o&ctl=admin_shop_products&act=queryTaobaoItems';
        if ($_GET['redirectUrl'])
        {
            $this->pagedata['redirectUrl']    = 'index.php?'.http_build_query($_GET['redirectUrl']);
        }
        
        $this->pagedata['url']          = $url;
        $this->pagedata['loadList']     = $loadList;
        
        $_POST['time'] = time();
        if($_POST)
        {
            $inputhtml = '';
            $post = http_build_query($_POST);
            $post = explode('&', $post);
            foreach ($post as $p) {
                list($name,$value) = explode('=', $p);
                $params = array(
                        'type' => 'hidden',
                        'name' => $name,
                        'value' => $value
                );
                $inputhtml .= utils::buildTag($params,'input');
            }
            $this->pagedata['inputhtml'] = $inputhtml;
        }
        
        $this->display('admin/shop/sync_taobao_page.html');
    }
    
    function queryTaobaoItems()
    {
        $shopProductLib    = kernel::single('tbo2o_shop_products');
        
        //页码
        $page    = intval($_GET['page']);
        $page    = ($page > 0 ? $page : 1);
        $flag    = $_GET['flag'];
        
        //全渠道店铺
        $shop_id    = $shopProductLib->getTbo2oShopId($error_msg);
        if(empty($shop_id))
        {
            $this->splash('error', null, $this->app->_($error_msg));
        }
        
        //获取同步状态
        $sync    = $shopProductLib->getTaobaoSync($shop_id);
        if ($sync === 'true')
        {
            $this->splash('error', null, $this->app->_("其他人正在同步中，请稍后同步!"));
        }
        
        $shopProductLib->setTaobaoSync($shop_id,'true');
        
        //查询淘宝后端商品
        try{
            $result    = $shopProductLib->queryAllScitem($page, $error_msg);
        } catch (Exception $e) {
            $error_msg = '同步失败：网络异常';
        }
        
        //设置同步状态
        $shopProductLib->setTaobaoSync($shop_id, 'false');
        
        $error_msg    = is_array($error_msg) ? implode('<br/>',$error_msg) : $error_msg;
        
        //下载数据
        if ($result === false)
        {
            $this->splash('error', null, $error_msg);
        }
        else
        {
            $totalResults    = $result['totalResults'];
            $download_limit  = tbo2o_shop_products::DOWNLOAD_ALL_LIMIT;
            $msg             = '同步完成';
            $downloadStatus  = 'running';
            
            $rate            = $page * $download_limit;
            $downloadRate    = $totalResults ? ($rate / $totalResults) : 0;
            $downloadRate    = intval($downloadRate * 100);
            
            # 判断是否已经全部下载完
            if($page >= ceil($totalResults/$download_limit) || $totalResults==0)
            {
                $downloadRate   = 100;
                $msg            = '全部下载完';
                $downloadStatus = 'finish';
            }
            
            $msgData    = array('errormsg'=>$error_msg, 'totalResults'=>$totalResults, 'downloadRate'=>$downloadRate, 'downloadStatus'=>$downloadStatus);
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
    }
}