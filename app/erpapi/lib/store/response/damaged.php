<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_damaged extends erpapi_store_response_abstract
{
    

    /**
     * 残次品登记
     * @param array $params
     */
    public function add($params){
        $this->__apilog['title'] = '门店仓库报残';

        if (!$params['store_bn']){
            $this->__apilog['result']['msg'] = '缺少门店编码';
            return false;
        }
       
        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }
        $branch_id = $branch['branch_id'];
       
        
        // 明细处理
        $items = @json_decode($params['items'], true);
        if (!$items) {
            $this->__apilog['result']['msg'] = '缺少报残明细';
            return false;
        }

        $bmFilter['material_bn'] = array_filter(array_column($items, 'bn'));
        if (!$bmFilter['material_bn']) {
            $this->__apilog['result']['msg'] = '缺少物料编码';

            return false;
        }

        $materialList = app::get('material')->model('basic_material')->getList('bm_id,material_bn', $bmFilter);
        $materialList = array_column($materialList, null, 'material_bn');

        foreach ($items as $key => $value) {
            if (!$value['bn']) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：缺少物料编码', $key);
                return false;
            }

            if (!is_numeric($value['nums']) || $value['nums'] <= 0) {
                unset($items[$key]);
                continue;
               
            }

            $bm = $materialList[$value['bn']];
            if (!$bm) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：[%s]物料编码不存', $key, $value['bn']);
            }

            $items[$key]['product_id'] = $bm['bm_id'];
        }

        if (!$items) {
            $this->__apilog['result']['msg'] = '缺少报残明细';
            return false;
        }

        $filter['items']     = $items;
        $filter['branch_id'] = $branch_id;

        return $filter;
    }


    /**
     * 残次品统计
     * @param  
     * @return
     */
    public function count($params){
        $this->__apilog['title']       = '门店仓库报残总数';
        $this->__apilog['original_bn'] = '';
        
        $filter = array();
        if (!$params['store_bn']){
            $this->__apilog['result']['msg'] = '缺少门店编码';
            return false;
        }
       
        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }
        $branch_id = $branch['branch_id'];
      
        if ($params['bn']){
            $filter['bn'] = $params['bn'];
        }
        
        $filter['status'] = 1;
        $filter['original_type'] = 'damaged_add';
      
        $filter['branch_id'] = $branch_id;
        $filter['appropriation_no'] = '0';

        $branchMdl = app::get('ome')->model('branch');
        $branch = $branchMdl->db_dump(array('branch_id' => $branch_id, 'check_permission' => 'false'), 'branch_id');
        if (!$branch) {
            $this->__apilog['result']['msg'] = sprintf('[%s]仓库不存在', $params['branch_id']);
            return false;
        }

        return $filter;
    }

    /**
     * 残次品列表
     * 
     */

    public function listing($params){
        $this->__apilog['title']       = '门店仓库报残列表';
        $this->__apilog['original_bn'] = '';
        if (!$params['store_bn']){
            $this->__apilog['result']['msg'] = '缺少门店编码';
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
        
        $filter = array();

        if ($params['bn']){
            $filter['material_bn'] = $params['bn'];
        }
       
        $filter['status'] = 1;
        $filter['original_type'] = 'damaged_add';
        $filter['appropriation_no'] = '0';
        
        $page_no = $params['page_no'] && $params['page_no'] > 0 ? $params['page_no'] : 1;
        
        $limit = $params['page_size'] ? $params['page_size'] : self::MAX_LIMIT;

        $filter['branch_id'] = $branch['branch_id'];
        $filter['offset'] = ($page_no - 1) * $limit;
        $filter['limit'] = $limit;


        return $filter;
    }

    /**
     *  创建残次单
     *  
     */

    public function create($params){
        $this->__apilog['title']       = '残次调拨单创建';
        $this->__apilog['original_bn'] = '';
      
        if (!$params['from_store']) {
            $this->__apilog['result']['msg'] = '缺少出货门店';
            return false;
        }
        
        if (!$params['to_store']) {
            $this->__apilog['result']['msg'] = '缺少到货门店';
            return false;
        }
        $approMdl = app::get('taoguanallocate')->model('appropriation');
        //支持编辑调拔单
        
        if ($params['appropriation_no']){
            
            $appro = $approMdl->db_dump(array('appropriation_no' => $params['appropriation_no'],'process_status'=>array(0)));
            
            if (!$appro) {
                $this->__apilog['result']['msg'] = sprintf('[%s]调拨单不存在', $params['appropriation_no']);
                return false;
            }
            
            $appropriation_id = $appro['appropriation_id'];
            
            
        }
        if ($params['from_store'] == $params['to_store']){
            $this->__apilog['result']['msg'] = '出货门店和到货门店不可相同';
            return false;
        }
        $branchMdl = app::get('ome')->model('branch');
        
        // 出货仓处理
        $from_store = $branchMdl->db_dump(array('branch_bn' => $params['from_store'], 'check_permission' => 'false'), 'branch_id,name,branch_bn,b_type');
        if (!$from_store) {
            $this->__apilog['result']['msg'] = sprintf('出货门店[%s]：未维护', $params['from_store']);
            return false;
        }
        
        // 进货仓处理
        $to_store = $branchMdl->db_dump(array('branch_bn' => $params['to_store'], 'check_permission' => 'false'), 'branch_id,name,branch_bn,b_type');
        if (!$to_store) {
            $this->__apilog['result']['msg'] = sprintf('出货门店[%s]：未维护', $params['to_store']);
            
            return false;
        }
        
        // 明细处理
        $mdl_artificial_freeze = app::get('material')->model('basic_material_stock_artificial_freeze');
        $filter =  $mdl_artificial_freeze->_filter(['branch_id'=>$from_store['branch_id'],'original_type'=>'damaged_add','status'=>1,'appropriation_no'=>0],'a');
        $sql = 'SELECT sum(a.freeze_num) as nums,b.material_name,b.material_bn as bn,b.material_spu FROM `sdb_material_basic_material_stock_artificial_freeze` as a left join `sdb_material_basic_material` as b on a.bm_id = b.bm_id  WHERE '.$filter . ' GROUP BY a.bm_id ORDER BY a.freeze_time DESC ';
      
        $items = kernel::database()->select($sql);
        
       
        if (empty($items)) {
            $this->__apilog['result']['msg'] = '暂无内容处理';
            return false;
        }
        
        $bn_list = array();
        foreach ((array)$items as $key => $value) {
            if (!$value['bn']) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：缺少物料编码', $key);
                return false;
            }
            
            if (!is_numeric($value['nums']) || $value['nums'] <= 0) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：数量异常', $key);
                return false;
            }
            
            $bn_list[] = $value['bn'];
        }
        
        $bm_list = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name', array('material_bn' => $bn_list));
        
        $bm_list    = array_column($bm_list, null, 'material_bn');
        $bm_id_list = array_column($bm_list, 'bm_id');
        
        // 查询出库仓库存
        // 需要区分门店和电商仓
        $bpModel       = app::get('ome')->model('branch_product');
       
        $product_store = array();
        
            
        foreach($bpModel->getList('product_id,branch_id,store,store_freeze', array('product_id' => $bm_id_list, 'branch_id' => $from_store['branch_id'])) as $value){
            
            $product_store[$value['branch_id']][$value['product_id']] = $value['store'];
        }

        
        foreach($bpModel->getList('product_id,branch_id,store,store_freeze', array('product_id' => $bm_id_list, 'branch_id' => $to_store['branch_id'])) as $value){
            
            $product_store[$value['branch_id']][$value['product_id']] = $value['store'];
        }
      
        
        
        // 判断出货仓是否有库存
        foreach ((array)$items as $key => $value) {
            $bm_id = $bm_list[$value['bn']]['bm_id'];
            $material_name = $bm_list[$value['bn']]['material_name'];
            
            if (!$bm_id) {
                $this->__apilog['result']['msg'] = sprintf('行明细物料[%s]：未维护', $key);
                
                return false;
            }
            
            if ($value['nums'] > $product_store[$from_store['branch_id']][$bm_id]) {
                $this->__apilog['result']['msg'] = sprintf('[%s]：库存不足', $value['bn']);
                
                return false;
            }
            
            $items[$key]['product_id']      = $bm_id;
            $items[$key]['material_name']   = $material_name;
            $items[$key]['material_bn']     = $value['bn'];
            $items[$key]['from_branch_id']  = $from_store['branch_id'];
            $items[$key]['to_branch_id']    = $to_store['branch_id'];
            $items[$key]['num']             = $value['nums'];
            $items[$key]['to_branch_num']   = $product_store[$to_store['branch_id']][$bm_id];
            $items[$key]['from_branch_num'] = $product_store[$from_store['branch_id']][$bm_id];
            $items[$key]['bill_type']       = 'returndefective';
            
            unset($items[$key]['nums']);
        }
        $oper = kernel::single('ome_func')->getDesktopUser();
        $data = array(
            'from_branch_id'   => $from_store['branch_id'],
            'to_branch_id'     => $to_store['branch_id'],
            'bill_type'        => 'returndefective',
            'items'            => $items,
            'memo'             => $params['memo'],
            'op_name'          => $oper['op_name'],
            'appropriation_id' => $appropriation_id,
            'appropriation_no' => $params['appropriation_no'],
            'process_status'   => 1,
            'branch_id'        => $from_store['branch_id'],
        );
        
        return $data;
    }
        
}

?>