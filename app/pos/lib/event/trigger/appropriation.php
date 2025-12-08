<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_event_trigger_appropriation
{
    /**
     * 
     * 订货单审批
     * @param 
     */
    public function check($appropriation_id)
    {

        $approMdl   = app::get('taoguanallocate')->model('appropriation');
        
        $appros = $approMdl->dump(array('appropriation_id'=>$appropriation_id),'appropriation_no,bill_type,from_branch_id');
        $store_id = kernel::single('ome_branch')->isStoreBranch($appros['from_branch_id']);
        $channel_type = 'store';
        $channel_id = $store_id;

        $params = array(

            'appropriation_no'  =>  $appros['appropriation_no'],
            'bill_type'         =>  $appros['bill_type'],
        );

       
        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->appropriation_check($params);
       
        if($result['rsp'] == 'succ'){
            $updateData = array(
                'sync_status'=>'1',

            );
            $rs = [true,'成功'];
        }else{
            $updateData = array(
                'sync_status'=>'2',

            );
            $rs = [false,'失败'];
        }
        
        return $rs;
    }
    
  
    /**
     * audit
     * @param mixed $appropriation_id ID
     * @return mixed 返回值
     */
    public function audit($appropriation_id){
        $approMdl   = app::get('taoguanallocate')->model('appropriation');
        
        $appros = $approMdl->dump(array('appropriation_id'=>$appropriation_id),'appropriation_no,bill_type,from_branch_id');
        $store_id = kernel::single('ome_branch')->isStoreBranch($appros['from_branch_id']);
        $channel_type = 'store';
        $channel_id = $store_id;

        $params = array(

            'appropriation_no'  =>  $appros['appropriation_no'],
            'bill_type'         =>  $appros['bill_type'],
        );
        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->appropriation_audit($params);

  
        if($result['rsp'] == 'succ'){
            $updateData = array(
                'sync_status'=>'1',

            );
            $rs = [true,'成功'];
        }else{
            $updateData = array(
                'sync_status'=>'2',

            );
            $rs = [false,'失败'];
        }
       
        return $rs;
    }

    /**
     * confirmStockReturn
     * @param mixed $iso_id ID
     * @return mixed 返回值
     */
    public function confirmStockReturn($iso_id){
      
        $iso_data = $this->formatReturnData($iso_id);
        $channel_type = 'store';
        $channel_id = $iso_data['from_physics_id'];

        $rs = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->stockout_create($iso_data);


    }

    /**
     * formatReturnData
     * @param mixed $iso_id ID
     * @return mixed 返回值
     */
    public function formatReturnData($iso_id){
        $isoMdl = app::get('taoguaniostockorder')->model("iso");
        $isodataLib = kernel::single('console_iostockdata');
        $iostockObj = kernel::single('siso_receipt_iostock');
        $itemsMdl = app::get('taoguaniostockorder')->model("iso_items");
        $isos = $isoMdl->dump(array('iso_id'=>$iso_id),'iso_bn,branch_id,type_id,create_time,memo,extrabranch_id,corp_id,supplier_id,appropriation_no,original_id,original_bn,bill_type,business_bn,physics_id,logi_code,logi_no,iso_status');

        if(!in_array($isos['iso_status'],array('3')) && $isos['bill_type']!='returnnormal'){
            return false;
        }
      
        
        $branch_detail = $isodataLib->getBranchByid($isos['branch_id']);
        $iso_items = $itemsMdl->getList('product_id,bn,product_name as name,price,defective_num,normal_num,iso_items_id',array('iso_id'=>$iso_id));
        $storeMdl = app::get('o2o')->model('store');
        $data = array(
            'io_bn'             =>  $isos['iso_bn'],
            'appropriation_no'  =>  $isos['appropriation_no'],
            'branch_bn'         =>  $branch_detail['branch_bn'],
            'branch_id'         =>  $isos['branch_id'],
            'branch_type'       =>  $branch_detail['type'],
            'storage_code'      =>  $branch_detail['storage_code'],
            'create_time'       =>  $isos['create_time'],
            'memo'              =>  $isos['memo'],
            'type_id'           =>  $isos['type_id'],
     
            'extrabranch_id'    =>  $isos['extrabranch_id'],
        
            'business_bn'       =>  $isos['business_bn'],
            'bill_type'         =>  $isos['bill_type'],
        );
        if($isos['appropriation_no']){

            //取from to门店信息
            $approMdl   = app::get('taoguanallocate')->model('appropriation');
            
            $appros = $approMdl->dump(array('appropriation_no'=>$isos['appropriation_no']),'appropriation_no,bill_type,from_physics_id,to_physics_id');

            $store_ids = [];
            $store_ids[] = $appros['from_physics_id'];
            $store_ids[] = $appros['to_physics_id'];
            
            $stores = $storeMdl->getlist('store_bn,store_id',array('store_id'=>$store_ids));
            $stores = array_column($stores, null, 'store_id');

            $data['from_physics'] = $stores[$appros['from_physics_id']]['store_bn'];
            $data['to_physics'] = $stores[$appros['to_physics_id']]['store_bn'];
        }

        $data['from_physics_id'] = $appros['from_physics_id'];
        $iostock_type = $iostockObj->getIoByType($isos['type_id']);
        $extrabranch_id = $isos['extrabranch_id'];
        if (in_array($isos['type_id'],array('4','40'))) {

            $extbranchs = $isodataLib->getBranchByid($Iso['extrabranch_id']);
            $data['ext_branch_bn'] = $extbranchs['branch_bn'];    
            $extrabranch_id = $isos['branch_id'];

        }

        if ($extrabranch_id){
            $extrabranch_detail = $isodataLib->getExtrabranch($extrabranch_id,$iostock_type,$Iso['type_id']);
            $data = array_merge($data,$extrabranch_detail);
        }

        foreach($iso_items as $k=>$v){
            $num = $v['normal_num'] + $v['defective_num'];
            $iso_items[$k]['num'] = $num;
        }
        $data['items'] = $iso_items;
       
        return $data;
    }
}
