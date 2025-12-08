<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_delivery_bindkey{
    
    /**
     * 获取合并条件值
     * @param sdf $sdf
     * @return string md5
     */
    public function get_bindkey($sdf){
        $bindkey = md5($sdf['shop_id'].$sdf['branch_id'].$sdf['consignee']['addr'].$sdf['member_id'].$sdf['is_cod'].$sdf['is_protect']);
        return $bindkey;
    }
    
}