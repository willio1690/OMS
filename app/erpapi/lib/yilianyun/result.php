<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_yilianyun_result extends erpapi_result
{

    /**
     * 业务返回码
     * 100000 操作成功，可以获取rsp_content业务数据
     * 600000 处理中，因网络超时等原因所致，需要调用方重试
     * 999999 操作失败
     * @return string
     */
    function get_status()
    {
        if (isset($this->response['error']) && $this->response['error'] == 0) {
            return 'succ';
        }
        return 'fail';
    }

    function get_msg_id()
    {
        return $this->response['msg_id'];
    }

    /**
     * 返回数据
     * @return mixed
     */
    function get_data()
    {
        if (isset($this->response['body'])) {
            return is_string($this->response['body']) ? json_decode($this->response['body'], true) : $this->response['body'];
        }
    }

    /**
     * 业务返回码描述
     * @return string
     */
    function get_err_msg()
    {
        return $this->response['error_description'];
    }

    /**
     * 业务成功状态
     * success :true, 业务失败状态 success :false
     * @return mixed
     */
    function get_result()
    {
        if (isset($this->response['error'])) {
            return $this->response['error'];
        }
    }


    function get_code()
    {
        return $this->response['error'];
    }
}