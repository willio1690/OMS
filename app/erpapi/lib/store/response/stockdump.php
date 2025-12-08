<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_stockdump extends erpapi_store_response_abstract
{
    

    /**
     * 
     * @param  $params [参数] method store.stockdump.add
     * @return array
     */
    public function add($params){
        $this->__apilog['title']       = $this->__channelObj->store['name'].'转储单创建';
        $this->__apilog['original_bn'] = $params['stockdump_bn'];

        if (!$params['from_store']) {
            $this->__apilog['result']['msg'] = '缺少出货门店';
            return false;
        }

        if (!$params['to_store']) {
            $this->__apilog['result']['msg'] = '缺少到货门店';
            return false;
        }
        if (!$params['from_branch']) {
            $this->__apilog['result']['msg'] = '缺少出货仓库';
            return false;
        }

        if (!$params['to_branch']) {
            $this->__apilog['result']['msg'] = '缺少到货仓库';
            return false;
        }

        $stockdumpMdl = app::get('console')->model('stockdump');
        
            
        $stockdumps = $stockdumpMdl->db_dump(array('stockdump_bn' => $params['stockdump_bn']),'stockdump_id');

        if ($stockdumps) {
            $this->__apilog['result']['msg'] = sprintf('[%s]转储单已存在', $params['stockdump_bn']);
            return false;
        }
        

        if ($params['from_branch'] == $params['to_branch']){
            $this->__apilog['result']['msg'] = '出货仓和到货仓不可相同';
            return false;
        }
        $branchMdl = app::get('ome')->model('branch');

        // 出货仓处理
        $from_store = $branchMdl->db_dump(array('branch_bn' => $params['from_branch'], 'check_permission' => 'false'), 'branch_id,name,branch_bn,b_type');
        if (!$from_store) {
            $this->__apilog['result']['msg'] = sprintf('出货门店[%s]：未维护', $params['from_branch']);
            return false;
        }

        // 进货仓处理
        $to_store = $branchMdl->db_dump(array('branch_bn' => $params['to_branch'], 'check_permission' => 'false'), 'branch_id,name,branch_bn,b_type');
        if (!$to_store) {
            $this->__apilog['result']['msg'] = sprintf('出货门店[%s]：未维护', $params['to_branch']);

            return false;
        }

        $storeMdl = app::get('o2o')->model('store');
        $from_physics = $storeMdl->db_dump(array('store_bn' => $params['from_store']),'store_id');
        
        $to_physics = $storeMdl->db_dump(array('store_bn' => $params['to_store']),'store_id');
        
        // 明细处理
        $items = @json_decode($params['items'], true);
        if (!$items) {
            $this->__apilog['result']['msg'] = '缺少明细';
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

            $product_store[$value['branch_id']][$value['product_id']] = $value['store'] - $value['store_freeze'];
        }
           

      
        foreach($bpModel->getList('product_id,branch_id,store,store_freeze', array('product_id' => $bm_id_list, 'branch_id' => $to_store['branch_id'])) as $value){
            
            $product_store[$value['branch_id']][$value['product_id']] = $value['store'] - $value['store_freeze'];
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
            $items[$key]['name']            = $material_name;
            $items[$key]['bn']              = $value['bn'];
            $items[$key]['num']             = $value['nums'];
            $items[$key]['appro_price']     = 0;
            unset($items[$key]['nums']);
        }
        $oper = kernel::single('ome_func')->getDesktopUser();
        $data = array(
            'from_branch_id'        => $from_store['branch_id'],
            'to_branch_id'          => $to_store['branch_id'],
            'from_physics_id'       => $from_physics['store_id'], 
            'to_physics_id'         => $to_physics['store_id'], 
            'from_branch_code'      => $params['from_branch'],
            'to_branch_code'        => $params['to_branch'],
            'items'                 => $items,
            'memo'                  => $params['memo'],
          
            'op_name'               => $oper['op_name'],
            'stockdump_bn'          => $params['stockdump_bn'],
            'status'                => 'FINISH',
            'source_from'           => 'store',
            'transfer_channel'      => 'store_yk',
           
        );

        return $data;
    }

     
}

?>