<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 按基础物料类型
 */
class omeauto_auto_type_materialtype extends omeauto_auto_type_abstract implements omeauto_auto_type_interface
{
    /**
     * 商品类型
     * 
     * @param object $tpl
     * @return void
     */
    public function _prepareUI(&$tpl)
    {
        $basicMaterialLib = kernel::single('material_basic_material');
        $tempList = $basicMaterialLib->get_material_types();
        
        $typeList = array();
        foreach ($tempList as $key => $val){
            if($val == '虚拟商品' || $val == '虚拟'){
                $typeList[$key] = $val;
            }
        }
        
        $tpl->pagedata['type_list'] = $typeList;
    }
    
    //检查输入的参数
    public function checkParams($params)
    {
        if (empty($params['material_type'])) {
            return "你还没有选择相应的基础物料类型\n\n请勾选以后再试！！";
        }
        
        return true;
    }

    /**
     * 生成规则字串
     *
     * @param Array $params
     * @return String
     */
    public function roleToString($params)
    {
        $basicMaterialLib = kernel::single('material_basic_material');
        $tempList = $basicMaterialLib->get_material_types();
        
        $caption = '';
        foreach ($tempList as $key => $val)
        {
            if($key == $params['material_type']){
                $caption = sprintf('订单明细中包含 [%s] 类型的基础物料', $val);
            }
        }
        
        $role = array('role'=>'materialtype', 'caption'=>$caption, 'content'=>array('material_type'=>$params['material_type']));
        
        return json_encode($role);
    }

    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item)
    {
        if(!empty($this->content)) {
            //基础物料类型
            $material_type = intval($this->content['material_type']);
            
            //获取订单明细中的基础物料
            $arrProductId = array();
            foreach ($item->getOrders() as $order)
            {
                foreach ($order['objects'] as $objKey => $objVal)
                {
                    foreach ($objVal['items'] as $itemKey => $itemVal)
                    {
                        $product_id = $itemVal['product_id'];
                        $arrProductId[$product_id] = $product_id;
                    }
                }
            }
            
            //获取虚拟商品
            $basicMaterialObj = app::get('material')->model('basic_material');
            $tempList = $basicMaterialObj->getList('*', array('bm_id'=>$arrProductId));
            
            //获取规则中的基础物料
            $virtualBns = array();
            foreach ($tempList as $key => $val)
            {
                $bm_id = $val['bm_id'];
                
                if($val['type'] == $material_type){
                    $virtualBns[$bm_id] = $val['material_bn'];
                }
            }
            
            if(empty($virtualBns)){
                return false;
            }
            
            return true;
        } else {
            return false;
        }
    }
}