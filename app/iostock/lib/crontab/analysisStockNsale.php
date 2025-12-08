<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

require_once(dirname(__FILE__) .'/config.php');
	cachemgr::init(false);
    echo "analysisStockNsale begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('iostock_crontab_script_analysisStockNsale')->analysisStockNsale();
//     kernel::single('omeanalysts_misc_task')->hour();
    echo "analysisStockNsale end(".date('Y-m-d H:i:s',time()).")...\n";
