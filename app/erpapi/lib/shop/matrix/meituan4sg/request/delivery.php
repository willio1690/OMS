<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 美团闪购发货单处理
 *
 * @category
 * @package
 * @author system
 * @version $Id: delivery.php
 */
class erpapi_shop_matrix_meituan4sg_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 检查是否为小时达订单且平台运力
     * 
     * @param array $params 发货单参数
     * @return bool 是否为小时达订单且平台运力
     */

    private function isXiaoshiDaPlatformDelivery($params)
    {
        if (!empty($params['bill_labels'])) {
            foreach ($params['bill_labels'] as $label) {
                if ($label['label_code'] === 'SOMS_XSDBC' && $label['DType'] != 'seller') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取确认发货参数
     * 
     * @param array $params 发货单参数
     * @return array 确认发货参数
     */
    public function get_confirm_params($params)
    {
        $confirmParams = parent::get_confirm_params($params);
        
        // 检查是否为小时达订单且平台运力
        $is_xsd_platform = $this->isXiaoshiDaPlatformDelivery($params);
        
        if ($is_xsd_platform) {
            // 小时达平台运力：简化参数，只保留 order_id
            $confirmParams = array(
                'order_id' => $params['orderinfo']['order_bn'],
            );
        } else {
            // 普通订单或小时达商家自配运力：保留完整参数
            $confirmParams['logistics_status'] = 20;
            
            //从delivery_bill中获取配送员信息
            if (!empty($params['delivery_bill'])) {
                $deliveryBill = $params['delivery_bill'][0] ?? [];
                
                //courier_name: 如果不存在，默认取logi_name
                $confirmParams['courier_name'] = $deliveryBill['courier_name'] ?? $deliveryBill['logi_name'] ?? '';
                
                //courier_phone: 如果不存在，默认取13812341234_123
                $confirmParams['courier_phone'] = $deliveryBill['courier_phone'] ?? '13812341234_123';
                
                //phone_type: courier_phone不存在传1，其他情况直接取delivery_bill中的phone_type
                if (empty($deliveryBill['courier_phone'])) {
                    $confirmParams['phone_type'] = 1;
                } else {
                    $confirmParams['phone_type'] = $deliveryBill['phone_type'] ?? 0;
                }
                
                //privacy_num_validity_seconds: courier_phone如果不存在，传1
                if (empty($deliveryBill['courier_phone'])) {
                    $confirmParams['privacy_num_validity_seconds'] = 1;
                } else {
                    $confirmParams['privacy_num_validity_seconds'] = $deliveryBill['privacy_num_validity_seconds'] ?? 0;
                }
            }
            
            //经纬度默认取仓库的
            if (!empty($params['branch'])) {
                $confirmParams['latitude'] = $params['branch']['latitude'] ?? '';
                $confirmParams['longitude'] = $params['branch']['longitude'] ?? '';
            }
        }
        
        return $confirmParams;
    }

    /**
     * 获取发货接口名称
     * 如果是小时达订单且平台运力，使用 store.trade.pickup.confirm 接口
     * 
     * @param array $params 发货单参数
     * @return string 接口名称
     */
    public function get_delivery_apiname($params)
    {
        // 检查是否为小时达订单且平台运力
        if ($this->isXiaoshiDaPlatformDelivery($params)) {
            // 小时达订单且平台运力，使用拣货确认接口
            return STORE_TRADE_PICKUP_CONFIRM;
        }
        
        // 普通订单或小时达商家自配运力，使用默认接口
        return parent::get_delivery_apiname($params);
    }
}