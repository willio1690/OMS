<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_wms_response_abstract
{
    protected $__channelObj;

    public $__apilog;

    /**
     * 初始化
     * @param erpapi_channel_abstract $channel channel
     * @return mixed 返回值
     */
    public function init(erpapi_channel_abstract $channel)
    {
        $this->__channelObj = $channel;

        return $this;
    }

    protected function getOmsProductBn($wms_id,$arrbn){

        $sku = app::get('console')->model('foreign_sku')->getList('inner_product_id,inner_sku,oms_sku', array('wms_id' => $wms_id, 'oms_sku' => $arrbn));
        $omsProductBn = array();
        foreach ($sku as $val) {
            if($val['oms_sku']){
                $omsProductBn[$val['oms_sku']] = $val['inner_sku'];
            }
            
        }


        return $omsProductBn;
    }
}
