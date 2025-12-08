<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 自建WAP移动端响应类
 *
 * @author xiayuanjun@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_wap_response_delivery  extends erpapi_store_response_delivery 
{
    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'发货单更新('.$params['status'].')';
        $this->__apilog['original_bn'] = $params['delivery_bn'];

        return $params;
    }
}
