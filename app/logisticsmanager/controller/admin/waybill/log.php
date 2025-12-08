<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_ctl_admin_waybill_log extends desktop_controller{
    /**
     * index
     * @return mixed 返回值
     */
    public function index() {
        $base_filter = array('status|noequal'=>'success');
        $params = array(
            'title'=>'电子面单异常请求',
            'actions'=>array(
                array('label' => '批量重试', 'submit' => 'index.php?app=logisticsmanager&ctl=admin_waybill_log&act=batch_retry','target' => 'dialog::{width:550,height:170,title:\'重试获取快递面单请求\'}'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'base_filter' => $base_filter,
        );
        $this->finder('logisticsmanager_mdl_waybill_log', $params);
    }

    /**
     * batch_retry
     * @return mixed 返回值
     */
    public function batch_retry(){
        $this->pagedata['log_id'] = $_POST['log_id'];
        $this->pagedata['logCount'] = count($_POST['log_id']);
        $this->pagedata['jsonLogs'] = json_encode($_POST['log_id']);
        $this->display("admin/waybill/retryLog.html");
    }

    /**
     * ajaxRetry
     * @return mixed 返回值
     */
    public function ajaxRetry(){
        echo json_encode(array('total' => 0, 'succ' => 0, 'fail' => 0));
    }
}