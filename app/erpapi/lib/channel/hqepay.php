<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 华强宝
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_channel_hqepay extends erpapi_channel_abstract
{
    public $channel;

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $hqepay_id ID
     * @return mixed 返回值
     */

    public function init($node_id, $hqepay_id)
    {
        $this->__adapter  = '';
        $this->__platform = '';

        $this->channel['hqepay_id'] = $hqepay_id;

        // 查询绑定的快递鸟
        $channel = app::get('channel')->model('channel')->dump([
            'channel_type' => 'kuaidi',
            'node_type'    => 'kdn',
            'filter_sql'   => 'node_id IS NOT NULL AND node_id!=""',
        ]);

        if (!$channel) {
        	return false;
        }

        $this->channel = $channel;

        return true;
    }
}
