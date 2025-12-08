<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**美团医药*/
class erpapi_shop_matrix_meituan4medicine_request_product extends erpapi_shop_request_product
{
    /**
     * format_stocks
     * @param mixed $stocks stocks
     * @return mixed 返回值
     */

    public function format_stocks($stocks)
    {
        $appPoiCode = $this->__channelObj->channel['addon']['user_id'];
        foreach ($stocks as $key => $val) {
            $stocks[$key]['app_poi_code'] = $appPoiCode;
        }
        return $stocks;
    }
}