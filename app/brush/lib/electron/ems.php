<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2015-12-16
 * @describe 发起电子面单请求
 */
class brush_electron_ems extends brush_electron_abstract{

    /**
     * 回填发货信息
     */
    public function delivery() {
        $shop = $this->getChannelExtend();
        foreach($this->delivery as $delivery) {
            $sdf = array();
            $sdf['delivery'] = $delivery;
            $sdf['shop'] = $shop;
            $this->request('delivery', $sdf);
        }
    }

    public function deliveryToSdf($delivery) {//各自实现
        $sdf = array();

        return $sdf;
    }
}