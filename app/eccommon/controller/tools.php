<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class eccommon_ctl_tools extends desktop_controller{

    function __construct($app) {
        parent::__construct($app);
		header("cache-control: no-store, no-cache, must-revalidate");
        $this->app = $app;
    }

    //该方法desktop框架js会调用掉
    function selRegion(){
        $path = $_GET['path'];
        $depth = $_GET['depth'];

        $local = kernel::single('eccommon_regions_select');
        $filter_arr = array('depth'=>$depth);
        if($_GET['effect']){
            $filter_arr['effect'] = $_GET['effect'];
        }
        $ret = $local->get_area_select($path,$filter_arr);
        
        if($ret){
            echo '&nbsp;-&nbsp;'.$ret;exit;
        }else{
            echo '';exit;
        }
    }
}