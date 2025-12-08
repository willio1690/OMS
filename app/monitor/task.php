<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/24
 * @Describe: 默认安装数据
 */

class monitor_task
{

    public function post_install()
    {
        kernel::log('Initial monitor');
        kernel::single('base_initial', 'monitor')->init();
    }
}
