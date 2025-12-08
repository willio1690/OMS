<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * WMS 发货单

 */
class erpapi_wms_response_receiverinfo extends erpapi_wms_response_abstract
{

    # wms.receiverinfo.query
    /**
     * query
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function query($params){
        // 参数校验
        $this->__apilog['title']       = $this->__channelObj->channel['channel_name'] . '发货单' . $params['delivery_order_code'] .',地址明文查询';
        $this->__apilog['original_bn'] = $params['delivery_order_code'];


        $data = array(
            'delivery_bn'    => trim($params['delivery_order_code']),
            'oaid'           => $params['oaid'],
            'branch_bn'      => $params['owner_code'],
        );
        return $data;
    }
}
