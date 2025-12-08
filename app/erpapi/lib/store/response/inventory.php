<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_inventory extends erpapi_store_response_abstract
{
    

    /**
     * 盘点单新建
     * @param
     */
    public function add($params){
        $this->__apilog['title']       = $this->__channelObj->store['name'].'盘点单创建';
        $this->__apilog['original_bn'] = $params['inventory_bn'];

        if(!$params['store_bn']){
             $this->__apilog['result']['msg'] = '门店编码必填';
            return false;
        }

        if(!$params['branch_bn']){
             $this->__apilog['result']['msg'] = '盘点仓库编码必填';
            return false;
        }
        $branch = $this->getBranchIdByBn($params['branch_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]仓库不存在', $params['branch_bn']);
            return false;
        }
        $branch_id = $branch['branch_id'];

        $inventory_type = $params['inventory_type'];

        if (!$inventory_type){
            $this->__apilog['result']['msg'] = '盘点类型不可为空';
            return false;
        }


        $inventory_bn = $params['inventory_bn'];
        if (!$inventory_bn){
            $this->__apilog['result']['msg'] = '盘点单号不可为空';
            return false;
        }

        $business_type = $params['business_type'];

        if(!$business_type || !in_array($business_type,array('day','month'))){
            $this->__apilog['result']['msg'] = '业务类型不可为空';
            return false;
        }
        $invMdl = app::get('o2o')->model('inventory');

        $inv = $invMdl->dump(array('inventory_bn'=>$inventory_bn),'inventory_id,branch_id,status');
  
        if ($inv && !in_array($inv['status'],array('1'))){
            $this->__apilog['result']['msg'] = sprintf('盘点单[%s]不存在', $inventory_bn);
            return false;
        }

        // 明细处理
        $items = @json_decode($params['items'], true);
        if (!$items) {
            $this->__apilog['result']['msg'] = '缺少调拨明细';
            return false;
        }

        $bn_list = array();
        foreach ($items as $key => $value) {
            if (!$value['bn']) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：缺少物料编码', $key);
                return false;
            }

            if (!is_numeric($value['nums']) || $value['nums'] <= 0) {
                //$this->__apilog['result']['msg'] = sprintf('行明细[%s]：[%s]数量异常', $key, $value['bn']);
               // return false;
            }

            if (!is_numeric($value['totalqty']) || $value['totalqty'] <= 0) {
                //$this->__apilog['result']['msg'] = sprintf('行明细[%s]：[%s]库存商品总量异常', $key, $value['bn']);
                //return false;
            }

            $bn_list[] = $value['bn'];
        }

        // 基础物料
        $bm_list = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name', array('material_bn' => $bn_list));
        $bm_list = array_column($bm_list, null, 'material_bn');

        // 获取账面数
        $bm_id_list = array_column($bm_list, 'bm_id');
        $bmExtMdl = app::get('material')->model('basic_material_ext');
        $bm_ext_list = $bmExtMdl->getList('retail_price,bm_id', array('bm_id' => $bm_id_list));
        $bm_ext_list = array_column($bm_ext_list, null, 'bm_id');

        
        foreach ($items as $key => $value) {
            $bm     = $bm_list[$value['bn']];

            $bm_id = $bm['bm_id'];
            $bm_ext = $bm_ext_list[$bm_id];
            
            if (!$bm) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：[%s]货号不存在', $key, $value['bn']);

                return false;
            }
            $items[$key]['price'] = $bm_ext['retail_price'];
            $items[$key]['material_bn'] = $bm['material_bn'];
            $items[$key]['material_name'] = $bm['material_name'];
            $items[$key]['bm_id'] = $bm['bm_id'];
          
        }

        $storeMdl = app::get('o2o')->model('store');
        $physics = $storeMdl->db_dump(array('store_bn' => $params['store_bn']),'store_id');
       
        $inventoryMode = app::get('ome')->getConf('taoguaninventory.quantity.mode');
        
        $data = array(
            'inventory_type'    => $inventory_type,
            'items'             => $items,
            'branch_id'         => $branch_id,
            'inventory_bn'      => $inventory_bn,
            'business_type'     => $business_type,
            'physics_id'        => $physics['store_id'],
            'apply_name'        => $params['application_name'],
            'confirm_name'      => $params['confirm_name'], 
            'inventory_time'    => $params['inventory_time'] ? strtotime($params['inventory_time']) : 0,

        );
        $data['mode'] = $inventoryMode  == '2' ? '2' : '1'; #1: 全量, 2: 增量
        if ($inv){
            $data['inventory_id'] = $inv['inventory_id'];
        }
      
        return $data;
      
    }


     /**
     * 盘点单任务创建完成
     * @param
     */
    public function finish($params){

        $inventory_bn = $params['inventory_bn'];
        $invMdl = app::get('o2o')->model('inventory');
        if(!$params['store_bn']){
             $this->__apilog['result']['msg'] = '门店编码必填';
            return false;
        }

        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }
        
        $inv = $invMdl->db_dump(array('inventory_bn' => $inventory_bn), 'inventory_id,inventory_bn,branch_id,status');

        if (!in_array($inv['status'],array('1'))){

            $this->__apilog['result']['msg'] = sprintf('[%s]盘点单状态已完成或作废', $inventory_bn);
            return false;
        }

        //空盘点单不允许完成
        $itemObj = app::get('o2o')->model('inventory_items');
        $count = $itemObj->count(array('inventory_id'=>$inv['inventory_id']));

        if ($count<=0){
            $this->__apilog['result']['msg'] = sprintf('[%s]盘点明细行数不于0不允许完成', $inventory_bn);
            return false;
        }
        $filter = array(
            'inventory_id'  =>  $inv['inventory_id'],
            'inventory_bn'  =>  $inv['inventory_bn'],
            'branch_id'     =>  $inv['branch_id'],
        );

        return $filter;
    }

    /**
     * 盘点单确认
     * @param
     */
    public function confirm($params){
        $inventory_bn = $params['inventory_bn'];
        $invMdl = app::get('o2o')->model('inventory');

        if(!$params['store_bn']){
             $this->__apilog['result']['msg'] = '门店编码必填';
            return false;
        }
        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }
        

        $inv = $invMdl->db_dump(array('inventory_bn' => $inventory_bn), 'inventory_id,inventory_bn,branch_id,status');

        if (!in_array($inv['status'],array('4'))){

            $this->__apilog['result']['msg'] = sprintf('[%s]盘点单状态未新建完成', $inventory_bn);
            return false;
        }

        $filter = array(
            'inventory_id'  =>  $inv['inventory_id'],
            'inventory_bn'  =>  $inv['inventory_bn'],
            'branch_id'     =>  $inv['branch_id'],
        );

        return $filter;

    }

    /**
     * 盘点单取消
     * @param
     */
    public function cancel($params){

        $this->__apilog['title']       = '盘点单取消';
        $this->__apilog['original_bn'] = $params['inventory_bn'];

        if(!$params['store_bn']){
             $this->__apilog['result']['msg'] = '门店编码必填';
            return false;
        }

        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }
        $filter = array(
            'branch_id'     =>  $branch['branch_id'],
            'inventory_bn'  =>  $params['inventory_bn'],
        );
        
        if (!$params['inventory_bn']){
            $this->__apilog['result']['msg'] = '盘点单号不可为空';
            return false;
        }

        $invMdl = app::get('o2o')->model('inventory');

        $inv = $invMdl->db_dump(array('inventory_bn' => $params['inventory_bn']), 'inventory_id,status');

        $filter['inventory_id'] = (int) $inv['inventory_id'];
      
        if (!in_array($inv['status'],array('1','4'))){
            $this->__apilog['result']['msg'] = '盘点单状态不可以取消';
            return false;
        }
       
        return $filter;
    }

    /**
     * 盘点单计数
     * @param
     */
    public function count($params){
        $this->__apilog['title']       = '门店详情查询';
        $this->__apilog['original_bn'] = $params['inventory_bn'];

        $filter = array(
            
        );

        if(!$params['store_bn']){
             $this->__apilog['result']['msg'] = '门店编码必填';
            return false;
        }

        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }
        $filter['branch_id'] = $branch['branch_id'];
        if ($params['inventory_bn']) {
            $invMdl = app::get('o2o')->model('inventory');

            $inv = $invMdl->db_dump(array('inventory_bn' => $params['inventory_bn']), 'inventory_id');

            $filter['inventory_id'] = (int) $inv['inventory_id'];
        }

        return $filter;
    }

    /**
     * 盘点单列表
     * @param
     */
    public function listing($params){
        $this->__apilog['title']       = '盘点列表查询';
        $this->__apilog['original_bn'] = '';

        $filter = array();

        if(!$params['store_bn']){
             $this->__apilog['result']['msg'] = '门店编码必填';
            return false;
        }

        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }

        if (isset($params['page_no']) && !is_numeric($params['page_no'])) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误', $params['page_no']);

            return false;
        }
      

        $page_no = $params['page_no'] && $params['page_no'] > 0 ? $params['page_no'] : 1;

        if (isset($params['page_size']) && $params['page_size'] > self::MAX_LIMIT) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误，最大允许[%s]', $params['page_size'], self::MAX_LIMIT);

            return false;
        }
   

        $limit = $params['page_size'] ? $params['page_size'] : 100;

        if ($params['inventory_bn']) {
            $invMdl = app::get('o2o')->model('inventory');

            $inv = $invMdl->db_dump(array('inventory_bn' => $params['inventory_bn']), 'inventory_id');
            if (!$inv) {
                $this->__apilog['result']['msg'] = sprintf('[%s]盘点单号不存在', $params['inventory_bn']);

                return false;
            }

            $filter['inventory_id'] = $inv['inventory_id'];
        }

        $filter['material_bn']  =  $params['material_bn'];
        $filter['offset']    = ($page_no - 1) * $limit;
        $filter['limit']     = $limit;
        $filter['branch_id'] = $branch['branch_id'];
        
        if ($params['status']){
            $filter['status']  =  $params['status'];
        }

        return $filter;
    }

    /**
     * 盘点单详情
     * @param
     */
    public function get($params){
        $this->__apilog['title']       = '门店详情查询';
        $this->__apilog['original_bn'] = $params['inventory_bn'];

        if(!$params['store_bn']){
             $this->__apilog['result']['msg'] = '门店编码必填';
            return false;
        }

        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }
        if (isset($params['page_no']) && !is_numeric($params['page_no'])) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误', $params['page_no']);

            return false;
        }
        
 
        $page_no = $params['page_no'] && $params['page_no'] > 0 ? $params['page_no'] : 1;

        if (isset($params['page_size']) && $params['page_size'] > self::MAX_LIMIT) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误，最大允许[%s]', $params['page_size'], self::MAX_LIMIT);

            return false;
        }
   
        $limit = $params['page_size'] ? $params['page_size'] : 100;
        if (!$params['inventory_bn']) {
            $this->__apilog['result']['msg'] = '缺少盘点单编码';
            return false;
        }

        $filter = array(
            'inventory_bn' => $params['inventory_bn'],
            'branch_id'    => $branch['branch_id'],
            'bill_type'    => $params['bill_type'],
            'material_bn'  => $params['bn'],
            'limit'        => $limit,
            'offset'       => ($page_no - 1) * $limit,
        );

        return $filter;
    }

    /**
     * 盘点单明细计数
     * @param
     */
    public function countitems($params){
        $this->__apilog['title']       = '盘点单明细总数';
        $this->__apilog['original_bn'] = $params['inventory_bn'];

        $filter = array();

        if(!$params['store_bn']){
             $this->__apilog['result']['msg'] = '门店编码必填';
            return false;
        }

        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }
        
        if (!$params['inventory_bn']){
            $this->__apilog['result']['msg'] = '盘点单号不可为空';
            return false;
        }
        
        $filter['branch_id'] = $branch['branch_id'];

        $invMdl = app::get('o2o')->model('inventory');

        $inv = $invMdl->db_dump(array('inventory_bn' => $params['inventory_bn']), 'inventory_id');

        $filter['inventory_id'] = (int) $inv['inventory_id'];
        
        if($params['bn']){
            $filter['bn'] = $params['bn'];
        }
       
        return $filter;
    }

        
}

?>