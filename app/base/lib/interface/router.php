<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


interface base_interface_router{

    function __construct($app);

    function gen_url($params=array());

    function dispatch($query);

}
