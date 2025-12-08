<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_analysis_abstract extends eccommon_analysis_abstract {

    public $logs_options = array(
        0 => array(
            'name' => '不动销货号数量',
            'flag' => array(),
            'memo' => '',
            'icon' => 'money.gif',
        ),
        1 => array(
            'name' => '不动销商品总数',
            'flag' => array(),
            'memo' => '',
            'icon' => 'coins.gif',
        ),
        2 => array(
            'name' => '不动销总成本',
            'flag' => array(),
            'memo' => '',
            'icon' => 'coins.gif',
        ),
    );
    
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app){
        parent::__construct($app);
        $this->_extra_view = array('iostock' => 'analysis/extra_view.html');
        $this->_render = kernel::single('omeanalysts_ctl_analysis_productnsale');
    }

    /**
     * detail
     * @return mixed 返回值
     */
    public function detail(){
    	$analysisProductNsaleObj = app::get('ome')->model('analysis_productnsale');
    	//不动销货号数量
    	$sql = 'SELECT count(1) as total_count from '.$analysisProductNsaleObj->table_name(1). ' WHERE ' . $analysisProductNsaleObj->_filter($this->_params);
    	$count = $analysisProductNsaleObj->db->selectrow($sql);
    	$this->logs_options[0]['value'] = $count['total_count'];
    	//不动销商品总数
    	$sql = 'SELECT sum(balance_nums) as total_store from '.$analysisProductNsaleObj->table_name(1). ' WHERE ' . $analysisProductNsaleObj->_filter($this->_params);
    	$count = $analysisProductNsaleObj->db->selectrow($sql);
    	$this->logs_options[1]['value'] = $count['total_store'];
    	//不动销总成本
    	$sql = 'SELECT sum(inventory_cost) as total_inventory_cost from '.$analysisProductNsaleObj->table_name(1). ' WHERE ' . $analysisProductNsaleObj->_filter($this->_params);
    	$count = $analysisProductNsaleObj->db->selectrow($sql);
    	$this->logs_options[2]['value'] = $count['total_inventory_cost'];
        foreach($this->logs_options AS $target=>$option){
            $detail[$option['name']]['value'] = $option['value'];
            $detail[$option['name']]['memo'] = $option['memo'];
            $detail[$option['name']]['icon'] = $option['icon'];
            $detail[$option['name']]['br'] = $option['br'] == true ? true : false;
        }
        $this->_render->pagedata['detail'] = $detail;
    }
    
}