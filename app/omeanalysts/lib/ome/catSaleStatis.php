<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_ome_catSaleStatis extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $report_type ='false';
    protected $_title = '商品类目销售对比统计';

    public $detail_options = array(
      'hidden' => true,
      'force_ext' => false,
    );

    public $graph_options = array(
        'hidden' => true,
    );

    public $type_options = array(
        'display' => 'true',
    );

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');

        for($i=0;$i<=5;$i++){
            if ($i == 1) continue;
            $val = $i+1;
            $this->_render->pagedata['time_shortcut'][$i] = $val;
        }
    }

    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type(){
        $lab = '店铺';
        $funcObj = kernel::single('omeanalysts_func');
        $data = $funcObj->shop();
        $return = array(
            'lab' => $lab,
            'data' => $data,
        );
        return $return;
    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        $filter = $this->_params;
        $base_query_string = 'time_from='.$filter['time_from'].'&time_to='.$filter['time_to'];
        return array(
            'model' => 'omeanalysts_mdl_ome_catSaleStatis',
            'params' => array(
                'actions'=>array(
                    array(
                         'class' => 'export',
                         'label' => '导出',
                         'href'=>'index.php?app=omeanalysts&ctl=ome_catSaleStatis&act=index&action=export',
                         'target'=>'{width:400,height:170,title:\'生成报表\'}'
                     ),
                ),
                'title'=>app::get('omeanalysts')->_('商品类目销售对比统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_catSaleStatis&act=index&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_selectrow'=>false,
                'base_query_string'=>$base_query_string,
            ),
       );
    }

}