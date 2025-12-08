<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class invoice_event_response
{
    private $_response = '';
    function __construct()
    {

    }

    public function send_succ($msg = '')
    {
        // return $this->_response->output('succ',$msg);
        $rs = array (
            'rsp'      => 'succ',
            'msg'      => $msg,
            'msg_code' => null,
            'data'     => null,
        );
        return $rs;
    }

    public function send_error($msg, $msg_code = null, $data = null)
    {
        // return $this->_response->output($rsp='fail', $msg, $msg_code, $data);

        $rs = array (
            'rsp'      => 'fail',
            'msg'      => $msg,
            'msg_code' => $msg_code,
            'data'     => $data,
        );
        return $rs;
    }
}
