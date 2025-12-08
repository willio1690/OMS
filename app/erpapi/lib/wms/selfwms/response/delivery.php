<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * WMS 发货单
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_selfwms_response_delivery  extends erpapi_wms_response_delivery
{
    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'发货单更新('.$params['status'].')';
        $this->__apilog['original_bn'] = $params['delivery_bn'];
        if($params['other_list_0']) {
            $logiList = json_decode($params['other_list_0'], true);
            $logiWeight = array();
            if($logiList){
                foreach($logiList as $val) {
                    if($val['logi_no'] != $logi_no) {
                        $logiWeight[$val['logi_no']] = $val['weight'] ;
                    }
                }
            }

            $params['bill_logi_weight'] = $logiWeight;
        }


        if ($params['packages']) {
            $params['packages'] = json_decode($params['packages'],true);
        }

        return $params;
    }
}
