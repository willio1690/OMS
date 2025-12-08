<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 商品分配推送
 *
 * @category
 * @package
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_request_goods extends erpapi_wms_request_goods
{
    /**
     * goods_add
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function goods_add($sdf){
        $title = $this->__channelObj->wms['channel_name'].'商品添加';

        $callback = array();

        foreach (array_chunk($sdf, 100) as $sub_sdf) {
            $inner_sku = array();

            foreach ($sub_sdf as $key => $pro) {
                if (!is_array($pro)) {unset($sub_sdf[$key]); continue;}

                if ($pro['bn']) $inner_sku['inner_sku'][] = $pro['bn'];
            }
            $inner_sku['node_id'] = $this->__channelObj->wms['node_id'];

            $params = $this->_format_goods_params($sub_sdf);
            $response = $this->__caller->call(WMS_ITEM_ADD, $params, $callback, $title, 10);

            if ($response) $this->goods_callback($response,$inner_sku);
        }
    }

    /**
     * goods_update
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function goods_update($sdf){
        $title = $this->__channelObj->wms['channel_name'] . '商品编辑';
        $callback = array();
        foreach (array_chunk($sdf, 100) as $sub_sdf) {
            $inner_sku = array();

            foreach ($sub_sdf as $key => $pro) {
                if (!is_array($pro)) {unset($sub_sdf[$key]); continue;}

                if ($pro['bn']) $inner_sku['inner_sku'][] = $pro['bn'];
            }
            $inner_sku['node_id'] = $this->__channelObj->wms['node_id'];

            $params = $this->_format_goods_params($sub_sdf);
            $response = $this->__caller->call(WMS_ITEM_UPDATE, $params, $callback, $title, 10);

            if ($response) $this->goods_callback($response,$inner_sku);
        }
    }

    protected function _format_goods_params($sdf){
        $params = $items = array();
        if ($sdf){
            foreach ((array) $sdf as $p){
                if (!is_array($p)) continue;

                $items[] = array(
                    'product_code'    => $p['bn'],
                    'bar_code'        => $p['barcode'] ? $p['barcode'] : '',
                    'product_name'    => $p['name'],
                    'weight'          => $p['weight'] ? $p['weight']/1000 : '0',//单位Kg
                );
            }
        }
        $params['data'] = json_encode(array('products'=>$items));
        return $params;
    }

    /**
     * goods_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function goods_callback($response,$callback_params){
        $inner_sku = $callback_params['inner_sku'];
        $node_id = $callback_params['node_id'];
        $wms_id = kernel::single('channel_func')->getWmsIdByNodeId($node_id);
        $foreignSkuModel = app::get('console')->model('foreign_sku');
        if($response['rsp'] == 'succ') {
            $foreignSkuModel->update(array('new_tag'=>'1','sync_status'=>'3'),array('inner_sku'=>$inner_sku, 'wms_id'=>$wms_id));
        } else {
            $foreignSkuModel->update(array('new_tag'=>'1','sync_status'=>'1'),array('inner_sku'=>$inner_sku,'wms_id'=>$wms_id));
        }
        return $this->callback($response,$callback_params);
    }
}