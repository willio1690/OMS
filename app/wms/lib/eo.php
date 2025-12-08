<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_eo{
    /*
     * 将采购单入库
     * 采购单入库会分配货位，生成供应商商品采购价历史记录
     * 更新库存
     */
   
    function save_eo($data)
    {
        $basicMaterialLib    = kernel::single('material_basic_material');
       
        $oPo = app::get('purchase')->model("po");
        $supplierObj = app::get('purchase')->model("supplier");
        $oPo_items = app::get('purchase')->model("po_items");
        
        $oEo = app::get('purchase')->model("eo");
        $oEo_items = app::get('purchase')->model("eo_items");
        $oCredit_sheet = app::get('purchase')->model("credit_sheet");
        
        $oProduct_batch = app::get('purchase')->model("branch_product_batch");
        $po_id = $data['po_id'];
        $branch_id = $_POST['branch_id'] ? $_POST['branch_id'] : $data['branch_id'];
        $Po = $oPo->dump($po_id,'*');
        $supplier = $supplierObj->dump($Po['supplier_id'],'*');
        $expire_bm_info = $data['expire_bm_info'];

        $batchs = $this->processBatchs($branch_id,$expire_bm_info);

        $amount=0;
        //start入库
        $history_data= array();
        foreach($data['ids'] as $i){
            $v = intval($data['entry_num'][$i]);
            $k = $i;
            $Po_items = $oPo_items->dump($k,'price,product_id,num,status,name,spec_info,bn');
            
            $Products    = $basicMaterialLib->getBasicMaterialExt($Po_items['product_id']);
            
            $amount+=$v*$Po_items['price'];
            $item_memo = $data['item_memo'][$k];

            if($batchs[$Po_items['product_id']]){
                $batch = array_values($batchs[$Po_items['product_id']]);
            }
            $eo_items[$Po_items['product_id']]=array(
                'product_id' => $Po_items['product_id'],
                'name' => $Po_items['name'],
                'spec_info' => $Po_items['spec_info'],
                'bn' => $Po_items['bn'],
                'unit' => $Products['unit'],
                'price' => $Po_items['price'],
                'purchase_num' => $Po_items['num'],
                'nums' => $v,
                'is_new' => $data['is_new'][$k],
                'memo' => $item_memo,
                'batch'=>$batch,
              );

           //为供应商与商品建立关联
           if($Po['supplier_id'] && $Products['bm_id']){
                $supplier_goods = array(
                    'supplier_id' => $Po['supplier_id'],
                    'bm_id' => $Products['bm_id']
                );
                $su_goodsObj = app::get('purchase')->model('supplier_goods');
                
                //关联关系不存在则插入
                $supGoodsData    = $su_goodsObj->getList('*', array('supplier_id'=>$Po['supplier_id'], 'bm_id'=>$Products['bm_id']), 0, 1);
                if(empty($supGoodsData))
                {
                    $su_goodsObj->save($supplier_goods);
                }
           }
           
            $history_data[]=array('product_id'=>$Po_items['product_id'],'purchase_price'=>$Po_items['price'],'store'=>$v,'branch_id'=>$Po['branch_id']);
            //更新采购单数量
            $po_items_data[] = array(
                'item_id'=>$k,
                'in_num'=>$v,
                'num'=>$v,
                'status'=>$Po_items['status'],
                'item_memo'=>addslashes($item_memo),
                'product_id' => $Po_items['product_id']
                );
        }

        //追加备注信息
        $memo = array();
        $op_name = kernel::single('desktop_user')->get_name();
        $newmemo =  htmlspecialchars($data['memo']);
        $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
        $memo = serialize($memo);

        $iostock_instance =  kernel::single('console_iostockorder');
        $eo_data = array (
                'iostockorder_name' => date('Ymd').'入库单',
                'supplier' => $supplier['name'],
                'supplier_id' => $Po['supplier_id'],
                'branch' => $Po['branch_id'],
                'bill_type'=>'po',
                'type_id' => siso_receipt_iostock::PURCH_STORAGE,
                'iso_price' => $Po['delivery_cost'],
                'memo' => $newmemo,
                'operator' => $data['operator'],
                'products' => $eo_items,
                'original_bn' => $Po['po_bn'],
                'original_id' => $po_id,
                'business_bn'=>$Po['po_bn'],
                'confirm' => 'Y',
                'po_type' => $Po['po_type'],
                'arrival_no' => $data['arrival_no'],
                 );
        if ( method_exists($iostock_instance, 'save_iostockorder') ){
            $eo_data['eo_id'] = $iostock_instance->save_iostockorder($eo_data, $msg);
            $eo_data['eo_bn'] = $iostock_instance->getIoStockOrderBn();
        }

        //日志备注
        $log_msg = '对编号为（'.$Po['po_bn'].'）的采购单进行采购入库，生成一张入库单编号为:'.$eo_data['eo_bn'];

        //更新采购单状态
        foreach($po_items_data as $ke=>$va){
            $oPo->db->exec('UPDATE sdb_purchase_po_items SET in_num=IFNULL(in_num,0)+'.$va['in_num'].' WHERE item_id='.$va['item_id']);
            //更新对应状态
            $new_Po_items = $oPo_items->dump($va['item_id'],'in_num,out_num,num');
            $status = 1;
            if($new_Po_items['num']>$new_Po_items['in_num']+$new_Po_items['out_num']){
                $status = 2;
            }else if($new_Po_items['num']==$new_Po_items['in_num']+$new_Po_items['out_num']){
                $status=3;
            }
            if ($va['item_memo']) $update_memo = ",memo='".$va['item_memo']."'";
            $oPo->db->exec(" UPDATE `sdb_purchase_po_items` SET `status`='".$status."'$update_memo WHERE item_id='".$va['item_id']."'");
        }
        $storeManageLib = kernel::single('ome_store_manage');
        $storeManageLib->loadBranch(array('branch_id' => $branch_id));
        $params                    = array();
        $params['node_type']       = 'changeArriveStore';
        $params['params']          = array(
            'obj_id' => $po_id, 
            'branch_id' => $branch_id, 
            'obj_type' => 'purchase',
            'operator' => '-'
        );
        $params['params']['items'] = $po_items_data;
        $storeManageLib->processBranchStore($params, $err_msg);
        //保存入库单
        $eorder_data = array(
                'eo_id'       => $eo_data['eo_id'],
                'supplier_id' => $eo_data['supplier_id'],
                'eo_bn'       => $eo_data['eo_bn'],
                'po_id'       => $po_id,
                'amount'      => $amount,
                'entry_time'  => time(),
                'arrive_time' => $Po['arrive_time'],
                'operator'    => kernel::single('desktop_user')->get_name(),
                'branch_id'   => $branch_id,
                'status'      => $status,

            );
       $oEo->save($eorder_data);
       $new_Po = $oPo->db->selectrow('SELECT SUM(num) as total_num,SUM(in_num) as total_in_num,SUM(out_num) AS total_out_num FROM sdb_purchase_po_items WHERE po_id='.$po_id);
       if($new_Po['total_num']>$new_Po['total_in_num']+$new_Po['total_out_num']){
           $po_data['eo_status'] =2;
       }else{
           $po_data['eo_status'] =3;
           if ($Po['po_status']==1){
                $po_data['po_status'] =4;
           }
           //取消在途
           $storeManageLib = kernel::single('ome_store_manage');
           $storeManageLib->loadBranch(array('branch_id' => $branch_id));
           $params                    = array();
           $params['node_type']       = 'deleteArriveStore';
           $params['params']          = array(
               'obj_id' => $po_id, 
               'branch_id' => $branch_id, 
               'obj_type' => 'purchase',
           );
           $storeManageLib->processBranchStore($params, $err_msg);
       }
       $po_data['po_id'] =$po_id;
       $oPo->save($po_data);

      

       //供应商商品采购价历史记录
       foreach($history_data as $k2=>$v2){

            $v2['supplier_id']=$eo_data['supplier_id'];
            $v2['eo_id'] =$eo_data['eo_id'];
            $v2['eo_bn'] =$eo_data['eo_bn'];
            $v2['purchase_time']=time();
            $v2['in_num'] = $v2['store'];
            $oProduct_batch->save($v2);
       }
       //--采购入库日志记录

       $log_msg .= '<br/>生成了供应商商品采购历史价格记录表';
       $opObj = app::get('ome')->model('operation_log');
       $opObj->write_log('purchase_storage@purchase', $po_id, $log_msg);

       return $eo_data['eo_id'];

    }


    /**
     * 处理Batchs
     * @param mixed $branch_id ID
     * @param mixed $expire_bm_info expire_bm_info
     * @return mixed 返回值
     */
    public function processBatchs($branch_id,$expire_bm_info){
        $batchs = [];

        $iostockLib = kernel::single('wms_event_trigger_otherinstorage');
        foreach($expire_bm_info as $v){

           $v= json_decode($v,true);

            foreach($v as $vv){
                $expire_bn = $vv['expire_bn'];
                $storagelife = $iostockLib->getlifedetail($branch_id,$vv['bm_id'],$expire_bn);
                $batchs[$vv['bm_id']][$vv['expire_bn']] = array(

                    'purchase_code'     => $vv['expire_bn'],

                    'product_time'      => strtotime($vv['production_date']),
                    'expire_time'       => $storagelife['expiring_date'],
                    'normal_defective'  => 'normal',
                    'num'               => $vv['in_num'],

                );


            }
        }
        return $batchs;


    }
}
