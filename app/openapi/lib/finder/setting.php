<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_finder_setting{

    static private $_statistics = null;

    function __construct(){
        $this->getStatistics();
    }


    var $addon_cols = "s_id,code,status";
    var $column_edit = "操作";
    var $column_edit_width = "100";

    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];
        if($row[$this->col_prefix . 'status'] == 1){
            $ret = "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定要暂停该设置吗？？？\\n\\n注意：你还可以随时启用该设置。\")) {href=\"index.php?app=openapi&ctl=admin_setting&act=setStatus&p[0]={$row[$this->col_prefix . 's_id']}&p[1]=0&finder_id={$finder_id}\";}'>暂停</a>";
        }else{
            $ret = "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定要启用该设置吗？？？\\n\\n注意：你还可以随时暂停该设置。\")) {href=\"index.php?app=openapi&ctl=admin_setting&act=setStatus&p[0]={$row[$this->col_prefix . 's_id']}&p[1]=1&finder_id={$finder_id}\";}'>启用</a>";
        }
        $ret .= "&nbsp;<a href='index.php?app=openapi&ctl=admin_setting&act=edit&p[0]={$row[$this->col_prefix . 's_id']}&finder_id={$finder_id}' target=\"_blank\">编辑</a>";
        return $ret;
    }

    var $column_total = "总调用次数";
    var $column_total_width = "120";
    function column_total($row){
        $total = (isset(self::$_statistics[$row[$this->col_prefix . 'code']]) && isset(self::$_statistics[$row[$this->col_prefix . 'code']]['total'])) ? self::$_statistics[$row[$this->col_prefix . 'code']]['total'] : 0;
        return $total;
    }

    var $column_yesterday_count = "昨日调用次数";
    var $column_yesterday_count_width = "120";
    function column_yesterday_count($row){
        $total = (isset(self::$_statistics[$row[$this->col_prefix . 'code']]) && isset(self::$_statistics[$row[$this->col_prefix . 'code']]['yesterday'])) ? array_shift(self::$_statistics[$row[$this->col_prefix . 'code']]['yesterday']) : 0;
        return $total;
    }

    var $column_today_count = "今日调用次数";
    var $column_today_count_width = "120";
    function column_today_count($row){
        $total = (isset(self::$_statistics[$row[$this->col_prefix . 'code']]) && isset(self::$_statistics[$row[$this->col_prefix . 'code']]['today'])) ? array_shift(self::$_statistics[$row[$this->col_prefix . 'code']]['today']) : 0;
        return $total;
    }

    private function getStatistics(){
        if(isset(self::$_statistics)){
            return self::$_statistics;
        }else{
            $tmpdata = app::get('openapi')->getConf('datainterface.statistics');
            self::$_statistics = unserialize($tmpdata);
        }

    }

    var $detail_basic = "基础设置详情";
    function detail_basic($sid){
        $render = app::get('openapi')->render();
        $settingObj = app::get('openapi')->model('setting');
        $settingInfo = $settingObj->dump(array('s_id'=>$sid));
        $flag = $settingInfo['code'];

        $tmp_statisticsInfos = self::$_statistics;
        $methodLists = openapi_conf::getMethods();
        if(isset($tmp_statisticsInfos[$flag]) && $flag){
            $data['total'] = isset($tmp_statisticsInfos[$flag]['total']) ? $tmp_statisticsInfos[$flag]['total'] : 0;
            $data['yesterday'] = isset($tmp_statisticsInfos[$flag]['yesterday']) ? array_shift($tmp_statisticsInfos[$flag]['yesterday']) : 0;
            $data['today'] = isset($tmp_statisticsInfos[$flag]['today']) ? array_shift($tmp_statisticsInfos[$flag]['today']) : 0;

            unset($tmp_statisticsInfos[$flag]['total']);
            unset($tmp_statisticsInfos[$flag]['yesterday']);
            unset($tmp_statisticsInfos[$flag]['today']);

            foreach($methodLists as $key => $obj){
                if(isset($tmp_statisticsInfos[$flag][$key])){
                    foreach ($obj['methods'] as $k => $name){
                        if(isset($tmp_statisticsInfos[$flag][$key][$k])){
                            $data['details'][$key]['label'] = $obj['label'];
                            $data['details'][$key]['info'][$k]['label'] = $name;
                            $data['details'][$key]['info'][$k]['total'] = isset($tmp_statisticsInfos[$flag][$key][$k]['total']) ? $tmp_statisticsInfos[$flag][$key][$k]['total'] : 0;
                            $data['details'][$key]['info'][$k]['yesterday'] = isset($tmp_statisticsInfos[$flag][$key][$k]['yesterday']) ? array_shift($tmp_statisticsInfos[$flag][$key][$k]['yesterday']) : 0;
                            $data['details'][$key]['info'][$k]['today'] = isset($tmp_statisticsInfos[$flag][$key][$k]['today']) ? array_shift($tmp_statisticsInfos[$flag][$key][$k]['today']) : 0;
                        }
                    }
                }
            }
        }else{
            $data = null;
        }

        $render->pagedata['data'] = $data;

        return $render->fetch('admin/setting/statistics.html');
    }
}
