<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_autotask_timer_accountsettlement extends ediws_autotask_timer_abstract
{
    
     /* 执行的间隔时间 */
    const intervalTime = 3600;
    //Lib
    protected $_accountLib = null;
    /* 当前的执行时间 */
    public static $now;
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->_mdl = app::get('ediws')->model('account_settlement');
        $this->_accountLib = kernel::single('ediws_account');
    }
    
    /**
     * 执行任务
     * 
     * @param array $params 请求参数
     * @param string $error_msg
     * @return bool
     */
    public function process($params=array(), &$error_msg='')
    {
        @set_time_limit(0);
        @ini_set('memory_limit','512M');
        ignore_user_abort(1);
        
        $apiLib = kernel::single('ediws_api');
        
        //指定时间戳
        if($params['start_time'] && $params['end_time']){
            list($start_time, $end_time) = $this->get_time_range($params);
        }else{
            //默认开始时间为本月一号
            $params['time_model'] = 'current_month';
            list($start_time, $end_time) = $this->get_time_range($params);
        }
        
        //供应商编码列表
        $shopList = $this->getJdlwmiShop();
        if(empty($shopList)){
            $error_msg = '未配置供应商编码';
            return false;
        }
        
        $accountsettle_flag = false;
        
        //page size
        $page_size = self::$request_page_size;
        
        //按供应商编码循环拉取
        foreach ($shopList as $codeKey => $codeVal)
        {
            
            $config = $codeVal['config'];
            if($config['account_settlement']!='sync') continue;

            //供应商编码
            $vendorCode = $codeVal['config']['ediwsuser'];
            
            $accountsettle_flag = true;
            $shop_id = $codeVal['shop_id'];
            //params
            $params = array(
                'shop_id'    => $shop_id,   
                'shop_bn' => $vendorCode, //供应商编码
                'start_time' => date('Y-m-d H:i:s', $start_time), //开始日期(年-月-日 时:分:秒)
                'end_time' => date('Y-m-d H:i:s', $end_time), //结束日期(年-月-日 时:分:秒)
            );
            
            //获取京东结算单总条数
            $params['page'] = 1; //页码
            $params['page_size'] = 1; //只拉取一条数据
            $params['pull_model'] = 'single'; //只拉取一次数据
            $requestResult = $apiLib->accountSettlementList($params);
          
            //count
            $countNum = intval($requestResult['data']['total']);
           
            if($countNum <= 0){
                continue;
            }
            
            //page按照分页进行拉取京东结算单
            $pageNum = ceil($countNum / $page_size);
            for($page=1; $page<=$pageNum; $page++)
            {
                //params
                $params['page'] = $page; //页码
                $params['page_size'] = $page_size;
                $params['pull_model'] = 'loop'; //循环拉取数据
                
                //request
                $requestResult = $apiLib->accountSettlementList($params);
            }


            //下载结算单文件
            $result = $this->downloadSettlementOrders($params, $error_msg);
            
            //解压结算单文件
            $result = $this->unZipSettlementOrders($params, $error_msg);
            
            //解析结算单文件
            $result = $this->analySettlementOrders($params, $error_msg);
        }
        
        if($accountsettle_flag){
            $this->synFinanceBill();
        }
        
        return true;
    }
    
    /**
     * 获取MainRow
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function getMainRow($data)
    {
        $mainRow = array(
            'shqid' => $data['shqid'], //结算单号
            'paymentOrder' => $data['paymentOrder'], //支付单号
            'confirmStatus' => $data['confirmStatus'], //确认状态
            'fileUrl' => $data['fileUrl'], //文件URL
            'approveStatus' => $data['approveStatus'], //审核状态：101待审核;103审核通过;104审核驳回;205未驳回;
            'summoney' => $data['summoney'], //应结金额
            'pattern' => intval($data['pattern']), //结算模式
            'operatorName' => $data['operatorName'],
            'vendorCode' => $data['vendorCode'],
            'paycompanyid' => intval($data['paycompanyid']), //合同主体id
            'isscf' => $data['isscf'],
            'payMoney' => $data['payMoney'], //应付金额
            'approvedpaydate' => $data['approvedpaydate'], //核定付款日期
            'settlementStatus' => intval($data['settlementStatus']), //结算状态
            'requestorName' => $data['requestorName'],
            'planpaydate' => $data['planpaydate'],
            'rejectCause' => $data['rejectCause'],
            'createDate' => ($data['createDate'] ? strtotime($data['createDate']) : 0), //结算单创建时间
            'receiveMoney' => $data['receiveMoney'], //应收金额
            'channelCode' => $data['channelCode'],
            'billingAmount' => $data['billingAmount'], //应开票金额
            'paytime' => ($data['paytime'] ? strtotime($data['paytime']) : 0), //付款日期
            'vendorName' => $data['vendorName'],
            'openInvoiceStatus' => intval($data['openInvoiceStatus']), //开票状态
            'paycompany' => $data['paycompany'],
            'hxstatus' => $data['hxstatus'],
            'addType' => intval($data['addType']), //出单方式
            'requestorId' => $data['requestorId'],
            'channelName' => $data['channelName'],
            'payStatus' => $data['payStatus'],
            'create_time' => time(),
            'last_modified' => time(),
        );
        
        return $mainRow;
    }
    
    /**
     * 下载结算单文件
     * 
     * @param $params
     * @param $error_msg
     * @return void
     */
    public function downloadSettlementOrders($params=array(), &$error_msg='')
    {
        //获取未下载的记录
        $filter = array('file_status'=>'none');
        $dataList = $this->_mdl->getList('*', $filter, 0, 500);
        if(empty($dataList)){
            return false;
        }
        
        //list
        foreach ($dataList as $key => $row)
        {
            //check
            if(in_array($row['file_status'], array('download'))){
                $error_msg = '结算单号：'. $row['shqid'] .'文件已下载,不允许重复操作';
                continue;
            }
            
            if(!in_array($row['file_status'], array('none', 'download_fail'))){
                $error_msg = '结算单号：'. $row['shqid'] .'文件不允许下载,请求检查';
                continue;
            }
            
            //download
            $result = $this->_accountLib->downloadSettlementFiles($row);
            if($result['rsp'] != 'succ'){
                $error_msg = '结算单号：'. $row['shqid'] .'下载失败：'. $result['error_msg'];
            }
        }
        
        return true;
    }
    
    /**
     * 解压结算单文件
     * 
     * @param $params
     * @param $error_msg
     * @return void
     */
    public function unZipSettlementOrders($params=array(), &$error_msg='')
    {
        //获取未解压的记录
        $filter = array('file_status'=>'download');
        $dataList = $this->_mdl->getList('*', $filter, 0, 500);
        if(empty($dataList)){
            return false;
        }
        
        //list
        foreach ($dataList as $key => $row)
        {
            //check
            if(in_array($row['file_status'], array('unzip'))){
                $error_msg = '结算单号：'. $row['shqid'] .'文件已解压,不允许重复操作';
                continue;
            }
            
            if(!in_array($row['file_status'], array('download', 'unzip_fail'))){
                $error_msg = '结算单号：'. $row['shqid'] .'文件未下载,请求检查';
                continue;
            }
            
            //download
            $result = $this->_accountLib->unzipSettlementFiles($row);
            if($result['rsp'] != 'succ'){
                $error_msg = '结算单号：'. $row['shqid'] .'解压失败：'. $result['error_msg'];
            }
        }
        
        return true;
    }
    
    /**
     * 解析结算单文件
     * 
     * @param $params
     * @param $error_msg
     * @return void
     */
    public function analySettlementOrders($params=array(), &$error_msg='')
    {
        //获取未解析的记录
        $filter = array('file_status'=>'unzip');
        $dataList = $this->_mdl->getList('*', $filter, 0, 500);

        if(empty($dataList)){
            return false;
        }
        
        //list
        foreach ($dataList as $key => $row)
        {
            //check
            if(in_array($row['file_status'], array('analysis'))){
                $error_msg = '结算单号：'. $row['shqid'] .'文件已解析,不允许重复操作';
                continue;
            }
            
            if(!in_array($row['file_status'], array('unzip', 'analysis_fail'))){
                $error_msg = '结算单号：'. $row['shqid'] .'文件不允许解析,请求检查';
                continue;
            }
            
            //download
            $result = $this->_accountLib->analysisSettlementFiles($row);
            if($result['rsp'] != 'succ'){
                $error_msg = '结算单号：'. $row['shqid'] .'解析失败：'. $result['error_msg'];
            }
        }
        
        return true;
    }

    /**
     * synFinanceBill
     * @return mixed 返回值
     */
    public function synFinanceBill(){

        $settlement_ordersMdl = app::get('ediws')->model('account_settlement_orders');
        $offset = 0;
        $pageSize  = 20;

      
        $page = 1;
        do {
            $offset     = ($page - 1) * $pageSize;
            $orderlist = $settlement_ordersMdl->getlist('*',array('sync_status'=>array('0','2')),$offset, $pageSize);

            if(empty($orderlist)){
                break;
            }

            //
           
            kernel::single('ediws_accountsettlement')->process($orderlist);


            $page++;
        } while (true);
       


    }
}
