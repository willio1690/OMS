<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_finder_aftersale
{

    var $detail_basic = '基本信息';
    function detail_basic($id){

        $aftersaleMdl = app::get('pos')->model('aftersale');
        $aftersales = $aftersaleMdl->db_dump($id,'params');
        $params = json_decode($aftersales['params'],true);
        echo '<pre>';
        print_r($params);       
       
    }
}
