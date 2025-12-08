<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_event_trigger_logistics_data_electron_sto extends wms_event_trigger_logistics_data_electron_common {
    protected $needGetWBExtend = true;

    protected function getWaybillExtendSdf($arrDelivery, $shop){
        
        $basicMaterialObj    = app::get('material')->model('basic_material');
        
        $sdf = array();
        foreach($arrDelivery as $delivery) {
            $deliveryItems = $this->getDeliveryItems($delivery['delivery_id']);
            
            $product_name = '';
            foreach ($deliveryItems as $item) {
                
                $basicMateriaItem    = $basicMaterialObj->dump(array('material_bn'=>$item['bn']), 'bm_id, material_name');
                
                $product_name        = $basicMateriaItem['material_name'];
                break;
            }
            $sdf[] = array(
                'delivery' => $delivery,
                'shop' => $shop,
                'product_name' => $product_name
            );
        }
        return $sdf;
    }

    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDirectSdf($arrDelivery, $arrBill, $shop) {
        return false;
    }
}