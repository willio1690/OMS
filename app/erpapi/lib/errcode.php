<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_errcode{

    public $errcode;
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct(){
        $this->errcode = array(
            'shop'  =>  $this->shop,
            'wms'   =>  $this->wms,
        );
    }

    /**
     * 获取Errcode
     * @param mixed $channel_type channel_type
     * @return mixed 返回结果
     */
    public function getErrcode($channel_type)
    {
        return $this->errcode[$channel_type];
    }

    private $shop=array(
        'G40012'   =>   array('primary_bn'=>'delivery_bn', 'obj_type'=>'JDDELIVERY', 'retry'=>1),
    );



    private $wms = array(
        'e00090'        =>   array( 'retry'=>1 ),
        'ERP00090'      =>   array( 'retry'=>1 ),
        'W30012'        =>   array('primary_bn'=>'delivery_bn', 'obj_type'=>'deliveryship', 'retry'=>1),
    );

}
