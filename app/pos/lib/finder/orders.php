<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_finder_orders
{

   
    var $detail_basic = '基本信息';
    function detail_basic($id){

       $ordersMdl = app::get('pos')->model('orders');
       $orders = $ordersMdl->db_dump($id,'params');
       $params = json_decode($orders['params'],true);
       if($_GET['display'] == 'true'){
         echo '<pre>';
         print_r($params);
       }
       
       
    }
}
