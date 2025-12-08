<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class pam_finder_log{

    public $column_event_time = '时间';
    public $column_event_time_width = '160';
    public $column_event_time_order = '10';
    /**
     * column_event_time
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_event_time($row, $list) {

        return date('Y-m-d H:i:s', $row['event_time']);
    }

    public $column_action = '动作';
    public $column_action_width = '220';
    public $column_action_order = '20';
    /**
     * column_action
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_action($row, $list) {
        return $row['event_data'];
    }

}
