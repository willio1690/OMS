<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_finder_platform_aftersale {

    public $addon_cols = "plat_aftersale_id";

    private static $sync_status_values = array(
        '0' => '未转换',
        '1' => '转换成功',
        '2' => '转换失败',
        '3' => '无需转换',
    );

    function detail_basic($plat_aftersale_id){

        $render = app::get('dealer')->render();


        $plat_aftersaleMdl = app::get('dealer')->model('platform_aftersale');
        $plat_aftersale_itemsMdl = app::get('dealer')->model('platform_aftersale_items');


        $platform_aftersale = $plat_aftersaleMdl->db_dump(array('plat_aftersale_id'=>$plat_aftersale_id),'*');

        $platform_aftersale_objectsMdl = app::get('dealer')->model('platform_aftersale_objects');

        $objects = $platform_aftersale_objectsMdl->getlist('*',array('plat_aftersale_id'=>$plat_aftersale_id));


        foreach($objects as $ok=>$ov){

            $items = $plat_aftersale_itemsMdl->getlist('*',array('plat_aftersale_id'=>$plat_aftersale_id,'plat_aftersale_obj_id'=>$ov['plat_aftersale_obj_id']));

            
            $sync_status_values = self::$sync_status_values;

            foreach($items as $k=>$v){
                $items[$k]['sync_status_value'] = $sync_status_values[$v['sync_status']];
            }

            $objects[$ok]['items'] = $items;
        }

        $render->pagedata['platform_aftersale'] = $platform_aftersale;

        $render->pagedata['objects'] = $objects;

        $logMdl = app::get('ome')->model('operation_log');
        $logList = $logMdl->read_log(array('obj_id'=>$plat_aftersale_id,'obj_type'=>'platform_aftersale@dealer'), 0, -1);

        foreach($logList as $k=>$v){
            $logList[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }


        $render->pagedata['logList'] = $logList;


        return $render->fetch('admin/platform/aftersale_items.html');

    }
}