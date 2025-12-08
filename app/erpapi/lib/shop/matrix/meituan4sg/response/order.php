<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 美团闪购订单处理
 *
 * @category 
 * @package 
 * @author system
 * @version $Id: order.php
 */
class erpapi_shop_matrix_meituan4sg_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');


        $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
        // 如果ERP收货人信息未发生变动时，则更新美团收货人信息
        if ($rs[0]['extend_status'] != 'consignee_modified') {
            $components[] = 'consignee';
        }


        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) || ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] == 'dead')) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id', array('order_id' => $this->_tgOrder['order_id'], 'status|noequal' => '3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }

        return $components;
    }

    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();
        $plugins[] = 'delivery';
        return $plugins;
    }

    protected function _analysis()
    {
        parent::_analysis();

        // 美团闪购就是小时达，直接设置 is_xsdbc=1
        $this->_ordersdf['is_xsdbc'] = true;
        
        // 美团闪购门店字段处理：从 extend_field.app_poi_code 设置到 order_objects.store_code
        if (isset($this->_ordersdf['extend_field']) 
            && isset($this->_ordersdf['extend_field']['app_poi_code']) 
            && !empty($this->_ordersdf['extend_field']['app_poi_code'])) {
            
            $storeCode = $this->_ordersdf['extend_field']['app_poi_code'];
            
            // 设置 o2o_info.o2o_store_bn
            $this->_ordersdf['o2o_info']['o2o_store_bn'] = $storeCode;
            
            // 设置所有订单商品的 store_code
            if (isset($this->_ordersdf['order_objects']) && is_array($this->_ordersdf['order_objects'])) {
                foreach ($this->_ordersdf['order_objects'] as &$order_object) {
                    $order_object['store_code'] = $storeCode;
                }
            }
        }
        
        // 初始化 cn_info 数组（如果不存在）
        if (!isset($this->_ordersdf['cn_info'])) {
            $this->_ordersdf['cn_info'] = [];
        }

        // 处理 extend_field.is_third_shipping 转换为 cn_info.DType
        if (isset($this->_ordersdf['extend_field']['is_third_shipping'])) {
            $isThirdShipping = $this->_ordersdf['extend_field']['is_third_shipping'];

            if ($isThirdShipping == '1') {
                $this->_ordersdf['cn_info']['trade_attr']['xsdFulfillmentInfo']['DType'] = 'thirdparty';
            } else {
                // 根据配送方式设置DType
                if ($this->_ordersdf['shipping']['shipping_name'] == '0000') {
                    $this->_ordersdf['cn_info']['trade_attr']['xsdFulfillmentInfo']['DType'] = 'seller';
                } else {
                    $this->_ordersdf['cn_info']['trade_attr']['xsdFulfillmentInfo']['DType'] = 'official';
                }
            }

            // 清除原始字段
            unset($this->_ordersdf['extend_field']['is_third_shipping']);
        }

        if (isset($this->_ordersdf['extend_field']['packing_fee'])) {
            $this->_ordersdf['service_price'] = (float)$this->_ordersdf['extend_field']['packing_fee'];
        }
        if (isset($this->_ordersdf['extend_field']['platform_service_fee'])) {
            $this->_ordersdf['platform_service_fee'] = (float)$this->_ordersdf['extend_field']['platform_service_fee'];
        }
    }
} 