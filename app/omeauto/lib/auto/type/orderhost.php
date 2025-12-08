<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单达人
 */

class omeauto_auto_type_orderhost  extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {
    
    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {

        if ($params['type'] == 'all') {
            $params['author'] = $params['room'] = '';
        } elseif ($params['type'] == 'assign') {
            $params['author'] = trim($params['author']);
            $params['room']   = trim($params['room']);
            if (empty($params['author'])) {
                return "指定达人订单，达人ID为必填。";
            }
        }

        return true;
    }
    
    /**
     * 生成规则字串
     * 
     * @param Array $params
     * @return String
     */
    public function roleToString($params) {

        $params['author'] = str_replace('，', ',', trim($params['author']));
        $params['room']   = str_replace('，', ',', trim($params['room']));
        $role = [
            'role'      => 'orderhost', 
            'caption'   => '', 
            'content'   => [
                'author'    =>  $params['author'], 
                'room'      =>  $params['room'], 
                'type'      =>  $params['type']
            ]
        ];
        switch ($params['type']) {
            case 'all':
                $role['caption'] = '所有达人订单';
                break;
            case 'assign':
                $author = $params['author'] ? '达人ID：'.$params['author'] : '';
                $room   = $params['room'] ? '直播间ID：'.$params['room'] : '';

                $role['caption'] = sprintf('指定达人订单 %s%s', $author, $room);
                break;
        }
        
        return json_encode($role);
    }
    
    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {

        if ($this->content['type'] == 'all') {
            // 所有达人
            foreach ($item->getOrders() as $order) {
                foreach($order['objects'] as $objects){
                    if ($objects['author_id'] || ($objects['addon'] && $objects['addon']['room_id'])) {
                        return true;
                    }
                }
            }
            return false;
        } elseif ($this->content['type'] == 'assign') {
            // 指定达人
            $author_config = $this->content['author'];
            $room_config   = $this->content['room'];

            foreach ($item->getOrders() as $order) {
                foreach($order['objects'] as $objects){
                    if ($objects['author_id'] && in_array($objects['author_id'], explode(',', $author_config))) {
                        if ($room_config) {
                            if ($objects['addon'] && $objects['addon']['room_id'] && in_array($objects['addon']['room_id'], explode(',', $room_config))) {
                                return true;
                            }
                        } else {
                            return true;
                        }
                    }
                }
            }
            return false;
        }
        return true;
    }
}