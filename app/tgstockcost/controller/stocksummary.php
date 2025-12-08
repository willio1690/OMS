<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class tgstockcost_ctl_stocksummary extends desktop_controller
{
    function __consruct($app)
    {
        $this->app = $app;
        parent::__construct($app);
    }
    
    function index()
    {
        $_POST['date_check'] = true;
        
        kernel::single('tgstockcost_stocksummary')->set_params($_POST)->display();
    }
    
    //检测查询时间是否跨成本法
    function checkedDate(){
        $obj_operation = app::get('tgstockcost')->model('operation');
        
        //检测查询时间是否合法
        $rs = $obj_operation->checkedDate($_POST['date_from'],$_POST['date_to']);
        echo json_encode($rs);
    }

    /**
     * 进销存CTL
     *
     * @return void
     * @author 
     **/
    public function sellstorage()
    {
        kernel::single('tgstockcost_stocksummary')->set_params($_POST)->display();
    }

}