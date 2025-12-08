<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 转储单
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_qimen_response_stockdump extends erpapi_wms_response_stockdump
{    
    /**
     * wms.stockdump.status_update
     *
     **/

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
            }
        }
        $params['item'] = json_encode($items);
        $params = parent::status_update($params);
        return $params;
    }
}
