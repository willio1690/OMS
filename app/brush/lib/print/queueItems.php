<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-25
 * @describe print_queue_items打印数据整理
 */
class brush_print_queueItems{

    /**
     * queueItems
     * @param mixed $oriData ID
     * @param mixed $corp corp
     * @param mixed $field field
     * @return mixed 返回值
     */

    public function queueItems(&$oriData, $corp, $field) {
        $pre = __FUNCTION__ . '.';
        foreach($oriData as $key => $value) {
            foreach($field as $f) {
                $oriData[$key][$pre . $f] = '';
            }
        }
    }
}