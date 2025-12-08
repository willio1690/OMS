<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 流水单类
 *
 * @author 334395174@qq.com
 * @version 0.1
 */

class financebase_data_bill 
{

    /**
     * 根据平台获取未匹配订单号的数量
     */
    public function getUnMatchCountByOrderBn()
    {
        $oFunc = kernel::single('financebase_func');
        $mdlBill = app::get('financebase')->model('bill');

        $platform_list = $oFunc->getShopPlatform();

        $sql = "select count(*) as count,platform_type from `sdb_financebase_bill` where status = 0 group by `platform_type`";

        $list = $mdlBill->db->select($sql);

        foreach ($list as $k => $v) 
        {
            if(isset($platform_list[$v['platform_type']]))
            {
                $list[$k]['platform_name'] = $platform_list[$v['platform_type']];
            }
            else
            {
                unset($list[$k]);
            }
        }
        return $list;
    }

    
}