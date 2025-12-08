<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_ctl_ome_setting extends desktop_controller{
    public $prefix = 'omeanalysts_ome_config.';

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $this->pagedata['week'] = array('星期日','星期一','星期二','星期三','星期四','星期五','星期六');
        $this->pagedata['time_shortcut'] = array('昨日','今日','本周','上周','本月','上月');
        $analysis_config = app::get('eccommon')->getConf('analysis_config');
        if(!$analysis_config){
            $analysis_config = array(
                'setting' => array(
                    'week' => 1,
                    'time_shortcut' => array(1,2,3,4,5,6),
                ),
            );
        }
        $this->pagedata['setData'] = $analysis_config;
        $this->page("ome/setting.html");
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save(){
        if($_POST){
            $this->begin('index.php?app=omeanalysts&ctl=ome_setting&act=index');
            $data['setting']['week'] = $_POST['week'];
            $data['setting']['time_shortcut'] = $_POST['time_shortcut'];
            $data['filter']['order_status'] = $_POST['order_status'];
            app::get('eccommon')->setConf('analysis_config', $data);
            $this->end(true, app::get('base')->_('保存成功'));
       }
    }

    /**
     * 设置
     * @param mixed $k k
     * @param mixed $v v
     * @return mixed 返回操作结果
     */
    public function set($k, $v){
        return app::get('omeanalysts')->setConf($prefix.$k, $v);
    }

    /**
     * 获取
     * @param mixed $k k
     * @return mixed 返回结果
     */
    public function get($k){
        return app::get('omeanalysts')->getConf($prefix.$k);
    }
}