<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 电子面单请求类
 */
class erpapi_logistics_request_electron extends erpapi_logistics_request_abstract
{
    protected $title;
    protected $timeOut = 10;
    protected $primaryBn = '';
    protected $cacheLimit = 5000;
    protected $directNum = 1;
    protected $everyNum = 100;

    //电子面单请求统一出口
    final protected function requestCall($method,$params,$callback=array(),$orign_params=array(),$gateway='')
    {
        if(!$this->title) {
            $this->title = $this->__channelObj->channel['name'] . $this->__channelObj->channel['channel_type'] . '获取电子面单';
        }
        // 御城河-运单直连
        if (!$callback && $orign_params['order_bns']) {
            $hchsafe = array(
                'to_node_id' => $this->__configObj->get_to_node_id(),
                'tradeIds'   => $orign_params['order_bns'],
            );

            kernel::single('base_hchsafe')->order_push_log($hchsafe);
        }
        $this->__caller->writeFailLog = false;
        
        return $this->__caller->call($method,$params,$callback,$this->title, $this->timeOut, $this->primaryBn,true,$gateway);
    }

    //回收电子面单 默认都做作废处理
    /**
     * recycleWaybill
     * @param mixed $waybillNumber waybillNumber
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回值
     */

    public function recycleWaybill($waybillNumber,$delivery_bn = '') {
        app::get('logisticsmanager')->model('waybill')->update(array('status'=>2,'create_time'=>time()),array('waybill_number'=>$waybillNumber));
    }

    protected function bufferBackToRet($rlt) {//各自实现
        return array();
    }

    //缓存池异步回调方法
    /**
     * bufferRequestCallBack
     * @param mixed $result result
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function bufferRequestCallBack($result, $callback_params){
        $rlt = $this->callback($result, $callback_params);
        $arrWaybill = $this->bufferBackToRet($rlt);
        if(empty($arrWaybill)) {
            return array('rsp'=>'fail', 'msg'=>'数据处理失败');
        }
        $objChannel = app::get('logisticsmanager')->model('channel');
        $channel = $objChannel->dump(array('channel_id'=>$callback_params['channel_id'], 'status'=>'true'));
        if(empty($channel)) {
            return array('rsp'=>'fail', 'msg'=>'电子面单来源缺失或停用');
        }
        $waybillModel = app::get('logisticsmanager')->model('waybill');
        $data = array();
        foreach($arrWaybill as $val) {
            $val = trim($val);
            $row = $waybillModel->dump(array('channel_id'=>$channel['channel_id'], 'waybill_number'=>$val), 'id');
            if(!$row) {
                $data[] = array(
                    'waybill_number' => $val,
                    'channel_id' => $channel['channel_id'],
                    'logistics_code' => $channel['logistics_code'],
                    'status' => 0,
                    'create_time' => time(),
                );
            }
        }
        if(empty($data)) {
            return array('rsp'=>'fail', 'msg'=>'没有可用的单号');
        }
        $insertSql = ome_func::get_insert_sql($waybillModel, $data);
        $ret = $waybillModel->db->exec($insertSql);
        if($ret) {
            return array('rsp'=>'succ', 'msg'=>'数据写入成功');
        } else {
            return array('rsp'=>'fail', 'msg'=>'数据写入失败');
        }
    }

    //回传物流公司请求统一接口
    final protected function deliveryCall($method,$logData,$params,$gateway='',$isAsync=true){
        if(!$this->title) {
            $this->title = $this->__channelObj->channel['name'] . $this->__channelObj->channel['channel_type'] . '电子面单物流回传';
        }
        $this->__caller->writeFailLog = false;

        $logData['obj_bn'] = $this->primaryBn;
        if($isAsync){
            $callback = array(
                'class' => get_class($this),
                'method' => 'deliveryBack',
                'params' => $logData
            );
        }
        
        $ret = $this->__caller->call($method, $params, $callback, $this->title, $this->timeOut, $this->primaryBn,true,$gateway);
        if(empty($callback)) {
            return $this->deliveryBack($ret, $logData);
        }
        $this->logisticsLog($logData['logi_no'], $logData['delivery_id'], $params);
        
        return true;
        
        
    }

    /**
     * deliveryBack
     * @param mixed $result result
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function deliveryBack($result, $callback_params) {

        if (in_array($callback_params['channel_type'],array('ems','360buy'))){
            $rlt = $this->callback($result, $callback_params);
            
            $logisticsLogObj = app::get('logisticsmanager')->model('logistics_log');
            if ($rlt['rsp'] == 'succ'){
                $logisticsLogObj->update(array('status'=>'success'),array('delivery_id'=>$callback_params['delivery_id'],'logi_no'=>$callback_params['logi_no']));
            } else {
                $logisticsLogObj->update(array('status'=>'fail'),array('delivery_id'=>$callback_params['delivery_id'],'logi_no'=>$callback_params['logi_no']));
            }
        }
        
        return true;
    }

    protected function logisticsLog($logiNo, $delivery_id, $params) {
        if (in_array($this->__channelObj->channel['channel_type'],array('ems','360buy'))){
            //回填日志只针对EMS 和京东记录便于重试
            $logisticsLogObj = app::get('logisticsmanager')->model('logistics_log');
            $row = $logisticsLogObj->dump(array('logi_no'=>$logiNo, 'delivery_id'=>$delivery_id), 'log_id, retry');
            if($row['log_id']) {
                return $logisticsLogObj->update(array('retry'=>$row['retry']+1), array('log_id'=>$row['log_id']));
            }
            $logSdf = array(
                'logi_no' => $logiNo,
                'delivery_id' => $delivery_id,
                'channel_id' => $this->__channelObj->channel['channel_id'],
                'channel_type'=>$this->__channelObj->channel['channel_type'],
                'status' => 'running',
                'create_time' => time(),
                'params' => $params,
            );
            return $logisticsLogObj->insert($logSdf);
        }else{
            return true;
        }
        
    }

    //是否直辖市
    /**
     * isMunicipality
     * @param mixed $province province
     * @return mixed 返回值
     */
    public function isMunicipality($province) {
        $municipality = array('北京市', '上海市', '天津市', '重庆市');
        $status = false;
        foreach ($municipality as $zxs) {
            if (substr($zxs, 0, strlen($province)) == $province) {
                $status = true;
                break;
            }
        }
        return $status;
    }

    #过滤特殊字符
    /**
     * charFilter
     * @param mixed $str str
     * @return mixed 返回值
     */
    public function charFilter($str){
        if(strpos($str, '@hash')) {
            return $str;
        }
        $str = str_replace(array('&#34;','“','&quot;','&quot',), '”', $str);
        $str = str_replace(array("<",">","&","'",'"','','+','\\'),'',$str);
        return $str;
    }

    /**
     * 处理直连返回结果
     * 
     */
    public function directDataProcess($data){
        $channel = $this->__channelObj->channel;

        $objWaybill = app::get('logisticsmanager')->model('waybill');
        $waybillExtendModel = app::get('logisticsmanager')->model('waybill_extend');
        foreach ($data as $val){

            if($val['succ']) {
                $logi_no = trim($val['logi_no']);
                $arrWaybill = $objWaybill->dump(array('channel_id' => $channel['channel_id'], 'waybill_number' => $logi_no),'id,status');
                if (!$arrWaybill) {
                    $arrWaybill = array(
                        'waybill_number' => $logi_no,
                        'channel_id'     => $channel['channel_id'],
                        'logistics_code' => $channel['logistics_code'],
                        'status'         => 1,
                        'create_time'    => time(),
                    );
                    $ret = $objWaybill->insert($arrWaybill);
                
                } elseif ($arrWaybill['status'] == '2') {
                    $objWaybill->update(array('status'=>'1'),array('id'=>$arrWaybill['id']));
                } 
                if(!$val['noWayBillExtend']) {
                    $waybillExtend = array(
                        'waybill_id' => $arrWaybill['id'],
                        'mailno_barcode' => $val['mailno_barcode'],
                        'qrcode' => $val['qrcode'],
                        'sort_code' => $val['sort_code'],
                        'position' => $val['position']?:'',
                        'position_no' => $val['position_no']?:'',
                        'package_wdjc' => $val['package_wdjc'],
                        'package_wd' => $val['package_wd'],
                        'print_config' => $val['print_config'],
                        'json_packet' => $val['json_packet'],
                    );
                    
                    $filter = array('waybill_id' => $waybillExtend['waybill_id']);
                    if (!$waybillExtendModel->dump($filter)) {
                        $ret = $waybillExtendModel->insert($waybillExtend);
                    } else {
                        $ret = $waybillExtendModel->update($waybillExtend, $filter);
                    }
                    
                }
            }
        }
        
    }

    /**
     * bind
     * @return mixed 返回值
     */
    public function bind() {
        $params = array(
            'app' => 'app.applyNodeBind',
            'node_id' => base_shopnode::node_id('ome'),
            'from_certi_id' => base_certificate::certi_id(),
            'callback' => kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>$this->node_type)),
            'sess_callback' => urlencode(kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>$this->node_type))),
            'api_url' => kernel::base_url(1).kernel::url_prefix().'/api',
            'node_type' => $this->node_type,
            'to_node' => $this->to_node,
            'shop_name' => $this->shop_name,
        );
        if ($api_version = $this->getBindApiVersion()) {
            $params['api_version'] = $api_version;
        }
        $params['certi_ac'] = $this->genBindSign($params);
        $api_url = MATRIX_RELATION_URL . 'api.php';
        $headers = array(
            'Connection' => 5,
        );
        
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(5)->post($api_url, $params, $headers);
        
        $response = json_decode($response,true);
       
        $status = false;
        if($response['res']=='succ' || $response['msg']['errorDescription'] == '绑定关系已存在,不需要重复绑定') {
            $status = true;
        }
        return $status;
    }

    /**
     * 获取BindApiVersion
     * @return mixed 返回结果
     */
    public function getBindApiVersion(){}

    public function genBindSign($params) {
        return base_certificate::getCertiAC($params);
    }

    protected function _formate_receiver_city($receiver_city)
    {
        $zhixiashi = array('北京','上海','天津','重庆');
        $zizhiqu = array('内蒙古','宁夏回族','新疆维吾尔','西藏','广西壮族');

        if (in_array($receiver_city,$zhixiashi)) {
           $receiver_city = $receiver_city.'市';
        }else if (in_array($receiver_city,$zizhiqu)) {
            $receiver_city = $receiver_city.'自治区';
        }else if($receiver_city == '广西'){
            $receiver_city = $receiver_city.'壮族自治区';
        }else if($receiver_city == '宁夏'){
            $receiver_city = $receiver_city.'回族自治区';

        }else if($receiver_city == '新疆'){
            $receiver_city = $receiver_city.'维吾尔自治区';
        }elseif(!preg_match('/(.*?)省/',$receiver_city)){
            $receiver_city = $receiver_city.'省';
        }
        return $receiver_city;
    }

    //获取关联信息
    /**
     * 获取LogisticRelate
     * @param mixed $logiId ID
     * @return mixed 返回结果
     */
    public function getLogisticRelate($logiId){
        $dlyCorpRes    = app::get('ome')->model('dly_corp')->dump(array('corp_id'=>$logiId),'channel_id,prt_tmpl_id');
        $logChannelRes = app::get('logisticsmanager')->model('channel')->dump(array('channel_id'=>$dlyCorpRes['channel_id']),'shop_id');
        list($jdbusinesscode,$shop_id) = explode('|||',$logChannelRes['shop_id']);
        return array('prt_tmpl_id' => $dlyCorpRes['prt_tmpl_id'],'jd_businesscode' => $jdbusinesscode);
    }

    //是否为京东打印控件
    /**
     * isJdPrintControl
     * @param mixed $corp_id ID
     * @return mixed 返回值
     */
    public function isJdPrintControl($corp_id){
        $corp = app::get('ome')->model('dly_corp')->db_dump($corp_id, 'prt_tmpl_id, channel_id');

        if ($corp['channel_id'] != $this->__channelObj->channel['channel_id']) {
            $prtTmpl = app::get('ome')->model('dly_corp_channel')->db_dump(
                array('channel_id' => $this->__channelObj->channel['channel_id'], 'corp_id' => $corp_id), 'prt_tmpl_id');

            if ($prtTmpl) {
                $corp['prt_tmpl_id'] = $prtTmpl['prt_tmpl_id'];
            }
        }

        $tmpRes = app::get('logisticsmanager')->model('express_template')->dump(array('template_id'=>$corp['prt_tmpl_id']),'control_type');

        return $tmpRes['control_type'] == 'jd'?true:false;
    }
    
    //获取京东打印数据
    /**
     * 获取PrintData
     * @param mixed $deliveryIdList ID
     * @param mixed $delivery delivery
     * @param mixed $jdBusinesscode jdBusinesscode
     * @param mixed $jpwj jpwj
     * @return mixed 返回结果
     */
    public function getPrintData($deliveryIdList,$delivery,$jdBusinesscode,$jpwj='jp'){
        $this->title = '获取京东打印数据';
        $orderNo                   = $delivery['order_bns'][0];
        $mapCode = [];
        $params = [];
        if($jpwj == 'jp') {
            $mapCode['ewCustomerCode'] = $jdBusinesscode; 

            $params['cp_code'] = 'JD';
        } 

        if($jpwj == 'wj') {
            $jdalpha = explode('|||',$this->__channelObj->channel['shop_id']);
            $mapCode['eCustomerCode'] = $jdalpha[2];  
        }

        $shopRes                   = app::get('ome')->model('shop')->dump(array('shop_id'=>$delivery['shop_id']),'tbbusiness_type');
        $popFlag                   = $shopRes['tbbusiness_type'] == 'SOP'?1:0;

        $waybillInfos = array();
        foreach ($deliveryIdList as $key => $logiNo) {
            $waybillInfos[$key]['orderNo']       = $orderNo;
            $waybillInfos[$key]['popFlag']       = $popFlag;
            $waybillInfos[$key]['wayBillCode']   = $logiNo;
            $waybillInfos[$key]['jdWayBillCode'] = $logiNo;
        }

        $params['map_code']        = json_encode($mapCode); 
        $params['waybill_infos']   = json_encode($waybillInfos); 
        $params['object_id']       = substr(time(), 4).uniqid();
        $back                      = $this->requestCall(STORE_USER_DEFINE_AREA, $params);
       
        $printData = '';
        if($back['rsp'] == 'succ'){
             $data      = json_decode($back['data'],true);
             $printData = $data['jingdong_printing_printData_pullData_responce']['returnType']['prePrintDatas'][0]['perPrintData']?:'';
        }
        return [$printData, $back['res']];  
    }

    /**
     * 获取EncryptPrintData
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getEncryptPrintData($sdf) {return $this->error('不支持获取打印数据');}
    
    /**
     * 替换使用平台订单号，进行取号电子面单
     * 
     * @param $deliveryInfo
     * @return void
     */
    public function _formatPlatformOrderBn($deliveryInfo)
    {
        $orderMdl = app::get('ome')->model('orders');
        
        //order_bns
        $orderBns = $deliveryInfo['order_bns'];
        
        //order_bn这个下标应该是不存在的
        if($deliveryInfo['order_bn']){
            $orderBns = array_merge($orderBns, array($deliveryInfo['order_bn']));
        }
        
        //unique
        $orderBns = array_unique(array_filter($orderBns));
        
        //orders
        $yjdfOrders = $orderMdl->getList('order_id,order_bn,platform_order_bn', array('order_bn'=>$orderBns));
        $yjdfOrders = array_column($yjdfOrders, null, 'order_bn');
        if(empty($yjdfOrders)){
            return array();
        }
        
        //format
        $dlyOrderBns = $deliveryInfo['order_bns'];
        $dly_order_bn = $deliveryInfo['order_bn'];
        foreach ($dlyOrderBns as $key => $order_bn)
        {
            if(isset($yjdfOrders[$order_bn]) && $yjdfOrders[$order_bn]['platform_order_bn']){
                $dlyOrderBns[$key] = $yjdfOrders[$order_bn]['platform_order_bn'];
                
                //order_bn
                if($dly_order_bn && $dly_order_bn == $order_bn){
                    $dly_order_bn = $yjdfOrders[$order_bn]['platform_order_bn'];
                }
            }
        }
        
        return array('order_bns'=>$dlyOrderBns, 'order_bn'=>$dly_order_bn);
    }
}
