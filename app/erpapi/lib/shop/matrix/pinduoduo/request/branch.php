<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_pinduoduo_request_branch extends erpapi_shop_request_branch {

    protected function _formatProvinceData($data) {
        $return = [];
        if (is_array($data) && is_array($data['data'])) {
            foreach($data['data'] as $v) {
                $return[] = [
                    'province_id' => $v['id'],
                    'province' => $v['region_name'],
                ];
            }
        }
        return $return;
    }

    protected $areaOutregionId = 'id';
    protected $areaOutregionName = 'region_name';
    protected $areaOutparentId = 'parent_id';
    protected function _formatAreasByProvince($data) {
        return $data['data'] ? [$data['data']]: [];
    }
}