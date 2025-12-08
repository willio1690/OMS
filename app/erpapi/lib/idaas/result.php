<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_idaas_result extends erpapi_result
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

        $rsp['rsp']     = $response['rsp'];
        $rsp['msg_id']  = $response['request_id'];
        $rsp['err_msg'] = $response['error_msg'];
        $rsp['res']     = $response['code'] ? $response['code'] : $response['error_code'];
        $rsp['data']    = $response['data'];
        $this->response = $rsp;
        return $this;
    }
}
