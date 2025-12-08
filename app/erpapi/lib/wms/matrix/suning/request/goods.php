<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_wms_matrix_suning_request_goods extends erpapi_wms_request_goods
{
    /**
     * 商品添加，单个同步
     *
     * @return void
     * @author
     **/
    public function goods_add($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] . '商品添加';

        $callback = array(
            'class'  => get_class($this),
            'method' => 'goods_callback',
            'params' => array('node_id' => $this->__channelObj->wms['node_id']),
        );

        foreach ((array) $sdf as $key => $pro) {
            if (!$pro['bn']) {
                continue;
            }

            $params = $this->_format_goods_params($pro);

            $callback['params']['inner_sku'] = array($pro['bn']);

            $rs = $this->__caller->call(WMS_ITEM_ADD, $params, $callback, $title, 10);
        }

        return $rs;
    }

    /**
     * 商品添加，单个同步
     *
     * @return void
     * @author
     **/
    public function goods_update($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] . '商品编辑';

        $callback = array(
            'class'  => get_class($this),
            'method' => 'goods_callback',
            'params' => array('node_id' => $this->__channelObj->wms['node_id']),
        );

        foreach ((array) $sdf as $key => $pro) {
            if (!$pro['bn']) {
                continue;
            }

            $params = $this->_format_goods_params($pro);

            $callback['params']['inner_sku'] = array($pro['bn']);

            $rs = $this->__caller->call(WMS_ITEM_UPDATE, $params, $callback, $title, 10);
        }

        return $rs;
    }

    protected function _format_goods_params($p)
    {
        $spec_info = preg_replace(array('/：/', '/、/'), array(':', ';'), $p['property']);
        $item      = array(
            'name'                => $p['name'],
            'title'               => $p['name'], // 商品标题
            'item_code'           => $p['bn'],
            'remark'              => '', //商品备注
            'type'                => 'NORMAL',
            'is_sku'              => '1',
            'gross_weight'        => $p['weight'] ? $p['weight'] : '', // 毛重,单位G
            'net_weight'          => $p['weight'] ? $p['weight'] : '', // 商品净重,单位G
            'tare_weight'         => '', // 商品皮重，单位G
            'is_friable'          => '', // 是否易碎品
            'is_dangerous'        => '', // 是否危险品
            //'weight'            => $p['weight'] ? $p['weight'] : '0',
            //'length'            => '0.00',// 商品长度，单位厘米
            //'width'             => '0.00',// 商品宽度，单位厘米
            //'height'            => '0.00',// 商品高度，单位厘米
            //'volume'            => '0.00',// 商品体积，单位立方厘米
            'pricing_cat'         => '', // 计价货类
            'package_material'    => '', // 商品包装材料类型
            'price'               => '',
            'support_batch'       => '否',
            'support_expire_date' => '否',
            'expire_date'         => date('Y-m-d'),
            'support_barcode'     => '0',
            'barcode'             => $p['barcode'] ? $p['barcode'] : '',
            'support_antifake'    => '否',
            'unit'                => $p['unit'] ? $p['unit'] : '',
            'package_spec'        => '', // 商品包装规格
            'ename'               => '', // 商品英文名称
            'brand'               => '',
            'batch_no'            => '',
            'goods_cat'           => '', // 商品分类
            'color'               => '', // 商品颜色
            'property'            => $spec_info, //规格
        );

        return $item;
    }
}
