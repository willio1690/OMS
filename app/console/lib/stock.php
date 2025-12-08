<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_stock{
    //仓储调拨 商品调拨 选择商品最大数
    const SELECT_PRODUCT_MAX_NUM = 300;
    
    /**
     * 获取货品类型
     * @access public
     * @param Int $product_id 货品ID
     * @return string normal/combination/pkg
     */
    public function get_product_type($product_id = ''){
        $product = $this->get_product_data($product_id);
        return $product['type'];
    }
    
    /**
     * 获取货品基础信息
     * @access public
     * @param Int $product_id 货品ID
     * @return array
     */
    public function get_product_data($product_id = ''){
        if($product_id == '') return null;
        
        $basicMaterialObj    = app::get('material')->model('basic_material');
        
        $product      = $basicMaterialObj->dump(array('bm_id'=>$product_id), '*');
        $product['product_id']    = $product['bm_id'];
        
        //[强制转换]基础物料_商品类型
        $product['type']    = ($product['type'] == '2' ? 'pkg' : 'normal');
        
        return $product ? $product : null;
    }
    
    /**
     *  释放出库单预占库存量
     */
    public function clear_stockout_store_freeze($stockdump_bn){
        $oAppro = app::get('console')->model('stockdump');
        $oAppro_items = app::get('console')->model('stockdump_items');
        $pStockObj = kernel::single('console_stock_products');
        $appro_lists = $oAppro_items->getList(
            'stockdump_id,product_id,num',
            array('stockdump_bn'=>$stockdump_bn)
        );
        $appro_data = $oAppro->dump(array('stockdump_bn'=>$stockdump_bn),'stockdump_id,from_branch_id,to_branch_id');
        
        //库存出入库单信息
        $branch_id       = $appro_data['from_branch_id'];
        $stockdump_id    = $appro_data['stockdump_id'];
        
        //库存管控处理
        $storeManageLib    = kernel::single('ome_store_manage');
        $storeManageLib->loadBranch(array('branch_id'=>$branch_id));
        
        $params    = array();
        $params['node_type'] = 'finishStockdump';
        $params['params']    = array('stockdump_id'=>$stockdump_id, 'branch_id'=>$branch_id);
        $params['params']['items'] = $appro_lists;
        
        $storeManageLib->processBranchStore($params, $err_msg);
        
        $appro_lists = null;
        unset($appro_lists);
        return true;
    }
    
    /**
     * 获取调账方式(默认增量) 弃用
     */
    public function getAdjustType()
    {
        $adjust_type = app::get('ome')->getConf('wms.adjust.type');
        if(empty($adjust_type)){
            $adjust_type = 'inc';
        }
        
        return $adjust_type;
    }
}
?>
