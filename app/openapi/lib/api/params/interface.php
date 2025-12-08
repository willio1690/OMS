<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

interface openapi_api_params_interface{

    function checkParams($method,$params,&$sub_msg);
	
    function description($method);
}