<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_payment_shop extends dbeav_model{
    /**
     * 获取ShopByPayBn
     * @param mixed $pay_bn pay_bn
     * @return mixed 返回结果
     */
    public function getShopByPayBn($pay_bn){
        $sql = 'SELECT pay_bn,name FROM sdb_ome_payment_shop AS P 
            LEFT JOIN sdb_ome_shop AS S ON P.shop_id=S.shop_id WHERE pay_bn=\''.addslashes($pay_bn).'\'';
        $rows = $this->db->select($sql);
        return $rows;
    }
}