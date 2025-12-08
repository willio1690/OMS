<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_openapi_pekon_result extends erpapi_result
{
    /**
     * 设置_response
     * @param mixed $response response
     * @param mixed $format format
     * @return mixed 返回操作结果
     */
    public function set_response($response, $format)
    {

        $response = kernel::single('erpapi_format_' . $format)->data_decode($response);

        if((int)$this->responseHttpCode != 200){
            $rsp = 'fail';
        }else{
            if($response['code'] == '10000'){
                $rsp = 'succ';
            }else{
                $rsp = 'fail';
            }
        }
        $this->response = [
            'msg_id'  => $response['catId'],
            'rsp'     => $rsp,
            'data'    => $response['data'],
            'res'     => '',
            'err_msg' => $response['message'],
        ];

        if ($response['code'] == '10000' && $response['data']) {
            $this->response['data'] = $response['data'];
        }

        return $this;
    }

}
