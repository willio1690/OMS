<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 转储单
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_selfwms_response_stockdump extends erpapi_wms_response_stockdump
{
    /**
     * quantity
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function quantity($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'转储单';
        $this->__apilog['original_bn'] = $params['stockdump_bn'];
        
        return $params;
    }
}
