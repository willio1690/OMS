<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_productnsale extends omeanalysts_analysis_abstract implements eccommon_analysis_interface{

    public $type_options = array(
            'display' => 'true',
    );
	
    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){

        $_GET['filter']['from'] = array(
            'material_type'=>$_POST['material_type'],
            'material_bn'=>$_POST['material_bn'],
            'material_name'=>$_POST['material_name'],
            'barcode'=>$_POST['barcode'],
            'nsale_days_from'=>$_POST['nsale_days_from'],
            'nsale_days_to'=>$_POST['nsale_days_to'],
        );

        return array(
            'model' => 'ome_mdl_analysis_productnsale',
            'params' => array(
                'actions'=>array(
                     array(
                        'label'  => '不动销天数配置',
                        'href'   => 'index.php?app=omeanalysts&ctl=analysis_productnsale&act=set_nsale_day',
                        'target' => 'dialog::{width:260,height:60,title:\'不动销天数配置\'}',
                     ),
                     array(
                        'label'  => '生成报表',
                        'href'   => 'index.php?app=omeanalysts&ctl=analysis_productnsale&act=index&action=export',
                        'target' => '{width:600,height:300,title:\'生成报表\'}',
                        'class'=>'export',
                     ),
                ),
                'title'                 => '不动销商品<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=analysis_productnsale&act=index&action=export\');}</script>',
                'use_buildin_recycle'   => false,
                'use_buildin_selectrow' => false,
                'use_buildin_filter'    => false,
                'title_help_message'    => "检查物料的最后的销售出库、赠品出库、样品出库日期，通过预配置的不动销天数进行不动销统计，符合该条件的物料明细信息集中在该报表展示",
            ),
        );
    }

    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type(){

        $return = array(
                'material_type'=>array(
                        'lab' => '物料属性',
                        'data' => array("全部","成品","半成品"),
                        'type' => 'select',
                ),
                'material_bn'=>array(
                        'lab' => '物料编码',
                        'type' => 'text',
                ),
                'material_name'=>array(
                        'lab' => '物料名称',
                        'type' => 'text',
                ),
                'barcode'=>array(
                        'lab' => '条形码',
                        'type' => 'text',
                ),
                'nsale_days'=>array(
                        'lab' => '不动销天数范围',
                        'type' => 'range_text',
                        'from' => 'nsale_days_from',
                        'to' => 'nsale_days_to',
                ),
        );
        return $return;
    }

    /**
     * 设置_params
     * @param mixed $params 参数
     * @return mixed 返回操作结果
     */
    public function set_params($params){
        $this->_params = $params;
        return $this;
    }
	
    /**
     * headers
     * @return mixed 返回值
     */
    public function headers(){
        $this->_render->pagedata["which_page_current"] = "analysis_nsale";
        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            
            //for range_text nsale_days
            $nsale_days_range_text = array(
                "from"=>$this->_params['nsale_days_from'],
                "to"=>$this->_params['nsale_days_to']	
            );

            $this->_render->pagedata['type_selected'] = array(
                    'material_bn'=>$this->_params['material_bn'],
                    'material_name'=>$this->_params['material_name'],
                    'material_type'=>$this->_params['material_type'],
                    'barcode'=>$this->_params['barcode'],
                    'nsale_days'=>$nsale_days_range_text,
            );
        }
    }
}