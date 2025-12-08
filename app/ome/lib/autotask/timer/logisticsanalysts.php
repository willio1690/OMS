<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 仓储物流配送统计定时脚本
 *
 * @author sunjing@shopex.cn
 * @version 0.1
 */
class ome_autotask_timer_logisticsanalysts
{
	
	public function __construct(){
        $this->db = kernel::database();
    }

	public function process(){

		if('false' == app::get('ome')->getConf('ome.delivery.hqepay')) return;
        $logistics_last_run_time = app::get("ome")->getConf("logistics_last_run_time");

       	$time = time();
       
		if(($time-$logistics_last_run_time)<(24*3600-60)) return false;//执行时间间隔小于一天跳过
		
        $this->exec_trace();
        app::get("ome")->setConf('logistics_last_run_time',$time);
	}

	public function exec_trace(){
		
		$typeObj = app::get('omeanalysts')->model('ome_type');
		$traceObj = app::get('omeanalysts')->model('logistics_analysts');
		$dlyCorpObj = app::get('ome')->model('dly_corp');
        $corp_list  = $dlyCorpObj->getList('corp_id',array('disabled'=>'false'));
		$branch_list = $typeObj->get_branch();

		$trace_date = strtotime('-1 day');
		$starttime = mktime(0,0,0,date('m',$trace_date),date('d',$trace_date),date('Y',$trace_date));
		$endtime = mktime(0,0,0,date('m'),date('d',$trace_date)+1,date('Y',$trace_date));
		foreach($branch_list as $branch){
			$branch_id = $branch['branch_id'];
			foreach($corp_list as $corp){

				$corp_id = $corp['corp_id'];
				$delivery_num = $this->get_delivery_num($branch_id,$corp_id,$starttime,$endtime);
				$embrace_num = $this->get_embrace_num($branch_id,$corp_id,$starttime,$endtime);
				$sign_num = $this->get_sign_num($branch_id,$corp_id,$starttime,$endtime);
				$problem_num = $this->get_problem_num($branch_id,$corp_id,$starttime,$endtime);
				$timeout_num = $this->get_timeout_num($branch_id,$corp_id,$starttime,$endtime);
				
				$data = array(
					'logi_id'		=> 	$corp_id,
					'branch_id'		=>	$branch_id,
					'delivery_num'	=>	$delivery_num,
					'embrace_num'	=>	$embrace_num,
					'sign_num'		=>	$sign_num,
					'problem_num'	=>	$problem_num,
					'timeout_num'	=>	$timeout_num,
					'trace_date'	=>	$trace_date,

				);


				$sql = ome_func::get_insert_sql($traceObj,$data);

        		$this->db->exec($sql);
			}
		}
		
	}

	/**
     * @ 获取当天发货数量
     * @access public
     * @param void
     * @return void
     */

	function get_delivery_num($branch_id,$corp_id,$starttime,$endtime){
		$sql = 'SELECT count(delivery_id) as delivery_num FROM sdb_ome_delivery WHERE branch_id='.$branch_id.' AND logi_id='.$corp_id.' AND parent_id=0 AND `status`=\'succ\' AND delivery_time>'.$starttime.' AND delivery_time<'.$endtime;

		$deliverys = $this->db->selectrow($sql);
		return $deliverys['delivery_num'] ? $deliverys['delivery_num'] : 0;
	}

	/**
     * @ 获取当天揽收数量
     * @access public
     * @param void
     * @return void
     */
	function get_embrace_num($branch_id,$corp_id,$starttime,$endtime){
		$sql = 'SELECT count(delivery_id) as delivery_num FROM sdb_ome_delivery WHERE branch_id='.$branch_id.' AND logi_id='.$corp_id.' AND parent_id=0 AND `status`=\'succ\' AND embrace_time>'.$starttime.' AND embrace_time<'.$endtime;

		$deliverys = $this->db->selectrow($sql);
		return $deliverys['delivery_num'] ? $deliverys['delivery_num'] : 0;
	}

	/**
     * @ 获取当天签收数量
     * @access public
     * @param void
     * @return void
     */

	function get_sign_num($branch_id,$corp_id,$starttime,$endtime){
		$sql = 'SELECT count(delivery_id) as delivery_num FROM sdb_ome_delivery WHERE branch_id='.$branch_id.' AND logi_id='.$corp_id.' AND parent_id=0 AND `status`=\'succ\' AND sign_time>'.$starttime.' AND sign_time<'.$endtime;

		$deliverys = $this->db->selectrow($sql);
		return $deliverys['delivery_num'] ? $deliverys['delivery_num'] : 0;
	}

	/**
     * @ 获取当天问题件数量
     * @access public
     * @param void
     * @return void
     */

	function get_problem_num($branch_id,$corp_id,$starttime,$endtime){
		$sql = 'SELECT count(delivery_id) as delivery_num FROM sdb_ome_delivery WHERE branch_id='.$branch_id.' AND logi_id='.$corp_id.' AND parent_id=0 AND `status`=\'succ\' AND logi_status=\'4\' AND problem_time>'.$starttime.' AND problem_time<'.$endtime;

		$deliverys = $this->db->selectrow($sql);
		return $deliverys['delivery_num'] ? $deliverys['delivery_num'] : 0;
	}

	/**
     * @ 获取配送超时数量
     * @access public
     * @param void
     * @return void
     */

	function get_timeout_num($branch_id,$corp_id,$starttime,$endtime){
		$branch_detail = kernel::single('ome_branch')->getBranchInfo($branch_id,'logistics_limit_time');
		$logistics_limit_time = $branch_detail['logistics_limit_time'] ? : 3;
		$sql = "SELECT count(delivery_id) as delivery_num FROM sdb_ome_delivery WHERE branch_id=".$branch_id." AND logi_id=".$corp_id." AND parent_id=0 AND `status`='succ' AND logi_status='0' AND DATEDIFF(NOW(),FROM_UNIXTIME(delivery_time,'%Y-%m-%d'))>".$logistics_limit_time;

		$deliverys = $this->db->selectrow($sql);

		return $deliverys['delivery_num'] ? $deliverys['delivery_num'] : 0;
	}

}


?>