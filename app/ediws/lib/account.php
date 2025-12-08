<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_account extends ediws_abstract
{
   
    
    /**
     * 获取渠道类型列表
     * 
     * @return void
     */
    public function getChannelTypes(&$error_msg=null)
    {
        $channelTypes = array(
            array('type'=>'jd_account', 'name'=>'京东入仓'),
            array('type'=>'jd_cloud', 'name'=>'京东云仓'),
        );
        
        return $channelTypes;
    }


    /**
     * 下载结算单文件
     * 
     * @param array $params
     * @param int $page
     * @return array
     */
    public function downloadSettlementFiles($params)
    {
        $settlementObj = app::get('ediws')->model('account_settlement');
        
        $funcLib = kernel::single('ediws_func');
        
        //check
        if(empty($params['fileUrl'])){
            $error_msg = '下载文件URL地址不能为空';
            return $this->error($error_msg);
        }
        
        if(empty($params['sid'])){
            $error_msg = '结算单信息不存在';
            return $this->error($error_msg);
        }
        
        //set filepath
        ediws_filename::set_filepath('jd_zip');
        $exp_path = ediws_filename::get_filepath();
        if(empty($exp_path)){
            $error_msg = '存储文件地址不能为空';
            return $this->error($error_msg);
        }
        
        //保存本地的文件名
        $filename = $params['shqid'] .'.zip';
        $write_file = $exp_path . '/' . $filename;
        
        //download
        $downResult = $funcLib->download_zip($params['fileUrl'], $write_file);
        if(!$downResult){
            $error_msg = '下载保存文件失败';
            
            //update
            $saveData = array('file_status'=>'download_fail', 'error_msg'=>$error_msg, 'last_modified'=>time());
            $settlementObj->update($saveData, array('sid'=>$params['sid']));
            
            return $this->error($error_msg);
        }
        
        //filename
        $filename = substr($write_file, strlen(DATA_DIR));
        
        //update
        $saveData = array('localUrl'=>$filename, 'file_status'=>'download', 'last_modified'=>time());
        $settlementObj->update($saveData, array('sid'=>$params['sid']));
        
        return $this->succ();
    }


    /**
     * 解压结算单文件
     * 
     * @param array $params
     * @param int $page
     * @return array
     */
    public function unzipSettlementFiles($params)
    {
        $settlementObj = app::get('ediws')->model('account_settlement');
        
        $funcLib = kernel::single('ediws_func');
        
        //check
        if(empty($params['localUrl'])){
            $error_msg = '本地文件URL地址不能为空';
            return $this->error($error_msg);
        }
        
        if(empty($params['sid'])){
            $error_msg = '结算单信息不存在;';
            return $this->error($error_msg);
        }
        
        //set filepath
        ediws_filename::set_filepath('jd_csv');
        $exp_path = ediws_filename::get_filepath();
        if(empty($exp_path)){
            $error_msg = '本地文件目录地址不能为空;';
            return $this->error($error_msg);
        }
        
        //zip文件
        $zip_file = DATA_DIR . $params['localUrl'];
        
        //download
        $unzipResult = $funcLib->unZip($zip_file, $exp_path);
        if($unzipResult['rsp'] != 'succ'){
            $error_msg = $unzipResult['error_msg'];
            
            //update
            $saveData = array('file_status'=>'unzip_fail', 'error_msg'=>$error_msg, 'last_modified'=>time());
            $settlementObj->update($saveData, array('sid'=>$params['sid']));
            
            return $this->error($error_msg);
        }
        
        //保存本地的文件名
        $unzip_file = $exp_path .'/'. $unzipResult['fileList'][0];
        $filename = substr($unzip_file, strlen(DATA_DIR));
        
        //update
        $saveData = array('unzipUrl'=>$filename, 'file_status'=>'unzip', 'last_modified'=>time());
        $settlementObj->update($saveData, array('sid'=>$params['sid']));
        
        return $this->succ();
    }
    
    /**
     * 解析结算单文件
     * 
     * @param array $params
     * @param int $page
     * @return array
     */
    public function analysisSettlementFiles($params)
    {
        $settlementObj = app::get('ediws')->model('account_settlement');
        $settOrderObj = app::get('ediws')->model('account_settlement_orders');
        
        $analysisLib = kernel::single('ediws_file_analysis_csv');
        //$sapLib = kernel::single('ediws_sap');
        $funcLib = kernel::single('ediws_func');
        @ini_set('memory_limit','1024M');
        //check
        if(empty($params['unzipUrl'])){
            $error_msg = '解压文件URL不能为空';
            return $this->error($error_msg);
        }
        
        if(empty($params['sid'])){
            $error_msg = '结算单信息不存在;';
            return $this->error($error_msg);
        }
        
        //解析文件
        $error_msg = '';
        $readResult = $analysisLib->readFile($params['unzipUrl'], $error_msg);

      
        if($readResult['rsp'] != 'succ'){
            $error_msg = $readResult['error_msg'];
            
            //update
            $saveData = array('file_status'=>'analysis_fail', 'error_msg'=>$error_msg, 'last_modified'=>time());
            $settlementObj->update($saveData, array('sid'=>$params['sid']));
            
            return $this->error($error_msg);
        }elseif(empty($readResult['data'])){
            $error_msg = '解析文件内容为空';
            
            //update
            $saveData = array('file_status'=>'analysis_fail', 'error_msg'=>$error_msg, 'last_modified'=>time());
            $settlementObj->update($saveData, array('sid'=>$params['sid']));
            
            return $this->error($error_msg);
        }
        
        //format
        $dataList = $this->frmatCsvSettlement($readResult['data']);
        
       
        //unset
        unset($readResult);
        
        //check
        if(empty($dataList)){
            $error_msg = '格式化数据后有效数据为空';
            
            //update
            $saveData = array('file_status'=>'analysis_fail', 'error_msg'=>$error_msg, 'last_modified'=>time());
            $settlementObj->update($saveData, array('sid'=>$params['sid']));
            
            return $this->error($error_msg);
        }
        
        //已经存在的数据
        $soleBns = $funcLib->_array_column($dataList, 'sole_bn');
        $existData = $settOrderObj->getList('oid,sole_bn', array('sole_bn'=>$soleBns));
        if($existData){
            $existData = $funcLib->_array_column($existData, 'oid', 'sole_bn');
        }
        
        //save
        $succFlag = false;
        foreach ($dataList as $key => $val)
        {
            $expenseId = $val['expenseId']; //应付账ID(唯一)
            $orderNo = $val['orderNo'];
            $sku_bn = $val['sku'];
            
            //唯一性编码 = 应付账ID + 订单号 + SKU
            //@todo：shqid结算单号不唯一,销售和退货结算单号是相同的；
            $sole_bn = $expenseId .'_'. $orderNo .'_'. $sku_bn;
            
            //check数据已经存在,则跳过
            if($existData[$sole_bn]){
                continue;
            }

            //merge
            $val['create_time'] = time();
            $val['last_modified'] = time();
            $val['shop_id']=$params['shop_id'];
            //insert
            $settOrderObj->insert($val);
            
            //flag
            $succFlag = true;
        }
        
        //update
        if($succFlag){
            $saveData = array('file_status'=>'analysis', 'last_modified'=>time());
            $settlementObj->update($saveData, array('sid'=>$params['sid']));
        }
        
        return $this->succ();
    }
    
    /**
     * 格式化Csv数据
     * 
     * @param array $fileData
     * @return array
     * 表头 结算单号   供应商名称   供应商简码   合同主体    应付帐单据类型 单据编号    采购单号    业务发生时间  台账类型    采购员 备注  SKU编码   SKU名称   单价  数量  金额  订单号 备件库条码   采购类型    单据ID    客户订单号   开票方向    非成品油金额  润滑油金额   润滑脂金额   计费类型
     */
    public function frmatCsvSettlement($fileData, &$error_msg=null)
    {
        //京东应付账单据类型
        $rootExpenseTypes = $this->setRootExpenseType();
        $rootExpenseTypes = array_flip($rootExpenseTypes);
        //list
        $dataList = array();


        $line_i = 0;
        foreach($fileData as $key => $val)
        {
            //check
            if(empty($val)){
                continue;
            }
            
            //编码转换
            //$val = mb_convert_encoding($val, 'UTF-8', 'GB2312');
            //$val = iconv('GB2312', 'UTF-8//IGNORE', $val);
          
            //$lineRow = explode(',', $val);
            $lineRow = $val;

            //check
            if(empty($lineRow['结算单号'])){
                continue;
            }
            
            //format
            $expenseId = $this->charFilter($lineRow['单据ID']); //应付账ID(唯一)
            $shqid = $this->charFilter($lineRow['结算单号']); //结算单号
            $orderNo = $this->charFilter($lineRow['订单号']);
            $sku_bn = $this->charFilter($lineRow['SKU编码']);
            $quantity = $this->charFilter($lineRow['数量']);
            $quantity = intval($quantity);
            
            //过滤标题行
            if($shqid == '结算单号' || $orderNo == '订单号'){
                continue;
            }
            
            if(empty($shqid) || empty($orderNo) || empty($sku_bn)){
                continue;
            }
            
            //人民币表示的价格(单位:元)
            $bills_amount = $this->charFilter($lineRow['金额']);
            $bills_amount = floatval($bills_amount);
            
            //$rebate_amount = $this->charFilter($lineRow[11]);
            $rebate_amount = floatval($rebate_amount);
            
            $settle_amount = $this->charFilter($lineRow['金额']);
            $settle_amount = floatval($settle_amount);
            
            //$tax_point = $this->charFilter($lineRow[13]);
            //$tax_point = floatval($tax_point); 
            
            //time
            //$complete_time = $this->charFilter($lineRow[业务发生时间]);
            //$complete_time = ($complete_time ? strtotime($complete_time) : 0);
            
            $business_time = $this->charFilter($lineRow['业务发生时间']);
            $business_time = ($business_time ? strtotime($business_time) : 0);
            
            //应付帐单据类型
            $expenseTypeName = $this->charFilter($lineRow['应付帐单据类型']);
            $rootExpenseType = ($rootExpenseTypes[$expenseTypeName] ? $rootExpenseTypes[$expenseTypeName] : $expenseTypeName);
            
            //唯一性编码 = 应付账ID + 订单号 + SKU
            //@todo：shqid结算单号不唯一,销售和退货结算单号是相同的；
            $sole_bn = $expenseId .'_'. $orderNo .'_'. $sku_bn;
            
            //data
            $rows = array(
                'sole_bn' => $sole_bn, //唯一编码
                'shqid' => $shqid, //结算单号
                'vendorName' => strtoupper($lineRow['供应商名称']), //供应商名称
                'vendorCode' => $this->charFilter($lineRow['供应商简码']), //供应商简码
                'rootExpenseType' => $rootExpenseType, //应付帐单据类型
                'expenseTypeName' => $expenseTypeName, //应付帐单据类型名称
                'orderNo' => $orderNo, //订单号
                'sku' => $sku_bn, //SKU编码
                'goodsName' => $this->charFilter($lineRow['SKU名称']), //SKU名称
                'quantity' => $quantity ? $quantity : 0, //数量
               // 'complete_time' => $complete_time ? $complete_time : 0, //订单完成时间
                'business_time' => $business_time ? $business_time : 0, //业务时间
                'bills_amount' => $bills_amount, //单据金额
                //'rebate_amount' => $rebate_amount, //返利金额
                //'settle_amount' => $settle_amount, //应结金额
                //'tax_point' => $tax_point, //点位
                'ouName' => $this->charFilter($lineRow['合同主体']), //合同主体
                //'is_factory' => $this->charFilter($lineRow[15]), //是否厂直
                //'departmentName' => $this->charFilter($lineRow[16]), //部门
                //'teamName' => $this->charFilter($lineRow[17]), //组别
                'spare_barcode' => $this->charFilter($lineRow['备件库条码']), //备件库条码
                'expenseId' => $expenseId, //单据ID
                'kefu_order' => $this->charFilter($lineRow['客户订单号']), //客户订单号
                'invoiceMode' => $this->charFilter($lineRow['开票方向']), //开票方向
                'xniName' => $this->charFilter($lineRow['采购类型']), //采购类型
            );
            
           
            //去除空格
            $dataList[] = array_map('trim', $rows);
        }
       
        return $dataList;
    }

   


    /**
     * 京东应付账单据类型
     * 
     * @return array
     */
    public function setRootExpenseType()
    {
        $types = array(
            '10001' => '采购入库单',
            '10002' => '采购退货单',
            '100700' => '实销实结销售单',
            '100710' => '实销实结退货入库单',
            '100300' => '售后退货',
            '10055' => '批次单',
            '10202' => 'ASN采购单',
            '22000000' => '返点单',
        );
        
        return $types;
    }


    /**
     * 结算单--结算模式
     * 
     * @return array
     */
    public function setPatterns()
    {
        $types = array(
            '1' => '账期结算',
            '2' => '实销实结',
            '3' => '实时结算',
            '4' => '预付款结算',
            '5' => '实销实结（老)',
            '6' => '返利清收',
            '7' => '流水倒扣',
            '9' => '账期结算（月结）',
            '10' => '实销实结（月结)',
            '11' => '货到付款',
        );
        
        return $types;
    }
    
    /**
     * 结算单--供应商结算状态
     * 
     * @return array
     */
    public function getSettlementStatus()
    {
        $types = array(
            '1' => '审批中',
            '2' => '审批通过',
            '3' => '未付款',
            '4' => '已付款',
            '5' => '审核不通过',
        );
        
        return $types;
    }
    
    /**
     * 结算单--确认状态
     * 
     * @return array
     */
    public function setConfirmStatus()
    {
        $types = array(
            '0' => '未确认',
            '1' => '结算员已确认',
            '2' => '供应商已确认',
            '3' => '供应商驳回',
            '4' => '系统驳回',
            '8' => '系统待结算员确认',
        );
        
        return $types;
    }
    
    /**
     * 结算单--出单方式
     * 
     * @return array
     */
    public function setAddTypes()
    {
        $types = array(
            '1' => '返利自动清算',
            '2' => '冲销生成结算单',
            '3' => '手动导入生成',
            '4' => '预付款自动平账',
            '5' => '自动勾单生成',
            '6' => '新自动勾单',
            '7' => 'VC提申请勾单',
            '8' => '页面手工勾单',
            '9' => '费用自动创建结算单',
            '10' => 'TC账扣',
        );
        
        return $types;
    }
    
    /**
     * 结算单--付款状态
     * 
     * @return array
     */
    public function setPayStatus()
    {
        $types = array(
            '1' => '未付款',
            '3' => '已开票',
            '4' => '已承兑',
            '9' => '已付款',
            '10' => '银企直连付款中',
            '20' => '九恒星直连付款中',
        );
        
        return $types;
    }
    
}