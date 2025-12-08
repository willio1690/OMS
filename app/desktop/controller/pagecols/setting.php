<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class desktop_ctl_pagecols_setting extends desktop_controller
{
    public $workground = 'setting_tools';
    
    /**
     * 列表页面
     */
    public function index()
    {
        $model = $this->app->model('pagecols_setting');
        
        // 获取所有配置数据
        $configs = $model->getList('*', array(), 0, -1, 'id ASC');
    
        // 按场景分组数据
        $grouped_configs = array();
        foreach ($configs as $config) {
            $scene = $config['tbl_name'];
            if (!isset($grouped_configs[$scene])) {
                $grouped_configs[$scene] = array();
            }
            $grouped_configs[$scene][$config['col_key']] = array(
                'required' => $config['is_required'] == '1',
                'default' => $config['default_value']
            );
        }
        $this->pagedata['grouped_configs'] = $grouped_configs;
        
        // 预定义的场景和元素
        $list = kernel::servicelist('set_pagecols_setting');
        $predefined_scenes = array();
        foreach ($list as $k => $obj) {
            if (method_exists($obj, 'get_pagecols_setting')) {
                $scenes = $obj->get_pagecols_setting();
                if (is_array($scenes)) {
                    $predefined_scenes = array_merge($predefined_scenes, $scenes);
                }
            }
        }
        $this->pagedata['predefined_scenes'] = $predefined_scenes;

        $this->page('admin/pagecols/setting.html');
    }
    
    /**
     * 获取配置数据
     */
    public function getConfigs()
    {
        $model = $this->app->model('pagecols_setting');
        
        // 获取所有配置数据
        $configs = $model->getList('*', array(), 0, -1, 'id ASC');
        
        // 按场景分组数据
        $grouped_configs = array();
        foreach ($configs as $config) {
            $scene = $config['tbl_name'];
            if (!isset($grouped_configs[$scene])) {
                $grouped_configs[$scene] = array();
            }
            $grouped_configs[$scene][] = $config;
        }
        
        $this->pagedata['grouped_configs'] = $grouped_configs;
        $this->pagedata['scenes'] = array_keys($grouped_configs);
        
        // 预定义的场景和元素
        $list = kernel::servicelist('set_pagecols_setting');
        $predefined_scenes = array();
        foreach ($list as $k => $obj) {
            if (method_exists($obj, 'get_pagecols_setting')) {
                $scenes = $obj->get_pagecols_setting();
                if (is_array($scenes)) {
                    $predefined_scenes = array_merge($predefined_scenes, $scenes);
                }
            }
        }
        $this->pagedata['predefined_scenes'] = $predefined_scenes;
        
        $this->display('admin/pagecols/config_table.html');
    }
    
    /**
     * 保存配置数据
     */
    public function saveConfigs()
    {
        $finder_vid = $_GET['finder_vid'] ? $_GET['finder_vid'] : '';
        $redirect_url = 'index.php?app=desktop&ctl=pagecols_setting&act=index';
        if ($finder_vid) {
            $redirect_url .= '&finder_vid=' . $finder_vid;
        }
        
        $this->begin($redirect_url);
        
        $model = $this->app->model('pagecols_setting');
        $success = true;
        $message = '';
        
        try {
            // 获取提交的数据
            $scene_data = $_POST['scene_data'];
            
            // 先删除所有现有配置
            $model->delete(array());
            
            // 保存新的配置数据
            foreach ($scene_data as $scene => $elements) {
                foreach ($elements as $element_key => $element_data) {
                    $data = array(
                        'tbl_name' => $scene,
                        'col_key' => $element_key,
                        'is_required' => $element_data['required'] ? '1' : '0',
                        'default_value' => $element_data['default'],
                    );
                    
                    $result = $model->insert($data);
                    if (!$result) {
                        $success = false;
                        $message = '保存失败';
                        break 2;
                    }
                }
            }
            
            if ($success) {
                $this->end(true, '保存成功');
            } else {
                $this->end(false, $message);
            }
            
        } catch (Exception $e) {
            $this->end(false, '保存失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取场景配置
     */
    public function getSceneConfig($scene)
    {
        $model = $this->app->model('pagecols_setting');
        $configs = $model->getList('*', array('tbl_name' => $scene), 0, -1, 'id ASC');
        
        $result = array();
        foreach ($configs as $config) {
            $result[$config['col_key']] = array(
                'required' => $config['is_required'] == '1',
                'default' => $config['default_value']
            );
        }
        
        echo json_encode($result);
        exit;
    }
} 