<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_bill_fee_type{

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->_render = app::get('finance')->render();
    }

    var $detail_items = '科目明细';
    /**
     * detail_items
     * @param mixed $fee_type_id ID
     * @return mixed 返回值
     */
    public function detail_items($fee_type_id)
    {
        $fee_items = app::get('finance')->model('bill_fee_item')->getList('*',array('fee_type_id' => $fee_type_id));
        foreach ((array) $fee_items as $key => $value) {
            $fee_items[$key]['channel'] = finance_channel::$channel_name[$value['channel']];
        }
        $this->_render->pagedata['fee_items'] = $fee_items;

        $fee_type = app::get('finance')->model('bill_fee_type')->dump($fee_type_id);
        $this->_render->pagedata['fee_type'] = $fee_type;

        return $this->_render->fetch('bill/fee/items.html');
    }

}
