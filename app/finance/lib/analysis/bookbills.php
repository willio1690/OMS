<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_analysis_bookbills extends finance_analysis_abstract implements eccommon_analysis_interface{
	
	public $detail_options = array(
        'hidden' => false,
    );
    public $graph_options = array(
        'hidden' => true,
    );
    public $type_options = array(
        'display' => 'true',
    );

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        $filter = array();
        foreach ($this->_params as $key => $value) {
            $filter['filter'][$key] = $value;
        }

        return array(
            'model' => 'finance_mdl_analysis_book_bills',
            'params' => array(
                'actions'=>array(
                     array(
                        'label'  => '导出',
                        'href'   => 'index.php?app=finance&ctl=analysis_bills&act=index&view=1&action=export',
                        'target' => '{width:600,height:300,title:\'导出\'}',
                        //'id'     => 'export_id',
                        'class'=>'export',
                     ),
                    array(
                        'label' => '同步费用',
                        'href' => 'index.php?app=finance&ctl=analysis_bills&act=sync_bills_book_get&flt=sale&finder_id='.$_GET['finder_id'],
                        'target' => "dialog::{width:550,height:150,title:'同步费用'}",
                    ),
                ),
                'title'                 => '固定费用<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=finance&ctl=analysis_bills&act=index&view=1&action=export\');}</script>',
                'use_buildin_recycle'   => false,
                'use_buildin_selectrow' => false,
                'use_buildin_filter'    => false,
            ),
        );
    }
}