<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016-01-20
 * @describe 短信平台返回数据预处理
 */
class erpapi_sms_result extends erpapi_result {

    function set_response($response, $format)
    {
        $response = kernel::single('erpapi_format_'.$format)->data_decode($response);
        
        $rsp['rsp'] = $response['res'] == 'succ' ? 'succ' : 'fail';
        $rsp['res'] = $response['code'] ? $response['code'] : $response['msg'];
        $rsp['data'] = $response['info'] ? $response['info'] : $response['data'];
        $this->response = $rsp;
        return $this;
    }
}