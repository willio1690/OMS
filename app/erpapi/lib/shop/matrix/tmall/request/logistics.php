<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2017/2/16
 * @describe 物流相关 请求接口类
 */
class erpapi_shop_matrix_tmall_request_logistics extends erpapi_shop_request_logistics {

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
        $result = $this->__caller->call(STORE_CN_WAYBILL_II_SEARCH,$params,array(),$title, 10, $params['cp_code']);

        return $result;
    }

    /**
     * timerule
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function timerule($sdf)
    {
        $params = [
            'api' => 'taobao.open.seller.biz.logistic.time.rule',
            'data' => json_encode([
                'last_pay_time' => date('H:i', $sdf['cutoff_time']),
                'last_delivery_time' => date('H:i', $sdf['latest_delivery_time']),
            ]),
        ];

        $title = '商家自定义发货时效';
        $result = $this->__caller->call(TAOBAO_COMMON_TOP_SEND,$params,array(),$title, 10, $this->__channelObj->channel['shop_bn']);
        return $result;
    }
}