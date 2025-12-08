<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_return_setting extends desktop_controller {

    var $workground = "setting_tools";
    
    function index()
    {
        $opObj    = app::get('ome')->model('operation_log');
        $branchMdl = app::get('ome')->model('branch');
        //配置信息保存
        if($_POST['set']){
            $this->begin('index.php?app=ome&ctl=admin_return_setting&act=index');
            $settins    = $_POST['set'];
            
            //保存配置
            foreach($settins as $set => $value)
            {
                $curSet = app::get('ome')->getConf($set);
                
                if($curSet != $settins[$set])
                {
                    $curSet = $settins[$set];
                    app::get('ome')->setConf($set, $settins[$set]);
                }
            }
            
            $this->end(true,'保存成功');
        }
        
        //读取配置
        $setData                                = array();
        $setData['return_auto_approve']         = app::get('ome')->getConf('return.auto_approve');
        $setData['aftersale_auto_approve']      = app::get('ome')->getConf('aftersale.auto_approve');
        $setData['aftersale_gift_auto_approve'] = app::get('ome')->getConf('aftersale.gift_auto_approve');
        $setData['return_auto_confirm']         = app::get('ome')->getConf('return.auto_confirm');
        $setData['return_logi_auto_approve'] = app::get('ome')->getConf('return.logi_auto_approve');
        
        $this->pagedata['setData'] = $setData;
        $this->page("admin/return_product/system/setting_index.html");
    }
}
?>