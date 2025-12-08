<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_po_eo_import {
    function run(&$cursor_id,$params)
    {
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        
        $eoObj = app::get($params['app'])->model($params['mdl']);
        $eiObj = app::get('purchase')->model('eo_items');
        $poObj = app::get('purchase')->model('po');
        $piObj = app::get('purchase')->model('po_items');
        $csObj = app::get('purchase')->model('credit_sheet');
        $bppObj = app::get('ome')->model('branch_product_pos');
        
        $payObj = app::get('purchase')->model('purchase_payments');
        $eoSdf = $params['sdfdata'];
        
        $eosdf = $eoSdf;
        unset($eosdf['eo_items']);
        unset($eosdf['po_type']);
        unset($eosdf['delivery_cost']);
        
        $eoObj->save($eosdf);
        $eo_id = $eosdf['eo_id'];
        $amount = 0;
        foreach ($eoSdf['eo_items'] as $v){
            $new_Po_items = $piObj->dump($v['po_item_id'],'in_num,out_num,num');
            if ($new_Po_items['num'] <= ($new_Po_items['in_num']+$new_Po_items['out_num'])){
                
                continue;//如果采购单
            }
            $new_Po_items['in_num'] += $v['entry_num'];
            $eisdf = $v;
            unset($eisdf['goods_id']);
            unset($eisdf['po_item_id']);
            $eisdf['eo_id'] = $eo_id;
            $eisdf['is_new'] = 'false';
            $eiObj->save($eisdf);
            
            $amount += $v['entry_num']*$v['price'];
            //更新在途库存
            $poObj->updateBranchProductArriveStore($eoSdf['branch_id'], $v['product_id'], $v['entry_num'], '-');
            $bpp = $bppObj->dump(array('product_id'=>$v['product_id'],'pos_id'=>$v['pos_id']));
            //更新货位库存
            if ($bpp){
                $num = $bpp['store']+$v['entry_num'];
                
                $libBranchProductPos->change_store($bpp['branch_id'],$v['product_id'],$v['pos_id'],$num);
            }else {
                $tmp = array('product_id'=>$v['product_id'],'pos_id'=>$v['pos_id'],'store'=>$v['entry_num']);
                $bppObj->save($tmp);
                $libBranchProductPos->count_store($v['product_id'],$eoSdf['branch_id']);
            }
            
            //更新po_items表的入库数量 
            $eiObj->db->exec("UPDATE sdb_purchase_po_items SET in_num=IFNULL(in_num,0)+".$v['entry_num']." WHERE item_id=".$v['po_item_id']);
            
            $status = 1;
            if($new_Po_items['num']>$new_Po_items['in_num']+$new_Po_items['out_num']){
                $status = 2;
            }else if($new_Po_items['num']<=$new_Po_items['in_num']+$new_Po_items['out_num']){
                $status=3;
            }
            //更新采购单明细状态 
            $piObj->db->exec(" UPDATE `sdb_purchase_po_items` SET `status`='".$status."' WHERE item_id='".$v['po_item_id']."'");
            
            $v2['supplier_id'] = $eoSdf['supplier_id'];
            $v2['eo_id'] = $eo_id;
            $v2['eo_bn'] = $eoSdf['eo_bn'];
            $v2['purchase_time'] = time();
            $v2['in_num'] = $v['entry_num'];
            $v2['store'] = $v['entry_num'];
            $v2['product_id'] = $v['product_id'];
            $v2['purchase_price'] = $v['purchase_price'];
            $v2['branch_id'] = $eoSdf['branch_id'];
            /*供应商商品采购价历史记录*/
            app::get('purchase')->model('branch_product_batch')->save($v2);
            
            if($v['product_id']!=''){
                $supplier_goods = array(
                    'supplier_id' => $eoSdf['supplier_id'],
                    'bm_id' => $v['product_id']
                );
                app::get('purchase')->model('supplier_goods')->save($supplier_goods);//end
           }
        }
        
         /*生成赊购单start*/
        if($eoSdf['po_type'] == 'credit'){
            $delivery_cost = $eoSdf['delivery_cost'];
            $credit_data = array(
               'cs_bn'=>$csObj->gen_id(),
               'add_time'=>time(),
               'supplier_id'=>$eoSdf['supplier_id'],
               'operator'=>$eoSdf['op_name'],
               'op_id'=>$eoSdf['op_id'],
               'payable'=>$amount+$delivery_cost,
               'eo_id'=>$eo_id,
               'delivery_cost'=>$delivery_cost,
               'product_cost'=>$amount
            );
    
            /*累加总费用*/
            $delivery_cost = is_numeric($delivery_cost) ? $delivery_cost : '0';
            if ($delivery_cost)
                $poObj->db->exec('UPDATE `sdb_purchase_po` SET `amount`=`amount`+'.$delivery_cost.' WHERE `po_id`='.$eoSdf['po_id'].' ');
            
            $csObj->save($credit_data);
        
        }
        
        $new_Po = $poObj->db->selectrow('SELECT SUM(num) as total_num,SUM(in_num) as total_in_num,SUM(out_num) AS total_out_num FROM sdb_purchase_po_items WHERE po_id='.$eoSdf['po_id']);
        
        if($new_Po['total_num']>$new_Po['total_in_num']+$new_Po['total_out_num']){
            $po_data['eo_status'] =2;
        }else{
            $po_data['eo_status'] =3;
        }
        
        $po_data['po_id'] =$eoSdf['po_id'];
        $poObj->save($po_data);
        
        $eo_data['amount'] = $amount;
        $eo_data['eo_id'] = $eo_id;
        $eoObj->save($eo_data);
        
        return false;
    }
}
