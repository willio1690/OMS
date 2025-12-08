<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京东工小达
 */
class wms_event_trigger_logistics_data_electron_jdgxd extends wms_event_trigger_logistics_data_electron_common
{
    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */

    public function getDirectSdf($arrDelivery, $arrBill, $shop)
    {
        $delivery = $arrDelivery[0];
    
        if(empty($arrBill)) {
            $this->needRequestId[] = $delivery['delivery_id'];
        } else {
            $this->needRequestId[] = $arrBill[0]['b_id'];
            $delivery['delivery_bn'] = $this->setChildRqOrdNo($delivery['delivery_bn'], $arrBill[0]['b_id']);
        }
        
        $branch  = app::get('ome')->model('branch')->db_dump($delivery['branch_id']);
        $area    = $branch['area'];
        $address = $branch['address'];
        if ($area) {
            kernel::single('eccommon_regions')->split_area($area);
            $receiver_state    = $area[0] ?? '';
            $receiver_city     = $area[1] ?? '';
            $receiver_district = $area[2] ?? '';
            $area              = $receiver_state . $receiver_city . $receiver_district;
        }
        
        
        $failArray = array('delivery_id' => $delivery['delivery_id'], 'delivery_bn' => $delivery['delivery_bn']);
        if (empty($area) || empty($address)) {
            $failArray['msg']          = '发货地址省份和详细地址不能少';
            $this->directRet['fail'][] = $failArray;
            return false;
            
        }
        
        if (empty($branch['phone']) && empty($branch['mobile'])) {
            $failArray['msg']          = '发货地址手机号和电话不能同时少';
            $this->directRet['fail'][] = $failArray;
            return false;
        }
        
        if (empty($branch['uname'])) {
            $failArray['msg']          = '发货人姓名不能少';
            $this->directRet['fail'][] = $failArray;
            return false;
        }
        
        $order_bns = [];
        foreach ($arrDelivery as $key => $value) {
            foreach ($value['delivery_order'] as $v) {
                $order_bns[] = $v['order_bn'];
            }
        }
        
        $deliveryInfo = [
            'senderName'      => $branch['uname'],//发货人姓名
            'deliveryAddress' => $area . $branch['address'],//发货详细地址
            'senderMobile'    => $branch['phone'] ?: $branch['mobile'],//发货人手机号
        ];

        
        $sdf               = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn'] = $delivery['delivery_bn'];;
        $sdf['shop']         = $shop;
        $sdf['deliveryInfo'] = $deliveryInfo;
        $sdf['orderIdList']  = $order_bns;
        $sdf['delivery']     = $delivery;
    
        return $sdf;
        
    }
}