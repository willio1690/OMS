<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @description 库存管理抽象类
 * @access public
 */
abstract class ome_store_manage_abstract{

    protected $_is_ctrl_store = true;

    function __construct($is_ctrl_store){
        $this->_is_ctrl_store = $is_ctrl_store;
    }

    protected function _sortAddBmNum($items, $bmIdField='product_id', $numField='number') {
        $nitems         = array();

        foreach ($items as $item) {
            if (isset($nitems[$item[$bmIdField]])) {
                $nitems[$item[$bmIdField]][$numField] += $item[$numField];
            } else {
                $nitems[$item[$bmIdField]] = array(
                    $bmIdField => $item[$bmIdField],
                    $numField => $item[$numField],
                );
            }

        }

        ksort($nitems);

        return $nitems;
    }
}
