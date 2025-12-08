<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_analysis_pick extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $report_type = 'true';
    public $type_options = array(
        'display' => 'false',
    );
    public $logs_options = array(
        '1' => array(
            'name' => '已完成单量',
            'flag' => array(),
            'memo' => '已完成单量',
            'icon' => 'money.gif',
        ),
        '2' => array(
            'name' => '已完成件数',
            'flag' => array(),
            'memo' => '已完成件数',
            'icon' => 'coins.gif',
        ),
    );
    public $graph_options = array(
        'hidden' => false,
        'iframe_height' => 180,
        'show' => 'bar',
        'callback' => 'tgkpi_mdl_analysis_pick',
    );

    public $detail_options = array(
        'hidden' => false,
        'force_ext' => true,
    );

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app){
        parent::__construct($app);
        $typeObj = app::get('omeanalysts')->model('ome_type');
        $flagList = $typeObj->get_shop();
        foreach($this->logs_options as $key=>$val){
            $this->logs_options[$key]['flag'][0] = '全部';
            foreach($flagList as $shop){
                $this->logs_options[$key]['flag'][$shop['relate_id']] = $shop['name'];
            }
        }
    }

    /**
     * finish_pick
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function finish_pick($delivery_id){
        $pickObj = app::get('tgkpi')->model('pick');
        $pickObj->finish_pick($delivery_id);
    }

    /**
     * begin_check
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function begin_check($delivery_id){
        $pickObj = app::get('tgkpi')->model('pick');
        if($delivery_id){
            //如果已有校验人并且不是当前操作员，则记录历史并更新校验人信息
            $pickInfo = $pickObj->dump(array('delivery_id'=>$delivery_id),'check_op_id');
            if($pickInfo['check_op_id']){
                $checkLogObj = app::get('tgkpi')->model('check_log');
                $memoObj = $this->app->model('check_memo');
                $opInfo = kernel::single('ome_func')->getDesktopUser();

                if($pickInfo['check_op_id'] != $opInfo['op_id']){
                    $data = array(
                        'delivery_id' => $delivery_id,
                        'old_op_id' => $pickInfo['check_op_id'],
                        'new_op_id' => $opInfo['op_id'],
                        'addtime' => time(),
                    );
                    $checkLogObj->save($data);

                    $data2 = array(
                        'delivery_id' => $delivery_id,
                        'check_op_id' => $opInfo['op_id'],
                        'check_op_name' => $opInfo['op_name'],
                        'reason_id' => 1,
                        'memo' => '校验未完成校验员变更',
                        'addtime' => time(),
                    );
                    $memoObj->save($data2);
                    $pickObj->begin_check($delivery_id);
                }
            }else{
                $pickObj->begin_check($delivery_id);
            }
        }
    }

    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type(){
        $lab = '工号';
        $oPick = $this->app->model('pick');
        $data = $oPick->get_picker();
        $return = array(
            'lab' => $lab,
            'data' => $data,
        );
        return $return;
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
     * ext_detail
     * @param mixed $detail detail
     * @return mixed 返回值
     */
    public function ext_detail(&$detail){
        $filter = $this->_params;
        $filter['time_from'] = sprintf('%s 00:00:00',$filter['time_from']);
        $filter['time_to'] = sprintf('%s 23:59:59',$filter['time_to']);

        foreach($this->logs_options AS $target=>$option){
            $detail[$option['name']]['value'] = 0;
            $detail[$option['name']]['memo'] = $option['memo'];
            $detail[$option['name']]['icon'] = $option['icon'];
        }

        $delivery_counts = $this->app->model('pick')->get_deliverys($filter);
        $pick_nums = $this->app->model('pick')->get_pick_nums($filter);

        $detail['已完成单量']['value'] = $delivery_counts ? $delivery_counts : 0;
        $detail['已完成件数']['value'] = $pick_nums ? $pick_nums : 0;
    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        return array(
            'model' => 'tgkpi_mdl_analysis_pick',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('tgkpi')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=tgkpi&ctl=admin_analysis&act=pick&action=export',
                        'target'=>'{width:400,height:170,title:\'生成报表\'}'),
                ),
                'title'=>'拣货绩效统计',
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