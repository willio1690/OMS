<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 商品
 */
class erpapi_wms_response_process_goods
{
    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params)
    {
        $sku_id              = $params['sku_id'];
        $data['data']        = [
            ['outer_sku' => $sku_id],
        ];
        $data['channel_id']  = $params['channel_id'];
        $data['wms_id']      = $params['wms_id'];
        $data['material_bn'] = $sku_id;
        return kernel::single('material_event_receive_goods')->update($data);
    }
}
