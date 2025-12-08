<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_appropriation extends erpapi_store_response_abstract
{
    

    /**
     * @param 调拨单创建 method=store.appropriation.add
     * return
     */
    public function add($params){
      
        $this->__apilog['title']       = $this->__channelObj->store['name'].'调拨单创建';
        $this->__apilog['original_bn'] = $params['appropriation_no'];

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
        $approMdl = app::get('taoguanallocate')->model('appropriation');
        

            
        $appro = $approMdl->db_dump(array('appropriation_no' => $params['appropriation_no']),'appropriation_id');

        if ($appro) {
            $this->__apilog['result']['msg'] = sprintf('[%s]调拨单已存在', $params['appropriation_no']);
            return false;
        }
        

        if ($params['from_branch'] == $params['to_branch']){
            $this->__apilog['result']['msg'] = '出货仓库和到货仓库不可相同';
            return false;
        }

        //
       /* if($params['bill_type'] == 'returnnormal'){//门店补货根据路由选择补货仓

            $flow = app::get('o2o')->model('branch_flow')->dump([
            'to_store_bn' => $params['from_store'],
            ]);

            if(!$flow){
                $this->__apilog['result']['msg'] = '门店对应退货仓未维护';
                return false;
            }

            $channel = app::get('o2o')->model('channel')->dump([
            'id' => $flow['channel_id'],
            ],'reship_branch_bn');

            if(!$channel){
                $this->__apilog['result']['msg'] = '门店对应退货仓未设置';
                return false;
            }

            $params['to_branch'] = $channel['reship_branch_bn'];
        }*/
        
        $branchMdl = app::get('ome')->model('branch');

        // 出货仓处理
       
        $from_store = $branchMdl->db_dump(array('branch_bn'=>$params['from_branch'], 'check_permission'=> 'false'), 'branch_id,name,branch_bn,b_type,type');
        if (!$from_store) {
            $this->__apilog['result']['msg'] = sprintf('出货仓[%s]：未维护', $params['from_branch']);
            return false;
        }

        // 进货仓处理
        $to_store = $branchMdl->db_dump(array('branch_bn' =>$params['to_branch'], 'check_permission' => 'false'), 'branch_id,name,branch_bn,b_type,type');
        if (!$to_store) {
            $this->__apilog['result']['msg'] = sprintf('到货仓[%s]：未维护', $params['to_branch']);

            return false;
        }

        //判断单据转换类型操作
        $bill_type = $params['bill_type'];
        
        
        $storeMdl = app::get('o2o')->model('store');
        $from_physics = $storeMdl->db_dump(array('store_bn'=> $params['from_store']),'store_id');
        
        $to_physics = $storeMdl->db_dump(array('store_bn' => $params['to_store']),'store_id');
        
        $materialMdl = app::get('material')->model('basic_material');

        // 明细处理
        $items = @json_decode($params['items'], true);
        if (!$items) {
            $this->__apilog['result']['msg'] = '缺少调拨明细';
            return false;
        }

        $bn_list = array();
        foreach ((array)$items as $key => $value) {
            
            if (!is_numeric($value['nums']) || $value['nums'] <= 0) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：数量异常', $key);
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
            if (!$value['bn']) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：物料编码或条码必须有一个不为空', $key);
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
            $items[$key]['material_name']   = $material_name;
            $items[$key]['material_bn']     = $value['bn'];
            $items[$key]['from_branch_id']  = $from_store['branch_id'];
            $items[$key]['to_branch_id']    = $to_store['branch_id'];
            $items[$key]['num']             = $value['nums'];
            $items[$key]['to_branch_num']   = $product_store[$to_store['branch_id']][$bm_id];
            $items[$key]['from_branch_num'] = $product_store[$from_store['branch_id']][$bm_id];
            $items[$key]['package_code']      = $value['batch_code'];
          

            unset($items[$key]['nums']);
        }
        $oper = kernel::single('ome_func')->getDesktopUser();

        if($params['receiver_province'] && $params['receiver_city'] && $params['receiver_district']){

            $area = $params['receiver_province'] .'/'. $params['receiver_city'] .'/'. $params['receiver_district'];
            kernel::single('ome_func')->region_validate($area);
        }
        
        $data = array(
            'from_branch_id'   => $from_store['branch_id'],
            'to_branch_id'     => $to_store['branch_id'],
            'from_physics_id'  => $from_physics['store_id'], 
            'to_physics_id'    => $to_physics['store_id'], 
            'bill_type'        => $params['bill_type'],
            'items'            => $items,
            'memo'             => $params['memo'],
            'op_name'          => $params['application_name'] ? $params['application_name'] : $oper['op_name'],
            'appropriation_no' => $params['appropriation_no'],
            'process_status'   => '1',
            //'movement_code'    => $params['movement_code'],
            'source_from'      => 'pos',
            'logi_code'        => $params['logi_code'],
            'logi_no'          => $params['logi_no'],
            'receiver_province'=> $params['receiver_province'],
            'receiver_city'     => $params['receiver_city'],
            'receiver_district' => $params['receiver_district'],
            'extra_ship_area'   => $area,
            'extra_ship_addr'   => $params['receiver_address'],
            'extra_ship_mobile'   => $params['receiver_mobile'],
            'extra_ship_name'     => $params['receiver_name'],
        );

        return $data;

       
    }


    /**
     * 调拔申请单审核 method=store.appropriation.check
     * @param  
     * @return 
     */
    public function check($params){

     
        $this->__apilog['title']       = '调拔申请单审核';
        $this->__apilog['original_bn'] = $params['appropriation_no'];

        if (!$params['appropriation_no']) {
            $this->__apilog['result']['msg'] = '缺少调拨单号';

            return false;
        }
        if (!$params['store_bn']){
            $this->__apilog['result']['msg'] = '缺少门店编码';
            return false;
        }
       
        $branch = $this->getBranchIdByBn($params['store_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }

        $approMdl = app::get('taoguanallocate')->model('appropriation');
        $appro = $approMdl->db_dump(array('appropriation_no' => $params['appropriation_no']),'appropriation_id,process_status,bill_type');

        if (!$appro) {
            $this->__apilog['result']['msg'] = sprintf('[%s]调拨单不存在', $params['appropriation_no']);
            return false;
        }

        if (!in_array($appro['process_status'],array('1'))){
            $this->__apilog['result']['msg'] = sprintf('[%s]当前调拨单状态不可操作', $params['appropriation_no']);
            return false;
        }

        $filter = array(
            'appropriation_id' => $appro['appropriation_id'],
            'bill_type'        => $appro['bill_type'],
            'appropriation_no' => $params['appropriation_no'],
        );

        return $filter;
    }


   
   
    /**
     * 调拔申请单取消 method=store.appropriation.cancel
     * @param  
     * @return 
     */
    public function cancel($params){

        $this->__apilog['title']       = '调拔申请单拒绝';
        $this->__apilog['original_bn'] = $params['appropriation_no'];

        if (!$params['appropriation_no']) {
            $this->__apilog['result']['msg'] = '缺少调拨单号';

            return false;
        }
        if (!$params['store_bn']){
            //$this->__apilog['result']['msg'] = '缺少门店编码';
           // return false;
        }


        $branchMdl = app::get('ome')->model('branch');
        $branch = $branchMdl->db_dump(array('branch_bn' => $params['store_bn'], 'check_permission' => 'false'), 'branch_id');
        if (!$branch){
            //$this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
           // return false;
        }
        $branch_id = $branch['branch_id'];
        $approMdl = app::get('taoguanallocate')->model('appropriation');
        $appro = $approMdl->db_dump(array('appropriation_no' => $params['appropriation_no']),'appropriation_id,process_status,bill_type,process_status');

        if (!$appro) {
            $this->__apilog['result']['msg'] = sprintf('[%s]调拨单不存在', $params['appropriation_no']);
            return false;
        }

        if(in_array($appro['process_status'],array('6','7'))){
            $this->__apilog['result']['msg'] = '已取消';
            return true;
        }
        if (!in_array($appro['process_status'],array('0','1','2'))){
            $this->__apilog['result']['msg'] = sprintf('[%s]当前调拨单状态不可取消', $params['appropriation_no']);
            return false;
        }
        $filter = array(
            'appropriation_id' => $appro['appropriation_id'],
            'appropriation_no' => $params['appropriation_no'],
            'bill_type'        => $appro['bill_type'],
            'is_quickly'       => $appro['is_quickly'],
            'process_status'   => $appro['process_status'], 

        );
        return $filter;
    }

    /**
     *退仓单列表查询
     *
     * @return void
     * @author
     **/
    public function listing($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '退仓单列表查询';
        $this->__apilog['original_bn'] = '';

        if (!$params['store_bn'] && !$params['bill_type']){
            $this->__apilog['result']['msg'] = '门店编码或bill_type必须有一个不为空!';
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

        if($params['start_time'] && $params['end_time']){



            $filter =  [
                'create_time|between' => [
                    strtotime($params['start_time']),
                    strtotime($params['end_time']),
                ],
            
            ];
        }else{
            if($params['end_time'] && empty($params['start_time'])){

                $filter = [
                'create_time|lthan' => strtotime($params['end_time'])];
            }
            if($params['start_time'] && empty($params['end_time'])){

                $filter = [
                'create_time|than' => strtotime($params['start_time'])];
            }

        }

        
        $branchMdl = app::get('ome')->model('branch');
        if($params['store_bn']){
            $branch = $branchMdl->db_dump(array('branch_bn' => $params['store_bn'], 'check_permission' => 'false'), 'branch_id');
            if (!$branch){
                $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
                return false;
            }
            $branch_id = $branch['branch_id'];

            $filter['from_branch_id'] = $branch_id;

        }
        
        if($params['branch_bn']){
            $branch = $branchMdl->db_dump(array('branch_bn' => $params['branch_bn'], 'check_permission' => 'false'), 'branch_id');
            if (!$branch){
                $this->__apilog['result']['msg'] = sprintf('[%s]仓库不存在', $params['branch_bn']);
                return false;
            }
            $branch_id = $branch['branch_id'];
           
            if($filter['from_branch_id']){
                $filter['from_branch_id'] = array_merge(array($filter['from_branch_id']),array($branch_id) );
            }else{
                $filter['from_branch_id'] = $branch_id;
            }
            

        }
        $approLib = kernel::single('erpapi_store_response_process_appropriation');
        $bill_types = $approLib->o2obill_type;
        
        $bill_types = array_keys($bill_types);
        if($params['bill_type']){

            if(!in_array($params['bill_type'],$bill_types)){
                $this->__apilog['result']['msg'] = '业务类型必须包含在已定义类型中:'.implode(',',$bill_types);
                return false;
            }
        }


        $filter['bill_type'] = $params['bill_type'];

        $approMdl = app::get('taoguanallocate')->model('appropriation');

        if ($params['appropriation_no']) {
            $appro = $approMdl->db_dump(array('appropriation_no' => $params['appropriation_no']));

            if (!$appro) {
                $this->__apilog['result']['msg'] = sprintf('[%s]调拨单不存在', $params['appropriation_no']);
                return false;
            }

            $filter['appropriation_id'] = $appro['appropriation_id'];
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

        $page_size = $params['page_size'] ? $params['page_size'] : self::MAX_LIMIT;

        $offset = ($page_no - 1) * $page_size;
        

       
        return ['filter' => $filter, 'limit' => $page_size, 'offset' => $offset];
    }
    
}

?>