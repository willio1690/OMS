<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_task_refundinfo {
    /* 执行的间隔时间 */
    const intervalTime = 3600;

    /* 当前的执行时间 */
    public static $now;
   
    function __construct()
    {
        self::$now = time();
    }
    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($params, $error_msg){
      
        @set_time_limit(0);
        @ini_set('memory_limit','128M');
        ignore_user_abort(1);
        //$this->refreshReturn();

        base_kvstore::instance('ediws/jd/refundinfo')->fetch('apply-lastexectime',$lastExecTime);
        if($lastExecTime && ($lastExecTime+self::intervalTime)>self::$now) {
            return false;
        }
        
        $lastExecTime = $lastExecTime ? : (time()-7*86400);
       
        $end_time = self::$now-600;
        base_kvstore::instance('ediws/jd/refundinfo')->store('apply-lastexectime', $end_time);

         //供应商编码列表
        $shopList = kernel::single('ediws_autotask_timer_accountsettlement')->getJdlwmiShop();

     
        if(empty($shopList)){
            $error_msg = '未配置供应商编码';
            return false;
        }

        $shippackage_flag = false;
        foreach ($shopList as $codeKey => $codeVal)
        {
            
            $config = $codeVal['config'];
            $vendorCode = $config['ediwsuser'];
            if($config['ediwssync'] != 'sync'){
               continue;
            }

            $sdf = array(
                'start_time'    =>$lastExecTime,
                'end_time'      =>$end_time,
                'shop_id'       =>$codeVal['shop_id'],
                'shop_bn'       =>$codeVal['shop_bn'],
                'shop_type'     =>$codeVal['shop_type'],
                'vendorcode'    =>$vendorCode,
            );
            $this->getRefundinfo($sdf);
            
            $this->fixrefundinfo($sdf);
        }


        $this->refreshReturn();
        return true;
    }


    /**
     * 获取Refundinfo
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function getRefundinfo($data) {
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        $bill_id = $data['bill_id'];
        $shop_id = $data['shop_id'];
        $outno = $data['outno'];
        if(!$data['vendorcode']){

            $shops = kernel::single('ediws_event_trigger_jdlvmi')->getShops($shop_id);

            $data['vendorcode'] = $shops['config']['ediwsuser'];
        }
        $pageNo = 1;
        do {

            $params = array(
              
                'applyBeginTime'    =>  date('Y-m-d H:i:s',$start_time),
                'applyEndTime'      =>  date('Y-m-d H:i:s',$end_time),
                'providerCode'      =>  $data['vendorcode'],
                'pageIndex'         =>  $pageNo,
                'pageSize'          =>  50,
                'method'            =>  'edi.request.refundinfo.getlist',

            );
            if($bill_id){
                $params['refundId'] = $bill_id;
            }
            if($outno){
                $params['outNo'] = $outno;
            }
            $rs = kernel::single('erpapi_router_request')->set('ediws',$data['shop_id'])->refundinfo_getlist($params);

         
            if (empty($rs['data']['data']) || $rs['data']['recordCount']==0) {
                return true;
                break;
            }
            $count = $rs['data']['recordCount'];
          
           
            foreach ($rs['data']['data'] as $v) {
                
                $bill_params = array(
                    'providerCode'  =>  $data['vendorcode'],
                    'refundId'      =>  $v['refundId'],
                    'pageIndex'     =>  1,
                    'pageSize'      =>  50,
                    'method'        => 'edi.request.refundinfo.detail',
                );
             
                $billresult= kernel::single('erpapi_router_request')->set('ediws',$data['shop_id'])->refundinfo_detail($bill_params);
               
                if (empty($billresult['data']['data'])) {
                    continue;
                }

              
                $main = $v;
                $main['items'] = $billresult['data']['data'];

                if($main){
                    kernel::single('ediws_event_trigger_jdlvmi')->addRefundinfo($main);

                    //
                }
            

            }

            if ($pageNo * $params['pageSize'] >= $count) {
                break;
            }
            $pageNo ++;
        } while(true);    
        
        return true;
    }


   
    /**
     * refreshReturn
     * @return mixed 返回值
     */
    public function refreshReturn(){

        $vopreturnMdl = app::get('console')->model('vopreturn');
        $itemObj = app::get('console')->model('vopreturn_items');
        $vopreturns = $vopreturnMdl->getlist('*',array('status'=>'0','bill_status'=>'0','shop_type'=>'360buy'));
        foreach($vopreturns as $v){
           
            $id = $v['id'];
           
            $this->updateRefundId($id);

        }

    }


    /**
     * 更新RefundId
     * @param mixed $return_id ID
     * @return mixed 返回值
     */
    public function updateRefundId($return_id){

        $vopreturnMdl = app::get('console')->model('vopreturn');
        $vopreturns = $vopreturnMdl->db_dump(array('id'=>$return_id,'bill_status'=>'0','shop_type'=>'360buy'), '*');

        $itemObj = app::get('console')->model('vopreturn_items');
        $items = $itemObj->getlist('*',array('return_id'=>$return_id));
        $db = kernel::database();
        $saleordid_flag = false;
        foreach($items as $v){
            $refundid = $v['refundid'];

            $originsaleordid = $v['originsaleordid'];
            if($refundid && $originsaleordid) continue;
            $id = $v['id'];
            $transferoutcode = $v['transferoutcode'];
            $partcode = $v['partcode'];

            $refunds = $db->selectrow("SELECT r.refundinfo_id,r.refundid FROM sdb_ediws_refundinfo  as r left join sdb_ediws_refundinfo_items as i on r.refundinfo_id=i.refundinfo_id WHERE r.outno='".$transferoutcode."'  AND i.partcode='".$partcode."'");

            if($refunds){
                $refundid = $refunds['refundid'];

                $db->exec("UPDATE sdb_console_vopreturn_items set refundid='".$refundid."' where id=".$id."");

            }

            //
            if(empty($v['originsaleordid'])){
                $saleordid_flag = true;

            }
        }
       
        if($saleordid_flag){

            $this->updateordid($vopreturns);
        }

        $itemresult = $db->select("select id FROM sdb_console_vopreturn_items WHERE return_id=".$return_id." AND (refundid='' or originsaleordid='')");

        if(!$itemresult){
            $db->exec("UPDATE sdb_console_vopreturn set bill_status='1' where id=".$return_id."");

        }

    }


    /**
     * 更新ordid
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function updateordid($data){

        $db = kernel::database();
        $id = $data['id'];

        $return_sn = $data['return_sn'];
        $shop_id = $data['shop_id'];

        $shops = kernel::single('ediws_event_trigger_jdlvmi')->getShops($shop_id);
        $vendor_code = $shops['config']['ediwsuser'];
        $bill_params = array(
            'providerCode'  =>  $vendor_code,
            'packageId'     =>  $return_sn,
            'pageIndex'     =>  1,
            'pageSize'      =>  50,
            'method'        =>  'edi.request.shippackage.detail',
        );
        
        $billresult= kernel::single('erpapi_router_request')->set('ediws',$shop_id)->shippackage_detail($bill_params);
        if (empty($billresult['data']['data'])) {
             return true;
        }
        $items = $billresult['data']['data'];
        $packagedetaillist = $items['packageDetailList'];
        foreach($packagedetaillist as $iv){
      
            $partcode = $iv['partCode'];
            $originSaleOrdId = $iv['originSaleOrdId'];

            $db->exec("UPDATE sdb_console_vopreturn_items SET originsaleordid='".$originSaleOrdId."' WHERE partcode='".$partcode."' and return_id=".$id."");


        }
    }

    
    /**
     * fixrefundinfo
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function fixrefundinfo($data){

        $start_time = strtotime('-3 days');
        $end_time = strtotime('-4 hours');
        $sdf = array(
            'start_time'    =>$start_time,
            'end_time'      =>$end_time,
            'shop_id'       =>$data['shop_id'],
            'shop_bn'       =>$data['shop_bn'],
            'shop_type'     =>$data['shop_type'],
            'vendorcode'    =>$data['vendorcode'],
        );
        $this->getRefundinfo($sdf);

    }
    
}