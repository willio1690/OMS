<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoguaninventory_task{

    function post_install($options){
        //初始化盘点的码表
        $encodedStateObj = app::get('taoguaninventory')->model('encoded_state');
        $state_data = array(
            'name' => 'inventory',
            'head' => 'PD',
            'currentno' => 0,
            'bhlen' => 4,
            'description' => '盘点表',
        );
        $encodedStateObj->save($state_data);
    }

}
