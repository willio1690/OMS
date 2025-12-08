<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/11
 * @Describe: 获取数据
 */
class ome_event_trigger_shop_data_delivery_weixinshop extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        
        if ($this->__sdf) {
            $this->_get_split_sdf($delivery_id);
            // 如果是拆单
            if ($this->__sdf['is_split'] == 1 && !$this->__sdf['oid_list']) {
                return array();
            }
            
            $this->_get_delivery_items_sdf($delivery_id, true, false);

            if($this->__sdf['oid_list']) {
                $delivery = $this->__deliverys[$delivery_id];
                $corp = $this->_get_corp($delivery['logi_id']);
                $this->__sdf['logi_no'] = $delivery['logi_no'];
                $this->__sdf['logi_type'] = $corp['type'];
                $this->__sdf['logi_name'] = $corp['name'];
                $shipMent = app::get('ome')->model('shipment_log')->getList('deliveryCode,oid_list', ['shopId'=>$delivery['shop_id'], 'orderBn'=>$this->__sdf['orderinfo']['order_bn']]);
                $deleteOlds = array();
                
                foreach ($shipMent as $value) {
                    if(!$value['oid_list'] || $this->__sdf['logi_no'] == $value['deliveryCode']) {
                        continue;
                    }

                    $oid_list = explode(',', $value['oid_list']);
                    $deleteOlds = array_merge($deleteOlds,$oid_list);
                    foreach ($this->__sdf['oid_list'] as $k => $v) {
                        if(in_array($v, $oid_list)) {
                            unset($this->__sdf['oid_list'][$k]);
                        }
                    }

                    if(empty($this->__sdf['oid_list'])) {
                        return false;
                    }
                }
            }

            $expresses = [];
            foreach ($this->__sdf['delivery_items'] as $item) {
                if (in_array($item['oid'],$deleteOlds)) {
                    continue;
                }
                $expresses[$item['oid']]['nums']                                       = $item['nums'];
                $expresses[$item['oid']]['packages'][$item['oid']]['logistics_no'] = $item['logi_no'];
                $expresses[$item['oid']]['packages'][$item['oid']]['company_code'] = $item['logi_type'];
                $expresses[$item['oid']]['packages'][$item['oid']]['company_name'] = $item['logi_name'];
                $expresses[$item['oid']]['packages'][$item['oid']]['product_id']   = $item['shop_goods_id'];
                $expresses[$item['oid']]['packages'][$item['oid']]['sku_id']       = $item['oid'];
                $expresses[$item['oid']]['packages'][$item['oid']]['product_cnt']  += $item['number'];
            }

            $packages = [];
            $newOids = array();
            foreach ($expresses as $oid => $express) {
                if ($express['nums'] == ceil(array_sum(array_column($express['packages'], 'product_cnt')))){
                    $packages[] = current($express['packages']);
                    $newOids[] = $oid;
                }
            }

            if (!$packages) {
                return array();
            }

            $this->__sdf['oid_list'] = $newOids;
            $this->__sdf['goods'] = $packages;
        }
        
        return $this->__sdf;
    }
}
