<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_analysis_store_daliy_show extends eccommon_analysis_abstract implements eccommon_analysis_interface{

    public $report_type = 'true';

    public $type_options = array(
        'display' => 'true',
    );

    public $logs_options = array(
        '1' => array(
            'name' => '订单总数',
            'flag' => array(),
            'memo' => '订单总数',
            'icon' => 'money.gif',
            'col'  => '1',
        ),
        '2' => array(
            'name' => '审核单量',
            'flag' => array(),
            'memo' => '审核单量',
            'icon' => 'money.gif',
            'col'  => '2',
        ),
        '3' => array(
            'name' => '拒绝单量',
            'flag' => array(),
            'memo' => '拒绝单量',
            'icon' => 'money.gif',
            'col'  => '3',
        ),
        '4' => array(
            'name' => '发货单量',
            'flag' => array(),
            'memo' => '发货单量',
            'icon' => 'money.gif',
            'col'  => '4',
        ),
        '5' => array(
            'name' => '销售货品数',
            'flag' => array(),
            'memo' => '销售货品数',
            'icon' => 'money.gif',
            'col'  => '5',
        ),
        '6' => array(
            'name' => '核销签收单量',
            'flag' => array(),
            'memo' => '核销签收单量',
            'icon' => 'money.gif',
            'col'  => '6',
        ),
    );

    public $graph_options = array(
        'hidden' => true,
        'iframe_height' => 180,
        'callback' => 'omeanalysts_ome_graph_month',
    );

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app){
        parent::__construct($app);

        $storeObj = app::get('o2o')->model('store');
        $flagList = $storeObj->getList("store_bn,name", array(), 0, -1);
        foreach($this->logs_options as $key=>$val){
            $this->logs_options[$key]['flag'][0] = '全部';
            foreach($flagList as $shop){
                $this->logs_options[$key]['flag'][$shop['store_bn']] = $shop['name'];
            }
        }
    }

    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type(){
        $lab = '门店';
        $storeObj = app::get('o2o')->model('store');
        $tmp = $storeObj->getList("store_bn,name", array(), 0, -1);

        foreach($tmp as $k=>$val){
            $data[$k]['type_id'] = $val['store_bn'];
            $data[$k]['name'] = $val['name'];
        }

        $return = array(
            'type_id'=>array(
                'lab' => $lab,
                'data' => $data,
            ),
        );
        return $return;
    }


    /**
     * 获取_logs
     * @param mixed $time time
     * @return mixed 返回结果
     */
    public function get_logs($time){
        $filter = $this->_params;

        //所有店铺统计
        $filter = array(
            'time_from' => date('Y-m-d',$time),
            'time_to' => date('Y-m-d',$time),
        );

        $storeDaliyObj = app::get('o2o')->model('store_daliy');

        $all = $storeDaliyObj->getList('*', $filter, 0, -1);

        $result[] = array('type'=>0, 'target'=>1, 'flag'=>0, 'value'=>$all['order_sum']);
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>0, 'value'=>$all['confirm_num']);
        $result[] = array('type'=>0, 'target'=>3, 'flag'=>0, 'value'=>$all['refuse_num']);
        $result[] = array('type'=>0, 'target'=>4, 'flag'=>0, 'value'=>$all['send_num']);
        $result[] = array('type'=>0, 'target'=>5, 'flag'=>0, 'value'=>$all['sale_sum']);
        $result[] = array('type'=>0, 'target'=>6, 'flag'=>0, 'value'=>$all['verified_num']);
        
        //单个店铺统计
        $storeObj = app::get('o2o')->model('store');
        $flagList = $storeObj->getList("store_bn,name", array(), 0, -1);

        foreach($flagList as $key=>$val){
            $filter['store_bn'] = $val['store_bn'];
            $shop = $storeDaliyObj->getList('*', $filter, 0, -1);

            $result[] = array('type'=>0, 'target'=>1, 'flag'=>$val['store_bn'], 'value'=>$shop['order_sum']);
            $result[] = array('type'=>0, 'target'=>2, 'flag'=>$val['store_bn'], 'value'=>$shop['confirm_num']);
            $result[] = array('type'=>0, 'target'=>3, 'flag'=>$val['store_bn'], 'value'=>$shop['refuse_num']);
            $result[] = array('type'=>0, 'target'=>4, 'flag'=>$val['store_bn'], 'value'=>$shop['send_num']);
            $result[] = array('type'=>0, 'target'=>5, 'flag'=>$val['store_bn'], 'value'=>$shop['sale_sum']);
            $result[] = array('type'=>0, 'target'=>6, 'flag'=>$val['store_bn'], 'value'=>$shop['verified_num']);

        }

        return $result;
    }

    /**
     * ext_detail
     * @param mixed $detail detail
     * @return mixed 返回值
     */
    public function ext_detail(&$detail){

        $filter = $this->_params;
        $shops = app::get('o2o')->model('store_daliy')->getList('*', $filter, 0, -1);

        $order_sum = $confirm_num = $refuse_num = $send_num = 0;
        $sale_sum = $distribution_rate = $self_pick_rate = $verified_num = 0;

        foreach ($shops as $shop) {
            $order_sum        += $shop['order_sum'];
            $confirm_num          += $shop['confirm_num'];
            $refuse_num       += $shop['refuse_num'];
            $send_num   += $shop['send_num'];
            $sale_sum     += $shop['sale_sum'];
            //$distribution_rate  += $shop['distribution_rate'];
            //$self_pick_rate      += $shop['self_pick_rate'];
            $verified_num      += $shop['verified_num'];
        }

        $detail['订单总数']['value'] = $order_sum;
        $detail['审核单量']['value'] = $confirm_num;
        $detail['拒绝单量']['value'] = $refuse_num;
        $detail['发货单量']['value'] = $send_num;
        $detail['销售货品数']['value'] = $sale_sum;
        //$detail['配送占比']['value'] = $distribution_rate;
        //$detail['自提占比']['value'] = $self_pick_rate;
        $detail['核销签收单量']['value'] = $verified_num;
    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){

        $_extra_view = array(
            'o2o' => 'admin/analysis/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        $params =  array(
            'model' => 'o2o_mdl_store_daliy',
            'params' => array(
                'actions'=>array(
                    'export'=>array(
                        'label'=>app::get('o2o')->_('导出报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=o2o&ctl=admin_analysis&act=storedaliy&action=export',
                        'target'=>'{width:600,height:300,title:\'导出报表\'}'
                    ),
                ),
                'title'=>app::get('o2o')->_('门店每日汇总<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=o2o&ctl=admin_analysis&act=storedaliy&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
            ),
        );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($params['params']['actions']['export']);
        }
        return $params;
    }

    /**
     * detail
     * @return mixed 返回值
     */
    public function detail() 
    {
        if($this->detail_options['hidden'] == true){
            $this->_render->pagedata['detail_hidden'] = 1;
            return false;
        }
        $detail = array();

        foreach($this->logs_options AS $target=>$option){
            $detail[$option['name']]['value'] = ($tmp[$target]) ? $tmp[$target] : 0;
            $detail[$option['name']]['memo'] = $this->logs_options[$target]['memo'];
            $detail[$option['name']]['icon'] = $this->logs_options[$target]['icon'];
            $detail[$option['name']]['col'] = $target;
        }

        if(method_exists($this, 'ext_detail')){
            $this->ext_detail($detail);
        }
        foreach($detail AS $key=>$val){
            $name = $this->app->_($key);
            $data[$name]['value'] = $val['value'];
            $data[$name]['memo'] = $this->app->_($val['memo']);
            $data[$name]['icon'] = $val['icon'];
            $data[$name]['col'] = $val['col'];
        }
        $this->_render->pagedata['detail'] = $data;
        return true;
    }//End Function

    /**
     * headers
     * @return mixed 返回值
     */
    public function headers() 
    {
        parent::headers();
        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $type_selected = array(
                                'type_id'=>$this->_params['type_id']
                            );
            $this->_render->pagedata['type_selected'] = $type_selected['type_id'];
        }
    }//End Function

}