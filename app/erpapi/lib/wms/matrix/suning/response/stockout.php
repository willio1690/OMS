<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/6/11 14:41:31
 * @describe: 类
 * ============================
 */
class erpapi_wms_matrix_suning_response_stockout extends erpapi_wms_response_stockout
{
    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params)
    {
        $items = isset($params['item']) ? json_decode($params['item'], true) : array();

        foreach ($items as $key => $value) {
            $items[$key]['num'] = $value['normal_num'];
        }

        $params['item'] = json_encode($items);

        return parent::status_update($params);
    }
}
