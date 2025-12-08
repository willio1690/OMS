<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 爱库存订单信息中拿到快递单
 *
 */
class erpapi_shop_response_plugins_order_waybill extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $waybillSdf = array();

        if (!$platform->_ordersdf['shipping']['shipping_id']) return $waybillSdf;

        $waybillSdf['position']        = $platform->_ordersdf['position'];
        $waybillSdf['position_no']     = $platform->_ordersdf['position_no'];
        $waybillSdf['waybill_number']  = $platform->_ordersdf['shipping']['shipping_id'];
        $waybillSdf['logistics_code']  = $platform->_ordersdf['shipping']['shipping_name'];
        $waybillSdf['order_bool_type'] = $platform->_ordersdf['order_bool_type'];

        return $waybillSdf;
    }

    /**
     * 订单完成后处理
     * 
     * @return void
     * @author
     * */
    public function postCreate($order_id,$waybillSdf)
    {
        $waybillModel       = app::get('logisticsmanager')->model('waybill');
        $waybillExtendModel = app::get('logisticsmanager')->model('waybill_extend');

        $channel_id = 0;

        if ($waybillSdf['logistics_code']) {
            $channelFilter = array (
                'status'         => 'true',
                'logistics_code' => $waybillSdf['logistics_code'],
            );
            $channelFilter['channel_type'] = kernel::single('ome_branch_type')->isAppointBranch(array ('order_bool_type' => $waybillSdf['order_bool_type']));

            $channel = app::get('logisticsmanager')->model('channel')->db_dump($channelFilter,'channel_id');

            $channel_id = $channel['channel_id'];
        }
        
        foreach (explode(',', $waybillSdf['waybill_number']) as $logi_no) {
            $waybill = array(
                'waybill_number' => $logi_no,
                'logistics_code' => $waybillSdf['logistics_code'],
                'create_time'    => time(),
                'status'         => '1',
                'channel_id'     => $channel_id,
            );
            $waybillModel->save($waybill);

            if ($waybillSdf['position'] || $waybillSdf['position_no']) {
                $waybillExtend = array(
                    'waybill_id'  => $waybill['id'],
                    'position'    => $waybillSdf['position'],
                    'position_no' => $waybillSdf['position_no'],
                );
                $waybillExtendModel->save($waybillExtend);                
            }   
        }
    }

        /**
     * postUpdate
     * @param mixed $order_id ID
     * @param mixed $waybillSdf waybillSdf
     * @return mixed 返回值
     */
    public function postUpdate($order_id, $waybillSdf)
    {
        $waybillModel       = app::get('logisticsmanager')->model('waybill');
        if($waybillModel->db_dump(array('waybill_number'=>explode(',', $waybillSdf['waybill_number'])))){
            return;
        }
        $this->postCreate($order_id, $waybillSdf);
    }
}