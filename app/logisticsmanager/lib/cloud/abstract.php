<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class logistics_waybill_abstract
{
   

    protected function success($msg = 'success', $data = [])
    {
        $result = [
            'rsp' => 'succ',
            'msg' => $msg,
            'data' => $data,
        ];
        return $result;
    }

    protected function error($msg = '')
    {
        $result = [
            'rsp' => 'fail',
            'msg' => $msg,
        ];
        return $result;
    }


    

}