<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_finder_refund
{

    var $detail_basic = '基本信息';
    function detail_basic($id){

        $refundMdl = app::get('pos')->model('refund');
        $refunds = $refundMdl->db_dump($id,'params');
        $params = json_decode($refunds['params'],true);
        echo '<pre>';
        print_r($params);       
    }
}
