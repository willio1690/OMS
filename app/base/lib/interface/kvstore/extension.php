<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


interface base_interface_kvstore_extension{

    function increment($key, $offset=1);

    function decrement($key, $offset=1);
}