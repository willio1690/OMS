<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_event_trigger_purchasereturn extends wms_event_trigger_stockoutabstract{

    function getStockOutData($data){
        $oRp = app::get('purchase')->model('returned_purchase');
        $iostockdataObj = kernel::single('wms_iostockdata');
        $rp = $oRp->dump($data['rp_id'],'rp_bn,branch_id');
        $branch_id = $rp['branch_id'];
        $branch_detail = $iostockdataObj->getBranchByid($branch_id);

        $expire_bm_arr = $data['expire_bm_arr'];

        $batchs = $this->processBatchs($branch_id,$expire_bm_arr);

        $outdata = array(
            'io_type' => 'PURCHASE_RETURN',
            'io_source'=>'selfwms',
            'io_bn'=>$rp['rp_bn'],
            'branch_id'=>$branch_id,
            'branch_bn'=>$branch_detail['branch_bn'],
            'memo' =>$returndata['memo'],
            'io_status' => 'FINISH',
        );
        $item = array();
        foreach($data['items'] as $products){

            if($batchs[$products['product_id']]){
                $batch = array_values($batchs[$products['product_id']]);
            }
            $item[] = array(
                'bn'=>$products['bn'],
                'num'=>$products['nums'],
                'batch'=>$batch,
            ); 
        }
        $outdata['items'] = $item;
        return $outdata;
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

                    'product_time'      => $storagelife['production_date'],
                    'expire_time'       => $storagelife['expiring_date'],
                    'normal_defective'  => 'normal',
                    'num'               => $vv['out_num'],

                );


            }
        }
        return $batchs;


    }
}

?>
