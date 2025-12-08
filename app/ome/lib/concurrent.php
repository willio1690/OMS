<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_concurrent{
    
	 /**
     * 自动清除同步日志
     * 每天检测将超2天的日志数据清除
     */
    public function clean(){
        
        $now = strtotime(date("Y-m-d"));
        $db = kernel::database();

        $where = " WHERE `current_time`<'".($now-2*24*60*60)."' ";
        $del_sql = " DELETE FROM `sdb_ome_concurrent` $where ";
        $db->exec($del_sql);
        
        $del_sql = 'DELETE FROM `sdb_ome_concurrent` WHERE `current_time` IS NULL';
        $db->exec($del_sql);

        $del_sql = 'OPTIMIZE TABLE `sdb_ome_concurrent`';
        $db->exec($del_sql);
    }
    
}