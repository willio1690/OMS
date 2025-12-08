<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoexlib_task{

    function post_install(){
    	kernel::single('base_initial', 'taoexlib')->init();
    }
   

    function install_options(){
        return array(
                
            );
    }
}
