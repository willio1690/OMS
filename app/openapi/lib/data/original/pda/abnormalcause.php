<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @describe pda 获取异常原因
 * @author pangxp
 */
class openapi_data_original_pda_abnormalcause{

    /**
     * 获取List
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */

    public function getList($filter, $offset = 0, $limit = 100){
        $abnormal_cause = app::get('wms')->model('abnormal_cause');
        $count = $abnormal_cause->count($filter);
        $data = $abnormal_cause->getList('*', $filter, $offset, $limit);
        return array(
        	'state' => 0, // 0成功 1失败  pda那边提供的接口约定  尽量以他们的为准了
            'lists' => $data,
            'count' => $count,
        );
    }

}