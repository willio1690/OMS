<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/1/16
 * @Describe: 其他入库单控制器
 */
class pos_ctl_stockin extends desktop_controller
{
    function index()
    {
        $this->title                       = '其他入库单';
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
                //     'label'  => '导入其他入库单',
                //     'href'   => sprintf('%s&act=import', $this->url),
                //     'target' => 'dialog::{width:760,height:400,title:\'导入其他入库单\'}',
                // ],
            ],
        );
        $params['base_filter']['iso_type'] = '1';
        
        $this->finder('pos_mdl_iso', $params);
    }
    
    /**
     * 导入其他入库单
     * @Author: xueding
     * @Vsersion: 2023/1/17 上午11:08
     */
    public function import()
    {
        $this->pagedata['url'] = 'index.php?app=pos&ctl=stockin&act=doImport';
        $this->pagedata['tmp_url'] = 'index.php?app=pos&ctl=stockin&act=tmpImport';
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
        $title = kernel::single('pos_iso')->getTitle('1');
        $msg = ['导入说明：
1.第二行为表头，不能修改、删除、增加。
2.您的数据从第三行开始导入，星号的字段表示必填。
3.带“（选项）”的列填写选项值，选项值在“选择项列表”中查找，多个请用英文逗号分隔。
4.鼠标悬停在表头处可以查看备注。
5.导入数据最多为1000000行。'];
        kernel::single('omecsv_phpoffice')->export("其他入库单模板.xlsx", [0 => $msg,1 => $title]);
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
        list($rs, $msg) = $posStockLib->doImport($_FILES['import_file'],1);
        
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