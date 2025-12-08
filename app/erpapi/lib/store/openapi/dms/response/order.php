<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_openapi_dms_response_order extends erpapi_store_response_order
{
    /**
     * 创建订单
     *
     * @return void
     * @author
     **/
    public function add($params)
    {
        $sdf = parent::add($params);

        if ($sdf == false) {
            return false;
        }

        // 判断是否传了movement code
        if (!$params['movement_code'] || !is_numeric($params['movement_code'])) {
            $this->__apilog['result']['msg'] = "缺少movement code";
            return false;
        }
        $sdf['movement_code'] = $params['movement_code'];

        $sdf['is_try'] = 'true';

        // 获取渠道发货仓
        $flow = app::get('o2o')->model('branch_flow')->dump([
            'to_store_bn' => $params['store_bn'],
        ]);
        if (!$flow) {
            $this->__apilog['result']['msg'] = sprintf('[%s]未匹配发货仓', $params['store_bn']);
            return false;
        }

        $channel = app::get('o2o')->model('channel')->dump($flow['channel_id']);
        if (!$channel['branch_id']) {
            $this->__apilog['result']['msg'] = sprintf('[%s]未匹配发货仓', $params['store_bn']);
            return false;
        }

        foreach ($sdf['order_objects'] as $key => $value) {
            $sdf['order_objects'][$key]['store_code'] = $channel['branch_bn'];
        }

        return $sdf;
    }
}
