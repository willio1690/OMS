<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016/5/17
 * @describe 添加商家备注 数据验证
 */

class erpapi_shop_response_params_remark extends erpapi_shop_response_params_abstract{

    protected function add(){
        return array(
            'process_status' => array(
                'type'=> 'enum',
                'required' => 'true',
                'errmsg' => '当前订单状态不能再更新,不接受！',
                'value' => array('unconfirmed','confirmed','splitting','splited')
            ),
            'ship_status' => array(
                'type'=>'enum',
                'in_out' => 'out',
                'value' => array('1'),
                'errmsg' => '订单已发货，不接受'
            ),
            'new_mark' => array(
                'type' => 'method',
                'method' => 'validAddNewMark',
                'errmsg' => '商家备注已经存在,不更新！'
            ),
        );
    }

    protected function validAddNewMark($params) {
        $remark = $params['mark_text'];
        if(!empty($remark)){
            foreach($remark as $val){
                if($val['op_content'] == $params['new_mark']['op_content']){
                    return false;
                }
            }
        }
        return true;
    }
}