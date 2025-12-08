<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京东阿尔法
 */
class wms_event_trigger_logistics_data_electron_jdalpha extends wms_event_trigger_logistics_data_electron_common
{
    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */

    public function getDirectSdf($arrDelivery, $arrBill, $shop){
        $delivery = $arrDelivery[0];
        if(empty($arrBill)) {
            $this->needRequestId[] = $delivery['delivery_id'];
        } else {
            $this->needRequestId[] = $arrBill[0]['b_id'];
            $delivery['delivery_bn'] = $this->setChildRqOrdNo($delivery['delivery_bn'], $arrBill[0]['b_id']);
        }
        if(empty($shop)){
            $shop_obj = app::get('ome')->model('shop');
            $_shop = $shop_obj->getList('shop_id,default_sender,mobile,tel,area,name as shop_name,addr as address_detail',array('' => $delivery['shop_id']));
            $shop  = $_shop[0];
            $addr_arr = explode(':',$shop['area']);
            $address = explode('/', $addr_arr[1]);
            $shop['province'] = $address[0];
            $shop['city'] = $address[1];
            $shop['area'] = $address[2];
        }
        $sdf = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $total_mount = 0;
        foreach ($arrDelivery as $key => $value) {
            foreach ($value['delivery_order'] as $v) {
               $order_bns[]= $v['order_bn'];
            }
            $total_mount += $value['total_amount'];
        }
        $chanel_info = explode('|||', $this->channel['shop_id']);
        $corp = $this->get_corp($delivery['logi_id']);
        $logistics = kernel::single('logisticsmanager_waybill_jdalpha')->logistics($this->channel['logistics_code']);
        $sdf['order_bns'] = $order_bns;
        $sdf['corp_type']     =  $logistics['jdalpha_code'];#物流编码
        $sdf['jdalpha_vendorCode'] = $chanel_info[2];#商家编码
        $sdf['jdalpha_businesscode'] = $chanel_info[0];#结算编码
        $sdf['mode']          =  $logistics['mode'];#加盟类型，是直营,或是加盟
        $sdf['primary_bn']    = $delivery['delivery_bn'];
        $sdf['delivery']      = $delivery;
        $sdf['shop']          = $shop;
        $sdf['sale_plateform'] = $this->get_sale_plateform( $shop['shop_type']);#销售平台
        if($corp['protect'] == 'true'){
            $sdf['is_protect'] = true;#是否保价
            $sdf['protect_price'] = sprintf('%.2f', max($total_mount * $corp['protect_rate'], $corp['minprice']));#保价费用
        }
        if( $sdf['corp_type'] =='SF'){
            $sdf['expressPayMethod']      = $chanel_info[3];#快递费付款方式
            $sdf['expressType'] = $chanel_info[4];#快件产品类别
        }
        return $sdf;
    }
    #销售平台代码平台
    /**
     * 获取_sale_plateform
     * @param mixed $shop_type shop_type
     * @return mixed 返回结果
     */
    public function get_sale_plateform($shop_type){
        $params = array(
            '360buy'=>'0010001',
            'taobao'=>'0010002',
            'amazon'=>'0010004',
            'suning'=>'0010003',
            'pop'=>'0090001',#POP自主售后
            'yihaodian'=>'00010008'
        );
        $plateform = $params[$shop_type]?$params[$shop_type]:'0030001';
        return $plateform;
    }
}
