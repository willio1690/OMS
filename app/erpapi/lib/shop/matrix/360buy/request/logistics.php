<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_360buy_request_logistics extends erpapi_shop_request_logistics
{
    /**
     * 搜索Address_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function searchAddress_callback($response, $callback_params){

        if ($response['rsp']=='succ') {
            $data = json_decode($response['data'],true);

            foreach ((array)$data['address_result'] as $key => $value ) {
                $data['address_result'][$key]['get_def']   = empty($value['get_def']) ? 'false' : 'true';
                $data['address_result'][$key]['cancel_def']   = $value['address_type'] == '0' ? 'true' : 'false';
                $data['address_result'][$key]['address_type'] = $value['address_type'];
            }

            $response['data'] = json_encode($data);
        }

        return parent::searchAddress_callback($response, $callback_params);   
    }
}