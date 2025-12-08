<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 不动销商品报表任务处理类(每天零晨2点执行)
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_timer_productnsale
{
    public function process($params, &$error_msg=''){
        set_time_limit(0);
        ignore_user_abort(1);

        kernel::single('omeanalysts_crontab_script_analysisProductNsale')->analysisProductNsale();
        
        //每天定时拉取抖音平台所有店铺的商家退货地址库
        kernel::single('ome_reship_luban')->getAllShopReturnAddress($error_msg);
        
        return true;
    }
}