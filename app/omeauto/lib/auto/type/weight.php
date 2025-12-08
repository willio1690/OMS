<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单产品 (会有时间概念)
 */
class omeauto_auto_type_weight extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 检查输入的参数
     *
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {
        $type = intval($params['type']);
        if (!in_array($type, array(1,2,3,4))) {

            return "你还没有选择订单商品总重量的范围类型\n\n请选择以后再试！！";
        }

        $minWeight = trim($params['min_weight_'.$type]);
        $maxWeight = trim($params['max_weight_'.$type]);

        if (intval($minWeight)<0) {

            return "你还没有输入订单商品总重量的最小值\n\n请正确输入以后再试！！";
        }

        if (intval($maxWeight)<1) {

            return "你还没有输入订单商品总重量的最大值\n\n请正确输入以后再试！！";
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

        $type = intval($params['type']);
        $minWeight = trim($params['min_weight_'.$type]);
        $maxWeight = trim($params['max_weight_'.$type]);

        $caption = '';
        $role = array('role' => 'weight', 'caption' => '', 'content'=> array('min' => $minWeight, 'max'=>$maxWeight , 'type' => $type));
        switch ($type) {
            case '1':
                $role['caption'] = sprintf('订单商品总重量小于 %s 克', $maxWeight);
                break;
            case '2':
                $role['caption'] = sprintf('订单商品总重量大于等于 %s 克', $minWeight);
                break;
            case '3':
                $role['caption'] = sprintf('订单商品总重量位于 %s 克(包含) - %s 克(不包含) 之间', $minWeight, $maxWeight);
                break;
            case '4':
                $role['caption'] = sprintf('订单商品总重量等于 %s 克', $minWeight);
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
        //所有合并订单的总重量做计算
        $weight = 0;
        if (!empty($this->content)) {
            $weight = $item->getWeight();
            switch($this->content['type']) {
                case 1:
                    return ($weight < $this->content['max'] ? true : false);
                    break;
                case 2:
                    return ($weight >= $this->content['min'] ? true : false);
                    break;
                case 3:
                    return (($weight >= $this->content['min'] && $weight < $this->content['max']) ? true : false);
                    break;
                case 4:
                    return (($weight == $this->content['min']) ? true : false);
                    break;
                default:
                    return false;
                    break;
            }
        }else{
            return false;
        }

        
    }
}