<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class siso_receipt_iostock extends siso_receipt_iostock_abstract{
    /**
     * 获取出入库业务类型列表
     * 
     * @return void
     */
    public function getIostockBillTypes($type_id=0)
    {
        $isoTypeObj = app::get('ome')->model('iso_type');
        
        //filter
        $filter = [];
        
        //指定出入库类型
        if($type_id){
            $filter['type_id'] = $type_id;
        }
        
        //list
        $billList = $isoTypeObj->getList('bill_type, bill_type_name', $filter);
        if($billList){
            $billList = array_column($billList, 'bill_type_name', 'bill_type');
            
            //系统定义的业务类型
            //@todo：系统定义的业务类型很多是未用上的,所以没有直接使用;
            //$isoObj = app::get('taoguaniostockorder')->model('iso');
            //$billTypes = $isoObj::$bill_type;
            
            $billTypes = [
                'oms_reship_diff' => '差异退货入库',
                'oms_reshipdiffout' => '差异退货出库',
                'vopjitrk' => '唯品会JIT入库单',
                'o2oprepayed'=> '门店预订单',
                'jdlreturn' => '京东自营',
            ];
            
            $billList = array_merge($billList, $billTypes);
        }
        
        return $billList;
    }
}
?>