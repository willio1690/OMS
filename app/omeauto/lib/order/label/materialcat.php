<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 按基础物料分类
 */
class omeauto_order_label_materialcat extends omeauto_order_label_abstract implements omeauto_order_label_interface
{
    /**
     * 检查订单数据是否符合要求
     *
     * @param array $orderInfo
     * @param string $error_msg
     * @return bool
     */
    public function vaild($orderInfo, &$error_msg=null)
    {
        if(empty($this->content)){
            $error_msg = '没有设置基础物料类型规则';
            return false;
        }
        
        $basicMaterialObj = app::get('material')->model('basic_material');
        
        
        //包含或不包含基础物料分类：大家电、小家电
        
        
        
        
        
        //基础物料类型
        $material_type = intval($this->content['material_type']);
        
        //获取订单明细中的基础物料
        $arrProductId = array();
        foreach ($orderInfo['order_objects'] as $objKey => $objVal)
        {
            //check
            if(empty($objVal['order_items'])){
                continue;
            }
            
            //items
            foreach ($objVal['order_items'] as $itemKey => $itemVal)
            {
                //check
                if($itemVal['delete'] == 'true'){
                    continue;
                }
                
                $product_id = $itemVal['product_id'];
                $arrProductId[$product_id] = $product_id;
            }
        }
        
        //check没有item明细
        if(empty($arrProductId)){
            $error_msg = '订单没有基础物料明细';
            return false;
        }
        
        //获取虚拟商品
        $virtualBns = array();
        $tempList = $basicMaterialObj->getList('bm_id,material_bn,type', array('bm_id'=>$arrProductId));
        foreach ($tempList as $key => $val)
        {
            $bm_id = $val['bm_id'];
            
            //check
            if($val['type'] == $material_type){
                $virtualBns[$bm_id] = $val['material_bn'];
            }
        }
        
        if(empty($virtualBns)){
            $error_msg = '没有符合条件的基础物料';
            return false;
        }
        
        return true;
    }
}