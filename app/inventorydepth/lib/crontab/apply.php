<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

require_once(dirname(__FILE__) .'/config.php');
cachemgr::init(false);
echo "regulation-apply begin(".date('Y-m-d H:i:s',time()).")...\n";
kernel::single('inventorydepth_logic_stock')->start();

kernel::single('inventorydepth_logic_frame')->start();
echo "regulation-apply end(".date('Y-m-d H:i:s',time()).")...\n";
