<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 客户分类Model类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.06.12
 */
class material_mdl_customer_classify extends dbeav_model
{
    /**
     * 删除客户分类
     *
     * @param $data
     * @return bool
     */
    public function pre_recycle($data=null)
    {
        $salesMaterialObj = app::get('material')->model('sales_material');
        
        //delete
        foreach ($data as $val)
        {
            if(empty($val['class_id'])){
                continue;
            }
            
            $saleMaterialList = $salesMaterialObj->getList('sm_id', array('class_id'=>$val['class_id']),0,1);
            if($saleMaterialList){
                $this->recycle_msg = '客户分类编码：'. $val['class_bn'] .'已经被销售物料使用，无法删除！';
                
                return false;
            }
        }
        
        return true;
    }
}
