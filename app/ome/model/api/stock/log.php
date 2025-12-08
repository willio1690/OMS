<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_api_stock_log extends dbeav_model{
    
    /**
     * 将更新部分库存失败的消息替换
     */
    public function modifier_msg($rows) {
        return str_replace('部分','',$rows);
    }

    public function getLastStockLog($shop_id = '', $product_bn = '', $product_id = '')
    {
        if (!$shop_id || (!$product_bn && !$product_id)) {
            return false;
        }

        $filter = [
            'status'   => 'success',
            'shop_id'  => $shop_id,
            'api_type' => 'request',
        ];
        if ($product_bn) {
            $filter['product_bn'] = $product_bn;
        } else {
            $filter['product_id'] = $product_id;
        }

        $list = $this->getList('*', $filter, 0, 1, 'last_modified desc');
        if (!$list) {
            return false;
        }
        return $list[0];
    }
    
    function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        if($filter['product_bn'] && is_string($filter['product_bn']) && strpos($filter['product_bn'], "\n") !== false){
            $filter['product_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['product_bn']))));
        }
        return parent::_filter($filter,$tableAlias,$baseWhere);
    }
}
?>