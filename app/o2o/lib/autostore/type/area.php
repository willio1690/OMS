<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_autostore_type_area extends o2o_autostore_type_abstract implements o2o_autostore_type_interface {

    protected $type ='area';

    protected $config = array(
        'tmpl'      => 'admin/autostore/type/area.html',
    );

    /**
     * 检查Params
     * @param mixed $params 参数
     * @return mixed 返回验证结果
     */
    public function checkParams(&$params){

        if(!$params["area_id"]){
            return false;
        }

        return true;
    }

    /**
     * 根据区域覆盖聚焦到所有的门店
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function process($filter, &$error_msg) {
        $branch_ids = array();
        $area_id = intval($filter['area_id']);

        if(empty($area_id) || $area_id <= 0){
            return '';
        }

        $ruleObj = app::get('o2o')->model('autostore_rule');
        $ruleItemsObj = app::get('o2o')->model('autostore_rule_area_items');

        $rule_ids    = array();
        
        //获取满足区域覆盖的规则ID数组
        $ruleItemsArr = $ruleItemsObj->getList('rule_id',array('area_id'=>$area_id), 0, -1);
        foreach($ruleItemsArr as $ruleItem){
            if(!in_array($ruleItem['rule_id'],$rule_ids)){
                $rule_ids[] = $ruleItem['rule_id'];
            }
        }

        //根据规则ID数组找到所有仓库
        if($rule_ids){
            $rule_arr = $ruleObj->getList('branch_id',array('rule_id'=>$rule_ids), 0, -1);
            if($rule_arr){
                foreach($rule_arr as $rule){
                    if (!in_array($rule['branch_id'],$branch_ids)){
                        $branch_ids[] = $rule['branch_id'];
                    }
                }
            }
        }

        return $branch_ids;
    }

}