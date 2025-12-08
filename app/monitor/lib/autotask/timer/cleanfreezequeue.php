<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author:
 * @Vsersion:
 * @Describe: 定时清理  
 *          sdb_ome_branch_freeze_queue
 *          sdb_ome_material_freeze_queue
 *          sdb_material_basic_material_stock_freeze
 */
class monitor_autotask_timer_cleanfreezequeue
{

    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
        $db = kernel::database();


        $cleanTime = strtotime('yesterday');
        $cleanTime = date('Y-m-d 00:00:00', $cleanTime);


        $branch_freeze_queue_sql = "DELETE FROM sdb_ome_branch_freeze_queue
                    WHERE status='2' AND up_time<'{$cleanTime}'";
        print_R($branch_freeze_queue_sql."\n");
        $db->exec($branch_freeze_queue_sql);


        $material_freeze_queue_sql = "DELETE FROM sdb_ome_material_freeze_queue
                    WHERE status='2' AND up_time<'{$cleanTime}'";
        print_R($material_freeze_queue_sql."\n");
        $db->exec($material_freeze_queue_sql);


        $material_stock_freeze_sql = "DELETE FROM sdb_material_basic_material_stock_freeze
                    WHERE num='0' AND up_time<'{$cleanTime}' AND at_time<>up_time";
        print_R($material_stock_freeze_sql."\n");
        $db->exec($material_stock_freeze_sql);

        return true;
    }

}
