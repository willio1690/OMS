<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2021/8/10 16:24:36
 * @describe: 库存设置导入
 * ============================
 */
class inventorydepth_ctl_shop_skustockset extends desktop_controller {

    public function index() {
        $actions = array();
        $actions[] = array(
            'label'  => '导出模板',
            'href'   => $this->url.'&act=getExportTemplate',
            'target' => '_blank',
        );
        $actions[] = array('label' => app::get('desktop')->_('导入'), 'icon' => 'upload.gif', 'href' => 'index.php?app=omecsv&ctl=admin_import&act=main&ctler=inventorydepth_mdl_shop_skustockset&add=inventorydepth', 'target' => 'dialog::{width:550,height:300,title:\'' . app::get('desktop')->_('导入') . '\'}');
        $params = array(
                'title'=>'平台SKU库存设置',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>true,
                'delete_confirm_tip'=>"删除平台SKU的独立库存配置后，前端库存不变\n需再次导入/更新OMS库存后才可覆盖更新平台的售卖库存数量，是否确认删除？",
                'actions'=>$actions,
                'orderBy'=>'id desc',
        );
        
        $this->finder('inventorydepth_mdl_shop_skustockset', $params);
    }

    public function getExportTemplate() {
        $this->singlepage('shop/sku_stock_set_template.html');
    }

    public function searchSaleStock() {
        $data = kernel::single('inventorydepth_shop_skustockset')->getShopSkuStockList($_POST);
        echo json_encode(['data'=>$data]);
    }

    public function doExportTemplate() {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=平台SKU库存设置模板-" . date('Ymd') . ".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');

        $item = array('记录ID','OMS商品编码','系统仓库ID','平台SKUID','平台商品ID','平台商品名称','库存','冻结库存','可用库存','平台独立库存');
        echo "\xEF\xBB\xBF".'"'.implode('","', $item) . '"';
        $stockData = kernel::single('inventorydepth_shop_skustockset')->getShopSkuStockList($_POST);
        foreach ($stockData as $v) {
            $item = array(
                $v['id'],
                $v['shop_product_bn']."\t",
                $v['branch_bn']."\t",
                $v['shop_sku_id']."\t",
                $v['shop_iid']."\t",
                $v['shop_title']."\t",
                $v['store'],
                $v['store_freeze'],
                $v['valid_stock'],
            );
            echo "\n";
            echo '"' . implode('","', $item) . '"';
        }
    }
}