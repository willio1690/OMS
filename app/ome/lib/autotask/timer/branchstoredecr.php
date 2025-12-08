<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 后置更新mysql的库存数量
 * 有扣减库存的数据需要把冻结库存也扣减掉
 *
 * @author
 * @version 0.1
 */

class ome_autotask_timer_branchstoredecr
{
    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);

        $now = time();
        base_kvstore::instance('ome/branch/stock/queue')->fetch('sync-lastexectime', $lastExecTime);
        !$lastExecTime && $lastExecTime = strtotime('-5 seconds');
        if ($now - $lastExecTime < 5) {
            return false; // 时间未到
        }
        base_kvstore::instance('ome/branch/stock/queue')->store('sync-lastexectime', $now);

        $db = kernel::database();

        $whereAtTime = " at_time between '" . date('Y-m-d H:i:s', $lastExecTime) . "' AND '" . date('Y-m-d H:i:s', $now) . "'";

        // 获取未处理的数据
        $selectSql = "SELECT branch_id,product_id,sum(quantity) as quantity
            FROM sdb_ome_branch_stock_queue
            WHERE " . $whereAtTime . " AND status='0'
            GROUP BY branch_id,product_id";

        $list = $db->select($selectSql);
        if (!$list) {
            return false;
        }

        // 状态更新为……处理中
        $upIngSql = "UPDATE sdb_ome_branch_stock_queue
            SET status='1'
            WHERE " . $whereAtTime . " AND status='0'";
        if (!$db->exec($upIngSql)) {
            return false;
        }

        foreach ($list as $item) {
            $sql_set = "`last_modified`=" . time() . ",`store`=IF((CAST(`store` AS SIGNED)+{$item['quantity']})>0,`store`+{$item['quantity']},0)";

            $upStatus = true;
            $db->beginTransaction();
            /*
            // 扣减库存的时候需要把冻结一起扣减掉
            if ($item['quantity'] < 0){
            $sql_set .= ", `store_freeze`=IF((CAST(`store_freeze` AS SIGNED)+{$item['quantity']})>0,`store_freeze`+{$item['quantity']},0)";
            }
             */
            $sql = "UPDATE `sdb_ome_branch_product`
                    SET " . $sql_set . "
                    WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}";

            // 如果是扣减库存需要判断库存是否足够
            if ($item['quantity'] < 0) {
                $sql .= " AND `store` + {$item['quantity']} >=0";
            }

            if (!$db->exec($sql)) {
                $upStatus = false;
            }

            if (1 !== $db->affect_row()) {
                $upStatus = false;
            }

            // 更新库存失败，修改队列表的状态
            if (!$upStatus) {
                $db->rollBack();
                $upFailSql = "UPDATE sdb_ome_branch_stock_queue
                    SET status='3'
                    WHERE " . $whereAtTime . " AND branch_id={$item['branch_id']} AND product_id={$item['product_id']} AND status='1'";
                $db->exec($upFailSql);
            } else {
                // 库存更新成功，删除队列数据
                $delSql = "DELETE
                    FROM sdb_ome_branch_stock_queue
                    WHERE " . $whereAtTime . " AND branch_id={$item['branch_id']} AND product_id={$item['product_id']} AND status='1'";

                $delSql = "UPDATE sdb_ome_branch_stock_queue
                    SET status='2'
                    WHERE " . $whereAtTime . " AND branch_id={$item['branch_id']} AND product_id={$item['product_id']} AND status='1'";
                $db->exec($delSql);
                $db->commit();
            }
        }

        // 检测当前时间段是否存在status=3的数据，存在即报警
        $checkSql = "SELECT * FROM sdb_ome_branch_stock_queue WHERE " . $whereAtTime . " AND status='3'";
        $failList = $db->selectrow($checkSql);
        if ($failList) {
            kernel::single('monitor_event_notify')->addNotify('store_freeze_abnormal', [
                'errmsg' => $checkSql . " 消费库存有异常 " . kernel::base_url(1),
            ]);
        }
        return true;
    }
}
