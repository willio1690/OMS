<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-16
 * @describe 发起电子面单请求
 */
class brush_electron_sto extends brush_electron_abstract
{
    /**
     * deliveryToSdf
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */

    public function deliveryToSdf($delivery) {//各自实现
        $sdf = array();

        return $sdf;
    }
    
    //获取大头笔
    /**
     * 获取WaybillExtend
     * @return mixed 返回结果
     */
    public function getWaybillExtend()
    {
        $waybill = array();
        $waybillDelivery = array();
        $notGetWaybillExtend = array();
        foreach($this->delivery as $delivery) {
            if(!empty($delivery['logi_no'])) {
                $waybill[] = $delivery['logi_no'];
                $waybillDelivery[$delivery['logi_no']] = $delivery;
            }
        }
        
        if(!empty($waybill)) {
            $objExtend = app::get("logisticsmanager")->model("waybill_extend");
            $sql = 'SELECT w.waybill_number, e.id FROM sdb_logisticsmanager_waybill w LEFT JOIN sdb_logisticsmanager_waybill_extend e ON (w.id = e.waybill_id) WHERE w.waybill_number in ("'. implode('","', $waybill) .'") AND w.channel_id = "' . $this->channel['channel_id'] . '"';
            $arrExtend = $objExtend->db->select($sql);
            foreach($arrExtend as $val) {
                if(empty($val['id']) && $val['waybill_number']) {
                    $params = $this->_deliveryData($waybillDelivery[$val['waybill_number']]);
                    $ret = $this->request('waybillExtend', $params);
                    if($ret['rsp'] == 'fail') {
                        $notGetWaybillExtend[$ret['not_extend']['delivery_id']] = $ret['not_extend']['delivery_bn'];
                    }
                }
            }
        }
        
        return $notGetWaybillExtend;
    }
    
    private function _deliveryData($delivery)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        
        $shopInfo = $this->getChannelExtend();
        
        $deliveryItems = $this->getDeliveryItems($delivery['delivery_id']);
        
        //material_name
        $product_name = '';
        foreach($deliveryItems as $item)
        {
            $basicMaterialInfo = $basicMaterialObj->dump(array('material_bn'=>$item['bn']), 'material_name');
            
            $product_name = $basicMaterialInfo['material_name'];
            
            break;
        }
        
        $params = array(
            'delivery' => $delivery,
            'shop' => $shopInfo,
            'product_name' => $product_name
        );
        
        return $params;
    }
}