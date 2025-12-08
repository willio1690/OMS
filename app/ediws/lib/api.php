<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_api
{
    
    
    /**
     * 获取应付账列表数据
     * 
     * @param array $params
     * @param int $lastId 上次分页查询的最后的id(第一次请求传0)
     * @return array
     */
    public function accountPayableList($params, $lastId)
    {
        $mdl = app::get('ediws')->model('account_payable');
        
        $payableLib = kernel::single('ediws_autotask_timer_accountpayable');
        $funcLib = kernel::single('ediws_func');
        
       
        
        //拉取模式(single：一次性简单的，loop：循环拉取)
        $pull_model = $params['pull_model'] ? $params['pull_model'] : 'single';
        
        $shop_id = $params['shop_id'];

        $shops = kernel::single('ediws_event_trigger_jdlvmi')->getShops($shop_id);
        //供应商编码
        $vendor_code = $shops['config']['ediwsuser'];
        
        //original_bn
        $original_bn = 'account_payable_' . date('md', time());
        
        //开始时间(年-月-日)
        if($params['start_day'] && $params['end_day']){
            $startTime = strtotime($params['start_day'].' 00:00:00');
            $endTime = strtotime($params['end_day'].' 00:00:00');
            
            $start_time = date('Y-m-d', $startTime).' 00:00:00';
            $end_time = date('Y-m-d', $endTime).' 23:59:59';
        }else{
            $start_time = date('Y-m-d', time()).' 00:00:00';
            $end_time = date('Y-m-d', time()).' 23:59:59';
        }
        
        //page size
        $page_size = $payableLib::$request_page_size;
        
        //params
        $request_params = array(
            'shop_id'    => $shop_id,   
            'vendorCode' => $vendor_code,
            'gmtCreateStart' => $start_time, //进入应付账开始时间
            'gmtCreateEnd' => $end_time, //进入应付账结束时间
            //'bizTimeStart' => $start_time, //单据日期开始时间
            //'bizTimeEnd' => $end_time, //单据日期结束时间
            'lastId' => $lastId,
            'page_size' => $page_size, //每页条数
            'method' => $url_method, //URL接口请求方法
            'original_bn' => $original_bn, //api同步日志单据号
            'pull_model' => $pull_model,
        );
        
        //setting
        $dataList = array();
        $page_no = 0;
        $response = array();
        
        //request
        //
        $result = kernel::single('erpapi_router_request')->set('ediws',$shop_id)->accountpayable_getlist($request_params);
        
        if($result['rsp'] != 'succ'){
            $result = array('rsp'=>'fail', 'error_msg'=>'请求失败：'. $result['msg']);
            return $result;
        }
        
        //获取的数据列表
        $dataList = $result['data']['data'];
        if(empty($dataList)){
            $result = array('rsp'=>'fail', 'error_msg'=>'没有获取到有效数据');
            return $result;
        }
        
        //数据总记录条数
        //@todo：接口无法知道总数,默认写乘以50倍,这样进度条会一直拉取直到返回空值才结束;
        $total_num = count($dataList) * 500;
        
        //本次拉取数据条数
        $current_num = count($dataList);
        
        //data
        $data = array(
            'rsp' => 'succ',
            'current_page' => $lastId, //当前页码
            'next_page' => 0, //下一页页码
            'page_size' => $page_size, //每次拉取数量
            'all_pages' => ceil($total_num / $page_size), //总页码
            'total_num' => $total_num, //数据总记录数
            'current_num' => $current_num, //本次拉取记录数
            'current_succ_num' => $current_num, //处理成功记录数
            'current_fail_num' => 0, //处理失败记录数
        );
        
        //是否拉取下一页(如果为0则无需拉取)
        if ($data['current_page'] == $data['all_pages']) {
            $data['next_page'] = 0;
        }
        
        //已存在的数据
        $expenseIds = $funcLib->_array_column($dataList, 'expenseId');
        $existData = array();
        if($expenseIds){
            $existData = $mdl->getList('pay_id,expenseId', array('expenseId'=>$expenseIds));
            if($existData){
                $existData = $funcLib->_array_column($existData, null, 'expenseId');
            }
        }
        
        //save
        foreach($dataList as $dataKey => $val)
        {
            $expenseId = $val['expenseId'];
            
            //没有单据ID
            if(empty($val['code']) || empty($expenseId)) {
                continue;
            }
            
            //分页查询的最后的id
            //@todo：京东没有count记录总数,只提供了最后lastId;
            $data['next_page'] = ($expenseId ? $expenseId : $val['code']);
            
            //check
            if($existData[$expenseId]){
                continue; //数据已经存在,则跳过
            }
            
            //主数据
            $mainRow = $payableLib->getMainRow($val);
            if(empty($mainRow)){
                continue; //无效数据
            }
            $mainRow['shop_id'] = $shop_id;
            //insert
            $did = $mdl->insert($mainRow);
            if(!$did){
                continue; //保存失败
            }
        }
        
        return $data;
    }
    
    
    
    /**
     * 获取京东结算单分页查询列表
     * 
     * @param array $params
     * @param int $page
     * @return array
     */
    public function accountSettlementList($params)
    {
        $mdl = app::get('ediws')->model('account_settlement');
        
        $stlmentLib = kernel::single('ediws_autotask_timer_accountsettlement');
        $funcLib = kernel::single('ediws_func');
        
        //original_bn
        $original_bn = 'account_settlement_' . date('md', time());
        
        //拉取模式(single：一次性简单的，loop：循环拉取)
        $pull_model = $params['pull_model'] ? $params['pull_model'] : 'loop';
        
        //开始日期(年-月-日 时:分:秒)
        $startDate = $params['start_time'] .' 00:00:00';
        
        //结束日期(年-月-日 时:分:秒)
        $endDate = $params['end_time'] .' 23:59:59';
        
        //page
        $page = intval($params['page']);
        
        //page_size
        $page_size = $params['page_size'] ? $params['page_size'] : $stlmentLib::$request_page_size;
        
        $shop_id = $params['shop_id'];

        $shops = kernel::single('ediws_event_trigger_jdlvmi')->getShops($shop_id);
        //供应商编码
        $vendorCode = $shops['config']['ediwsuser'];
        //params
        $request_params = array(
            'vendorCode' => $vendorCode, //供应商简码
            'start_time' => $startDate,
            'end_time' => $endDate,
            //'approveStatus' => '101', //审核状态：101待审核:103审核通过;
            //'pattern' => '1', //结算模式:1账期结算;2实销实结;3实时结算;4预付款结算;5实销实结(老);6返利清收;7流水倒扣;9账期结算(月结);10实销实结(月结);
            //'confirmStatus' => '1', //确认状态：0未确认;1结算员已确认;2供应商已确认;3供应商驳回;4系统驳回;8系统待结算员确认;
            //'payStatus' => '1', //付款状态：1未付款;9已付款;3已开票;4已承兑;
            //'channelCode' => '', //渠道编码
            'page_no' => $page,
            'page_size' => $page_size,
            'pull_model' => $pull_model,
        );
        
      
        
        //api同步日志单据号
        $request_params['original_bn'] = $original_bn; //请求单据号
        
        $dataList = array();
        $page_no = 0;
        $response = array();
        
        //request
        $result = kernel::single('erpapi_router_request')->set('ediws',$params['shop_id'])->accountsettlement_getlist($request_params);
     
        //[兼容]只拉取一次数据,获取总条数
        if($pull_model == 'single' && $page_size == 1){
            return $result;
        }
        
        //请求失败
        if($result === false) {
            if($response['rsp'] == 'succ'){
                $result = array('rsp'=>'fail', 'error_msg'=>'请求失败：没有获取到列表数据');
            }else{
                $result = array('rsp'=>'fail', 'error_msg'=>'响应失败：'. $response['error_msg']);
            }
            
            return $result;
        }
        $dataList = $result['data']['data'];
        //获取的数据列表
        if(empty($dataList)){
            $result = array('rsp'=>'fail', 'error_msg'=>'获取失败：没有获取到有效数据');
            return $result;
        }
        
        //数据总记录条数
        $total_num = $result['data']['data']['total'];
        
        //本次拉取数据条数
        $current_num = count($dataList);
        
        //data
        $next_page = ($page + 1);
        $data = array(
            'rsp' => 'succ',
            'current_page' => $page, //当前页码
            'next_page' => $next_page, //下一页页码
            'page_size' => $page_size, //每次拉取数量
            'all_pages' => ceil($total_num / $page_size), //总页码
            'total_num' => $total_num, //数据总记录数
            'current_num' => $current_num, //本次拉取记录数
            'current_succ_num' => $current_num, //处理成功记录数
            'current_fail_num' => 0, //处理失败记录数
        );
        
        //是否拉取下一页(如果为0则无需拉取)
        if ($data['current_page'] == $data['all_pages']) {
            $data['next_page'] = 0;
        }
        
        //已存在的数据
        $shqids = $funcLib->_array_column($dataList, 'shqid');
        $existData = array();
        if($shqids){
            $existData = $mdl->getList('sid,shqid,paymentOrder', array('shqid'=>$shqids));
            if($existData){
                $existData = $funcLib->_array_column($existData, null, 'shqid');
            }
        }
        
        //save
        $flag = false;
        
        foreach($dataList as $dataKey => $val)
        {
            $shqid = trim($val['shqid']);
            
            //结算单号不能为空
            if(empty($shqid)) {
                continue;
            }
            
            //check数据已经存在,则跳过
            if($existData[$shqid]){
                continue;
            }
            
            //主数据
            $mainRow = $stlmentLib->getMainRow($val);
            if(empty($mainRow)){
                continue; //无效数据
            }
            $mainRow['shop_id'] = $params['shop_id'];
            //insert
            $did = $mdl->insert($mainRow);
            if(!$did){
                continue; //保存失败
            }
            
            $flag = true;
        }
        
        return $data;
    }
    
   
}