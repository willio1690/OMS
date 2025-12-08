<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_mdl_order_taobao extends dbeav_model{

    var $has_many = array(
       'order_taobao_items' => 'order_taobao_items',
    );

}