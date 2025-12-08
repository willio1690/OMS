<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_autotask_timer_fixaccountorders extends ediws_autotask_timer_abstract
{
    
     /* 执行的间隔时间 */
    const intervalTime = 3600;
    /* 当前的执行时间 */
    public static $now;
    
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->_mdl = app::get('ediws')->model('account_orders');
        
        self::$now = time();
    }
    
    /**
     * 执行任务
     * 
     * @param array $taskInfo 同步任务配置信息
     * @param string $error_msg
     * @return bool
     */
    public function process($params=array(), &$error_msg='')
    {
        @set_time_limit(0);
        @ini_set('memory_limit','512M');
        ignore_user_abort(1);
        
        $nowtime = strtotime('-1 days');
        $start_time = date('Y-m-d 23:00:00',$nowtime);
        $start_time = strtotime($start_time);
        $end_time = date('Y-m-d 1:00:00');
        $end_time = strtotime($end_time);
         //供应商编码列表
        $shopList = $this->getJdlwmiShop();

        if(empty($shopList)){
            $error_msg = '未配置供应商编码';
            return false;
        }
        
        foreach($shopList as $shop){

            $config = $shop['config'];

            if($config['account_orders']=='sync'){
                $sdf = array(
                
                    'start_time'=>  $start_time,
                    'end_time'  =>  $end_time,
                    'shop_bn'   =>  $shop['shop_bn'],
                );

               
                kernel::single('ediws_autotask_timer_accountorders')->getPullList($sdf, $shop['shop_id']);
               
            }
            
        }
        
   
        return true;
    }


}
