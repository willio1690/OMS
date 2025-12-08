<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class omecsv_ctl_admin_to_import extends desktop_controller{

    function treat(){
        $_GET['ctler'];
        $_GET['add'];
        $finder = kernel::single('omecsv_to_import');
        $finder->main();
    }

}
