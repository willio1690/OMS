<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_finder_series_endorse_products
{

    public $addon_cols = "";

    public $column_edit       = '操作';
    public $column_edit_width = 90;
    public $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row)
    {
        $btn_arr = [
            '<a href="index.php?app=dealer&ctl=admin_series_endorse_products&act=batchSetShopyjdfType&view=' . $_GET['view'] . '&p[0]=' . $row['sep_id'] . '&finder_id=' . $_GET['_finder']['finder_id'] . '" target="dialog::{width:690,height:310,title:\'设置发货方式\'}">设置发货方式</a>',
        ];
        return implode("&nbsp;", $btn_arr);
    }

    /**
     * 订单操作记录
     * @param int $sep_id
     * @return string
     */
    public $detail_show_log = '操作记录';
    /**
     * detail_show_log
     * @param mixed $sep_id ID
     * @return mixed 返回值
     */
    public function detail_show_log($sep_id)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $omeLogMdl = app::get('ome')->model('operation_log');
        $logList   = $omeLogMdl->read_log(array('obj_id' => $sep_id, 'obj_type' => 'series_endorse_products@dealer'), 0, -1);
        foreach ($logList as $k => $v) {
            $logList[$k]['operate_time'] = date('Y-m-d H:i:s', $v['operate_time']);

            if ($v['operation'] == '设置发货方式') {
                $logList[$k]['memo'] .= " <a href='index.php?app=dealer&ctl=admin_series_endorse_products&act=show_history&p[0]={$v['log_id']}&finder_id={$finder_id}' onclick=\"window.open(this.href, '_blank', 'width=690,height=240'); return false;\">查看快照</a>";
            }
        }
        $render                   = app::get('dealer')->render();
        $render->pagedata['logs'] = $logList;
        return $render->fetch('admin/bbb_show_log.html');
    }

    
    public $column_actual_stock = '可售库存数';
    public $column_actual_stock_order = 89;
    private $initMaterialSale = false;
    /**
     * column_actual_stock
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_actual_stock($row, $list)
    {
        if(!$this->initMaterialSale) {
            $this->initMaterialSale = true;
            kernel::single('inventorydepth_calculation_basicmaterial')->initFromBasic(array_unique(array_column($list, 'bm_id')));
        }
        list($actual_stock, $asRs) = kernel::single('inventorydepth_calculation_basicmaterial')->get_actual_stock(
            $row['bm_id'], 
            $row['shop_bn'], 
            $row['shop_id']);
        return $actual_stock;
    }
}
