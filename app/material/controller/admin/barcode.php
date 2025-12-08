<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 条码控制器
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */
class material_ctl_admin_barcode extends desktop_controller{

    var $workground = 'goods_manager';

    function __construct($app){
        parent::__construct($app);
        $this->__codeType = material_codebase::getBarcodeType();
    }
    /**
     * 条码列表
     * 
     * @param Post
     * @return String
     */

    public function index(){
        $params = array(
            'title'=>'条码',
            'actions' => array(
                    array(
                        'label' => '新建',
                        'href' => 'index.php?app=material&ctl=admin_barcode&act=add',
                        'target' => 'dialog::{width:600,height:400,title:\'新建条码\'}',
                    ),
                    array(
                        'label' => '导入模板下载',
                        'href' => 'index.php?app=material&ctl=admin_barcode&act=exportTemplate',
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

         $this->finder('material_mdl_barcode',$params);
    }

    /**
     * 物料特性添加
     * 
     * @param Null
     * @return String
     */
    public function add(){
        $this->page('admin/barcode/add.html');
    }

    /**
     * 保存新增特性
     * 
     * @param Post
     * @return String
     */
    public function toAdd(){
        $params = $_POST;

        //检查参数
        if(!$this->checkAddParams($params, $err_msg)){
            echo $err_msg;exit;
        }
        $sdf = array(
            'bm_id' => $params['bm_id'],
            'type' => $this->__codeType,
            'code' => $params['barcode'],
        );
        app::get('material')->model('barcode')->insert($sdf);

        echo "SUCC";exit;
    }

    /**
     * 特性新增参数检查
     * 
     * @param Array $params 
     * @param String $err_msg
     * @return Boolean
     */
    public function checkAddParams(&$params, &$err_msg){
        if(empty($params['bm_id']) || empty($params['barcode'])){
            $err_msg ="必填信息不能为空";
            return false;
        }

        $barcodeObj = app::get('material')->model('barcode');
        $barcodeInfo = $barcodeObj->getList('bm_id',array('code'=>$params['barcode']));
        if($barcodeInfo && ($barcodeInfo[0]['bm_id'] != $params['bm_id'])){
            $err_msg ="当前条码已经被占用";
            return false;
        }

        return true;
    }

    /**
     * 编辑特性内容
     * 
     * @param Int $bm_id
     * @return String
     */
    public function edit($bm_id){
        $barcodeObj = app::get('material')->model('barcode');
        $basicMaterialObj = app::get('material')->model('basic_material');

        $tmp_bm_id = intval($bm_id);
        $barcodeInfo = $barcodeObj->dump(array('bm_id'=>$tmp_bm_id));
        $basicMInfo = $basicMaterialObj->dump($tmp_bm_id,'material_name,material_bn');
        $barcodeInfo = array_merge($barcodeInfo,$basicMInfo);

        //检查部分按钮是否只读不可能修改
        $readonly = $this->checkEditReadOnly($bm_id);

        $this->pagedata['barcode_info'] = $barcodeInfo;
        $this->pagedata['readonly'] = $readonly;
        $this->page('admin/barcode/edit.html');
    }

    /**
     * 检查特性某些内容是否可编辑
     * 
     * @param Int $bm_id
     * @return Array
     */
    public function checkEditReadOnly($bm_id){
        $readonly = array('code' => false);

        //如果基础物料有库存、冻结、采购、订单，那么物料类型不能变
        $basicMStockObj = app::get('material')->model('basic_material_stock');
        $storeInfo = $basicMStockObj->getList('store,store_freeze',array('bm_id'=>$bm_id));
        
        //根据基础物料ID获取对应的冻结库存
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        $storeInfo[0]['store_freeze']    = $basicMStockFreezeLib->getMaterialStockFreeze($bm_id);
        
        if($storeInfo[0]['store'] > 0 || $storeInfo[0]['store_freeze'] > 0){
            $is_type_readonly = true;
        }

        $purchaseItemObj = app::get('purchase')->model('po_items');
        $purchaseInfo = $purchaseItemObj->getList('product_id',array('product_id'=>$bm_id));
        if($purchaseInfo){
            $is_type_readonly = true;
        }

        $orderItemObj = app::get('ome')->model('order_items');
        $orderInfo = $orderItemObj->getList('product_id',array('product_id'=>$bm_id));
        if($orderInfo){
            $is_type_readonly = true;
        }

        if($is_type_readonly){
            $readonly['code'] = true;
        }

        return $readonly;
    }

    /**
     * 保存编辑过后的特性
     * 
     * @param Post
     * @return String
     */
    public function toEdit(){
        $params = $_POST;

        //检查参数
        if(!$this->checkEditParams($params, $err_msg)){
            echo $err_msg;exit;
        }
        $sdf = array(
            'bm_id' => $params['bm_id'],
            'type' => $this->__codeType,
            'code' => $params['code'],
        );
        app::get('material')->model('barcode')->insert($sdf);

        echo "SUCC";exit;
    }

    /**
     * 特性新增参数检查
     * 
     * @param Array $params 
     * @param String $err_msg
     * @return Boolean
     */
    public function checkEditParams(&$params, &$err_msg){
        if(empty($params['code']) || empty($params['bm_id'])){
            $err_msg ="必填信息不能为空";
            return false;
        }

        $featureObj = app::get('material')->model('barcode');
        $featureInfo = $featureObj->getList('bm_id',array('code'=>$params['code']));
        if($featureInfo && $featureInfo[0]['bm_id'] != $params['bm_id']){
            $err_msg ="当前条码已被占用";
            return false;
        }

        return true;
    }

    /**
     * 物料条件导入标准模板
     * 
     * @param Null
     * @return String
     */
    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=BARCODE".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $featureObj = app::get('material')->model('barcode');
        $title = $featureObj->exportTemplate('barcode');
        echo '"'.implode('","',$title).'"';
    }
}
