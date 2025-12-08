<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 入库单
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_selfwms_response_stockin extends erpapi_wms_response_stockin
{
    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'入库单';
        $this->__apilog['original_bn'] = $params['io_bn'];

        return $params;
    }
}
