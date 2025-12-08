<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_tasklog{

    /* 详情
     */
    function detail_log($log_id){
        
        $render = app::get('finance')->render();
        $oApilog = &app::get('finance')->model("tasklog");
        $apilog = $oApilog->dump($log_id);

        $apilog['params'] = unserialize($apilog['params']);
        foreach ($apilog['params'] as $key=>$val){
            if (is_array($val)) $params .= $key.'='.serialize($val)."<br/>";
            else $params .= $key."=".$val."<br/>";
        }
        $apilog['send_api_params'] = $params;

        $apilog_msg = @json_decode($apilog['msg'],true);
        $msg = '';
        if (is_array($apilog_msg)){
            ob_start();
            echo "<pre>";
            var_export($apilog_msg);
            $msg = ob_get_contents();
            ob_clean();
        }else{
            $msg = htmlspecialchars($apilog['msg']);
        }
        $apilog['msg'] = $msg;
        $render->pagedata['apilog'] = $apilog;
        return $render->fetch("tasklog/detail.html");
    }

    var $column_retry='操作';
    var $column_retry_width = 130;
    var $column_retry_order = 1;
    var $addon_cols = "status";
    function column_retry($row){
        $log_id = $row['log_id'];
        $finder_id = $_GET['_finder']['finder_id'];

        $but1 = "<a href=\"index.php?app=finance&ctl=tasklog&act=retry&p[0]={$log_id}&finder_id={$finder_id}\" target=\"dialog::{title:'日志重试', width:550, height:300}\">重试</a>";

        $but2 = sprintf('<a href="javascript:if (confirm(\'当前任务正在运行中，是否确定强制失败？\')){W.page(\'index.php?app=finance&ctl=tasklog&act=abort_fail&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">强制失败</a>',$log_id,$finder_id);
        
        $return_but = array();
        if ($row[$this->col_prefix.'status'] == 'fail'){
            $return_but[] = $but1;
        }
        if ($row[$this->col_prefix.'status'] == 'running'){
            $return_but[] = $but2;
        }

        return @implode(' | ',$return_but);
    }

}
?>