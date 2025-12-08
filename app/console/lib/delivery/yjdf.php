<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/9/22 11:27:32
 * @describe: 开普勒一件代发业务发货单处理
 * ============================
 */
class console_delivery_yjdf {

    #获取渠道ID
    /**
     * 获取WMSChannelId
     * @param mixed $branchId ID
     * @param mixed $items items
     * @return mixed 返回结果
     */

    public function getWMSChannelId($branchId, $items) {
        $wms_type = kernel::single('ome_branch')->getNodetypBybranchId($branchId);
        if($wms_type != 'yjdf') {
            return '';
        }
        foreach ($items as $key => $value) {
            $product_bn = $value['bn'];
            break;
        }
        if(empty($product_bn)) {
            return '';
        }
        //获取上架状态：只拿最新2条数据
        $sql = "SELECT * FROM sdb_material_basic_material_channel WHERE material_bn='". $product_bn ."' ORDER BY approve_status,id DESC LIMIT 0,2";
        $channelList = kernel::database()->select($sql);
        if(empty($channelList)){
            return '';
        }
        
        foreach ($channelList as $key => $val)
        {
            $channel_id = $val['channel_id'];
            
            if($channel_id && $val['approve_status']=='1'){
                return $channel_id;
            }
        }
        return '';
    }
}