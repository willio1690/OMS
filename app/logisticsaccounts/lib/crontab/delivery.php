<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

require_once(dirname(__FILE__) .'/config.php');
    cachemgr::init(false);
   kernel::single('logisticsaccounts_estimate')->crontab_delivery();
