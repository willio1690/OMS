<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_event_response{

    private $_response = '';

    function __construct(){
        // $this->_response = kernel::single('middleware_message');
    }

    /**
     * send_succ
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function send_succ($msg=''){
        // return $this->_response->output('succ',$msg);
        $rs = array(
            'rsp'      => 'succ',
            'msg'      => $msg,
            'msg_code' => null,
            'data'     => null,
        );
        return $rs;
    }

    /**
     * send_error
     * @param mixed $msg msg
     * @param mixed $msg_code msg_code
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function send_error($msg, $msg_code=null, $data=null){
        // return $this->_response->output($rsp='fail', $msg, $msg_code, $data);

        $rs = array(
            'rsp'      => 'fail',
            'msg'      => $msg,
            'msg_code' => $msg_code,
            'data'     => $data,
        );
        return $rs;
    }
}
