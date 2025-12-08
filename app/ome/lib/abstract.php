<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class ome_abstract
{
    public $page_size = 100;
    
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        //--
    }
    
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