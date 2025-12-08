<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_ctl_admin_analysis_pick extends desktop_controller{
    
    function index(){
        $this->finder(
	        'tgkpi_mdl_pick',
	        array(
		        'title'=>'拣货绩效',
				'base_filter'=>array('order_refer'=>'local','disabled'=>'false'),
				'use_buildin_export'=>true,
		        'use_buildin_set_tag'=>true,
		        'use_buildin_filter'=>true,
		        'use_buildin_tagedit'=>true,
	        	'allow_detail_popup'=>false,
				'use_view_tab'=>true,
                'orderBy'=>'pick_id DESC',
	        )
        );
    }

    /**
     * @description 图表显示员工当日捡货绩效
     * @access public
     * @param String $chart 图表类型
     * @return void
     */
    public function showCharts($chart='column') 
    {
        $this->pagedata['title'] = $this->app->_('当日员工拣货绩效');
        $this->pagedata['chart'] = $chart;

        $this->singlepage('admin/analysis/charts.html','tgkpi');
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function ajaxChartData() 
    {  
        $post = $_POST;
        if (!isset($post['start_time']) && !isset($post['end_time'])) {
            $post['start_time'] =  strtotime(date('Y-m-d'));
            $post['end_time'] = $post['start_time']+86400;
        }
        $chartData = $this->app->model('pick')->getChartData($post);
        echo json_encode($chartData);exit;
    }

    /**
     * @description 拣货查询页
     * @access public
     * @param void
     * @return void
     */
    public function spickIndex() 
    {
        $this->page('admin/analysis/spickIndex.html');
    }

    /**
     * @description 进度查询页
     * @access public
     * @param void
     * @return void
     */
    public function sscheduleIndex() 
    {
        $this->page('admin/analysis/sscheduleIndex.html');
    }

    /**
     * @description 拣货查询
     * @access public
     * @param void
     * @return void
     */
    public function spick() 
    {
        $post = $_POST;
        if (empty($post['logi_no'])) {
            $result = array(
                'status' => 'fail',
                'data' =>'',
                'msg' => $this->app->_('快递号不能为空!'),
            );
            echo json_encode($result);exit;
        }

        // 通过快递单号查发货单号
        $deliveryModel = app::get('ome')->model('delivery');
        $delivery_id = $deliveryModel->select()->columns('delivery_id')
                                                  ->where('logi_no=?',$post['logi_no'])
                                                  ->where('parent_id=0')->instance()->fetch_one();
        if (!$delivery_id) {
            $result = array(
                'status' => 'fail',
                'data' => array('logi_no'=>$post['logi_no']),
                'msg' => $this->app->_("快递单不存在!"),
            );
            echo json_encode($result);exit;
        }

        // 通过发货单号查姓名
        $pickModel = app::get('tgkpi')->model('pick');
        $pickOwner = $pickModel->select()->distinct()->columns('pick_owner')
                                            ->where('delivery_id=?',$delivery_id)->instance()->fetch_one();
        $userModel = app::get('desktop')->model('users');
        $username = $userModel->select()->columns('name')
                                            ->where('op_no=?',$pickOwner)->instance()->fetch_one();
        if (!$username) {
            $result = array(
                'status' => 'fail',
                'data' => array('logi_no'=>$post['logi_no']),
                'msg' => $this->app->_('拣货员不存在!'),
            );
            echo json_encode($result);exit;
        }

        $result = array(
            'status' => 'succ',
            'data' => array('logi_no'=>$post['logi_no'],'username'=>$username),
            'msg' => '',
        );

        echo json_encode($result);exit;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function sschedule() 
    {
        $post = $_POST;
        if (empty($post['logi_no'])) {
            $result = array(
                'status' => 'fail',
                'data' =>'',
                'msg' => $this->app->_('快递号不能为空!'),
            );
            echo json_encode($result);exit;
        }

        // 通过快递单号查发货单号
        $deliveryModel = app::get('ome')->model('delivery');
        $delivery_id = $deliveryModel->select()->columns('delivery_id')
                                                  ->where('logi_no=?',$post['logi_no'])
                                                  ->where('parent_id=0')->instance()->fetch_one();
        if (!$delivery_id) {
            $result = array(
                'status' => 'fail',
                'data' => array('logi_no'=>$post['logi_no']),
                'msg' => $this->app->_("快递单不存在!"),
            );
            echo json_encode($result);exit;
        }

        // 发货日志
        $opModel  = app::get('ome')->model('operation_log');
        $deliveryLog = $opModel->read_log(array('obj_id'=>$delivery_id,'obj_type'=>'delivery@ome'), 0, -1);
        foreach($deliveryLog as $k=>$v){
            $deliveryLog[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }

        if (!$deliveryLog) {
            $result = array(
                'status' =>'fail',
                'data' => array('logi_no'=>$post['logi_no']),
                'msg' => $this->app->_('无发货操作日志!'),
            );
            echo json_encode($result);exit;
        }else{
            $result = array(
                'status' => 'succ',    
                'data' => $deliveryLog,    
                'msg' => '',
            );
            echo json_encode($result);exit;
        }
        
    }

}