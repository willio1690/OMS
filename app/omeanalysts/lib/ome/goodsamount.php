<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_ome_goodsamount extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $report_type ='false';
    protected $_title = '商品销量统计';

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

    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type(){
        $lab = '店铺';
        $typeObj = $this->app->model('ome_type');
        $data = $typeObj->get_shop();
        $return = array(
            'lab' => $lab,
            'data' => $data,
        );
        return $return;
    }

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
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        $filter = $this->_params;
        if(isset($filter['report']) && $filter['report']=='month'){
            $filter['time_from'] = date("Y-m-d", $filter['time_from']);
            $filter['time_to'] = date("Y-m-d",$filter['time_to']);
        }
        $base_query_string = 'time_from='.$filter['time_from'].'&time_to='.$filter['time_to'];
        return array(
            'model' => 'omeanalysts_mdl_ome_goodsamount',
            'params' => array(
                'actions'=>array(
                     array(
                         'class' => 'export',
                         'label' => '生成报表',
                         'href'=>'index.php?app=omeanalysts&ctl=ome_goodsamount&act=index&action=export',
                         'target'=>'{width:400,height:170,title:\'生成报表\'}'
                     ),
                ),
                'title'=>app::get('omeanalysts')->_('商品销量统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_goodsamount&act=goods_amount&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'base_query_string'=>$base_query_string,
            ),
            );
    }

    /**
     * rank
     * @return mixed 返回值
     */
    public function rank() {
        $filter = $this->_params;
        $filter['time_from'] = isset($filter['time_from'])?$filter['time_from']:'';
        $filter['time_to'] = isset($filter['time_to'])?$filter['time_to']:'';

        $render = kernel::single('base_render');
       // print_r($filter['time_from']);exit;
        $render->pagedata['time_from'] = $filter['time_from'];
        $render->pagedata['time_to'] = $filter['time_to'];

        $html = $render->fetch('ome/goodsamount.html','omeanalysts');

        $this->_render->pagedata['rank_html'] = $html;
    }


}