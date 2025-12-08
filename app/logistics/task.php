<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logistics_task{

    function post_install($options){

        kernel::single('base_initial', 'logistics')->init();
    }
}
?>