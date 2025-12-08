<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_ctl_orders extends desktop_controller
{
    
    
    function index() {
        
        $this->title='订单列表';
        $params = array(
            'title' => $this->title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>true,
            'use_buildin_importxls'=>true,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
              
        );
        $this->finder('pos_mdl_orders', $params);
    }


    /**
     * import
     * @return mixed 返回值
     */
    public function import()
    {
        $this->display('admin/bill/import/bill.html');
    }


    /**
     * doImport
     * @return mixed 返回值
     */
    public function doImport()
    {
        set_time_limit(0);
        ini_set('memory_limit', '768M');

        $this->begin();

        list($rs, $msg) = kernel::single('pos_bill')->process($_FILES['import_file']);

        $this->endonly($rs ? true : false);

        if ($rs) {
            echo "<script>parent.$('iMsg').setText('导入完成');parent.$('import-form').getParent('.dialog').retrieve('instance').close();parent.finderGroup['" . $_GET['finder_id'] . "'].refresh();</script>";
            flush();
            ob_flush();
            exit;
        }
    }


}