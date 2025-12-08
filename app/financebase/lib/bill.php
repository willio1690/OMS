<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_bill extends eccommon_analysis_abstract implements eccommon_analysis_interface{

	public $detail_options = array(
        'hidden' => false,
    );
    public $graph_options = array(
        'hidden' => true,
    );
    public $type_options = array(
        'display' => 'false',
    );

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');

   
        #店铺
        $shopdata = financebase_func::getShopList(financebase_func::getShopType());
        $this->_render->pagedata['shopdata']= $shopdata;
        $this->_render->pagedata['shop_id']= $_POST['shop_id'] ? $_POST['shop_id'] : '0';
        $this->_render->pagedata['fee_type']= isset($_POST['fee_type']) ? $_POST['fee_type'] : 'all';
        $this->_render->pagedata['status']= isset($_POST['status']) ? $_POST['status'] : 'all';

        $oBill = kernel::single('financebase_data_bill');
        $this->_render->pagedata['unmatch_orderbn_list'] = $oBill->getUnMatchCountByOrderBn();

        $this->_extra_view = array('financebase' => 'admin/bill.html');
    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){

        $actions = array();

        $actions[] = array(
                    'label' => '导出订单号未匹配',
                    'href' => 'index.php?app=financebase&ctl=admin_shop_settlement_bill&act=exportUnMatch&finder_id='.$_GET['finder_id'],
                    'target' => "dialog::{width:500,height:200,title:'导出订单号未匹配'}",
                );

        $actions[] = array(
                    'label' => '导入订单号未匹配',
                    'href' => 'index.php?app=financebase&ctl=admin_shop_settlement_bill&act=importUnMatch&finder_id='.$_GET['finder_id'],
                    'target' => "dialog::{width:500,height:200,title:'导入订单号未匹配'}",
                );

        $params =  array(
            'model' => 'financebase_mdl_bill',
            'params' => array(
                'actions'=>$actions,
                'title'=>'流水明细单<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{}</script>',
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
                'orderBy'=> 'id desc',

            ),
        );

        return $params;
    }

    /**
     * _views
     * @return mixed 返回值
     */
    public function _views(){
        $show_menu = array(
            0=>array('label'=>'全部','optional'=>'','filter'=>array('disabled'=>'false'),'href'=>'','addon'=>'_FILTER_POINT_','show'=>'true'),
            // 1=>array('label'=>'成功','optional'=>'','filter'=>array('status'=>'succ','disabled'=>'false'),'href'=>'','addon'=>'_FILTER_POINT_','show'=>'true'),
            1=>array('label'=>'失败','optional'=>'','filter'=>array('status'=>'error','disabled'=>'false'),'href'=>'','addon'=>'_FILTER_POINT_','show'=>'true'),
        );
        return $show_menu;
    }


}