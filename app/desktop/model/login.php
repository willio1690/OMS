<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/1/20 14:36:16
 * @describe: model层
 * ============================
 */
class desktop_mdl_login extends dbeav_model {

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
           $table_name = 'sdb_pam_log';
        }else{
           $table_name = 'log';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $return = app::get('pam')->model('log')->get_schema();
        foreach ($return['columns'] as $key => $value) {
            $return['columns'][$key]['label'] = $value['comment'];
        }
        $return['in_list'] = ['event_time','event_data'];
        $return['default_in_list'] = ['event_time','event_data'];
        return $return;
    }

    /**
     * modifier_event_time
     * @param mixed $col col
     * @return mixed 返回值
     */
    public function modifier_event_time($col) {
        return date('Y-m-d H:i:s', $col);
    }
}