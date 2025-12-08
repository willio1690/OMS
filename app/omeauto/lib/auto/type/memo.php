<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2020/10/29 16:14:22
 * @describe: 类
 * ============================
 */

class omeauto_auto_type_memo  extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {
    
    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {
        $memo_type = $params['memo_type'];
        if(!$memo_type) {
            return "你还没有选择备注类型\n\n请选择以后再试！！";
        }
        $memo_scope = $params['memo_scope'];
        if(!$memo_scope) {
            return "你还没有选择筛选范围\n\n请选择以后再试！！";
        }
        $memo_text = $params['memo_text'];
        if(!$memo_text) {
            return "你还没有填写备注关键字\n\n请填写以后再试！！";
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
        $memo_type = $params['memo_type'];
        $memo_scope = $params['memo_scope'];
        $memo_text = $params['memo_text'];

        $caption = '';
        if(in_array('custom', $memo_type)) {
            $caption .= '买家备注';
        }
        if(in_array('shop', $memo_type)) {
            $caption .= ($caption ? ',' : '') . '客服备注';
        }
        $caption .= $memo_scope == 'part' ? '包含部分关键字' : "包含全部关键字";
        $caption .= '('.$memo_text.')';
        $role = array('role' => 'memo', 'caption' => $caption, 'content'=> array('memo_type' => $memo_type, 'memo_scope'=>$memo_scope , 'memo_text' => $memo_text));
        
        return json_encode($role);
    }
    
    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {
        $memo_text = explode('#', $this->content['memo_text']);
        if(empty($memo_text)) {
            return false;
        }
        foreach ($item->getOrders() as $order) {
            if(empty($memo_text)) {
                break;
            }
            if(in_array('custom', $this->content['memo_type'])) {
                $custom_mark = $order['custom_mark'];
                $custom_mark = kernel::single('ome_func')->format_memo($custom_mark);
                foreach ((array)$custom_mark as $k=>$v){
                    foreach ($memo_text as $mk => $memo) {
                        if(preg_match("/{$memo}/", $v['op_content'])) {
                            if($this->content['memo_scope'] == 'part') {
                                $memo_text = array();
                                break 3;
                            }
                            unset($memo_text[$mk]);
                        }
                    }
                }
            }
            if(in_array('shop', $this->content['memo_type'])) {
                $mark_text = $order['mark_text'];
                $mark_text = kernel::single('ome_func')->format_memo($mark_text);
                foreach ((array)$mark_text as $k=>$v){
                    foreach ($memo_text as $mk => $memo) {
                        if(preg_match("/{$memo}/", $v['op_content'])) {
                            if($this->content['memo_scope'] == 'part') {
                                $memo_text = array();
                                break 3;
                            }
                            unset($memo_text[$mk]);
                        }
                    }
                }
            }
        }
        if(empty($memo_text)) {
            return true;
        }
        return false;
    }
}