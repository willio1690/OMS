<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 
* 拼多多商品同步
* 
*/
class inventorydepth_service_shop_pinduoduo extends inventorydepth_service_shop_common
{
    //定义每页拉取数量
    public $customLimit = 5;

    public $approve_status = array(
            array('filter'=>array('approve_status'=>'onsale'),'name'=>'在架','flag'=>'onsale'),
            array('filter'=>array('approve_status'=>'instock'),'name'=>'下架','flag'=>'instock'),

    );
    function __construct(&$app)
    {
        $this->app = $app;
    }
}