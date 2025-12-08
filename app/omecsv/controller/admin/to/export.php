<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class omecsv_ctl_admin_to_export extends desktop_controller{

    function treat(){
        $finder = kernel::single('omecsv_to_export');
        $finder->main();
    }
}
