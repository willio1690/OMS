<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_autotask_timer_accountorders extends ediws_autotask_timer_abstract
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
        
        base_kvstore::instance('ediws/sxsj')->fetch('lastexectime',$lastExecTime);

        if($lastExecTime && ($lastExecTime+self::intervalTime)>self::$now) {
            $error_msg = '上次执行时间为'.date('Y-m-d H:i:s',$lastExecTime);
            return false;
        }
        $lastExecTime = $lastExecTime ? : (time()-7*86400);

        $end_time = self::$now-600;
        

        $start_time = $lastExecTime;

        

        $accountorders_flag = false;
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

               
                $this->getPullList($sdf, $shop['shop_id']);
                $accountorders_flag = true;
            }
            
        }
        

        if($accountorders_flag){
            $this->synSales();
        }

        base_kvstore::instance('ediws/sxsj')->store('lastexectime', $end_time);
        return true;
    }



    /**
     * 获取PullList
     * @param mixed $params 参数
     * @param mixed $shop_id ID
     * @return mixed 返回结果
     */
    public function getPullList($params, $shop_id)
    {
        
        
        $lastid = 0;
        // 分页循环查询
        do {

            list($result, $msg) = $this->getPull($params, $shop_id, $lastid);

            if (!$result) {
                break;
            }

        } while (true);

        
        return [true];
    }



    /**
     * 获取Pull
     * @param mixed $params 参数
     * @param mixed $shop_id ID
     * @param mixed $lastid ID
     * @return mixed 返回结果
     */
    public function getPull($params, $shop_id, &$lastid){

        $original_bn = $params['shop_bn'] ? $params['shop_bn'] : 'shop_bn';

       
        $request_params = array(
           
            'original_bn'       => $original_bn, //请求单据号
            'start_time'        => $params['start_time'],
            'end_time'          => $params['end_time'],
            'lastid'            => $lastid,
        );

       
        $result = kernel::single('erpapi_router_request')->set('shop',$shop_id)->accountorders_getlist($request_params);
       
     
        if($result['rsp'] != 'succ'){
            
            $result = [false, '请求失败：'. $result['msg']];
            return $result;
        }

        $dataList = $result['data']['data'];

        if(count($dataList)<=0) return [false, '没有数据返回'];

        $total = count($dataList);

        foreach($dataList as $val)
        {

            $orderresult = $this->processAccountorder($val,$shop_id);

            
            $lastid = max($lastid,$val['lastId']);
            
        }

        if($lastid==$request_params['lastid']){

            return [false,'请求id重复'];//避免死循环两次都是同一个跳出
        }
        $data = array('total'=>$total);
        return [true,$data];
    }


    /**
     * 处理Accountorder
     * @param mixed $data 数据
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function processAccountorder($data,$shop_id){

        $mdl = app::get('ediws')->model('account_orders');
        $orderNo = $data['orderNo'];
        
        $lastId = $data['lastId'];

        $orders = $mdl->db->selectrow("select ord_id from sdb_ediws_account_orders where lastId=".$lastId."");
        
        if(!$orders){
            //主数据
            $mainRow = $this->getMainRow($data);


            if(empty($mainRow)){
                return [false,"主数据为空"];
            }
            $mainRow['shop_id'] = $shop_id;

            $sku = $mainRow['sku'];

            $materials = kernel::single('ediws_jdlvmi')->get_sku($shop_id,$sku);

            if($materials){
                $mainRow['material_bn'] = $materials['material_bn'];
                $mainRow['bm_id']       = $materials['bm_id'];
                
            }
         
            $ord_id = $mdl->insert($mainRow);
            
            return [true];

        }
        
        return [false,"lastid".$lastId."已存在"];
    }
    
    /**
     * 获取MainRow
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function getMainRow($data)
    {
        $mainRow = array(
            'kDanHao'           =>  $data['kDanHao'], //出管单号
            'orderNo'           =>  $data['orderNo'], //订单号
            'purchaseOrderNo'   =>  $data['purchaseOrderNo'],//采购单号

            'sku'              =>  $data['sku'],//商品编码
            'goodsName'        =>  $data['goodsName'],//商品名称
            'quantity'         =>  $data['quantity'], //数量
            'price'            =>  $data['price'], //单价
            'amount'           =>  $data['amount'], //销售金额
            'storeOutDate'     =>  $data['storeOutDate'] ? $data['storeOutDate']/1000 : 0, //出入库时间

            'supplierId'       =>  $data['supplierId'],//供应商简码

            'xnitype'          =>  $data['xnitype'],//采购单类型
            'purchaseDate'     =>  $data['purchaseDate'] ? $data['purchaseDate']/1000 : 0,//采购日期
            'refType'          =>  $data['refType'],//单据类型

            'orderCompleteDate'  =>$data['orderCompleteDate'] ? $data['orderCompleteDate']/1000 : 0,//订单完成时间

            'salesPrice'       =>  $data['salesPrice'],//基础单价

            'salesAmount'      =>  $data['salesAmount'],//基础金额。
            'rebateAmount'     =>  $data['rebateAmount'], //扣点金额
            'rebateRate'       =>  $data['rebateRate'], //扣点比例

            'discountAmount'   =>  $data['discountAmount'], //折扣项金额

            'orderTime'        =>  $data['orderTime'] ? $data['orderTime']/1000 : 0,//下单时间
            'settleAmount'     =>  $data['settleAmount'], //结算金额

            'flowFlag'         =>  $data['flowFlag'],//是否流水倒扣

            'channelCode'      =>  $data['channelCode'],//渠道编码

            'bizType'          =>  $data['bizType'],//账套
            'lastId'           =>  $data['lastId'], 
            'create_time'       => time(),
            'last_modified'     => time(),
        );
        


        return $mainRow;
    }

    /**
     * synSales
     * @return mixed 返回值
     */
    public function synSales(){

        $ordersMdl = app::get('ediws')->model('account_orders');
        $offset = 0;
        $pageSize  = 50;

        $page = 1;
        do {

            $offset     = ($page - 1) * $pageSize;
            $orderlist = $ordersMdl->getlist('ord_id',array('sync_status'=>array('0'),'refType'=>array('1002')),$offset, $pageSize);

            if(empty($orderlist)){
                break;
            }

            foreach($orderlist as $v){
                
                $ord_id = $v['ord_id'];
                kernel::single('ediws_accountorders')->createBill($ord_id);
                
            }

            $page++;
        } while (true);
       


    }


    

}
