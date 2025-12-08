<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsaccounts_task{
    function pre_uninstall(){
        app::get('logisticsaccounts')->setConf('logisticsaccounts.delivery.downtime','');
    }
}

?>