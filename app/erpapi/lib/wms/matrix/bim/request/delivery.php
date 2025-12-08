<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing@shopex.cn
 * @describe 菜鸟保税发货单
 */
class erpapi_wms_matrix_bim_request_delivery extends erpapi_wms_request_delivery
{

    /**
     * 发货单创建接口名
     * 
     * @return void
     * @author 
     */

    protected function _get_create_api_name()
    {
        return WMS_TRADEORDER_CONSIGN;
    }

    protected function _format_delivery_create_params($sdf)
    {
       
        $params = array(
            'trade_id'   => $sdf['order_bn'],
            'store_code' => $this->get_warehouse_code($this->__channelObj->wms['channel_id'],$sdf['branch_bn']),
        );
        return $params;
    }

    protected function _getNextObjType() 
    {
        return 'search_delivery';
    }

    protected function _format_search_params($sdf)
    {
        // $sdf['out_order_code'] = 'LBX0326272715072577';

        $params = array(
            'cn_order_code'=> $sdf['out_order_code'],
        );

        // $params['order_code'] = '322878370407819067';

        return $params;
    }

    /**
     * @param $rs
     * @return array
     */
    protected function _deal_search_result($rs)
    {
        $result = array();

        $data = @json_decode($rs['data'],true);

        $consign_send_info = $data['consign_send_info_list']['consignsendinfolist'][0]['consign_send_info'];

        if ($consign_send_info['status'] != 'WMS_CONFIRMED') return $result;

        $tms_order = array_shift($consign_send_info['tms_order_list']['tmsorderlist']);

        $logi_no       = $tms_order['tms_order']['tms_order_code'];
        $logistics     = $tms_order['tms_order']['tms_code'];
        $weight        = $tms_order['tms_order']['package_weight']/1000;
        $warehouse     = $consign_send_info['store_code'];
        $cn_order_code = $consign_send_info['cn_order_code'];

        $deliveryExtend = app::get('console')->model('delivery_extension')->db_dump(array('original_delivery_bn' => $cn_order_code));

        if (!$deliveryExtend) return $result;

        $other_list_0 = array();
        foreach ($consign_send_info['tms_order_list']['tmsorderlist'] as $value) {
            $other_list_0[] = array(
                'logi_no' => $value['tms_order']['tms_order_code'],
                'weight'  => $value['tms_order']['package_weight']/1000,
            );
        }

        $result['data'] = array(
            'status'          => 'DELIVERY',
            'delivery_bn'     => $deliveryExtend['delivery_bn'],
            'logi_no'         => $logi_no,
            'logistics'       => $logistics,
            'weight'          => $weight,
            'warehouse'       => $warehouse,
            // 'volume'          => ,
            // 'memo'            => ,
            'other_list_0'    => json_encode($other_list_0),
            'out_delivery_bn' => $cn_order_code,
            // 'item'            => json_encode($item),
        );

        return $result;
    }

    protected function _needEncryptOriginData($sdf) {
        return true;
    }

    protected function _getEncryptOriginData(&$sdf) {
        $sdf['consignee'] = [];
    }
}
