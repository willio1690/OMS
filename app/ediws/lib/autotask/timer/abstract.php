<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class ediws_autotask_timer_abstract
{
    /**
     * Obj对象
     */
    protected $_mdl = null;
    protected $_operLogMdl = null;
    
    /**
     * Lib对象
     */
    protected $_apiLib = null;
    
    /**
     * 拉取数据分页大小
     */
    static public $request_page_size = 50;
    
    /**
     * 生成数据分页大小
     */
    static public $create_page_size = 50;
    
    /**
     * 文件生成记录行数
     */
    static public $file_page_size = 5000;
    
    /**
     * XML文件生成记录行数
     */
    static public $xml_page_size = 1000;
    
    //construct
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        //Obj
       
    }
    
    /**
     * succ
     * @param mixed $msg msg
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function succ($msg='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'data'=>$data);
    }
    
    /**
     * error
     * @param mixed $error_msg error_msg
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function error($error_msg, $data=null)
    {
        return array('rsp'=>'fail', 'msg'=>$error_msg, 'error_msg'=>$error_msg, 'data'=>$data);
    }
    
    /**
     * 获取请求时间戳
     * 
     * @param array $params
     * @return array
     */
    public function get_time_range($params)
    {
        //今天零点的日期
        $ent_day = date('Y-m-d 00:00:00', time());
        
        //默认时间
        if($params['time_model'] == 'yesterday'){
            //昨天的零点日期
            $yesterday_time = strtotime('-1 day');
            $start_day = date('Y-m-d 00:00:00', $yesterday_time);
        }elseif($params['time_model'] == 'current_month'){
            //本月一号的日期
            $current_month = date('Y-m', time()) .'-01 00:00:00';
            $month_time = strtotime($current_month);
            $start_day = date('Y-m-d 00:00:00', $month_time);
        }elseif($params['time_model'] == 'halfhour'){
//            //30分钟之前的日期
//            $hour_time = strtotime('-30 minutes');
//            $start_day = date('Y-m-d H:i:00', $hour_time);
//            $ent_day = date('Y-m-d H:i:00', time());
            
            //前一小时到现在的日期
            $hour_time = strtotime('-1 hour');
            $start_day = date('Y-m-d H:00:00', $hour_time);
            $ent_day = date('Y-m-d H:i:00', time() - 60);
        }else{
            //前一小时的日期
            $hour_time = strtotime('-1 hour');
            $start_day = date('Y-m-d H:00:00', $hour_time);
            $ent_day = date('Y-m-d H:00:00', time());
        }
        
        //时间戳范围
        $start_time = $params['start_time'] ? $params['start_time'] : strtotime($start_day);
        $end_time = $params['end_time'] ? $params['end_time'] : strtotime($ent_day);
        
        return array($start_time, $end_time);
    }

    /**
     * 获取JdlwmiShop
     * @return mixed 返回结果
     */
    public function getJdlwmiShop(){

        $shopObj    = app::get('ome')->model('shop');
        $shopList   = $shopObj->getList('shop_id, shop_bn, name,config', array('node_type'=>'360buy', 'business_type'=>'jdlvmi'));

        if(empty($shopList)){
            return false;
        }

        foreach($shopList as $k=>$v){
            $config = $v['config'] ? unserialize($v['config']) : '';
            $shopList[$k]['config'] = $config;
            if($config['ediwssync'] != 'sync'){
               // unset($shopList[$k]);
            }

        }

        return $shopList;
    }
}