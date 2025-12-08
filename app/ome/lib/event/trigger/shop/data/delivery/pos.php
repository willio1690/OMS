<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 获取数据
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class ome_event_trigger_shop_data_delivery_pos extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);

        if ($this->__sdf) {
            $this->_get_delivery_items_sdf($delivery_id);

            $this->_get_order_objects_sdf($delivery_id);

            $this->_get_members_sdf($delivery_id);
            $this->_get_split_sdf($delivery_id);
            $this->__sdf['split_model'] = $this->_is_split_switch($delivery_id);
        }
        $branch = $this->_get_branch($this->__deliverys[$delivery_id]['branch_id']);
        $this->__sdf['branch_bn'] = $branch['branch_bn'];

        //取唯一码
        
        $product_serial = $this->_get_posproduct_serial($delivery_id);
        if($product_serial){
            foreach($this->__sdf['delivery_items'] as $k=>$v){
                if ($product_serial[$v['bn']]){
                    $uniqueCodes = array_splice($product_serial[$v['bn']], 0, $v['number']);
                    $this->__sdf['delivery_items'][$k]['uniqueCodes'] = $uniqueCodes;
                }
           
            }
        }
        

        return $this->__sdf;
    }

    function _get_posproduct_serial($delivery_id)
    {
        $product_serial = [];
        $delivery = $this->__deliverys[$delivery_id];

        $deliveryIds = $deliveryBns= array();
        foreach ($this->__deliverys as $d) {
            $deliveryIds[] = $d['delivery_id'];
            $deliveryBns[] = $d['delivery_bn'];
            if ($d['parent_id'] > 0) {
                $deliveryIds[] = $d['parent_id'];
            }
        }

        $serialMdl    = app::get('ome')->model('product_serial_history');
        $rows = $serialMdl->getList('bn,serial_number', array('bill_id'=>$deliveryIds,'bill_type'=>1), 0, -1);

        foreach ($rows as $row) {
            $product_serial[$row['bn']][] = $row['serial_number'];
        }

        return $product_serial;
    }

}