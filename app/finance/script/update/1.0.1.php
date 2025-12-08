<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

echo PHP_EOL.' uninstall accounting...';
//卸载app accounting  accountinganalysis
$shell->exec_command("uninstall accounting");
$shell->exec_command("uninstall accountinganalysis");