<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * WMS 商品
 *
 * @category
 * @package
 * @author xueding@shopex.cn
 */
class erpapi_wms_response_goods extends erpapi_wms_response_abstract
{
    protected $unitConversion = 1000;
    
    /**
     * wms.goods.status_update
     *
     **/
    public function status_update($params)
    {
        // 参数校验
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'] . '云交易商品信息变更MQ' . $params['sku_id'];
        $this->__apilog['original_bn'] = $params['sku_id'];
        
        $data = array(
            'sku_id'       => trim($params['sku_id']),
            'channel_id'    => trim($params['channel_id']),
            'type'         => trim($params['type']),
            'wms_id'       => $this->__channelObj->wms['channel_id'],
        );
        return $data;
    }
}
