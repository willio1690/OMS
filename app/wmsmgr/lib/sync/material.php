<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 初始化基础物料
 *
 */
class wmsmgr_sync_material
{
    
    /**
     * 同步物料
     * @param $data
     * @return string[]
     */
    public function syncMaterial($data)
    {
        $channel = app::get('wmsmgr')->model('channel')->dump(['channel_id' => $data['channelId']], '*');
        
        if (empty($channel)) {
            return array('rsp' => 'fail', 'msg'=>'请求渠道不存在', 'err_msg'=>'请求渠道不存在');
        }
    
        $sdf = array(
            'scroll_id'    => $data['scrollId'],
            'start_time'   => $data['startTime'],
            'end_time'     => $data['endTime'],
            'channel_id'   => $data['channelId'],
            'start_ymdhis' => $data['start_ymdhis'],
            'end_ymdhis'   => $data['end_ymdhis'],
        );
        
        //第一步请求商品列表
        $shop_list_result = kernel::single('erpapi_router_request')->set('wms',
            $channel['wms_id'])->goods_syncGet($sdf);
        
        if ($shop_list_result['rsp'] != 'succ' || empty($shop_list_result)) {
            return $shop_list_result;
        }
        
        $res = $shop_list_result['data']['items'];
        
        if (!$res) {
            return $shop_list_result;
        }
        $rs['material_add_error'] = 0;
        $rs['madd_foreign_succ']  = 0;
        $rs['madd_foreign_error'] = 0;
        $rs['fail']               = 0;
        $rs['succ']               = 0;
        $rs['err_msg']            = array();
        
        $where = ['data' => $res, 'channel_id' => $sdf['channel_id']];
        //第二步根据列表请求商品详情
        $shop_detail_result = kernel::single('erpapi_router_request')->set('wms',
            $channel['wms_id'])->goods_syncDetail($where);
        if ($shop_detail_result['rsp'] != 'succ') {
            $rs['err_msg'][] = $shop_detail_result['err_msg'] . '<br/>';
        }
        $shop_detail_arr = array_column($shop_detail_result['data']['items'], null, 'outer_sku');
        
        //第三步根据商品请求商品价格
        $shop_price_result = kernel::single('erpapi_router_request')->set('wms',
            $channel['wms_id'])->goods_syncPrice($where);
        if ($shop_price_result['rsp'] != 'succ') {
            $rs['err_msg'][] = $shop_detail_result['err_msg'] . '<br/>';
        }
        $shop_price_arr = array_column($shop_price_result['data']['items'], null, 'outer_sku');
        
        foreach ($res as $key => $value) {
            $rs['succ']                    += 1;
            $res[$key]['price']            = $shop_price_arr[$value['outer_sku']]['price'];
            $res[$key]['shop_product_bn']  = $value['inner_sku'];
            $res[$key]['outer_product_id'] = $value['outer_sku'];
            $res[$key]['retail_price']     = $shop_price_arr[$value['outer_sku']]['price'];
            $res[$key]['errorMessage']     = $shop_price_arr[$value['outer_sku']]['errorMessage'];
            $res[$key]['weight']           = $shop_detail_arr[$value['outer_sku']]['base_info']['weight'];
            $res[$key]['length']           = $shop_detail_arr[$value['outer_sku']]['base_info']['length'];
            $res[$key]['width']            = $shop_detail_arr[$value['outer_sku']]['base_info']['width'];
            $res[$key]['high']             = $shop_detail_arr[$value['outer_sku']]['base_info']['height'];
            $res[$key]['unit']             = $shop_detail_arr[$value['outer_sku']]['base_info']['unit'];
            $res[$key]['specifications']   = $shop_detail_arr[$value['outer_sku']]['base_info']['packageType'];
            $res[$key]['color']            = $shop_detail_arr[$value['outer_sku']]['base_info']['color'];
            $res[$key]['size']             = $shop_detail_arr[$value['outer_sku']]['base_info']['size'];
            $res[$key]['sku_status']       = $shop_detail_arr[$value['outer_sku']]['base_info']['skuStatus'];//上下架状态(1: 上架 0:下架)
            $res[$key]['banner']           = $shop_detail_arr[$value['outer_sku']]['images'];
            $res[$key]['source_from']      = 'kepler';
            $res[$key]['channel_name']     = $channel['channel_name'];
            if (isset($shop_price_arr[$value['outer_sku']]['errorMessage'])) {
                continue;
            }
            //如果是on初始化物料否则只是同步物料外部编码与价格
            if ($data['isInit'] == 'on') {
                //插入基础销售物料信息
                $add_material_res = kernel::single('material_material')->add($res[$key]);
                if (!$add_material_res['res']) {
                    $rs['material_add_error'] += 1;
                    $rs['err_msg'][]          = $res[$key]['outer_sku'] . '增加基础物料失败:' . $add_material_res['err_msg'] . '<br/>';
                }
                //插入oms与wms映射关系数据
                $add_foreign_res = $this->add_foreign_sku($res[$key], $channel['wms_id']);
                if ($add_foreign_res['res']) {
                    $rs['add_foreign_succ'] += 1;
                } else {
                    $rs['add_foreign_error'] += 1;
                    $rs['err_msg'][]         = $res[$key]['outer_sku'] . 'WMS关联失败:' . $add_foreign_res['err_msg'] . '<br/>';
                }
            } else {
                $add_foreign_res = $this->add_foreign_sku($res[$key], $channel['wms_id']);
                if ($add_foreign_res['res']) {
                    $rs['add_foreign_succ'] += 1;
                } else {
                    $rs['add_foreign_error'] += 1;
                    $rs['err_msg'][]         = $res[$key]['outer_sku'] . 'WMS关联失败:' . $add_foreign_res['err_msg'] . '<br/>';
                }
            }
        }
        
        $rs['total']    = $shop_list_result['data']['total'];
        $rs['scrollId'] = $shop_list_result['data']['scrollId'];
        $rs['rsp']      = $shop_list_result['rsp'];
        return $rs;
    }
    
    /**
     * 插入oms与wms映射关系数据
     * @param $data
     * @param $wms_id
     * @return bool
     */
    public function add_foreign_sku($data, $wms_id)
    {
        $materialObj = app::get('material')->model('basic_material');
        if (empty($data)) {
            return ['res' => false, 'err_msg' => '插入数据为空'];
        }
        
        if (empty($data['inner_sku'])) {
            return ['res' => false, 'err_msg' => 'WMS物料编码为空'];
        }
        $materialRow = $materialObj->db_dump(array('material_bn' => $data['inner_sku']), 'bm_id');
        if (!$materialRow) {
            return ['res' => false, 'err_msg' => 'OMS未找到物料'];
        }
        $upData     = array(
            'inner_sku'        => $data['inner_sku'],
            'inner_product_id' => $materialRow['bm_id'],
            'wms_id'           => $wms_id,
            'outer_sku'        => $data['outer_sku'],
            'price'            => $data['price'],
            'sync_status'      => 3,
        );
        $foreignObj = app::get('console')->model('foreign_sku');
        $oldRow     = $foreignObj->db_dump(array(
            'inner_sku' => $upData['inner_sku'],
            'wms_id'    => $upData['wms_id']
        ), 'fsid');
        if ($oldRow) {
            $foreignObj->update(array('outer_sku' => $upData['outer_sku'], 'price' => $upData['price']),
                array('fsid' => $oldRow['fsid']));
        } else {
            $foreignObj->insert($upData);
        }
        return ['res' => true, 'err_msg' => ''];
    }
    
}
