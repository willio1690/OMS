<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2022/8/25 16:00:48
 * @describe 发货单布尔型同步状态
 */
class console_delivery_bool_sync {
    #发起请求
    const __SEND_CODE         = 0x001;
    #请求成功
    const __SEND_SUCC         = 0x002;
    #请求失败
    const __SEND_FAIL         = 0x004;
    #取消失败
    const __CANCEL_FAIL       = 0x008;
    #取消成功
    const __CANCEL_SUCC       = 0x010;
    #查询失败
    const __SEARCH_FAIL       = 0x020;
    #查询成功
    const __SEARCH_SUCC       = 0x040;

    /**
     * 获取BoolSync
     * @param mixed $filter filter
     * @return mixed 返回结果
     */

    public function getBoolSync($filter) {
        $where = array();
        if($filter['in']) {
            $in = 0;
            foreach((array)$filter['in'] as $val) {
                $in = $in | $val;
            }
            $where[] = 'sync & ' . $in . ' = ' . $in;
        }
        if($filter['out']) {
            $out = 0;
            foreach((array)$filter['out'] as $val) {
                $out = $out | $val;
            }
            $where[] = '!(sync & ' . $out . ')';
        }
        if(empty($where)) {
            return array();
        }
        $sql = 'select sync from sdb_ome_delivery where ' . implode(' and ', $where) . ' group by sync';
        $boolData = kernel::database()->select($sql);
        $boolStatus = array('-1');
        foreach($boolData as $val) {
            $boolStatus[] = $val['sync'];
        }
        return $boolStatus;
    }
}