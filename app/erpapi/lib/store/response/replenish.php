<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_replenish extends erpapi_store_response_abstract
{
   

    /**
     * @param 补货单创建 method=store.replenish.add
     * return
     */
    public function add($params){
      
        $this->__apilog['title']       = $this->__channelObj->store['name'].'补货单创建';
        $this->__apilog['original_bn'] = $params['task_bn'];

     
        

        if (!$params['task_bn']) {
            $this->__apilog['result']['msg'] = '缺少补货任务号';
            return false;
        }

        $suggestMdl         = app::get('console')->model("replenish_suggest");
        

            
        $suggest = $suggestMdl->db_dump(array('task_bn' => $params['task_bn']),'sug_id');

        if ($suggest) {
            $this->__apilog['result']['msg'] = sprintf('[%s]补货单已存在', $params['task_bn']);
            return false;
        }
        
        if (!$params['store_bn']) {
            $this->__apilog['result']['msg'] = '缺少门店编码';
            return false;
        }
        if (!$params['branch_bn']) {
            $this->__apilog['result']['msg'] = '缺少仓库编码';
            return false;
        }
        
        $out_branch_bn = $params['out_branch_bn'];
        if (!$out_branch_bn) {
            $this->__apilog['result']['msg'] = '缺少出货仓库编码';
            return false;
        }

        if($params['out_branch_bn']==$params['branch_bn']){

            $this->__apilog['result']['msg'] = 'out_branch_bn和branch_bn不可以是同一个';
            return false;
        }

        $bill_type = $params['bill_type'];
        $branchMdl = app::get('ome')->model('branch');


        $branchs = $branchMdl->db_dump(array('branch_bn' => $params['branch_bn'], 'check_permission' => 'false'), 'branch_id,name,branch_bn,b_type,is_ctrl_store');
        if (!$branchs) {
            $this->__apilog['result']['msg'] = sprintf('仓库[%s]：未维护', $params['branch_bn']);
            return false;
        }
    
        //是否管控库存
        if ($branchs && $branchs['is_ctrl_store'] != '1') {
            $this->__apilog['result']['msg'] = sprintf('仓库[%s]：不管控库存', $params['branch_bn']);
            return false;
        }
      
        // 明细处理
        $items = @json_decode($params['items'], true);
        if (!$items) {
            $this->__apilog['result']['msg'] = '缺少调拨明细';
            return false;
        }

        $bn_list = array();
        foreach ((array)$items as $key => $value) {

            if (!$value['bn'] && !$value['barcode']) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：物料编码或条码至少有一个', $key);
                return false;
            }

            if($value['barcode'] && empty($value['bn'])){
                $bn = kernel::single('material_codebase')->getBnBybarcode($value['barcode']);
                $items[$key]['bn'] = $bn;
                $value['bn'] = $bn;
                if(empty($bn)){
                    $this->__apilog['result']['msg'] = sprintf('行明细[%s]：条码不存在', $key);
                    return false;
                }
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

        $storeMdl = app::get('o2o')->model('store');
        $physics = $storeMdl->db_dump(array('store_bn' => $params['store_bn']),'store_id');
        
        
        $outBranch = $branchMdl->db_dump(array('branch_bn'=>$out_branch_bn, 'check_permission'=> 'false'), 'branch_id,name,branch_bn');
        
        if($outBranch){
            $bpModel       = app::get('ome')->model('branch_product');
            
            $product_store = array();
            foreach($bpModel->getList('product_id,branch_id,store,store_freeze', array('product_id' => $bm_id_list, 'branch_id' => $outBranch['branch_id'])) as $value){

                $product_store[$value['branch_id']][$value['product_id']] = $value['store'] - $value['store_freeze'];
            }

            foreach ((array)$items as $key => $value) {
                $bm_id = $bm_list[$value['bn']]['bm_id'];
                $material_name = $bm_list[$value['bn']]['material_name'];

               
                if ($value['nums'] > $product_store[$outBranch['branch_id']][$bm_id]) {
                    $this->__apilog['result']['msg'] = sprintf('[%s]：库存不足', $value['bn']);

                    return false;
                }
            }
        }
        $product_amount = 0;
        // 判断出货仓是否有库存
        foreach ((array)$items as $key => $value) {
            $bm_id = $bm_list[$value['bn']]['bm_id'];
            $material_name = $bm_list[$value['bn']]['material_name'];

            if (!$bm_id) {
                $this->__apilog['result']['msg'] = sprintf('行明细物料[%s]：未维护', $key);

                return false;
            }

            $items[$key]['product_id']      = $bm_id;
            $items[$key]['material_name']   = $material_name;
            $items[$key]['material_bn']     = $value['bn'];
           
            $items[$key]['nums']             = $value['nums'];
            $items[$key]['price']            = $value['price'];
            $product_amount+=$value['nums']*$value['price'];
        }
       
        $data = array(
            'task_bn'          => $params['task_bn'], 
            'apply_name'       => $params['apply_name'],
            'store_bn'         => $params['store_bn'],
            'branch_id'        => $branchs['branch_id'],  
            'physics_id'       => $physics['store_id'],
            'source'           => 'pos', 
            'memo'             => $params['memo'],
            'items'            => $items,
            'bill_type'        => $bill_type,
            'product_amount'   => $product_amount,
            'out_branch_bn'    => $out_branch_bn,
            'out_branch_id'    => $outBranch['branch_id'], 
            'check_auto'       => $params['check_auto'],
        );
       
        
        return $data;

       
    }


    /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params){
        $this->__apilog['title']       =  '订货单查询接口';
        $this->__apilog['original_bn'] = $this->__channelObj->store['server_bn'];

        if(empty($params['task_bn']) && empty($params['start_time']) && empty($params['end_time'])){

            $this->__apilog['result']['msg'] = '查询条件里任务号或者时间至少有一个不为空!';

            return false;
        }

        if ($params['start_time'] &&  !strtotime($params['start_time'])) {
            $this->__apilog['result']['msg'] = '开始时间格式不正确';

            return false;
        }

        if ($params['end_time'] && !strtotime($params['end_time'])) {
            $this->__apilog['result']['msg'] = '结束时间格式不正确';

            return false;
        }

        if ($params['page_size'] <= 0 || $params['page_size'] > 100) {
            $this->__apilog['result']['msg'] = '每页数量必须大于0小于等于100';
            return false;
        }

        if ($params['page_no'] <= 0) {
            $this->__apilog['result']['msg'] = '页码必须大于0';
            return false;
        }

        $task_bn = $params['task_bn'];

        if($task_bn){
            $filter = [
                'task_bn'   =>  $task_bn,
            ];
    
        }else{
            $filter = [
                'create_time|between' => [
                    strtotime($params['start_time']),
                    strtotime($params['end_time']),
                ],
            
            ];
        }
        
        if (!$params['store_bn']){
            //$this->__apilog['result']['msg'] = '缺少门店编码';
            //return false;
        }
        if ($params['store_bn']){
            $branch = $this->getBranchIdByBn($params['store_bn']);
            if (!$branch){
                $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
                return false;
            }
            $branch_id = $branch['branch_id'];
           
        }

        if($branch_id){
            $filter['branch_id'] = $branch_id;
        }
        
       
        $limit  = $params['page_size'];
        $offset = ($params['page_no'] - 1) * $limit;

        return ['filter' => $filter, 'limit' => $limit, 'offset' => $offset];

    }
   
    
}

?>