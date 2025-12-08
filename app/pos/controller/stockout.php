<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/1/16
 * @Describe: 其他出库单控制器
 */
class pos_ctl_stockout extends desktop_controller
{
    function index()
    {
        $this->title                       = '其他出库单';
        $params                            = array(
            'title'                  => $this->title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'use_view_tab'           => true,
            'actions'                => [
                // [
                //     'label'  => '导入其他出库单',
                //     'href'   => sprintf('%s&act=import', $this->url),
                //     'target' => 'dialog::{width:760,height:400,title:\'导入其他出库单\'}',
                // ],
            ]
        );
        $params['base_filter']['iso_type'] = '0';
        
        $this->finder('pos_mdl_iso', $params);
    }
    
    /**
     * 导入其他出库单
     * @Author: xueding
     * @Vsersion: 2023/1/17 上午11:08
     */
    public function import()
    {
        $this->pagedata['url'] = 'index.php?app=pos&ctl=stockout&act=doImport';
        $this->pagedata['tmp_url'] = 'index.php?app=pos&ctl=stockout&act=tmpImport';
        $this->display('admin/iso/import.html');
    }
    
    /**
     * 定制订单模板
     *
     * @return void
     * @author
     **/
    public function tmpImport()
    {
        $title = kernel::single('pos_iso')->getTitle();
        $msg = ['单据的导入为主单+明细的1对多格式，请严格按照格式填写。
1. 每一张主单信息只需填写第一行，之后的明细行前无需再填写主单信息，请参照下面的示例。
2. 单据号可以不填，系统会自动生成。
3. 多规格的商品，需要填写商品编码和规格属性。
4. 单规格的商品，只需填写规格编码。
5. 规格属性的格式为 【属性名1:属性值1 | 属性名2:属性值2】，比如【颜色:红 | 尺寸:大】，注意冒号为半角冒号。'];
        kernel::single('omecsv_phpoffice')->export("其他出库单模板.xlsx", [0 => $msg,1 => $title]);
    }
    
    /**
     * 导入其他入库单逻辑
     * @Author: xueding
     * @Vsersion: 2023/1/17 上午11:09
     */
    public function doImport()
    {
        set_time_limit(0);
        ini_set('memory_limit', '768M');
        $posStockLib = kernel::single('pos_iso');
        
        $this->begin();
        /**@used-by pos_iso::doImport **/
        list($rs, $msg) = $posStockLib->doImport($_FILES['import_file']);
        
        $this->endonly($rs ? true : false);
        
        // 保存iso单据
        /**@used-by pos_iso::createIostock **/
        $posStockLib->createIostock();
        
        if ($rs) {
            echo "<script>parent.$('iMsg').setText('导入完成');parent.$('import-form').getParent('.dialog').retrieve('instance').close();parent.finderGroup['" . $_GET['finder_id'] . "'].refresh();</script>";
            flush();
            ob_flush();
            exit;
        }
    }
}