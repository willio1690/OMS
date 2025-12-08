<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_statistics {

    function set($flag,$obj,$method){

        $now_date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $yes_date = mktime(0, 0, 0, date("m"), date("d")-1, date("Y"));

        $settingObj = app::get('openapi')->model('setting');
        $settingInfo = $settingObj->dump(array('code'=>$flag),'*');

        $tmp_data = app::get('openapi')->getConf('datainterface.statistics');
        $tmp_statisticsInfos = unserialize($tmp_data);
        if(isset($tmp_statisticsInfos[$flag])){
            if(isset($tmp_statisticsInfos[$flag]['today'][$now_date])){
                $tmp_statisticsInfos[$flag]['today'][$now_date] = $tmp_statisticsInfos[$flag]['today'][$now_date]+1;
                $tmp_statisticsInfos[$flag]['total'] = $tmp_statisticsInfos[$flag]['total']+1;
            }else{
                unset($tmp_statisticsInfos[$flag]['yesterday']);
                $tmp_yesterday_num = $tmp_statisticsInfos[$flag]['today'][$yes_date];
                unset($tmp_statisticsInfos[$flag]['today']);

                $tmp_statisticsInfos[$flag]['today'][$now_date] = 1;
                $tmp_statisticsInfos[$flag]['yesterday'][$yes_date] = $tmp_yesterday_num;
                $tmp_statisticsInfos[$flag]['total'] = $tmp_statisticsInfos[$flag]['total']+1;
            }

            if(isset($tmp_statisticsInfos[$flag][$obj][$method])){
                if(isset($tmp_statisticsInfos[$flag][$obj][$method]['today'][$now_date])){
                    $tmp_statisticsInfos[$flag][$obj][$method]['today'][$now_date] = $tmp_statisticsInfos[$flag][$obj][$method]['today'][$now_date]+1;
                    $tmp_statisticsInfos[$flag][$obj][$method]['total'] = $tmp_statisticsInfos[$flag][$obj][$method]['total']+1;
                }else{
                    unset($tmp_statisticsInfos[$flag][$obj][$method]['yesterday']);
                    $tmp_yesterday_num = $tmp_statisticsInfos[$flag][$obj][$method]['today'][$yes_date];
                    unset($tmp_statisticsInfos[$flag][$obj][$method]['today']);

                    $tmp_statisticsInfos[$flag][$obj][$method]['today'][$now_date] = 1;
                    $tmp_statisticsInfos[$flag][$obj][$method]['yesterday'][$yes_date] = $tmp_yesterday_num;
                    $tmp_statisticsInfos[$flag][$obj][$method]['total'] = $tmp_statisticsInfos[$flag][$obj][$method]['total']+1;
                }
            }else{
                $tmp_statisticsInfos[$flag][$obj][$method] = array(
                    'total' => 1,
                    'yesterday' => array(
                        $yes_date => 0
                    ),
                    'today' => array(
                        $now_date => 1
                    ),
                );
            }
        }else{
            $tmp_statisticsInfos[$flag] = array(
                'total' => 1,
                'yesterday' => array(
                    $yes_date => 0
                ),
                'today' => array(
                    $now_date => 1
                ),
            );

            $tmp_statisticsInfos[$flag][$obj][$method] = array(
                'total' => 1,
                'yesterday' => array(
                    $yes_date => 0
                ),
                'today' => array(
                    $now_date => 1
                ),
            );
        }

        app::get('openapi')->setConf('datainterface.statistics',serialize($tmp_statisticsInfos));
    }
}