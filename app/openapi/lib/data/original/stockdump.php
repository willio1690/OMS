<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_stockdump
{
    /**
     * 获取List
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getList($filter, $offset=0, $limit=100)
    {

        $stockdumpMdl = app::get('console')->model('stockdump');
        $itemMdl = app::get('console')->model('stockdump_items');
        $branchMdl = app::get('ome')->model('branch');

        $count = $stockdumpMdl->count($filter);

        if (!$count) {
            return ['lists' => [],'count' => 0,];
        }

        $lists = [];

        $stockdumpList = $stockdumpMdl->getList('*', $filter, $offset, $limit);

        $branchList = $branchMdl->getList('branch_id,branch_bn', [
            'branch_id' => array_merge(array_column($stockdumpList, 'from_branch_id'), array_column($stockdumpList, 'to_branch_id'))
        ]);
        $branchList = array_column($branchList, null, 'branch_id');

        foreach ($stockdumpList as $stockdump) {
            $l = [
                'stockdump_bn' => $stockdump['stockdump_bn'],
                'create_time' => date('Y-m-d H:i:s', $stockdump['create_time']),
                'operator_name' => $stockdump['operator_name'],
                'in_status' => $stockdump['in_status'],
                'self_status' => $stockdump['self_status'],
                'transfer_channel' => $stockdump['transfer_channel'],
                'source_from' => $stockdump['source_from'],
                'confirm_type' => $stockdump['confirm_type'],
                'confirm_name' => $stockdump['confirm_name'],
                'confirm_time' => $stockdump['confirm_time'] ? date('Y-m-d H:i:s', $stockdump['confirm_time']) : '',
                'memo' => $stockdump['memo'],
                'branch_memo' => $stockdump['branch_memo'],
                'from_branch_bn' => $branchList[$stockdump['from_branch_id']]['branch_bn'],
                'to_branch_bn' => $branchList[$stockdump['to_branch_id']]['branch_bn'],
            ];

            $lists[$stockdump['stockdump_id']] = $l;
        }


        $items = $itemMdl->getList('*', [
            'stockdump_id' => array_column($stockdumpList, 'stockdump_id'),
        ]);
        foreach ($items as $item) {
            $i = [
                'bn' => $item['bn'],
                'product_name' => $item['product_name'],
                'num' => $item['num'],
                'in_nums' => $item['in_nums'],
                'defective_num' => $item['defective_num'],
            ];

            $lists[$item['stockdump_id']]['items'][] = $i;
        }

        return ['lists' => array_values($lists),'count' => $count,];
    }
}