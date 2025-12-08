<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_iso
{
    

    static public $in_status = array(
        1 => '未入库',
        2 => '部分入库',
        3 => '全部入库',
        4 => '取消',
    );
    static public $out_status = array(
        1 => '未出库',
        2 => '部分出库',
        3 => '全部出库',
        4 => '取消',
    );

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter){
       

        $rs = kernel::single('erpapi_front_response_process_o2o_iso')->count($filter);

        return $rs;


    }




    /**
     * cancel
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function cancel($params){
        
        $data = array(
            'io_bn'           => $params['iso_bn'],  
            'io_status'       => 'CANCEL',
           
        );

        $rs = kernel::single('console_event_receive_transferStockOut')->outStorage($data);

        return $rs;
    }


    /**
     * 检查
     * @param mixed $filter filter
     * @return mixed 返回验证结果
     */
    public function check($filter)
    {
        kernel::database()->beginTransaction();

        $isoMdl = app::get('taoguaniostockorder')->model('iso');
        $iso    = $isoMdl->db_dump($filter['iso_id']);
       
        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);

        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');

        // 需要判断可用库存是否足够
        $iso_items = $isoItemMdl->getlist('bn,nums,product_id', array('iso_id' => $iso['iso_id']));

        $affect_rows = $isoMdl->update(array('check_status' => '2','confirm_time'=>time()), array('iso_id' => $iso['iso_id']));

        if (is_bool($affect_rows)) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => '确认失败：单据异常');
        }

        if ($io == '0') {
            $storeManageLib = kernel::single('ome_store_manage');
            $storeManageLib->loadBranch(array('branch_id' => $iso['branch_id']));

            $params = array(
                'node_type' => 'checkStockout',
                'params'    => array(
                    'iso_id'    => $iso['iso_id'],
                    'branch_id' => $iso['branch_id'],
                    'items'     => $iso_items,
                ),
            );
            if ($iso['is_quickly'] != 'true') {
                $processResult = $storeManageLib->processBranchStore($params, $err_msg);
                if (!$processResult) {
                    kernel::database()->rollBack();
        
                    return array('rsp' => 'fail', 'msg' => '确认失败：' . $err_msg);
                }
            }
            $approMdl     = app::get('taoguanallocate')->model('appropriation');
            $filter = array('appropriation_no'=>$iso['appropriation_no']);
            $approMdl->update(array('process_status'=>3),$filter);
        } else {

            if ($iso['type_id'] == '4') {
                $storeManageLib = kernel::single('ome_store_manage');
                $storeManageLib->loadBranch(array('branch_id' => $iso['branch_id']));

                $params = array(
                    'node_type' => 'changeArriveStore',
                    'params'    => array(
                        'branch_id' => $iso['branch_id'],
                        'items'     => $iso_items,
                        'operator'  => '+',
                    ),
                );
                $storeManageLib->processBranchStore($params, $err_msg);

            }
        }

        kernel::database()->commit();

        return array('rsp' => 'succ', 'data' => array());
    }

    /**
     * 出入库单确认
     * 
     * @return void
     * @author
     * */
    public function confirm($iso)
    {
        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);
        if ($io == '0') {
            // 叫物流
           // $result = $this->ready($iso);

            //if ($result['rsp'] == 'fail') {
                //return $result;
            //}

            $items = array();
            foreach ($iso['items'] as $value) {
                if($value['normal_num']>0){
                    $items[] = array(
                        'bn'        => $value['bn'],
                        'num'       => $value['normal_num'],
                        'sn_list'   => $value['sn_list'],
                        'batch_code'=> $value['batch_code'],
                    );
                }
                
            }

            $iso['items'] = $items;
        }

        $data = array(
            'io_bn'        => $iso['iso_bn'],
            'io_status'    => $iso['io_status'],
            'operate_time' => date('Y-m-d H:i:s'),
            'items'        => $iso['items'],
        );

        if ($iso['type_id'] == '4' || $iso['type_id'] == '40') {
            $data['io_type'] = 'ALLCOATE';
        } else {
            $data['io_type'] = 'OTHER';
        }

        if ($io == '1') {
            return kernel::single('console_event_receive_iostock')->stockin_result($data);
        } else {
            
            $rs = kernel::single('console_event_receive_iostock')->stockout_result($data);
            $isos = app::get('taoguaniostockorder')->model('iso')->dump(array('type_id'=>'4','appropriation_no'=>$iso['appropriation_no'],'original_id' => $iso['iso_id']),'iso_bn,iso_id');

            unset($rs['msg_code']);
            $data = array('appropriation_no'=>$iso['appropriation_no'],'iso_bn'=>$isos['iso_bn']);

            $rs['data'] = $data;
            //自动审核入库
            //$checkRS=$this->check(array('iso_id'=>$isos['iso_id'],'type_id'=>4,'iso_bn'=>$isos['iso_bn']));
            
            //推送
            // if($checkRS['rsp'] == 'succ'){
                //kernel::single('console_event_trigger_otherstockin')->create(array('iso_id' => $isos['iso_id']), false);
            // }
            return $rs;
        }
    }

   
        /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params){
       
        $filter = $params['filter'];     
        $offset = $params['offset'];
        $limit = $params['limit'];
        
        $isoMdl     = app::get('taoguaniostockorder')->model('iso');
        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');
        $approMdl     = app::get('taoguanallocate')->model('appropriation');
        $branchMdl  = app::get('ome')->model('branch');

        $count =$isoMdl->count($filter);
        $iso_list = $isoMdl->getList('*', $filter, $offset, $limit);

        $branch_id      = array_column($iso_list, 'branch_id');
        $extrabranch_id = array_column($iso_list, 'extrabranch_id');

        $branch_list = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => array_merge((array) $branch_id, (array) $extrabranch_id), 'check_permission' => 'false'));
        $branch_list = array_column($branch_list, null, 'branch_id');

        $iso_id = array();

        $iso_ids = array_column($iso_list, 'iso_id');

        $data = array();
        $isoItemsDetailObj  = app::get('taoguaniostockorder')->model('iso_items_detail');

        foreach ($iso_list as $key => $value) {

            $appropriation_no = $value['appropriation_no'];

            $appro = $approMdl->db_dump(array('appropriation_no'=>$appropriation_no),'from_branch_id,from_physics_id');
            $from_branch_id = $appro['from_branch_id'];
            $from_branch = $branchMdl->db_dump(array('branch_id' => $from_branch_id, 'check_permission' => 'false'),'branch_id,branch_bn,name');
            $bill_type = $value['bill_type'];



            $io = kernel::single('ome_iostock')->getIoByType($value['type_id']);
            $branchs = $branch_list[$value['branch_id']];
            
            $item_list = $isoItemMdl->getList('product_id,product_name as material_name,bn as material_bn,nums,normal_num,defective_num', array('iso_id' => $value['iso_id']));
            

            if(in_array($bill_type,array('replenishment'))){

                //找大仓出库单
                $out_iso = $isoMdl->dump(array('appropriation_no'=>$appropriation_no,'type_id'=>'40'),'iso_id');

                $detailList = $isoItemsDetailObj->getList('product_id,nums,batch_code,product_date,expire_date,extendpro',['iso_id'=>$out_iso['iso_id']]);
            }
            
    
            $packages = array();
            if($detailList){
                foreach ($detailList as $k => $item) {
                    if($item['extendpro']){

                        $extendpro = unserialize($item['extendpro']);

                        if($extendpro['package_code']){
                            $packages[$item['product_id']] = $extendpro['package_code'];

                        }
                        
                    }
                    
                }
               
            }

            foreach($item_list as $ik=>$iv){

                $material_bn = $iv['material_bn'];
                $barcode = kernel::single('material_codebase')->getBarcodeBybn($material_bn);
                $item_list[$ik]['barcode'] = $barcode;

                $product_id = $iv['product_id'];

                if($packages[$product_id]){
                    $item_list[$ik]['batch_code'] = $packages[$product_id];
                }

            }
            $data[$key] = array(
                'business_bn'       => $value['business_bn'],
                'from_physics'      => $from_branch['branch_bn'],
                'from_branch_bn'    => $from_branch['branch_bn'],
                
                'iso_bn'            => $value['iso_bn'],
            
                'iso_status'      => self::$in_status[$value['iso_status']],
                //'process_status'  => $value['process_status'],
                'oper'            => $value['oper'],
                'operator'        => $value['operator'],
                'branch_name'     => $branchs['name'],
                'branch_bn'   =>      $branchs['branch_bn'],
                'logi_no'         =>    $value['logi_no'],  
                'last_modified'   => $value['complete_time'] ? date('Y-m-d H:i:s', $value['complete_time']) : date('Y-m-d H:i:s', $value['create_time']),
                'items'           => $item_list,
            );
            
        }
        

        return array('rsp' => 'succ', 'data' => array('lists' => $data, 'count' => $count));
        
    }
    

}

?>