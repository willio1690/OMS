<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 出库单
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_qimen_response_stockout extends erpapi_wms_response_stockout
{
    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params){
        $items = isset($params['item']) ? json_decode($params['item'], true) : array();
        if($items){
            $product_bns = array();
            foreach($items as $key=>$val){
                if ($val['product_bn']){
                    
                    $product_bns[] = $val['product_bn'];
                }
                    
            }

            $skuData = $this->getOmsProductBn($this->__channelObj->wms['channel_id'],$product_bns);

            foreach($items as $key=>$val){
                if (!$val['product_bn'])  continue;
                if($skuData[$val['product_bn']]){
                    $items[$key]['product_bn'] = $skuData[$val['product_bn']];
                }
                $items[$key]['num'] = (int)$val['num'] + (int)$val['normal_num']+(int)$val['defective_num'];
            }
        }

        $params['item'] = json_encode($items);
        $data = parent::status_update($params);
        return $data;
    }
}
