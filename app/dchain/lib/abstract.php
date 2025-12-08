<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class dchain_abstract
{
    /**
     * 成功输出
     *
     * @param string $msg
     * @param string $data
     * @return array
     */
    /**
     * succ
     * @param mixed $msg msg
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function succ($msg='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'data'=>$data);
    }

    /**
     * error
     * @param mixed $error_msg error_msg
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function error($error_msg, $data=null)
    {
        return array('rsp'=>'fail', 'msg'=>$error_msg, 'error_msg'=>$error_msg, 'data'=>$data);
    }
}
