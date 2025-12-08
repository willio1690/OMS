<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class o2o_autotask_task_statistic
{
    //仓库ID
    private $_branch_id    = '';
    
    private $_wapDlyObj    = null;
    
    //条件
    private $_where        = '';
    
    //缓存数据
    public $_data          = array();
    
    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($params, &$error_msg=''){
        set_time_limit(0);
        ignore_user_abort(1);
        
        if(empty($params['branch_id']) || empty($params['type']))
        {
            return false;
        }
        
        $this->_wapDlyObj   = app::get('wap')->model('delivery');
        $this->branch_id    = $params['branch_id'];
        $this->_where       = "WHERE branch_id=". $this->branch_id;
        
        //读取缓存数据
        $this->_data    = $this->fetchDataFromCache();
        if(empty($this->_data))
        {
            $params['type']    = 'all';
        }
        
        switch($params['type'])
        {
            //拒单
            case 'refuse':
                $this->confirm();
                $this->overtimeOrder();
            break;
            //确单
            case 'confirm':
                $this->confirm();
                $this->consign();
            break;
            //发货
            case 'consign':
                $this->consign();
                $this->sign();
                $this->overtimeOrder();
            break;
            //销单
            case 'sign':
                $this->sign();
            break;
            //今日订单总数
            case 'today':
                $this->todayOrder();
            break;
            //统计所有
            case 'all':
                $this->todayOrder();
                $this->confirm();
                $this->consign();
                $this->sign();
                $this->overtimeOrder();
            break;
        }
        
        //缓存数据
        $this->saveDataToCache($this->_data);
        
        return true;
    }
    
    /**
     * 今日订单总数(包括取消和拒绝的订单)
     */
    function todayOrder()
    {
        $today  = strtotime(date('Y-m-d', time()) .'00:00');
        $sql    = "SELECT count(*) AS num FROM sdb_wap_delivery ". $this->_where ." AND create_time>=". $today;//AND status in(0,3) AND confirm in(1,3)
        $row    = $this->_wapDlyObj->db->selectrow($sql);
        
        $this->_data['today_orders']    = $row['num'];
        
        //缓存今日订单数据缓存标识(每15分钟更新一次)
        cachecore::store('wap_today_order_'. $this->branch_id, '1', 900);
    }
    
    /**
     * 待确认
     */
    function confirm()
    {
        $sql    = "SELECT count(*) AS num FROM sdb_wap_delivery ". $this->_where ." AND status=0 AND confirm=3";
        $row    = $this->_wapDlyObj->db->selectrow($sql);
        
        $this->_data['unConfirm']    = $row['num'];
    }
    
    /**
     * 待发货
     */
    function consign()
    {
        $sql    = "SELECT count(*) AS num FROM sdb_wap_delivery ". $this->_where ." AND status=0 AND confirm=1";
        $row    = $this->_wapDlyObj->db->selectrow($sql);
        
        $this->_data['unConsign']    = $row['num'];
    }
    
    /**
     * 待核销
     */
    function sign()
    {
        $sql    = "SELECT count(*) AS num FROM sdb_wap_delivery ". $this->_where ." AND status=3 AND confirm=1 AND process_status=7 AND is_received=1";
        $row    = $this->_wapDlyObj->db->selectrow($sql);
        
        $this->_data['unSign']    = $row['num'];
    }
    
    /**
     * 超时订单
     */
    function overtimeOrder()
    {
        //履约超时时间设置(分钟)
        $minute    = app::get('o2o')->getConf('o2o.delivery.dly_overtime');
        $minute    = intval($minute);
        
        //超时订单
        $count_data['count_overtime']    = 0;
        if($minute)
        {
            $second          = $minute * 60;
            $dly_overtime    = time() - $second;//现在时间 减去 履约时间
            
            //过滤已发货&&已拒绝的发货单
            $sql    = "SELECT count(*) AS num FROM sdb_wap_delivery ". $this->_where ." AND status=0 AND confirm!=2 AND create_time<". $dly_overtime;
            $row    = $this->_wapDlyObj->db->selectrow($sql);
            
            $this->_data['count_overtime']    = $row['num'];
        }
    }
    
    /**
     * 读取缓存数据
     */
    function fetchDataFromCache()
    {
        return cachecore::fetch('wap_statistic_'. $this->branch_id);
    }
    
    /**
     * 缓存数据(默认缓存30分钟)
     */
    function saveDataToCache($data)
    {
        cachecore::store('wap_statistic_'. $this->branch_id, $data, 1800);
    }
}