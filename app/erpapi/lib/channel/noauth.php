<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author chenping@shopex.cn 2017/5/24
 * @describe 无需授权接口
 */
class erpapi_channel_noauth extends erpapi_channel_abstract {
    public $channel;

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $node_type node_type
     * @return mixed 返回值
     */

    public function init($node_id, $node_type) {
        // 利用CC上的绑定店铺
        $this->channel = array(
            'node_type'    => 'taobao',
            'node_id'      => '1065393031',
            'shop_type'    => 'taobao',
            'certi_id'     => '1167790537',
            'from_node_id' => '1182778034',
            'token'        => '1082f8c19a445aea6f41c8badf71b33ca6cd2e139b99bc41295c6440f2511276',
        );

        // 判读是否有淘宝点
        $taobaoShop = app::get('ome')->model('shop')->getList('node_type,node_id',array('node_type'=>'taobao','filter_sql'=>"node_id!='' AND node_id is not null"));
        if ($taobaoShop) {
            $this->channel['node_id']      = $taobaoShop[0]['node_id'];
            $this->channel['certi_id']     = base_certificate::certi_id();
            $this->channel['from_node_id'] = base_shopnode::node_id('ome');
            $this->channel['token']        = base_certificate::token();
        }

        $this->__adapter  = 'matrix';
        $this->__platform = 'taobao';

        return true;
    }
}