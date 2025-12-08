<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_system_setting{

    public $tab_name = '门店配置';

    public $tab_key = 'o2o';

    private $_setting_tab = array(
        array('name' => '门店配置', 'file_name' => 'admin/system/setting/tab_o2o.html', 'app' => 'o2o', 'order' => 99),
    );

    /**
     * 获取_setting_tab
     * @return mixed 返回结果
     */
    public function get_setting_tab()
    {
        return $this->_setting_tab;
    }

    /**
     * 获取View
     * @return mixed 返回结果
     */
    public function getView(){
        $settings = $this->all_settings();
        foreach($settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = app::get('o2o')->getConf($set);
        }

        $render = kernel::single('base_render');


        $render->pagedata['tab_key'] = $this->tab_key;
        $render->pagedata['setData'] = $setData;

        $html = $render->fetch('admin/system/setting.html','o2o');
        return $html;
    }

    /**
     * all_settings
     * @return mixed 返回值
     */
    public function all_settings(){
        $all_settings =array(
            'o2o.autostore.type',
            'o2o.baidumap.show',
            'o2o.baidumap.ak',
            'o2o.baidumap.sk',
            'o2o.delivery.confirm.code',
            'o2o.delivery.dly_overtime',
            'o2o.ctrl.supply.relation',
        );
        return $all_settings;
    }

    /**
     * 保存Conf
     * @param mixed $data 数据
     * @return mixed 返回操作结果
     */
    public function saveConf($data){
        foreach($data as $set=>$value){
            $curSet = app::get('o2o')->getConf($set);
            if(in_array($set,$this->all_settings()) && $curSet!=$data[$set]){
                app::get('o2o')->setConf($set,$data[$set]);
            }
        }
        return true;
    }

    /**
     * 获取_setting_data
     * @return mixed 返回结果
     */
    public function get_setting_data()
    {
        $setData = array();

        $all_settings = $this->all_settings();

        foreach($all_settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = app::get('o2o')->getConf($set);
        }

        $modes = o2o_autostore::getAutoStoreModes();
        $setData['autostore_modes'] = $modes;

        return $setData;
    }

}
