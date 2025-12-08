<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 千牛自助修改
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
class erpapi_dealer_request_qianniu extends erpapi_dealer_request_abstract
{
    /**
     * 更新SkuContract
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function updateSkuContract($sdf)
    {
        $rs = array('rsp' => 'fail', 'msg' => '', 'data' => '');

        if (!$sdf) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $shop_id = $sdf['shop_id'];
        $shop_obj = app::get('ome')->model('shop');
        $shop_info = $shop_obj->dump(['shop_id' => $shop_id]);

        if (!$shop_info) {
            $rs['msg'] = '没有找到商店信息';
            return $rs;
        }

        $params = [
            'app_id' => "ecos.ome",
            'certi_id' => base_certificate::certi_id(),
            'date' => date('Y-m-d H:i:s', time()),
            'format' => "json",
            'from_node_id' => base_shopnode::node_id('ome'),
            "method" => "store.modifysku.open",
            'to_node_id'   => $this->__channelObj->channel['node_id'],
            'node_type'    => $shop_info['node_type'],
            "v"=> "1"
        ];

        $title = '店铺(' . $this->__channelObj->channel['name'] . ') 签约更换sku信息)';
        $rs = $this->__caller->call(STORE_MODIFYSKU_OPEN, $params, [], $title, 10, $shop_id);
        return $rs;
    }
}