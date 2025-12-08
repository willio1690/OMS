<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 电子发票接口通路定义类
 *
 * @author xiayuanjun<xiayuanjun@shopex.cn>
 * wangjianjun update 20160623
 * @version 0.1
 */
class erpapi_channel_invoice extends erpapi_channel_abstract
{
    private $bindConfig = array (
        'jinshui'  => array (
            'node_type' => 'jinshui',
            'to_node'   => '1483186233',
            'shop_name' => '金税',
            'title'     => '金税电子发票',
        ),
        'chinaums' => array (
            'node_type' => 'chinaums',
            'to_node'   => '',
            'shop_name' => '银联金4',
            'title'     => '银联金4电子发票',
        ),
        'baiwang'  => array (
            'node_type' => 'baiwang',
            'to_node'   => '',
            'shop_name' => '百望金4',
            'title'     => '百望金4电子发票',
        ),
        'huifu' => array(
            'node_type' => 'huifu',
            'to_node'   => '',
            'shop_name' => '汇付发票',
            'title'     => '汇付电子发票',
        ),
    );

    private static $channel_mapping = array (
        'baiwang_cloud' => 'baiwang',
    );

    /**
     * 发票渠道初始化
     * @param $node_id
     * @param $shop_id
     * @return bool
     */

    public function init($node_id, $shop_id): bool
    {
        if (!$node_id && !$shop_id) {
            return false;
        }

        if ($node_id) {
            return $this->responseInit($node_id);
        } else {
            return $this->requestInit($shop_id);
        }
    }

    /**
     * 响应时匹配开票渠道
     * @param $node_id
     * @return bool
     */
    private function responseInit($node_id): bool
    {
        $this->__adapter = 'matrix';

        // 响应渠道定位
        $filter = ['node_id' => $node_id];
        // 1. 存在nodeId , 则尝试获取
        $channelMdl  = app::get('invoice')->model('channel');
        $channel     = $channelMdl->dump($filter);
        $channelType = $channel['channel_type'];
        // 硬编码查询
        if (!$channel) {
            // 使用 bindConfig查询
            $channelList = array_column($this->bindConfig, 'node_type', 'to_node');

            if (!isset($channelList[$node_id])) {
                // todo 兼容淘宝
                return false;
            }
            $channelType = $channelList[$node_id];
            $channel     = $channelMdl->getChannelByType($channelType);
        }

        if (!$channel) {
            return false;
        }

        $this->__adapter          = 'matrix';
        $this->__platform         = $channelType;
        $this->channel            = $channel;
        $this->channel['node_id'] = $node_id;

        if (isset($channel['channel_extend_data'])) {
            $this->channel['extend_data'] = @json_decode($channel['channel_extend_data'], true);
        } elseif (isset($this->channel['extend_data']) && is_string($this->channel['extend_data'])) {
            $this->channel['extend_data'] = @json_decode($this->channel['extend_data'], true);
        }

        return true;
    }

    /**
     * 请求时匹配开票渠道
     * @param $shop_id
     * @return bool
     */
    private function requestInit($shop_id): bool
    {
        #一个店铺，同一个时刻，只会属于唯一一个开票渠道
        $channel = app::get('invoice')->model('channel')->get_channel_info($shop_id);
        if (empty($channel)) {
            return false;
        }

        $this->__adapter  = 'matrix';
        $this->__platform = $channel['channel_type'];

        // 节点id赋值逻辑
        if ($channel['node_id']) {
            // 1. 银联, 存在channel表内
            $this->channel['node_id'] = $channel['node_id'];
        } elseif ($channel['channel_type'] == 'taobao') {
            // 2. 淘宝, 取绑定店铺节点
            $this->channel['node_id'] = $channel["billing_shop_node_id"];
        } else {
            // 3. 其他, 硬编码于绑定配置内
            $this->channel['node_id'] = $this->bindConfig[$channel['channel_type']]['to_node'];
        }

        $this->channel['extend_data']        = @json_decode($channel['channel_extend_data'], true);
        $this->channel['channel_id']         = $shop_id;
        $this->channel['channel_type']       = isset(self::$channel_mapping[$channel['channel_type']]) ? self::$channel_mapping[$channel['channel_type']] : $channel['channel_type'];
        $this->channel['node_type']          = $channel["node_type"];
        $this->channel['golden_tax_version'] = $channel["golden_tax_version"];

        if ($channel['channel_type'] == 'taobao') {
            $this->channel['node_id'] = $channel["billing_shop_node_id"];
        }

        return true;
    }
}