<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 商品处理抽象类
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
abstract class tbo2o_shop_service_common
{
    public $approve_status = array(
            array('filter'=>array('approve_status'=>'all'),'name'=>'全部','flag'=>'all'),
    );
    
    public $totalResults = 0;
    
    function __construct(&$app)
    {
        $this->app = $app;
    }
    
    /**
     * 获取上下架状态
     *
     * @return void
     * @author 
     **/
    public function get_approve_status($flag='', &$exist=false)
    {
        if (isset($this->approve_status[$flag]))
        {
            $exist = true;
            return $this->approve_status[$flag];
        }
        
        return $this->approve_status;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function getTotalResults() 
    {
        return $this->totalResults;
    }

    /**
     * 下载全部商品(包含SKU)
     *
     * @return void
     * @author 
     **/
    public function downloadList($filter,$shop_id,$offset=0,$limit=200,&$errormsg)
    {
    }
}