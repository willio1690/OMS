<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/12/29 16:59:59
 * @describe: 类
 * ============================
 */
class crm_gift_memo {

    /**
     * 处理
     * @param mixed $ruleBase ruleBase
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function process($ruleBase, $sdf) {
        //指定商家备注
        if($ruleBase['filter_arr']['order_remark'])
        {
            $no_find = true;
            foreach (explode('|', $ruleBase['filter_arr']['order_remark']) as $key => $val)
            {
                if(stripos($sdf['mark_text'], $val) !== false)
                {
                    $no_find = false; //备注符合条件
                    break;
                }
            }
            
            //没有找到
            if($no_find)
            {
                return [false, '不符合指定商家备注'];
            }
        }
        
        //指定客户备注
        if($ruleBase['filter_arr']['member_remark'])
        {
            $no_find = true;
            foreach (explode('|', $ruleBase['filter_arr']['member_remark']) as $key => $val)
            {
                if(stripos($sdf['custom_mark'], $val) !== false)
                {
                    $no_find = false; //备注符合条件
                    break;
                }
            }
            
            //没有找到
            if($no_find)
            {
                return [false, '不符合指定客户备注'];
            }
        }
        return [true];
    }
}