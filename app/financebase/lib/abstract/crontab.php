<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class financebase_abstract_crontab{
	public $_interval_time = 1800; // 间隔时间
	public $_is_enable = true; // 是否启用
	abstract public function process();// 执行方式

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
	{
		$this->oQueue = app::get('financebase')->model('queue');
		$this->financeObj = base_kvstore::instance('setting/financebase');
		$this->now_time = time();
		
	}

    /**
     * 获取Time
     * @return mixed 返回结果
     */
    public function getTime()
	{
		$this->financeObj->fetch($this->_time_key,$run_time);
		return $run_time?$run_time:0;
	}

    /**
     * 设置Time
     * @return mixed 返回操作结果
     */
    public function setTime()
	{
		$next_run_time = time() + $this->_interval_time;
		$this->financeObj->store($this->_time_key,$next_run_time);
		return true;
	}
}