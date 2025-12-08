<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_task_stocksync
{
    /**
     * @access public
     * @param void {,,,,items:[{product_id:0,number:0}]}
     * @return void
     */
    public function process($params, &$error_msg = '')
    {
        $delivery_id   = $params['delivery_id'];
        $ship_province = $params['ship_province'];
        $ship_city     = $params['ship_city'];
        $ship_district = $params['ship_district'];
        $ship_town     = $params['ship_town'];
        $ship_village  = $params['ship_village'];
        $ship_addr     = $params['ship_addr'];

        $items = @json_decode($params['items'], true);

        $uniqid = md5('kepler-stocksync-' . $delivery_id);

        $product_id = array_filter(array_column($items, 'product_id'));
        if (!$product_id) {
            $error_msg = '未查到商品';
            return true;
        }

        $items = array_column($items, null, 'product_id');

        // 查找覆盖范围渠道仓
        $sql = 'SELECT b.branch_id, b.wms_id, b.branch_bn, c.crop_config
                FROM sdb_logisticsmanager_warehouse w
                LEFT JOIN sdb_ome_branch b ON(w.branch_id = b.branch_id)
                LEFT JOIN sdb_channel_channel c ON(c.channel_id = b.wms_id)
                WHERE c.node_type = "yjdf" AND c.node_id IS NOT NULL AND c.node_id != "" AND FIND_IN_SET("' . $ship_province . '",w.region_names)';

        $list = kernel::database()->select($sql);
        if (!$list) {
            $error_msg = '未查到覆盖仓';
            return true;
        }

        $area_addr_list = [];
        foreach ($list as $l) {
            $crop_config = @unserialize($l['crop_config']); unset($l['crop_config']);

            if (!$crop_config['stock_monitor']) {
                continue;
            }

            $area_addr_list[$l['branch_id']] = $l;

            $area_addr_list[$l['branch_id']]['province'] = $ship_province;
            $area_addr_list[$l['branch_id']]['city']     = $ship_city;
            $area_addr_list[$l['branch_id']]['street']   = $ship_district;
            $area_addr_list[$l['branch_id']]['town']     = $ship_town;
            $area_addr_list[$l['branch_id']]['address']  = $ship_addr;

            $object        = kernel::single('erpapi_router_request')->set('wms', $l['wms_id']);
            $platform_area = $object->branch_getAreaId([
                'ship_province' => $ship_province,
                'ship_city'     => $ship_city,
                'ship_district' => $ship_district,
                'ship_town'     => $ship_town,
                'ship_addr'     => $ship_addr,
            ]);

            $area_addr_list[$l['branch_id']]['provinceId'] = $platform_area['data']['provinceid'];
            $area_addr_list[$l['branch_id']]['cityId']     = $platform_area['data']['cityid'];
            $area_addr_list[$l['branch_id']]['townId']     = $platform_area['data']['streetid'];
            $area_addr_list[$l['branch_id']]['countyId']   = $platform_area['data']['townid'];
        }

        unset($list);

        if (!$area_addr_list) {
            $error_msg = '库存监控未开启';
            return true;
        }

        $branch_id = array_keys($area_addr_list);


        // 获取渠道关系
        $sql = 'SELECT c.material_bn,c.channel_id,c.bm_id,bp.branch_id,bp.store
                FROM sdb_material_basic_material_channel c
                LEFT JOIN sdb_ome_branch_product bp ON(bp.product_id=c.bm_id)
                WHERE c.bm_id IN(' . implode(',', $product_id) . ') AND bp.branch_id IN(' . implode(',', $branch_id) . ') AND c.approve_status = "1" AND bp.store > 0 AND c.is_error="0"';
        $list = kernel::database()->select($sql);

        if (!$list) {
            $error_msg = '未查到有货库存';
            return true;
        }

        // 按渠道分类
        $skus_list = [];
        foreach ($list as $l) {
            $l['warn_num'] = $items[$l['bm_id']]['number'];

            $skus_list[$l['channel_id']][$l['branch_id']][$l['bm_id']] = $l;
        }
        unset($list);

        // 库存处理
        foreach ($skus_list as $channel_id => $bp) {
            foreach ($bp as $branch_id => $skus) {
                $addr = $area_addr_list[$branch_id];

                $params = [
                    'channel_id' => $channel_id,
                    'skus'       => $skus,
                    'addr'       => $addr,
                ];

                $object = kernel::single('erpapi_router_request')->set('wms', $addr['wms_id']);
                $object->goods_syncStore($params);
            }
        }

        return true;
    }
}
