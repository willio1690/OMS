<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_delivery_freeze{

    /**
     * 发货单冻结
     * @param
     */
    public function add($dly_id){

        $deliveryObj = app::get('ome')->model('delivery');
        $delivery_detail = $deliveryObj->dump(array('delivery_id'=>$dly_id),'delivery_bn,branch_id,status');

        if (!in_array($delivery_detail['status'],array('cancel','back'))){
            //return true;
        }
        $freezeMdl  = app::get('material')->model('basic_material_stock_artificial_freeze');
        $freeze_list = $freezeMdl->dump(array('original_type'=>'deliverycancel','original_bn'=>$delivery_detail['delivery_bn']),'bmsaf_id');

        if ($freeze_list) return true;
      
        $oper = kernel::single('ome_func')->getDesktopUser();

        $itemsObj = app::get('ome')->model('delivery_items');

        $delivery_items = $itemsObj->getlist('*',array('delivery_id'=>$dly_id));
        $storeManageLib = kernel::single('ome_store_manage');
        $params = array();
        $params['node_type'] = "artificialFreeze";
        $storeManageLib->loadBranch(array('branch_id'=>$delivery_detail['branch_id']));

        //按数量为1重新组合
        
        $freeze_items = array();

        foreach($delivery_items as $v){
            $number = $v['number'];
            if($number>1){
                for($i=0;$i<$number;$i++){

                    $freeze_items[] = array(
                        'product_id'    =>  $v['product_id'],
                        'number'        =>  1,
                        'bn'            =>  $v['bn'],
                    );
                }
            }else{
                $freeze_items[] = $v;
            }
        }
       
        foreach($freeze_items as $value){

            $freeze = array(
                'branch_id'     => $delivery_detail['branch_id'],
                'product_id'    => $value['product_id'],
                'bm_id'         => $value['product_id'],
                'freeze_num'    => $value['number'],
                'freeze_reason' => sprintf('[%s]发货单取消冻结', $delivery_detail['delivery_bn']),
                'freeze_time'   => time(),
                'op_id'         => $oper['op_id'],
                'original_bn'   => $delivery_detail['delivery_bn'],
                'original_type' => 'deliverycancel',
                'bn'            => $value['bn'],
            );

            $freezeMdl->insert($freeze);

           
            //库存管控
            $params['params'][] = array_merge(array('obj_id'=>$freeze['bmsaf_id']),$freeze);
        }

        $storeManageLib->processBranchStore($params,$err_msg);

    }

    /*
    *  释放对应明细库存
    * 
    */
    public function unArtificialfreeze($data){
        $product_bns = array();
        foreach($data as $v){
           
            $product_bns[] = $v['product_bn'];
        }

        $materialObj = app::get('material')->model('basic_material');

        $material_list = $materialObj->getlist('bm_id,material_bn',array('material_bn'=>$product_bns));

        $bm_ids = array();

        foreach($material_list as $mv){
            $bm_ids[$mv['material_bn']] = $mv['bm_id'];
        }

        $stockFreeze_list = $this->getStockFreezeList($bm_ids);

        foreach($data as $v){
            if (!isset($bm_ids[$v['product_bn']])) continue;
            if ($v['normal_num']<=0) continue;

            $nums   = $v['normal_num'];
            $bm_id  = $bm_ids[$v['product_bn']];
            $stockFreeze = $stockFreeze_list[$bm_id];
            if (!$stockFreeze) continue;
            foreach($stockFreeze as $sk=>$sv){
               
                $nums=$nums-$sv['freeze_num'];
                
                if ($nums<0) break;
              
                $trans = kernel::database()->beginTransaction();
                $result = kernel::single('console_stock_artificial_freeze')->do_unfreeze(array($sv['bmsaf_id']), false);
                if($result['rsp'] == 'succ'){
                    kernel::database()->commit($trans);
                } else {
                    kernel::database()->rollBack();
                }
                
            }
        }

    }

    public function getStockFreezeList($bm_ids){
        $freezeMdl  = app::get('material')->model('basic_material_stock_artificial_freeze');
        $freeze_list = $freezeMdl->getlist('bm_id,freeze_num,bmsaf_id',array('original_type'=>'deliverycancel','bm_id'=>$bm_ids,'status'=>'1','freeze_num|than'=>0),0,-1,'freeze_time ASC');

        $stock_list = array();

        foreach($freeze_list as $v){
            $stock_list[$v['bm_id']][$v['bmsaf_id']] = $v;
        }

        return $stock_list;
    }

    /**
     * 定时释放48小时前冻结
     * 
     */
    
    public function releaseStockFreeze(){
        $time = strtotime('-48 hours',time());

        $freezeMdl  = app::get('material')->model('basic_material_stock_artificial_freeze');
        $freeze_list = $freezeMdl->getlist('bmsaf_id',array('original_type'=>'deliverycancel','status'=>'1','freeze_num|than'=>0,'freeze_time|lthan'=>$time),0,-1,'freeze_time ASC');

        foreach($freeze_list as $v){
            $trans = kernel::database()->beginTransaction();
            $result = kernel::single('console_stock_artificial_freeze')->do_unfreeze(array($v['bmsaf_id']), false);
            if($result['rsp'] == 'succ'){
                kernel::database()->commit($trans);
            } else {
                kernel::database()->rollBack();
            }
        }

    }

}
