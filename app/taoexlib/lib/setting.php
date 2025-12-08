<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoexlib_setting{
    /**
     * view
     * @return mixed 返回值
     */
    public function view(){
        $settings = $this->all_settings();
        foreach($settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = app::get('taoexlib')->getConf($set);
        }

        $render = kernel::single('base_render');
        $render->pagedata['setData'] = $setData;
        $html = $render->fetch('admin/setting.html','taoexlib');
        return $html;
    }

    /**
     * all_settings
     * @return mixed 返回值
     */
    public function all_settings(){
        $all_settings =array(
				  'taoexlib.message.switch',
				  'taoexlib.message.warningnumber',
				  'taoexlib.message.sampletitle',
				  'taoexlib.message.samplecontent',
				  'taoexlib.message.blacklist',
                );
        return $all_settings;
    }
}
