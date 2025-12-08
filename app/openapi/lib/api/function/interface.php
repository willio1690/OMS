<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

interface openapi_api_function_interface{

    function getList($params,&$code,&$sub_msg);
    function add($params,&$code,&$sub_msg);
}