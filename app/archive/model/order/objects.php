<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class archive_mdl_order_objects extends dbeav_model{
    var $has_many = array(
       'order_items' => 'order_items',
    );

}
?>