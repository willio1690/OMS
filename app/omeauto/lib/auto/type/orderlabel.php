<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单标记分组类型
 * 支持通过订单标记（如闪购订单标记is_xsdbc）进行分组
 */
class omeauto_auto_type_orderlabel extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 在显示前为模板做一些数据准备工作
     * 
     * @param object $tpl
     * @return void
     */
    public function _prepareUI(& $tpl, $val) {

        // 从数据库获取订单标记类型，使用tail限制数量
        $label_rows = app::get('omeauto')->model('order_labels')->getList('label_id,label_code,label_name', array(), 0, 50);

        $labelList = array();
        foreach ($label_rows as $row) {
            $labelList[] = array('label_id' => $row['label_id'], 'label' => $row['label_name']);
        }

        $tpl->pagedata['label_types'] = $labelList;
    }

    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @return mixed
     */
    public function checkParams($params) {

        if (empty($params['label_id'])) {
            return "你还没有选择订单标记类型\n\n请选择以后再试！！";
        }

        return true;
    }
    
    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {
        
        if (!empty($this->content) && !empty($this->content['label_id'])) {
            $labelIds = is_array($this->content['label_id']) ? $this->content['label_id'] : array($this->content['label_id']);
            
            foreach ($item->getOrders() as $order) {
                // 检查订单是否包含指定的标记类型
                if (!$this->hasOrderLabel($order, $labelIds)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查订单是否包含指定的标记类型
     * 
     * @param array $order 订单数据
     * @param array $labelIds 标记类型ID数组
     * @return boolean
     */
    private function hasOrderLabel($order, $labelIds) {
        
        // 检查订单标签
        if (isset($order['labels']) && is_array($order['labels'])) {
            foreach ($order['labels'] as $label) {
                if (in_array($label['label_id'], $labelIds)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * 生成规则字串
     * 
     * @param Array $params
     * @return String
     */
    public function roleToString($params) {
        
        // 处理多选的情况
        $labelIds = is_array($params['label_id']) ? $params['label_id'] : array($params['label_id']);
        
        // 一次性查询所有需要的订单标记类型名称
        $label_rows = app::get('omeauto')->model('order_labels')->getList('label_id,label_name', array('label_id' => $labelIds));
        
        // 构建ID到名称的映射
        $labelIdToName = array();
        foreach ($label_rows as $row) {
            $labelIdToName[$row['label_id']] = $row['label_name'];
        }
        
        // 获取名称列表
        $labelTypeNames = array();
        foreach ($labelIds as $labelId) {
            $labelTypeNames[] = isset($labelIdToName[$labelId]) ? $labelIdToName[$labelId] : $labelId;
        }
        
        $labelTypeNameStr = implode('、', $labelTypeNames);
        $caption = sprintf('订单标记 %s', $labelTypeNameStr);
        
        $role = array(
            'role' => 'orderlabel', 
            'caption' => $caption, 
            'content' => array(
                'label_id' => $params['label_id']
            )
        );
        
        return json_encode($role);
    }
}
