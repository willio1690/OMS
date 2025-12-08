<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_finder_extend_filter_payments{
    function get_extend_colums(){
            $db['purchase_payments']=array (
              'columns' =>
              array (
                'po_bn' =>
                array (
                  'type' => 'varchar(50)',
                  'label' => '采购单编号',
                  'width' => 75,
                  'editable' => true,
                  'filtertype' => 'has',
                  'filterdefault' => true,
                ),
              ));
        return $db;
    }
}

