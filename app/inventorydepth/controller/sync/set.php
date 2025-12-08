<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @describe: 库存同步设置
 * ============================
 */
class inventorydepth_ctl_sync_set extends desktop_controller {
    public function index() {
        //配置信息保存
        if($_POST['set']){
            $this->begin($this->url);
            $settins    = $_POST['set'];
            
            //保存配置
            foreach($settins as $set => $value)
            {
                $curSet = app::get('inventorydepth')->getConf($set);
                
                if($curSet != $settins[$set])
                {
                    $curSet = $settins[$set];
                    app::get('inventorydepth')->setConf($set, $settins[$set]);
                }
            }
            
            $this->end(true,'保存成功');
        }
        
        //读取配置
        $setData                                = array();
        $setData['stock_sync_set']         = app::get('inventorydepth')->getConf('stock.sync.set');
        $setData['stock_sync_mode']        = app::get('inventorydepth')->getConf('stock.sync.mode');
        
        $this->pagedata['setData'] = $setData;
        $this->pagedata['support_name'] = kernel::single('inventorydepth_sync_set')->getSupportName();
        $this->pagedata['mode_support_name'] = kernel::single('inventorydepth_sync_set')->getModeSupportName();
        $this->page("sync/setting.html");
    }
}