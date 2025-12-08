<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_appropriation
{
    static public $process_status = array(
        '0' => '新建',
        '1' => '待审核',
        '2' => '已审核',
        '3' => '待发货',
        '4' => '待收货',
        '5' => '完成',
        '6' => '取消',
        '7' => '拒绝',
    );

    public static $bill_type = array(
        'normal'          => '普通',
        'transfer'        => '调拔',
        'replenishment'   => '补货',
        'returndefective' => '残次品退仓',
        'returnnormal'    => '正品退仓',
    );

    public $o2obill_type = array(
        'o2otransfer' => '门店调拨单',
        
        'returnnormal' => '门店退仓单',
        'replenishment' => '门店订货单',
        'o2oprepayed' => '门店预订单',
    );
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){
       
        $approMdl     = app::get('taoguanallocate')->model('appropriation');
        $approItemMdl = app::get('taoguanallocate')->model('appropriation_items');
        $isoObj = app::get('taoguaniostockorder')->model('iso');

        kernel::database()->beginTransaction();

        $rs = kernel::single('console_receipt_allocate')->create($params,$msg);

        if (!$rs) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => $msg);
        }

        kernel::database()->commit();

      
        $appros = $approMdl->db_dump(array('appropriation_no'=>$params['appropriation_no']),'appropriation_id');

     
        if(in_array($params['bill_type'],array('o2otransfer','o2oprepayed')) && $appros){//不跨门店之间 直接审核 跨店有审批 无审核 退仓:审核
            //判断是否相当组织如果相同自动审核
            //$orgflag = kernel::single('console_receipt_allocate')->getParentOrg($params['appropriation_no']);

            //if($orgflag){
                //finish
                

                //$this->finish(array('appropriation_id'=>$appros['appropriation_id']));
                //check
                $this->check(array('appropriation_id'=>$appros['appropriation_id']));
            //}
        }
        
        if($params['bill_type'] == 'returnnormal' && $appros){//退仓自动完成

            $stockout_id = kernel::single('console_receipt_allocate')->do_out_iostockorder($appros['appropriation_id'], $msg);
            
            if($stockout_id){
                $stockin_id = kernel::single('console_iostockdata')->allocate_out($stockout_id);
                kernel::single('console_iostockorder')->doCkeck($stockin_id, 1);
            }
             

        }
        $data = array('appropriation_no'=>$params['appropriation_no']);
        return array('rsp' => 'succ', 'data' => array());
       
    }


    /**
     * 调拔申请单审核 method=store.appropriation.check
     * @param  
     * @return 
     */
    public function check($params)
    {
        kernel::database()->beginTransaction();
        $approMdl = app::get('taoguanallocate')->model('appropriation');
        $filter = array('appropriation_id'=>$params['appropriation_id']);
        $filter['process_status'] = array('1');
        $affect_rows              = $approMdl->update(array('process_status' => 2, 'confirm_time' => time()), $filter);

        if (is_bool($affect_rows)) {
            kernel::database()->rollBack();
        
            return array('rsp' => 'fail', 'msg' => '审核失败：单据异常');
        }
    
        $appropriation_id = $params['appropriation_id'];
    
        $appro_info = $approMdl->getList('*', ['appropriation_id' => $appropriation_id], 0, -1);
    
        $iso_id = kernel::single('console_receipt_allocate')->do_out_iostockorder($appropriation_id, $msg);
    
        kernel::single('console_iostockorder')->doCkeck($iso_id, 0);
        kernel::database()->commit();
        $isos = app::get('taoguaniostockorder')->model('iso')->dump(array('iso_id' => $iso_id),'iso_bn');
        return array('rsp' => 'succ', 'data' => array('appropriation_no'=>$params['appropriation_no'],'iso_bn'=>$isos['iso_bn']),'msg'=>'审核成功');
    }

   
    
    /**
     * 调拔申请单取消 method=front.o2o.appropriation.cancel
     * @param  
     * @return 
     */
    public function cancel($filter){
        $process_status = $filter['process_status'];
        $approMdl = app::get('taoguanallocate')->model('appropriation');
        $filter['process_status'] = array('0','1','2');
        $appropriation_no = $filter['appropriation_no'];
        unset($filter['appropriation_no']);
        
        if(in_array($process_status,array('2','3'))){

            $rs = $this->cancelIso($appropriation_no);
         
            if(!$rs){

               return array('rsp' => 'fail', 'msg'=>'取消失败');
            }

        }
        
        $approMdl->update(array('process_status'=>6),$filter);
     
        return array('rsp' => 'succ', 'data' => array(),'msg'=>'取消成功');
    }

    /**
     *  调拔单创建完成 method=store.appropriation.finish
     * @param  
     * @return 
     */
    public function finish($filter){
        kernel::database()->beginTransaction();
        $approMdl = app::get('taoguanallocate')->model('appropriation');
        $approItemMdl = app::get('taoguanallocate')->model('appropriation_items');
    
        $filter['process_status'] = array('0');

        $affect_rows = $approMdl->update(array('process_status'=>1),$filter);
        if (is_bool($affect_rows)) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => '单据异常');
        }
        
        kernel::database()->commit();
        return array('rsp' => 'succ', 'data' => array());
    }

    public function listing($params)
    {

        $filter = $params['filter'];
        $approMdl     = app::get('taoguanallocate')->model('appropriation');
        $approItemMdl = app::get('taoguanallocate')->model('appropriation_items');

        $offset = $params['offset'];
        $limit = $params['limit'];
        $count =$approMdl->count($filter);
        $appro_list = $approMdl->getList('*', $filter, $offset, $limit);
       
        if (!$appro_list) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $storeMdl = app::get('o2o')->model('store');

        $appro_id_list = array_column($appro_list, 'appropriation_id');

        $appro_no_list = array_column($appro_list, 'appropriation_no');
        $items_list    = $approItemMdl->getList('*', array('appropriation_id' => $appro_id_list));
        $appr_itemlist = array();
        foreach($items_list as $v){

            $appr_itemlist[$v['appropriation_id']][] = $v;


        }
        $from_branch_id = array_column($items_list, 'from_branch_id');
        $to_branch_id   = array_column($items_list, 'to_branch_id');
       
        $branchMdl   = app::get('ome')->model('branch');
        $from_branch = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => $from_branch_id, 'check_permission' => 'false'));

        $from_branch = array_column($from_branch, null, 'branch_id');

        $to_branch = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => $to_branch_id, 'check_permission' => 'false'));
        $to_branch = array_column($to_branch, null, 'branch_id');
        
        $from_physics_ids = array_column($appro_list, 'from_physics_id');
        $to_physics_ids = array_column($appro_list, 'to_physics_id');

        $from_physiclist = $storeMdl->getlist('store_bn,store_id',array('store_id'=>$from_physics_ids));
        $from_physicslist = array_column($from_physiclist, null, 'store_id');
       
        $to_physiclist = $storeMdl->getlist('store_bn,store_id',array('store_id'=>$to_physics_ids));
        $to_physiclist = array_column($to_physiclist, null, 'store_id');
        
        $isoMdl = app::get('taoguaniostockorder')->model('iso');
        //4调拨入库
        $in_iso_list = $isoMdl->getList('iso_id,appropriation_no,check_status,iso_status,original_id,iso_bn', array('appropriation_no' => $appro_no_list, 'type_id' => '4'));
        
        $iniso_list = array_column($in_iso_list, null, 'appropriation_no');  //因入是出所以用的调拔单号 


        $in_iso_ids = array_column($in_iso_list,  'iso_id');       
        //40调拨出库
        $out_iso_list = $isoMdl->getList('iso_id,appropriation_no,check_status,iso_status,original_id,iso_bn,logi_no,logi_code', array('original_id' => $appro_id_list, 'type_id' => '40'));
       
        $outiso_list = array_column($out_iso_list, null, 'original_id');   
           
        $out_iso_ids = array_column($out_iso_list,  'iso_id');    


        $all_iso_ids = array_merge($in_iso_ids,$out_iso_ids);

        $isoitemsMdl = app::get('taoguaniostockorder')->model('iso_items');

        $isoitemsList = $isoitemsMdl->getlist('iso_id,bn,product_id,normal_num,defective_num',array('iso_id'=>$all_iso_ids));
        $iso_itemsList = array();
        foreach($isoitemsList as $v){
            $iso_itemsList[$v['iso_id']][$v['product_id']] = $v;

        }
        $data = array();
        foreach ($appro_list as $key => $appro) {
            
            $iniso = (array) $iniso_list[$appro['appropriation_no']];
            $iniso_id = $iniso['iso_id'];
            
            $outiso = (array) $outiso_list[$appro['appropriation_id']];
            $outiso_id = $outiso['iso_id'];

            
            $from_physics_id    = $appro['from_physics_id'];
            $to_physics_id      = $appro['to_physics_id'];

            $from_physics = $from_physics_id ? $from_physicslist[$from_physics_id]['store_bn'] : '';

            $to_physics = $to_physics_id ? $to_physiclist[$to_physics_id]['store_bn'] : '';
            $appr_items = $appr_itemlist[$appro['appropriation_id']];

            $bill_type = $appro['bill_type'];
            if($bill_type=='returnnormal'){
                $to_physics = $to_branch[$appro['to_branch_id']]['branch_bn'];
            }
            
            if($bill_type=='replenishment'){
                $from_physics = $from_branch[$appro['from_branch_id']]['branch_bn'];
            } 
            $items = array();
            foreach ($appr_items as $item) {

                $product_id = $item['product_id'];
                $in_nums = $iso_itemsList[$iniso_id] ? $iso_itemsList[$iniso_id][$product_id]['normal_num']+$iso_itemsList[$iniso_id][$product_id]['defective_num'] : 0;
                $out_nums = $iso_itemsList[$outiso_id] ? $iso_itemsList[$outiso_id][$product_id]['normal_num']: 0;
                $barcode = kernel::single('material_codebase')->getBarcodeBybn($item['bn']); 
                $items[] = array(
                    'barcode'           => $barcode, 
                    'material_bn'       => $item['bn'],
                    'material_name'     => $item['product_name'],
                    'nums'              => $item['num'],
                    'in_nums'           => $in_nums,
                    'out_nums'          => $out_nums,
                );
            }
            $delivery_time = $appro['delivery_time'] ? date('Y-m-d H:i:s', $appro['delivery_time']) : '';
            $data[] = array(
                'appropriation_no'      => $appro['appropriation_no'],
                'business_bn'           => $appro['business_bn'], 
                'bill_type'             => $this->o2obill_type[$appro['bill_type']],
                'create_time'           => date('Y-m-d H:i:s', $appro['create_time']),
                'operator_name'         => $appro['operator_name'],
                'delivery_time'         => $delivery_time,
                'memo'                  => $appro['memo'],
                'from_branch_bn'        => $from_branch[$appro['from_branch_id']]['branch_bn'],
                'from_branch_name'      => $from_branch[$appro['from_branch_id']]['name'],

                'to_branch_bn'          => $to_branch[$appro['to_branch_id']]['branch_bn'],
                'to_branch_name'        => $to_branch[$appro['to_branch_id']]['name'],
                'from_physics'          => $from_physics,
                'to_physics'            =>$to_physics,
                'process_status_value'  =>self::$process_status[$appro['process_status']],
                'logi_no'               => $outiso['logi_no'],
                'logi_code'             => $outiso['logi_code'],
                'items'                 => $items,
            );


        }

        

        return array('rsp' => 'succ', 'data' => array('lists' => $data, 'count' => $count));
    }

    /**
     * cancelIso
     * @param mixed $appropriation_no appropriation_no
     * @return mixed 返回值
     */
    public function cancelIso($appropriation_no){
        $isoMdl = app::get('taoguaniostockorder')->model('iso');
        //4调拨入库
        $isos = $isoMdl->db_dump(array('appropriation_no' => $appropriation_no, 'type_id' => '40'),'iso_bn,iso_id,branch_id,check_status,iso_status');
       
        if($isos && $isos['check_status']=='2'){
            $data = array(
                'io_bn'     =>  $isos['iso_bn'],
                'branch_id' =>  $isos['branch_id'],
            );
            $rs = kernel::single('console_event_trigger_otherstockout')->cancel($data);

            if($rs['rsp']=='succ'){
                $iso_id = $isos['iso_id'];
                $isoMdl->update(array('iso_status' => 4), array('iso_id' => $iso_id));
                $stockObj = kernel::single('console_receipt_stock');
                if ($stockObj->checkExist($isos['iso_bn'])) {
                    $stockObj->clear_stockout_store_freeze($isos, 'FINISH');
                }

                return true;
            }else{
                return false;
            }

        }else{
            return true;
        }
    }
}

?>