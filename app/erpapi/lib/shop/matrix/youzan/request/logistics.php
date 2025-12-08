<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016/5/10
 * @describe 物流相关 请求接口类
 */
class erpapi_shop_matrix_youzan_request_logistics extends erpapi_shop_request_logistics {

    /**
     * 获取CorpServiceCode
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */

    public function getCorpServiceCode($sdf) {
        $params = array(
            'cp_code' => $sdf['cp_code']
        );
        $title = '获取物流商服务类型';
        $result = $this->__caller->call(STORE_WAYBILL_SERVICE_SEARCH,$params,array(),$title, 10, $params['cp_code']);
        
        return $result;
    }
}