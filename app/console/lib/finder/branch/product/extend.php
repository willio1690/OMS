<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_branch_product_extend
{
    //model类
    public $_braProExtObj = null;
    public $_branchObj = null;
    public $_basicMaterialCodeObj = null;
    public $_basicMaterialExtObj = null;
    
    public $_basicMStockFreezeLib = null;
    
    //货品信息列表
    static $_productList = null;
    
    //仓库信息列表
    static $_branchList = null;
    
    //扩展信息列表
    static $_extendList = null;
    
    var $addon_cols = 'sell_end_time';
    
    function __construct()
    {
        $this->_braProExtObj = app::get('ome')->model('branch_product_extend');
        $this->_branchObj = app::get('ome')->model('branch');
        
        $this->_basicMaterialObj = app::get('material')->model('basic_material');
        $this->_basicMaterialCodeObj = app::get('material')->model('codebase');
        $this->_basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        
        $this->_basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
    }
    
    /**
     * 库存扩展详情
     */
    var $detail_base = '库存扩展详情';
    /**
     * detail_base
     * @param mixed $eid ID
     * @return mixed 返回值
     */
    public function detail_base($eid)
    {
        $render = app::get('console')->render();
        
        $operLogObj = app::get('ome')->model('operation_log');
        
        $logList = $operLogObj->read_log(array('obj_id'=>$eid, 'obj_type'=>'branch_product_extend@ome'), 0, -1);
        foreach($logList as $k => $v)
        {
            $logList[$k]['operate_time'] = date('Y-m-d H:i:s', $v['operate_time']);
        }
        
        //扩展信息
        //$extendInfo = $this->_braProExtObj->dump(array('eid'=>$eid), '*');
        
        $render->pagedata['data'] = $logList;
        return $render->fetch('admin/branch/product/extend_detail_logs.html');
    }
    
    var $column_bn = '货号';
    var $column_bn_width = 200;
    var $column_bn_order = 10;
    function column_bn($row, $list)
    {
        $product_id = $row['product_id'];
        
        if(empty(self::$_productList) || empty(self::$_branchList)){
            $branch_ids = array();
            $product_ids = array();
            foreach ($list as $key => $val)
            {
                $branch_id = $val['branch_id'];
                $product_id = $val['product_id'];
                
                $branch_ids[$branch_id] = $branch_id;
                $product_ids[$product_id] = $product_id;
            }
        }
        
        //扩展信息列表
        if(empty(self::$_productList)){
            $tempList = $this->_basicMaterialObj->getList('bm_id,material_bn,material_name', array('bm_id'=>$product_ids));
            if($tempList){
                foreach ($tempList as $key => $val)
                {
                    $bm_id = $val['bm_id'];
                    
                    self::$_productList[$bm_id] = array(
                            'material_bn' => $val['material_bn'],
                            'material_name' => $val['material_name'],
                    );
                }
            }
            
            unset($tempList);
        }
        
        //仓库信息列表
        if(empty(self::$_branchList)){
            $tempList = $this->_branchObj->getList('branch_id,branch_bn,name', array('branch_id'=>$branch_ids));
            if($tempList){
                foreach ($tempList as $key => $val)
                {
                    $branch_id = $val['branch_id'];
                    
                    self::$_branchList[$branch_id] = array(
                            'branch_bn' => $val['branch_bn'],
                            'branch_name' => $val['name'],
                    );
                }
            }
            
            unset($tempList);
        }
        
        return self::$_productList[$product_id]['material_bn'];
    }
    
    var $column_product_name = '货品名称';
    var $column_product_name_width = 300;
    var $column_product_name_order = 20;
    function column_product_name($row)
    {
        $product_id = $row['product_id'];
        
        return self::$_productList[$product_id]['material_name'];
    }
    
    var $column_branch_id = '仓库';
    var $column_branch_id_width = 150;
    var $column_branch_id_order = 12;
    function column_branch_id($row)
    {
        $branch_id = $row['branch_id'];
        
        return self::$_branchList[$branch_id]['branch_name'];
    }
    
    //库存
    var $column_store = '库存';
    var $column_store_width = 100;
    var $column_store_order = 13;
    function column_store($row)
    {
        return $row['store'];
    }
    
    //冻结库存
    var $column_store_freeze = '冻结库存';
    var $column_store_freeze_width = 100;
    var $column_store_freeze_order = 15;
    function column_store_freeze($row)
    {
        $store_freeze = $this->_basicMStockFreezeLib->getBranchFreeze($row['product_id'], $row['branch_id']);
        
        return $store_freeze;
    }
    
    //全款预售截止时间
    var $column_sell_end_time = '全款预售截止时间';
    var $column_sell_end_time_width = 130;
    var $column_sell_end_time_order = 95;
    function column_sell_end_time($row)
    {
        if($row['store_sell_type'] == 'presell'){
            $sell_end_time = ($row['sell_end_time'] ? $row['sell_end_time'] : $row[$this->col_prefix.'sell_end_time']);
            
            return ($sell_end_time ? date('Y-m-d H:i:s', $sell_end_time) : '');
        }else{
            return '';
        }
    }
}
