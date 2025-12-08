<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_aikucun {
    //获取物流公司
    /**
     * logistics
     * @param mixed $logistics_code logistics_code
     * @return mixed 返回值
     */
    public function logistics($logistics_code = '') {
        $logistics = array(
            'ZTO'        => array('code' => 'ZTO', 'name' => '中通速递','mode'=>'join'),
            'YUNDA'      => array('code' => 'YUNDA', 'name' => '韵达快递','mode'=>'join'),
            'YTO'        => array('code' => 'YTO','name'=>'圆通快递','mode'=>'join'),
            'DBL'       => array('code' => 'DBL','name'=>'德邦快递','mode'=>'direct'),
            'UC'         => array('code' => 'UC', 'name'=>'优速快递','mode'=>'join'),
            'JD'         => array('code' => 'JD', 'name'=>'京东快递'),
        );

        if(!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }

        return $logistics;
    }

}