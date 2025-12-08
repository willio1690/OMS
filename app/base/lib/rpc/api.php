<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_rpc_api{

    function __construct(){
    }

    private function break_client($message='Process is running'){
        header('Connection: close');
        header('Content-length: '.strlen($message));
        echo $message;
    }

}
