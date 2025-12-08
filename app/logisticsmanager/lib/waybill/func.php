<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_func
{
    //获取面单来源渠道
    /**
     * channels
     * @param mixed $channel_type channel_type
     * @return mixed 返回值
     */
    public function channels($channel_type = null)
    {
        $channels = array(
            'taobao'   => array('code' => 'taobao', 'name' => '淘宝电子面单'),
            'ems'      => array('code' => 'ems', 'name' => 'EMS官方'),
            '360buy'   => array('code' => '360buy', 'name' => '京东快递'),
            'sf'       => array('code' => 'sf', 'name' => '顺丰电子面单'),
            'yunda'    => array('code' => 'yunda', 'name' => '韵达电子面单'),
            'sto'      => array('code' => 'sto', 'name' => '申通'),
            'hqepay'   => array('code' => 'hqepay', 'name' => '快递鸟电子面单'),
            //'unionpay' => array('code' => 'unionpay', 'name' => '银联电子面单'),
            'bbd'      => array('code' => 'bbd', 'name' => '棒棒达'),
            'customs'  => array('code' => 'customs', 'name' => '跨境电商'),
            'jdalpha'  => array('code' => 'jdalpha', 'name' => '京东电子面单'),
            'aikucun'  => array('code' => 'aikucun', 'name' => '爱库存电子面单'),
            'pinjun'   => array('code' => 'pinjun', 'name' => '品骏'),
            'pdd'      => array('code' => 'pdd', 'name' => '拼多多电子面单'),
            'vopjitx'  => array('code' => 'vopjitx', 'name' => '唯品会JITX'),
            'yto4gj'   => array('code' => 'yto4gj', 'name' => '圆通国际'),
            'douyin'   => array('code' => 'douyin', 'name' => '抖音电子面单'),
            'wphvip'   => array('code' => 'wphvip', 'name' => '唯品会vip电子面单'),
            'kuaishou' => array('code' => 'kuaishou', 'name' => '快手电子面单'),
            'xhs'      => array('code' => 'xhs', 'name' => '小红书电子面单'),
            'wxshipin' => array('code' => 'wxshipin', 'name' => '微信视频号电子面单'),
            'dewu'     =>array('code' => 'dewu', 'name'  =>  '得物品牌直发电子面单'),
            'meituan4bulkpurchasing' => array('code' => 'meituan4bulkpurchasing', 'name' => '美团电商'),
            'jdgxd'    => array('code' => 'jdgxd', 'name' => '京东工小达'),
            // 'youzan'   => array('code' => 'youzan', 'name' => '有赞电子面单'),
        );
        if (!empty($channel_type)) {
            return $channels[$channel_type];
        }
        return $channels;
    }
}
