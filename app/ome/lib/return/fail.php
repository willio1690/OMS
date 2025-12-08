<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_return_fail{
    

    /**
     * modifyReturn
     * @param mixed $return_id ID
     * @return mixed 返回值
     */
    public function modifyReturn($return_id){
        $oReturn = app::get('ome')->model('return_product');
        $oReturn_items = app::get('ome')->model('return_product_items');
        $returninfo = $oReturn->dump($return_id,'order_id,return_id,is_fail');
        $itemObj = app::get('ome')->model('order_items');
        $return_items = $oReturn_items->getlist('*',array('return_id'=>$return_id));
        $order_id = $returninfo['order_id'];
        $edit_status = true;
        if ($returninfo['is_fail'] == 'true'){
            foreach($return_items as $item){
                $items = $itemObj->dump(array('bn'=>$item['bn'],'order_id'=>$order_id,'delete'=>'false'),'product_id,bn,name');
                if(!$items){
                        $edit_status = false;
                }
            }
            if ($edit_status) {

                $returnData['is_fail'] = 'false';
                
                $oReturn->update($returnData,array('return_id' =>$return_id));
            }
            

          
        }
        return true;
    }

    /**
     * modifyReturnItems
     * @param mixed $return_id ID
     * @param mixed $oldPbn oldPbn
     * @param mixed $pbn pbn
     * @return mixed 返回值
     */
    public function modifyReturnItems($return_id,$oldPbn,$pbn)
    {
        $orderObj = app::get('ome')->model('orders');
        $itemObj = app::get('ome')->model('order_items');
        $Oorder_objects = app::get('ome')->model('order_objects');
        
        $oReturn = app::get('ome')->model('return_product');
        $oReturn_items = app::get('ome')->model('return_product_items');
        $returninfo = $oReturn->dump($return_id,'is_fail,order_id,return_id');
        $order_id = $returninfo['order_id'];
        //对货品进行过滤更新
        
        if($pbn && $returninfo['is_fail'] == 'true'){
            foreach($pbn as $item_id=>$bn){
                if($bn){
                    $items = $itemObj->dump(array('bn'=>$bn,'order_id'=>$order_id,'delete'=>'false'),'product_id,bn,name');
                    if($items){
                        $item = array(
                            'product_id'=>$items['product_id'],
                            'bn'=>$items['bn'],

                            'name' => $items['name'],
                        );
                        
                        $oReturn_items->update($item,array('return_id'=>$return_id,'item_id'=>$item_id));
                      }
                }
            }
        }
        if($this->modifyReturn($return_id)){
            return true;
        }else{
            return false;
        }
    }

}