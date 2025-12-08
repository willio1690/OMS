<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_tasklog extends desktop_controller{
    var $workground = "tasklog";
    
    function index($log_type){
        if ($log_type){
            $log_type = $log_type ? $log_type : $_GET['log_type'];
            $crc32_log_type = sprintf('%u',crc32(md5($log_type)));
            $this->base_filter = array('crc32_log_type'=>$crc32_log_type);
        }
        $params = array(
            'title'=>'任务日志列表',
            'actions'=> $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'orderBy' => $orderby,
        );
        $params['base_filter'] = $this->base_filter;
        
        $this->finder('finance_mdl_tasklog',$params);
    }

    function _views(){
        $mdl_tasklog = $this->app->model('tasklog');
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$this->base_filter,'optional'=>false),
        );
        $_columns = $mdl_tasklog->_columns();
        $status = $_columns['status']['type'];
        foreach($status as $key=>$name){
            $sub_menu[] = array(
                'label' => $name,
                'filter' => array('status'=>$key),
                'optional' => false,
            );
        }
        $i=0;
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_tasklog->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=finance&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&p[0]='.$this->base_filter['log_type'].'&view='.$i++.'&status='.$v['filter']['status'];
        }
        return $sub_menu;
    }
    
    /*
     * 重试同步日志
     * @param int or array $log_id 待重试的日志ID
     * @param string $retry_type 重试方式，默认为单个重试，batch:为批量重试
     */
    function retry($log_id='', $retry_type='single'){
        set_time_limit(0);
        if ($retry_type == 'single'){
            $this->pagedata['log_id'] = $log_id;
        }else{
            if (is_array($log_id['log_id'])){
                $this->pagedata['log_id'] = implode("|", $log_id['log_id']);
            }
        }
        $this->pagedata['isSelectedAll'] = $log_id['isSelectedAll'];
        $this->pagedata['retry_type'] = $retry_type;
        $this->pagedata['log_type'] = $log_id['log_type'];
        $this->pagedata['postData'] = serialize($_POST);
        $this->display("tasklog/retry.html");
    }
    
    function retry_do(){
        set_time_limit(0);
        $log_id = urldecode($_GET['log_id']);
        $retry_type = $_GET['retry_type'];
        $log_type = $_GET['log_type'];
        $isSelectedAll = $_GET['isSelectedAll'];
        $cursor = $_GET['cursor'];
        $return = $this->app->model('tasklog')->retry($log_id, $log_type, $retry_type, $isSelectedAll, $cursor,$postData);
        echo json_encode($return);
        exit;
    }
    
    function batch_retry(){
        $this->retry($_POST, 'batch');
    }

    /**
     * 强制失败
     * 手动更改运行中或发起中的日志状态为失败
     * @param $_POST 日志ID
     * @return 更新日志状态为失败
     */
    function abort_fail($log_id='',$finder_id=''){
        $this->begin('index.php?app=finance&ctl=tasklog&act=index&view='.$view.'&finder_id='.$finder_id);
        kernel::single('finance_tasklog')->abort_fail($log_id);
        $this->end(true,'强制失败操作成功');
    }
    
}
?>