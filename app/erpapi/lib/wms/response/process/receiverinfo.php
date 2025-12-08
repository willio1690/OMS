<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货
 */
class erpapi_wms_response_process_receiverinfo
{
    
    /**
     * query
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function query($params)
    {
        $field = 'delivery_id,delivery_bn,shop_id,shop_type,ship_tel,ship_mobile,ship_addr,ship_name';
        $data = app::get('ome')->model('delivery')->db_dump(array('delivery_bn'=>$params['delivery_bn']), $field);
        if(!$data) {
            return array('rsp'=>'fail', 'msg'=>'发货单['.$params['delivery_bn'].']不存在');
        }
        /* if($data['shop_type'] == 'taobao') {
            return array('rsp'=>'fail', 'msg'=>'获取虚拟号失败，未购买解密服务费或余额不足');
        } */
        $order_bn = app::get('ome')->model('delivery')->getOrderBnbyDeliveryId($data['delivery_id']);

        $obj = kernel::single('ome_security_router',$data['shop_type']);
        // 京东解密
        $decrypt_data = $obj->decrypt(array (
            'ship_tel'    => $data['ship_tel'],
            'ship_mobile' => $data['ship_mobile'],
            'ship_addr'   => $data['ship_addr'],
            'shop_id'     => $data['shop_id'],
            'order_bn'    => $order_bn['order_bn'],
            'ship_name' => $data['ship_name'],
        ), 'order');


        if ($obj->is_encrypt($decrypt_data, 'delivery')){
            return ['rsp' => 'fail', 'msg' => '解密失败'];
        }

        if (!$decrypt_data['ship_name'] || !$decrypt_data['ship_addr'] || (!$decrypt_data['ship_tel']&&!$decrypt_data['ship_mobile'])){
            return ['rsp' => 'fail', 'msg' => '解密失败'];
        }

        return ['rsp'=>'succ', 'data'=>['name'=>$decrypt_data['ship_name'], 'tel'=>$decrypt_data['ship_tel'], 'mobile'=>$decrypt_data['ship_mobile'], 'detailAddress'=>$decrypt_data['ship_addr']]];
    }
}
