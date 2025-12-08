<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class siso_task{

    function post_install(){
    	kernel::single('base_initial', 'siso')->init();
    }
   

    function install_options(){
        return array(
                
            );
    }
}
