<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @describe: 预发票订单
 * ============================
 */
class invoice_finder_order_front {
    public $addon_cols = "";
    /* public $column_edit = "操作";
    public $column_edit_width = 120;
    public $column_edit_order = 1;
    public function column_edit($row){
        $btn = [];
        $btn[] = '<a class="lnk" target="_blank" href="index.php?app=invoice&ctl=admin_order_front&act=downloadRow&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'&finder_vid='.$_GET['finder_vid'].'">下载</a>';
        $btn[] = '<a class="lnk" href="javascript:if(confirm(\'确定标记上传该单据?\')) {W.page(\'index.php?app=invoice&ctl=admin_order_front&act=flag&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'&finder_vid='.$_GET['finder_vid'].'\');};">
                标记</a>';
        $btn[] = '<a class="lnk" target="_blank" href="index.php?app=desktop&act=alertpages&goto='.urlencode('index.php?app=invoice&ctl=admin_order_front&act=item&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id']).'">查看</a>';
        return implode('|', $btn);
    } */

    public $detail_item = "详情";
    public function detail_item($id){
        $render = app::get('invoice')->render();
        $itemsObj = app::get('invoice')->model('order_front_items');
        $items = $itemsObj->getList('*', array('of_id'=>$id), 0, -1);
        $render->pagedata['items'] = $items;
        return $render->fetch("admin/order/front/item.html");
    }

    /* public $detail_oplog = "操作记录";
    public function detail_oplog($id){
        $render = app::get('console')->render();
        $opObj  = app::get('ome')->model('operation_log');
        $logdata = $opObj->read_log(array('obj_id'=>id,'obj_type'=>'order_front@invoice'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['log'] = $logdata;
        return $render->fetch('admin/oplog.html');
    } */
}