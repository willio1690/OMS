<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单使用福袋明细信息
 *
 * @author wangbiao@shopex.cn
 * @version 2024.12.26
 */
class erpapi_shop_response_plugins_order_luckybag extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        //检查失败订单,直接返回
        //@todo：不能使用$platform->_ordersdf获取订单信息;
        if($platform->_newOrder['is_fail'] == 'true'){
            return [];
        }
        
        if(empty($platform->_newOrder['order_objects'])){
            return [];
        }
        
        //order_objects
        $luckyList = [];
        foreach ($platform->_newOrder['order_objects'] as $objKey => $objVal)
        {
            $oid = $objVal['oid'];
            $goods_bn = $objVal['goods_bn'];
            
            //check obj_type
            if($objVal['obj_type'] != 'lkb'){
                continue;
            }
            
            //check order_items
            if(empty($objVal['order_items'])){
                continue;
            }
            
            //order_items
            foreach($objVal['order_items'] as $itemKey => $itemVal)
            {
                $product_bn = $itemVal['bn'];
                $luckybag_id = $itemVal['luckybag_id']; //福袋组合ID
                
                //obj_key
                $obj_key = ($oid ? $oid : $goods_bn);
                
                //items
                $luckyList[$obj_key][$product_bn][$luckybag_id] = array(
                    'oid' => $objVal['oid'],
                    'goods_bn' => $objVal['bn'],
                    'bm_id' => $itemVal['product_id'],
                    'product_bn' => $itemVal['bn'],
                    'combine_id' => $luckybag_id,
                );
            }
        }
        
        return $luckyList;
    }
    
    /**
     * 订单创建完成后进行处理
     * 
     * @param int $order_id
     * @param text $luckyList 福袋使用信息
     * @return void
     */
    public function postCreate($order_id, $luckyList)
    {
        //check
        if(empty($luckyList)){
            return false;
        }
        
        $luckyMdl = app::get('ome')->model('order_luckybag');
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        
        //order_objects
        $objectList = $luckyList['order_objects'];
        if(empty($objectList)){
            return false;
        }
        
        //unset
        unset($luckyList['order_objects']);
        
//        //order_objects
//        $objectList = $this->getOrderObjects($order_id);
//        $objectList = array_column($objectList, null, 'obj_id');
//
//        //order_items
//        $itemList = $this->getOrderItems($order_id);
//
//        //format
//        foreach ($itemList as $itemKey => $itemVal)
//        {
//            $obj_id = $itemVal['obj_id'];
//            $item_id = $itemVal['item_id'];
//
//            $objectList[$obj_id]['order_items'][$item_id] = $itemVal;
//        }
        
        //data
        $dataList = array();
        foreach ($objectList as $objKey => $objVal)
        {
            $obj_id = $objVal['obj_id'];
            $oid = $objVal['oid'];
            $goods_bn = $objVal['goods_bn'];
            
            //order_items
            foreach ($objVal['order_items'] as $item_id => $itemVal)
            {
                $item_id = $itemVal['item_id'];
                $product_bn = $itemVal['bn'];
                $luckybag_id = $itemVal['luckybag_id']; //福袋组合ID
                $price_rate = ($itemVal['price_rate'] ? $itemVal['price_rate'] : 0); //价格贡献占比
                
                //obj_key
                $obj_key = ($oid ? $oid : $goods_bn);
                
                //check
                if(!isset($luckyList[$obj_key][$product_bn][$luckybag_id])){
                    continue;
                }
                
                //info
                $lukcyInfo = $luckyList[$obj_key][$product_bn][$luckybag_id];
                $lukcyInfo['obj_id'] = $obj_id;
                $lukcyInfo['item_id'] = $item_id;
                $lukcyInfo['price_rate'] = $price_rate;
                
                $dataList[] = $lukcyInfo;
            }
        }
        
        //combine_id
        $combineIds = array_column($dataList, 'combine_id');
        
        //福袋组合列表
        $combineList = $combineMdl->getList('combine_id,combine_bn,selected_number,include_number', array('combine_id'=>$combineIds), 0, -1);
        $combineList = array_column($combineList, null, 'combine_id');
        
        //福袋组合关联基础物料列表(按实际比例real_ratio字段进行降序排序)
        if($combineList){
            $combineItems = $combineItemMdl->getList('item_id,combine_id,bm_id,real_ratio', array('combine_id'=>array_keys($combineList)), 0, -1, 'real_ratio DESC');
            foreach ((array)$combineItems as $itemKey => $itemVal)
            {
                $combine_id = $itemVal['combine_id'];
                $bm_id = $itemVal['bm_id'];
                
                $combineList[$combine_id]['items'][$bm_id] = $itemVal;
            }
        }
        
        //save
        foreach ($dataList as $itemKey => $itemVal)
        {
            $combine_id = $itemVal['combine_id'];
            $bm_id = $itemVal['bm_id'];
            
            //info
            $combineInfo = $combineList[$combine_id];
            if(empty($combineInfo)){
                continue;
            }
            
            //real_ratio
            $real_ratio = $combineInfo['items'][$bm_id]['real_ratio'];
            if(empty($real_ratio)){
                $real_ratio = 0;
            }
            
            //sdf
            $sdf = array(
                'order_id' => $order_id,
                'obj_id' => $itemVal['obj_id'],
                'item_id' => $itemVal['item_id'],
                'combine_id' => $itemVal['combine_id'],
                'combine_bn' => $combineInfo['combine_bn'],
                'bm_id' => $itemVal['bm_id'],
                'selected_number' => $combineInfo['selected_number'],
                'include_number' => $combineInfo['include_number'],
                'real_ratio' => $real_ratio,
                'price_rate' => $itemVal['price_rate'],
            );
            $luckyMdl->save($sdf);
        }
        
        return true;
    }
}