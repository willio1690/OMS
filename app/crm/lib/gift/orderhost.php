<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/3/19
 * Time: 18:04
 */
class crm_gift_orderhost
{

    /**
     * 处理
     * @param mixed $ruleBase ruleBase
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function process($ruleBase, $sdf) {

        $is_host = false;
        if ($ruleBase['filter_arr']['is_host']['type'] == 'infinite') {
            // 无限制
            $is_host = true;
            $msg     = '';

        } elseif ($ruleBase['filter_arr']['is_host']['type'] == 'all') {
            // 所有达人
            foreach ($sdf['objects'] as $k => $objects) {
                if ($objects['author_id'] || $objects['author_name'] || (is_array($objects['addon']) && $objects['addon']['room_id'])) {
                    $is_host = true;
                    break;
                }
            }
            $msg = '达人订单才赠送';

        } elseif ($ruleBase['filter_arr']['is_host']['type'] == 'assign') {
            // 指定达人
            $author_arr = array_filter(explode(',', $ruleBase['filter_arr']['is_host']['author']));
            $room_arr   = array_filter(explode(',', $ruleBase['filter_arr']['is_host']['room']));
            foreach ($sdf['objects'] as $k => $objects) {
                if ($objects['author_id'] && in_array($objects['author_id'], $author_arr)) {
                    if ($room_arr) {
                        // 指定了直播间ID
                        if (is_array($objects['addon']) && $objects['addon']['room_id'] && in_array($objects['addon']['room_id'], $room_arr)) {
                            $is_host = true;
                        }
                    } else {
                        $is_host = true;
                    }
                }
            }
            $msg = '指定达人订单才赠送';
        }

        if (!$is_host) {
            return array(false, $msg);
        }

        return array(true);
    }
}