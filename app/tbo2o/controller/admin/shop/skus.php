<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_ctl_admin_shop_skus extends desktop_controller {

    var $name = '淘宝前端宝贝';
    var $workground = 'tbo2o_center';

    function index()
    {
        $base_filter = array();
        $params = array(
                'title'=>'淘宝前端宝贝管理',
                'actions' => array(
                        array(
                                'label' => '同步淘宝宝贝',
                                'href' => 'index.php?app=tbo2o&ctl=admin_shop_skus&act=syncTbPage&finder_id='.$_GET['finder_id'],
                                'target'=>'dialog::{title:\'同步淘宝店铺商品\'}',
                        ),
                        array(
                                'label' => '导出关联模板',
                                'href' => 'index.php?app=tbo2o&ctl=admin_shop_skus&act=exportTemplate',
                                'target' => '_blank',
                        ),
                ),
                'base_filter' => $base_filter,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>true,
        );
        
        $this->finder('tbo2o_mdl_shop_skus', $params);
    }
    
    /**
     * 列表分栏菜单
     * 
     * @return Array
     */
    function _views()
    {
        $skuObj    = app::get('tbo2o')->model('shop_skus');
        
        $sub_menu = array(
                0 => array('label'=>app::get('base')->_('全部'),'optional'=>false),
                1 => array('label'=>app::get('base')->_('已关联'),'filter'=>array('is_bind_product'=>1),'optional'=>false),
                2 => array('label'=>app::get('base')->_('已绑定'),'filter'=>array('is_bind'=>1),'optional'=>false),
        );
        
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $skuObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=tbo2o&ctl=admin_shop_skus&act=index&view='.$k;
        }
        
        return $sub_menu;
    }

    /**
     * 商品同步宝贝页
     * 
     * @return void
     * @author
     * */
    function syncTbPage()
    {
        #店铺配置信息
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        $shop_id = $tbo2o_shop['shop_id'];
        
        if(empty($shop_id))
        {
            echo('没有找到可同步的店铺！');
            exit;
        }
        
        $shopObj     = app::get('ome')->model('shop');
        $filter      = array('shop_id'=>$shop_id, 'taobao'=>'taobao', 'active'=>'true', 'disabled'=>'false', 'node_type'=>'taobao');
        $shopItem    = $shopObj->dump($filter, 'shop_id, shop_bn, name, shop_type, business_type');
        if(empty($shopItem))
        {
            echo('店铺不存在或者不是淘宝类型店铺！');
            exit;
        }
        
        #同步页面
        $url            = 'index.php?app=tbo2o&ctl=admin_shop_skus&act=downloadByShop&p[0]='. $shop_id;
        $shopfactory    = tbo2o_shop_service_factory::createFactory($shopItem['shop_type'], $shopItem['business_type']);
        if ($shopfactory)
        {
            $loadList    = $shopfactory->get_approve_status();
        }
        
        $this->pagedata['shop_id']      = $shop_id;
        $this->pagedata['url']          = $url;
        $this->pagedata['loadList']     = $loadList;
        $this->pagedata['width']        = intval(100/count($loadList));
        $this->pagedata['downloadType'] = 'shop';
        
        if ($_GET['redirectUrl']) {
            $this->pagedata['redirectUrl'] = 'index.php?'.http_build_query($_GET['redirectUrl']);
        }
        
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
    
    /**
     * 下载淘宝店铺中所有宝贝
     * 
     * @param Null
     * @return String
     */
    function downloadByShop($shop_id)
    {
        if(empty($shop_id))
        {
            $this->splash('error', null, $this->app->_('请选择店铺！'));
        }
        
        $page    = $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $flag    = $_GET['flag'];
        
        $shopLib     = kernel::single('tbo2o_shop_sync');
        $shopObj     = app::get('ome')->model('shop');
        
        # 店铺配置信息
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        if(empty($tbo2o_shop['shop_id']) || ($tbo2o_shop['shop_id'] != $shop_id))
        {
            $this->splash('error', null, $this->app->_('没有找到可同步的店铺！'));
        }
        
        $shop        = $shopObj->dump(array('shop_id'=>$shop_id), 'shop_id, shop_bn, name, shop_type, business_type');
        if(empty($shop))
        {
            $this->splash('error', null, $this->app->_('店铺不存在'));
        }
        
        # 查看是否在同步中
        $sync    = $shopLib->getShopSync($shop_id);
        if ($sync === 'true')
        {
            $this->splash('error', null, $this->app->_("其他人正在同步中，请稍后同步!"));
        }
        
        # 加载处理类
        $shopfactory    = tbo2o_shop_service_factory::createFactory($shop['shop_type'], $shop['business_type']);
        if ($shopfactory == false) {
            $this->splash('error',null,$this->app->_("工厂生产类失败!"));
        }
        
        $exist             = true;
        $approve_status    = $shopfactory->get_approve_status($flag, $exist);
        if($exist == false)
        {
            $this->splash('error',null,$this->app->_("标记异常!"));
        }
        
        $shopLib->setShopSync($shop_id,'true');
        
        #下载商品
        try{
            $result    = $shopLib->downloadList($shop_id, $approve_status['filter'], $page, $errormsg);
        } catch (Exception $e) {
            $errormsg = '同步失败：网络异常';
        }
        
        $shopLib->setShopSync($shop_id,'false');
        
        $errormsg = is_array($errormsg) ? implode('<br/>',$errormsg) : $errormsg;
        
        if ($result === false)
        {
            $this->splash('error', null, $errormsg);
        }
        else
        {
            $loading         = $shopfactory->get_approve_status();
            $rate            = $loading ? 100/count($loading) : 100;
            $totalResults    = $shopfactory->getTotalResults();
            $download_limit  = tbo2o_shop_sync::DOWNLOAD_ALL_LIMIT;
            $msg             = '同步完成';
            $downloadStatus  = 'running';
            
            # 判断是否已经全部下载完
            if($page >= ceil($totalResults/$download_limit) || $totalResults==0)
            {
                $msg            = '全部下载完';
                $downloadStatus = 'finish';
                $downloadRate   = $rate * ($flag+1);
                
                if($_POST['time'] && count($loading)==($flag+1))
                {
                    base_kvstore::instance('tbo2o/batchframe')->store('downloadTime'.$shop_id, $_POST['time']);
                    
                    $shopItemLib    = kernel::single('tbo2o_shop_items');
                    $shopSkuLib     = kernel::single('tbo2o_shop_skus');
                    
                    $shopItemLib->deletePassData($shop_id, $_POST['time']);
                    $shopSkuLib->deletePassData($shop_id, $_POST['time']);
                }
            } else {
                $downloadRate    = $rate*$flag + $page*$download_limit/$totalResults*$rate;
            }
            
            $msgData    = array('errormsg'=>$errormsg,'totalResults'=>$totalResults,'downloadRate'=>intval($downloadRate),'downloadStatus'=>$downloadStatus);
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
    }
    
    /**
     * 批量导入的模板
     * 
     * @param Null
     * @return String
     */
    function exportTemplate()
    {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=淘宝宝贝关联后端商品导入模板-".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        
        $shopItemObj    = app::get('tbo2o')->model('shop_skus');
        $title          = $shopItemObj->exportTemplate('item');
        
        echo '"'.implode('","',$title).'"';
        
        #模板案例
        $data[0]        = array('淘宝前端商品名称一', 'taobao_bn_001', '淘宝后端商品一', 'product_bn_001');
        $data[1]        = array('淘宝前端商品名称二', 'taobao_bn_002', '淘宝后端商品二', 'product_bn_002');
        
        foreach ($data as $items)
        {
            foreach ($items as $key => $val)
            {
                $items[$key]    = kernel::single('base_charset')->utf2local($val);
            }
            
            echo "\n";
            echo '"'.implode('","', $items).'"';
        }
    }
    
    /**
     * 关联后端商品
     * 
     * @param $id
     * @return String
     */
    function bind_product($id)
    {
        if(empty($id))
        {
            die('操作出错，请重新操作');
        }
        
        $skuObj    = app::get('tbo2o')->model('shop_skus');
        $skuRow    = $skuObj->dump(array('id'=>$id), '*');
        if(empty($skuRow))
        {
            die('操作出错，请重新操作');
        }
        
        if($skuRow['is_bind'] == 1)
        {
            die('店铺货品已绑定,请先解绑再进行关联[后端商品]');
        }
        
        if($_POST)
        {
            $this->begin('index.php?app=tbo2o&ctl=admin_shop_skus&act=index');
            
            $shopProductObj      = app::get('tbo2o')->model('shop_products');
            
            $product_id    = $_POST['product_id'];
            if(empty($product_id))
            {
                $this->end(false, '请选择关联后端商品');
            }
            
            $row    = $shopProductObj->dump(array('id'=>$product_id, 'visibled'=>1), 'id, bn, name');
            if(empty($row))
            {
                $this->end(false, '关联的后端商品不存在');
            }
            
            //基础物料只能关联一次前端宝贝
            $item    = $skuObj->dump(array('product_id'=>$product_id), 'shop_product_bn');
            if($item)
            {
                $this->end(false, '后端商品:'. $row['product_bn'] .' 已关联过前端宝贝:'. $item['shop_product_bn']);
            }
            
            $updata    = array(
                          'product_id'=>$row['id'],
                          'product_bn'=>$row['bn'],
                          'product_name'=>$row['name'],
                          'is_bind_product'=>1,
                    );
            $result    = $skuObj->update($updata, array('id'=>$skuRow['id']));
            if($result)
            {
                $this->end(true, '关联成功');
            }
            
            $this->end(false, '关联失败');
        }
        
        if($skuRow['product_id'])
        {
            $this->pagedata['bind_product_id']    = $skuRow['product_id'];
            $this->pagedata['replacehtml'] = <<<EOF
<div id='hand-selected-product'>已选择了1个物料,<a href='javascript:void(0);' onclick='product_selected_show();'>查看关联的后端商品.</a></div>
EOF;
        }
        
        $this->pagedata['data']          = $skuRow;
        $this->singlepage('admin/shop/bind_product.html');
    }
    
    /**
     * @description 显示关联的物料
     * @access public
     * @param void
     * @return void
     */
    public function showProducts()
    {
        $product_id    = kernel::single('base_component_request')->get_post('product_id');
        
        if ($product_id) {
            $this->pagedata['_input'] = array(
                    'name' => 'id',
                    'idcol' => 'id',
                    '_textcol' => 'product_name',
            );
            
            $shopProductObj = app::get('tbo2o')->model('shop_products');
            $list = $shopProductObj->getList('id,bn,name',array('id'=>$product_id),0,-1);
            
            //显示基础物料编码
            foreach ($list as $key => $val)
            {
                $list[$key]['product_name']    = $val['bn'] .' '. $val['name'];
            }
            
            $this->pagedata['_input']['items'] = $list;
        }
        
        $this->display('admin/shop/show_products.html');
    }

    /**
     * 创建IC商品与后端商品的映射关系
     * 
     * @param $id
     * @return String
     */
    function bind_scitem_map($id)
    {
        $finder_id    = $_GET['finder_id'];
        
        $shopSkuLib    = kernel::single('tbo2o_shop_skus');
        
        $result    = $shopSkuLib->scitemMapAdd($id, $error_msg);
        
        if($result === false)
        {
            echo "<script>parent.MessageBox.success('同步失败(". $error_msg .")！');parent.finderGroup['{$finder_id}'].refresh();</script>";
            exit;
        }
        
        echo "<script>parent.MessageBox.success('同步成功！');parent.finderGroup['{$finder_id}'].refresh();</script>";
        exit;
    }

    /**
     * 解除淘宝前端商品的绑定关系
     * 
     * @param $id
     * @return String
     */
    function unbind_scitem_map($id)
    {
        $finder_id    = $_GET['finder_id'];
        
        $shopSkuLib    = kernel::single('tbo2o_shop_skus');
        
        $result    = $shopSkuLib->scitemMapDelete($id, $error_msg);
        
        if($result === false)
        {
            echo "<script>parent.MessageBox.success('解绑失败(". $error_msg .")！');parent.finderGroup['{$finder_id}'].refresh();</script>";
            exit;
        }
        
        echo "<script>parent.MessageBox.success('解绑成功！');parent.finderGroup['{$finder_id}'].refresh();</script>";
        exit;
    }
}