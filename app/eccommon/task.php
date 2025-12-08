<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class eccommon_task
{

    /**
     * post_install
     * @return mixed 返回值
     */
    public function post_install()
    {
        kernel::log('Initial eccommon');
        kernel::single('base_initial', 'eccommon')->init();

        kernel::log('Initial Regions');
        kernel::single('eccommon_regions_mainland')->install();
    }//End Function
}//End Class
