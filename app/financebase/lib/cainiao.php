<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_cainiao extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $detail_options = array(
        'hidden' => false,
        'force_ext' => true,
    );
    public $graph_options = array(
        'hidden' => true,
    );
    public $type_options = array(
        'display' => 'false',
    );

    /**
     * ext_detail
     * @param mixed $detail detail
     * @return mixed 返回值
     */
    public function ext_detail(&$detail){
        $params = $this->_params;
        $rows = app::get('financebase')->model('cainiao')->getTotal($params);
        $row = array();
        $detailItems = array();
        foreach ($rows as $v) {
            $detailItems[$v['bill_category']] = array(
                'name' => $v['bill_category'],
                'memo' => $v['bill_category'],
                'icon' => 'money.gif',
                'value' => 
                    '<span style="color:black;margin-right:10px">'.($v['total_money'] ? : 0).'</span>'.
                    '<span style="color:green;margin-right:10px">'.($v['confirm_money'] ? : 0).'</span>'.
                    '<span style="color:red;margin-right:10px">'.($v['unconfirm_money'] ? : 0).'</span>'
            );
            $row['total_money'] += $v['total_money'];
            $row['confirm_money'] += $v['confirm_money'];
            $row['unconfirm_money'] += $v['unconfirm_money'];
        }
        $this->_render->pagedata['detailTotal'] = array(
            '总费用' => array(
                'name' => '总费用',
                'memo' => '总费用',
                'icon' => 'money.gif',
                'value' => '<span style="color:black">'.($row['total_money'] ? : 0).'</span>',
            ),
            '已对账费用' => array(
                'name' => '已对账费用',
                'memo' => '和菜鸟已对账费用',
                'icon' => 'money.gif',
                'value' => '<span style="color:green">'.($row['confirm_money'] ? : 0).'</span>',
            ),
            '未对账费用' => array(
                'name' => '未对账费用',
                'memo' => '和菜鸟未对账费用',
                'icon' => 'money.gif',
                'value' => '<span style="color:red">'.($row['unconfirm_money'] ? : 0).'</span>',
            ),
        );
        $this->_render->pagedata['detailItems'] = $detailItems;
    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        $this->set_extra_view(array('financebase' => 'admin/cainiao/items.html'));
        $actions = array();
        $actions[] = array(
                    'label' => '导出对账单',
                    'class' => 'export',
                    'href' => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiaoitems&act=index&action=export',
                );
        $actions[] = array(
                    'label' => '重新对账',
                    'href' => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiaoitems&act=reConfirm',
                    'target' => "dialog::{width:500,height:200,title:'重新对账'}",
                );

        $params =  array(
            'model' => 'financebase_mdl_cainiao',
            'params' => array(
                'actions'=>$actions,
                'title'=>'支付宝实付明细<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{}</script>',
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_setcol'=>true,
                'base_filter' => array(),
                'orderBy'=> 'id desc',
            ),
        );

        return $params;
    }


}