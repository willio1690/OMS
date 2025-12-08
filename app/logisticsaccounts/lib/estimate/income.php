<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsaccounts_estimate_delivery extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $type_options = array(
        'display' => 'true',
    );


    public $graph_options = array(
        'hidden' => true,
    );


    /**
     * ext_detail
     * @param mixed $detail detail
     * @return mixed 返回值
     */
    public function ext_detail(&$detail){
        $filter = $this->_params;


    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        return array(
            'model' => 'logisticsaccounts_mdl_estimate',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('logisticsaccounts')->_('获取数据'),

                        'href'=>'index.php?app=logisticsaccounts&ctl=admin_estimate&act=import'),
                ),
                'title'=>app::get('logisticsaccounts')->_('预估单'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_view_tab'=>true,
            ),
        );
    }
}