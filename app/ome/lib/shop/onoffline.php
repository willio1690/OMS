<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/2/21 15:32:03
 * @describe: 网店门店关系
 * ============================
 */
class ome_shop_onoffline {

    /**
     * doSave
     * @param mixed $offline_id ID
     * @param mixed $online_id ID
     * @return mixed 返回值
     */

    public function doSave($offline_id, $online_id) {
        if(empty($offline_id)) {
            return [false, '参数不全'];
        }
        $onOff = app::get('ome')->model('shop_onoffline');
        $onOff->delete(['off_id'=>$offline_id]);
        if(empty($online_id) || !is_array($online_id)) {
            return [true];
        }
        $data = [];
        foreach ($online_id as $v) {
            $data[] = ['on_id'=>$v, 'off_id'=>$offline_id];
        }
        $sql = kernel::single('ome_func')->get_insert_sql($onOff, $data);
        $onOff->db->exec($sql);
        return [true];
    }
    
    /**
     * 前端店铺云店绑定
     * @param $online_shop_id string
     * @param $offline_shop_ids array
     * @return array
     * @date 2024-04-09 4:10 下午
     */
    public function onlineSave($online_shop_id, $offline_shop_ids)
    {
        if (!$online_shop_id) {
            return [false, '参数不全'];
        }
        $onOff = app::get('ome')->model('shop_onoffline');
        $onOff->delete(['on_id' => $online_shop_id]);
        
        if (empty($offline_shop_ids) || !is_array($offline_shop_ids)) {
            return [true, '保存成功'];
        }
        $data = [];
        foreach ($offline_shop_ids as $v) {
            $data[] = ['on_id' => $online_shop_id, 'off_id' => $v];
        }
        $sql = kernel::single('ome_func')->get_insert_sql($onOff, $data);
        $onOff->db->exec($sql);
        return [true, '保存成功'];
    }
}