<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class channel_channel
{

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->channel_mdl = app::get('channel')->model('channel');
    }

    /**
     * 判断渠道是否存在
     * @param $channel_type 渠道类型
     * @return bool
     */
    public function exists($channel_type = '')
    {
        if (empty($channel_type)) {
            return false;
        }

        $channel = $this->channel_mdl->getList('channel_id', array('channel_type' => $channel_type), 0, 1);
        return isset($channel[0]['channel_id']) && $channel[0]['channel_id'] ? true : false;
    }

    /**
     * 获取渠道信息
     * @param $filter 条件
     * @return mixd
     */
    public function dump($filter = '', $cols = '*')
    {
        if (empty($filter)) {
            return null;
        }

        $cols    = $cols ? $cols : 'channel_id,channel_name,node_id,node_type';
        $channel = $this->channel_mdl->getList($cols, $filter, 0, 1);
        return isset($channel[0]) ? $channel[0] : null;
    }

    /**
     * 获取渠道信息列表
     * @param $filter 条件
     * @return mixd
     */
    public function getList($filter = '', $cols = '*')
    {
        if (empty($filter)) {
            return null;
        }

        $cols    = $cols ? $cols : 'channel_id,channel_name,node_id,node_type';
        $filter  = $filter ? $filter : array();
        $channel = $this->channel_mdl->getList($cols, $filter, 0, -1);
        return isset($channel[0]) ? $channel : null;
    }

    /**
     * 添加渠道记录
     * @param $sdf 渠道数据
     * @return bool
     */
    public function insert(&$sdf)
    {
        if (empty($sdf)) {
            return false;
        }

        return $this->channel_mdl->insert($sdf);
    }

    /**
     * 更新渠道记录
     * @param $sdf 更新数据
     * @param $filter 更新条件
     * @return bool
     */
    public function update($sdf, $filter = '')
    {
        if (empty($sdf) || empty($filter)) {
            return false;
        }

        return $this->channel_mdl->update($sdf, $filter);
    }

    /**
     * 删除渠道记录
     * @param $filter 删除条件
     * @return bool
     */
    public function delete($filter = '')
    {
        if (empty($filter)) {
            return false;
        }

        return $this->channel_mdl->delete($filter);
    }

    /**
     * 绑定
     * @param $node_id 节点号
     * @param $node_type 节点类型
     * @param $filter 更新绑定条件
     * @return bool
     */
    public function bind($node_id, $node_type, $filter = '')
    {
        if (empty($node_id) || empty($filter)) {
            return false;
        }

        return $this->channel_mdl->update(array('node_id' => $node_id, 'node_type' => $node_type), $filter);
    }

    /**
     * 解除绑定
     * @param $filter 更新绑定条件
     * @return bool
     */
    public function unbind($filter = '')
    {
        if (empty($filter)) {
            return false;
        }

        return $this->channel_mdl->update(array('node_id' => ''), $filter);
    }

    /**
     * 获取奇门聚石塔内外互通渠道信息
     * @param string $app_key 可选的app_key查询条件
     * @return array|null 返回渠道信息数组，不存在返回null
     */
    public function getQimenJushitaErp($app_key = '')
    {
        $filter = array(
            'channel_type' => 'qimen',
            'channel_bn'  => 'qimen-jst-erp',
        );
        
        if (!empty($app_key)) {
            $filter['app_key'] = $app_key;
        }
        
        $channel = app::get('channel')->model('channel')->getList('channel_id,channel_bn,channel_name,node_id,app_key,secret_key', $filter, 0, 1);
        return isset($channel[0]) ? $channel[0] : null;
    }

    /**
     * 判断是否是奇门聚石塔内外互通
     * @param string $channel_type 渠道类型
     * @param string $channel_bn 渠道编号
     * @return bool
     */
    public function isQimenJushitaErp($channel_type, $channel_bn)
    {
        return $channel_type == 'qimen' && $channel_bn == 'qimen-jst-erp';
    }

}
