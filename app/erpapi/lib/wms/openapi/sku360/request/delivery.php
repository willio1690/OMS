<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单推送
 *
 * @category
 * @package
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_request_delivery extends erpapi_wms_request_delivery
{
    protected function _format_delivery_create_params($sdf)
    {
        $logistics_code = kernel::single('wmsmgr_func')->getWmslogiCode($this->__channelObj->wms['channel_id'],$sdf['logi_code']);
        $data['orders'][0] = array(
            'order_code' => $sdf['outer_delivery_bn'],
            'shop_name' => $sdf['shop_name'],
            'express' => $logistics_code ? $logistics_code : $sdf['logi_code'],
            'rec_name' => $sdf['consignee']['name'] ? $sdf['consignee']['name'] : '未知',
            'rec_mobile' => $sdf['consignee']['mobile'] ? $sdf['consignee']['mobile'] : '未知',
            'rec_tel' => $sdf['consignee']['telephone'] ? $sdf['consignee']['telephone'] : '未知',
            'rec_province' => $sdf['consignee']['province'] ? $sdf['consignee']['province'] : '未知',
            'rec_city' => $sdf['consignee']['city'] ? $sdf['consignee']['city'] : '未知',
            'rec_county' => $sdf['consignee']['district'] ? $sdf['consignee']['district'] : '未知',
            'rec_address' => $sdf['consignee']['addr'] ? $sdf['consignee']['addr'] : '未知',
        );

        $items = array();
        if ($sdf['delivery_items']){
            foreach ($sdf['delivery_items'] as $k => $v){
                $items[] = array(
                    'product_code' => $v['bn'],
                    'qty' => $v['number'],
                );
            }
        }
        $data['orders'][0]['skus'] = $items;
        $params['data'] = json_encode($data);
        return $params;
    }

    /**
     * 发货单创建
     *
     * @return void
     * @author
     **/

    public function delivery_create($sdf){
        $delivery_bn = $sdf['outer_delivery_bn'];

        $iscancel = kernel::single('ome_interface_delivery')->iscancel($delivery_bn);
        if ($iscancel) {
            return $this->succ('发货单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'] . '发货单添加';

        $params = $this->_format_delivery_create_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }

        // 御城河
        /*
        $tradeIds = explode('|',$sdf['order_bn']);
        kernel::single('base_hchsafe')->order_send_log($tradeIds,$this->__channelObj->wms['node_id']);
        */

        $callback  = array();
        $response = $this->__caller->call(WMS_SALEORDER_CREATE, $params, $callback, $title,10,$delivery_bn);
        $callback_params = array(
            'delivery_bn' => $delivery_bn,
            'method' => WMS_SALEORDER_CREATE
        );
        if($response) {
            $this->delivery_create_callback($this->__resultObj->get_response(), $callback_params);
        }

        return $response;
    }

    protected function _format_delivery_cancel_params($sdf)
    {
        $params['data'] = json_encode(array(
            'order_code' => $sdf['outer_delivery_bn'],
        ));

        return $params;
    }
}