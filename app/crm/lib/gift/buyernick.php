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
class crm_gift_buyernick
{

    /**
     * 处理
     * @param mixed $ruleBase ruleBase
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function process($ruleBase, $sdf) {
        #会员是否已经购买过了
        if($ruleBase['filter_arr']['buyer_nick'] == '1' ){
            #查询会员已经送出的订单数,针对当前规则
            $sql = "select count(distinct order_bn) as total_orders from sdb_ome_gift_logs where gift_rule_id =".$ruleBase['rule_id']." and buyer_account='".$sdf['buyer_nick']."'";
            $_rs = kernel::database()->selectRow($sql);
            if($_rs['total_orders'] > 0) {
                return array(false, '每ID只第一次购买赠送');
            }
        }
        if ($ruleBase['filter_arr']['member_uname']) {
            if (!$sdf['buyer_nick'] or !in_array(trim($sdf['buyer_nick']), explode(',', $ruleBase['filter_arr']['member_uname']))) {
                return array(false, '不符合指定会员');
            }
        }
        return array(true);
    }
}