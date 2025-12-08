<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_analysis_reason extends eccommon_analysis_abstract implements eccommon_analysis_interface{

    public $type_options = array(
        'display' => 'false',
    );

    public $logs_options = array(
        '1' => array(
            'name' => '全部',
            'flag' => array(),
            'memo' => '全部',
            'icon' => 'money.gif',
        ),
    );


    public $graph_options = array(
        'hidden' => false,
        'iframe_height' => 180,
        'show' => 'bar',
        'callback' => 'tgkpi_mdl_analysis_reason',
    );


    public $detail_options = array(
        'hidden' => true,
        'force_ext' => true,
    );

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app){
        $reasonObj = app::get('tgkpi')->model('reason');
        $reasonList = $reasonObj->getList('*',null,0,-1);
        foreach($reasonList as $key=>$val){
            $this->logs_options[$val['reason_id']+1]['name'] = $val['reason_memo'];
            $this->logs_options[$val['reason_id']+1]['memo'] = $val['reason_memo'];
        }
        parent::__construct($app);
    }

    /**
     * 获取_logs
     * @param mixed $time time
     * @return mixed 返回结果
     */
    public function get_logs($time){
		$filter = array(
            'time_from' => date('Y-m-d',$time),
            'time_to' => date('Y-m-d',$time),
        );
        $storeObj = $this->app->model('ome_store');
		$outstock = $storeObj->get_outstock($filter);
		$store = $storeObj->get_store($filter);
		$store_value = $storeObj->get_value($filter);

		$result[] = array('type'=>0, 'target'=>1, 'flag'=>0, 'value'=>$outstock);
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>0, 'value'=>$store['store']);
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>1, 'value'=>$store['store_freeze']);
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>2, 'value'=>$store['arrive_store']);
        $result[] = array('type'=>0, 'target'=>3, 'flag'=>0, 'value'=>$store_value);

        return $result;
	}

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        return array(
            'model' => 'tgkpi_mdl_analysis_reason',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('tgkpi')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=tgkpi&ctl=admin_analysis&act=reason&action=export',
                        'target'=>'{width:400,height:170,title:\'生成报表\'}'),
                ),
                'title'=>'校验失败原因统计',
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
            ),
        );
    }

    /**
     * @description 参数设置
     * @access public
     * @param Array $params
     * @return Object $this
     */
    public function set_params($params)
    {
        $this->_params = $this->dealTime($params);
        return $this;
    }

    /**
     * @description 时间处理
     * @access public
     * @param void
     * @return void
     */
    public function dealTime($params)
    {
        if ($params['report']=='month') {     // 按月表
            // 默认为本月
            $now = time();
            $time_from = date('Y-m-01',$now);
            if ($params['time_from']) {
                $time_from = date('Y-m-01',strtotime($params['time_from']));
            }

            $time_to = date('Y-m-t',strtotime($time_from));
            if ($params['time_to']) {
                $time_to = date('Y-m-t',strtotime($params['time_to']));
            }

            $params['time_from'] = $time_from;
            $params['time_to'] = $time_to;
            $params['order_status'] = $this->analysis_config['filter']['order_status'];
        }else{                                           // 按日报
            if(isset($this->analysis_config)){
                $time_from = date("Y-m-d", time()-(date('w')?date('w')-$this->analysis_config['setting']['week']:7-$this->analysis_config['setting']['week'])*86400);
            }else{
                $time_from = date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400);
            }
            $time_to = date("Y-m-d", strtotime($time_from)+86400*7-1);

            $params['time_from'] = ($params['time_from']) ? $params['time_from'] : $time_from;
            $params['time_to'] = ($params['time_to']) ? $params['time_to'] : $time_to;
            $params['order_status'] = $this->analysis_config['filter']['order_status'];
        }

        return $params;
    }
}