<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_ctl_admin_analysis_check extends desktop_controller{

    /**
     * @description 图表显示员工当日捡货绩效
     * @access public
     * @param String $chart 图表类型
     * @return void
     */
    public function showCharts($chart='column')
    {
        $this->pagedata['title'] = '当日员工校验绩效';
        $this->pagedata['chart'] = $chart;

        $this->singlepage('admin/analysis/checkCharts.html','tgkpi');
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
        $chartData = $this->app->model('pick')->getCheckChartData($post);
        echo json_encode($chartData);exit;
    }

}