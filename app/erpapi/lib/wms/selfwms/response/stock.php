<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class erpapi_wms_selfwms_response_stock extends erpapi_wms_response_stock
{
    /**
     * quantity
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function quantity($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'] . '库存对帐';   
        $this->__apilog['original_bn'] = $data['batch'];

        return $params;
    }
}
