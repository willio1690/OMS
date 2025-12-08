<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_interface_delivery{

    /**
     * iscancel
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回值
     */
    public function iscancel($delivery_bn){
        $dlyObj = app::get('ome')->model('delivery');
        $dlyInfo = $dlyObj->dump(array('delivery_bn'=>$delivery_bn),'status');
        if(in_array($dlyInfo['status'],array('failed','cancel','back','stop'))){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取OmeDlyShipType
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回结果
     */
    public function getOmeDlyShipType($delivery_bn){
        $dlyObj = app::get('ome')->model('delivery');
        $dlyInfo = $dlyObj->dump(array('delivery_bn'=>$delivery_bn),'delivery');
        return isset($dlyInfo['delivery']) ? $dlyInfo['delivery'] : '';
    }
}