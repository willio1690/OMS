<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 后置更新mysql的冻结数量
 *
 *
 * @author
 * @version 0.1
 */

class ome_autotask_timer_branchfreezedecr extends ome_autotask_timer_common
{
    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);

        $limit = 1000; // 默认每次获取的数量

        $now = time();
        base_kvstore::instance('ome/branch/freeze/queue')->fetch('lastexectime', $lastExecTime);
        !$lastExecTime && $lastExecTime = strtotime('-5 seconds');
        if ($now - $lastExecTime < 1) {
            $error_msg = 'its not time yet';
            return true; // 时间未到
        }
        base_kvstore::instance('ome/branch/freeze/queue')->store('lastexectime', $now);

        $db = kernel::database();

        // ==================================================================
        // ==================================================================
        // $pid = microtime(1);
        $pid = (int)(microtime(true)*1000000000);

        // 获取未处理和失败的数据
        $selectSql = "SELECT id
            FROM sdb_ome_branch_freeze_queue
            WHERE `status` IN ('0','3') AND pid = '0' LIMIT {$limit}";
        $list = $db->select($selectSql);
        if (!$list) {
            $error_msg = 'no info to work';
            return true;
        }

        $ids = "'" . implode("','", array_column($list, 'id')) . "'";

        // 状态更新为……处理中
        $upStartSql = "UPDATE sdb_ome_branch_freeze_queue
            SET `status`='1', pid= '{$pid}'
            WHERE id IN ({$ids}) AND pid = '0'";
        if (!$db->exec($upStartSql)) {
            $error_msg = 'update status to underway fail';
            return true;
        }

        // 根据pid获取数据
        $selectSql = "SELECT id, branch_id, product_id, quantity
            FROM sdb_ome_branch_freeze_queue
            WHERE `status` = '1' AND pid = '{$pid}'";
        $list = $db->select($selectSql);
        if (!$list) {
            $error_msg = 'get underway fail';
            return true;
        }

        // 根据branch_id+product_id分组
        $groupList = [];
        foreach ($list as $k => $v) {
            $key = $v['branch_id'] . '-' . $v['product_id'];
            if (!isset($groupList[$key])) {
                $groupList[$key] = [
                    'quantity'   =>  0,
                    'branch_id'  =>  $v['branch_id'],
                    'product_id' =>  $v['product_id'],
                ];
            }
            $groupList[$key]['quantity'] += $v['quantity'];
            $groupList[$key]['ids'][] = $v['id'];
        }

        // 对 $groupList 按照键 $key 升序排序
        ksort($groupList);

        // 更新仓冻结
        foreach ($groupList as $key => $item) {
            // $db->beginTransaction();

            $ids = "'" . implode("','", $item['ids']) . "'";
            try {
                $dateline = time();

                $sql = "UPDATE `sdb_ome_branch_product`
                        SET `last_modified`={$dateline},
                            `store_freeze`=`store_freeze`+{$item['quantity']}
                        WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']} ";
                        // AND (CAST(`store_freeze` AS SIGNED)+{$item['quantity']})>=0 

                if (!$db->exec($sql) || 1 !== $db->affect_row()) {
                    // 更新冻结失败，修改队列表的状态
                    // $db->rollBack();

                    $upFailSql = "UPDATE sdb_ome_branch_freeze_queue
                        SET `status`='3', `pid`='0'
                        WHERE id IN ({$ids})";

                    $db->exec($upFailSql);

                } else {
                    // 冻结更新成功，删除队列数据
                    $delSql = "DELETE
                        FROM sdb_ome_branch_freeze_queue
                        WHERE id IN ({$ids})";

                    // $delSql = "UPDATE sdb_ome_branch_freeze_queue
                    //     SET `status`='2'
                    //     WHERE id IN ({$ids})";
                    $db->exec($delSql);
                    // $db->commit();
                }
            } catch (Exception $e) {
                // 发生异常时回滚事务
                // $db->rollBack();
                $upFailSql = "UPDATE sdb_ome_branch_freeze_queue
                        SET `status`='3', `pid`='0'
                        WHERE id IN ({$ids})";

                $db->exec($upFailSql);
                !$error_msg && $error_msg = $e->getMessage();
            }
        }

        // // 检测当前时间段是否存在status=3的数据，存在即报警
        // $checkSql = "SELECT * FROM sdb_ome_branch_freeze_queue WHERE " . $whereAtTime . " AND status='3'";
        // $failList = $db->selectrow($checkSql);
        // if ($failList) {
        //     kernel::single('monitor_event_notify')->addNotify('store_freeze_abnormal', [
        //         'errmsg' => $checkSql . " 消费冻结有异常 " . kernel::base_url(1),
        //     ]);
        // }

        return true;
    }
}
