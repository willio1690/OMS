<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_po_return_import {
    
    function run(&$cursor_id,$params)
    {
        $basicMaterialLib    = kernel::single('material_basic_material');
        
       $adata = $params['sdfdata'];
       
       //所有的供应商信息
       $rs = app::get('purchase')->model('supplier')->getList();
       foreach($rs as $v) {
        $suppliers[$v['bn']] = $v['supplier_id'];
       }
        
        $items = $adata['return_items'];
        foreach ($items as $v){
            $ids[] = $v['product_id'];
            $total += $v['price'] * $v['num'];
        }
        
        $oPurchase = app::get('purchase')->model('returned_purchase');
        $data['rp_bn'] = $adata['rp_bn'];
        $data['name'] = $adata['rp_bn'].'采购退货单';
        $data['supplier_id'] = intval($suppliers[$adata['supplier_id']]);
        $data['operator'] = $adata['operator'];
        $data['emergency'] = 'false';
        $data['branch_id'] = intval($adata['branch_id']);
        $data['amount'] = bcadd((string)$adata['delivery_cost'], (string)$total, 3);
        $data['product_cost'] = $total;
        $data['delivery_cost'] = floatval($adata['delivery_cost']);
        $data['logi_no'] = $adata['logi_no'];
        $data['returned_time'] = time();
        $data['rp_type'] = 'eo';
        $data['po_type'] = 'cash';
        $memo = $adata['memo'];
        if ($memo){
            $op_name = kernel::single('desktop_user')->get_login_name();
            $newmemo = array();
            $newmemo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$memo);
        }
        $data['memo'] = serialize($newmemo);
        $rs = $oPurchase->save($data);
        if ($rs){
            $rp_id = $data['rp_id'];
            $oPurchase_items = app::get('purchase')->model("returned_purchase_items");
            
            if ($ids)
            foreach ($items as $v){//插入采购退货单详情
                
                $p    = $basicMaterialLib->getBasicMaterialExt($v['product_id']);
                
                $row['rp_id'] = $rp_id;
                $row['product_id'] = $v['product_id'];
                $row['num'] = $v['num'];
                $row['price'] = $v['price'];
                $row['bn'] = $p['material_bn'];
                $row['barcode'] = $p['barcode'];
                $row['name'] = $p['material_name'];
                $row['spec_info'] = $p['specifications'];
                $oPurchase_items->save($row);
                $row = null;
            }
            //--生成退货单日志记录
            $log_msg = '生成了编号为:'.$rp_bn.'的采购退货单';
            $opObj = app::get('ome')->model('operation_log');
            $opObj->write_log('purchase_refund@purchase', $rp_id, $log_msg);
        }else{
            echo('failed');
        }
        return false;
       /*end*/
   }
    
    // 已经废弃不用
    function run2(&$cursor_id,$params)
    {
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        
        $returnObj = app::get($params['app'])->model($params['mdl']);
        $rpiObj = app::get('purchase')->model('returned_purchase_items');
        $poObj = app::get('purchase')->model('po');
        $piObj = app::get('purchase')->model('po_items');
        $eiObj = app::get('purchase')->model('eo_items');
        $bppObj = app::get('ome')->model('branch_product_pos');
        
        $returnSdf = $params['sdfdata'];
        
        $returnsdf = $returnSdf;
        unset($returnsdf['return_items']);
        
        $returnObj->save($returnsdf);
        $rp_id = $returnsdf['rp_id'];
        $amount = 0;
        foreach ($returnSdf['return_items'] as $v){
            $new_Eo_items = $eiObj->dump($v['eo_item_id'],'entry_num,out_num');
            if ($new_Eo_items['entry_num'] <= $new_Eo_items['out_num']){
                continue;
            }
            $new_Eo_items['out_num'] += $v['num'];
            $risdf = $v;
            $risdf['rp_id'] = $rp_id;
            $rpiObj->save($risdf);
            
            $amount += $v['num']*$v['price'];
            
            $bpp = $bppObj->dump(array('product_id'=>$v['product_id'],'pos_id'=>$v['pos_id']));
            //更新货位库存
            if ($bpp){
                $num = $bpp['store']-$v['num'];
                
                $libBranchProductPos->change_store($bpp['branch_id'],$v['product_id'],$v['pos_id'],$num);
            }
            
            //更新po_items表的入库数量 
            $eiObj->db->exec("UPDATE sdb_purchase_po_items SET out_num=out_num+".$v['num']." WHERE item_id=".$v['po_item_id']);
            //更新eo_items表的入库数量 
            $eiObj->db->exec("UPDATE sdb_purchase_eo_items SET out_num=out_num+".$v['num']." WHERE item_id=".$v['eo_item_id']);
            /*扣库存*/
            $eiObj->db->exec('UPDATE sdb_purchase_branch_product_batch SET out_num=out_num+'.$v['num'].',store=store-'.$v['num'].' WHERE eo_id='.$returnSdf['object_id'].' AND product_id='.$v['product_id']);
        }
         /*start生成退款单*/
        $refundObj = app::get('purchase')->model('purchase_refunds');
        $refund_data=array(
            'operator'=>$returnSdf['op_name'],
            'refund_bn'=>$refundObj->gen_id(),
            'add_time'=>time(),
            'supplier_id'=>$returnSdf['supplier_id'],
            'po_type'=>$returnSdf['po_type'],
            'type'=>'eo',
            'refund'=>$amount+$returnSdf['delivery_cost'],
            /*运费*/
            'delivery_cost'=>$returnSdf['delivery_cost'],
            'product_cost'=>$amount,
            'rp_id'=>$rp_id,
            'op_id'=>$returnSdf['op_id'],
           );
        $refundObj->save($refund_data);
        
        $po_data['po_status'] =3;
        $po_data['po_id'] =$returnSdf['po_id'];
        $poObj->save($po_data);
        
        $return_data['amount'] = $amount+$returnSdf['delivery_cost'];
        $return_data['product_cost'] = $amount;
        $return_data['rp_id'] = $rp_id;
        $returnObj->save($return_data);
        
        return false;
    }
}
