<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_openapi_dms_response_aftersale extends erpapi_store_response_aftersale
{
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params)
    {
        $sdf = parent::add($params);

        // 退货仓
        $flow = app::get('o2o')->model('branch_flow')->dump([
            'to_store_bn' => $params['store_bn'],
        ]);
        if (!$flow) {
            $this->__apilog['result']['msg'] = sprintf('[%s]未匹配退货仓', $params['store_bn']);
            return false;
        }

        $channel = app::get('o2o')->model('channel')->dump($flow['channel_id']);
        if (!$channel['reship_branch_id']) {
            $this->__apilog['result']['msg'] = sprintf('[%s]未匹配退货仓', $params['store_bn']);
            return false;
        }

        $sdf['warehouse_code'] = $channel['reship_branch_bn'];
        $sdf['status']         = 'APPLY';

        return $sdf;
    }
}
