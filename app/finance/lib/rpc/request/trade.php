<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_rpc_request_trade{

    function __construct(){
        $this->funcObj = kernel::single('finance_func');
    }

    /**
     * 实时获取交易记录
     * @access public
     * @param String $node_id 节点ID
     * @param String $start_time 开始时间
     * @param String $end_time 结束时间
     * @param Int $page 当前请求页码
     * @param Int $limit 每页请求数量
     */
    public function trade_search($node_id,$start_time,$end_time,$page=1,$limit=100){
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');
        if (empty($node_id) || empty($start_time) || empty($end_time)){
            $rs['msg'] = 'node_id,start_time,end_time不能为空';
            return $rs;
        }

        $shop_detail = $this->funcObj->getShopByNodeID($node_id);
        $func = $shop_detail['node_type'].'_trade_search';
        if (method_exists($this,$func)){
            $shop_id = $shop_detail['shop_id'];
            $shop_name = $shop_detail['name'];
            $rs = $this->$func($shop_id,$shop_name,$start_time,$end_time,$page,$limit);
        }else{
            $rs['rsp'] = 'succ';
            $rs['msg'] = 'shop not support';
        }
        return $rs;
    }

    /**
     * 实时获取[支付宝]交易记录
     * @access public
     * @param String $shop_id 店铺ID
     * @param String $shop_name 店铺名称
     * @param String $start_time 开始时间
     * @param String $end_time 结束时间
     * @param Int $page 当前请求页码
     * @param Int $limit 每页请求数量
     */
    public function taobao_trade_search($shop_id,$shop_name,$start_time,$end_time,$page=1,$limit=100){
        return kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_trade_search($start_time,$end_time,$page,$limit,'','');
    }

    /**
     * 获取交易记录任务号
     * @access public
     * @param String $node_id 节点ID
     * @param String $start_time 开始时间
     * @param String $end_time 结束时间
     */
    public function trade_taskid_get($node_id,$start_time,$end_time){
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');
        if (empty($node_id) || empty($start_time) || empty($end_time)){
            $rs['msg'] = 'node_id,start_time,end_time不能为空';
            return $rs;
        }

        $shop_detail = $this->funcObj->getShopByNodeID($node_id);
        $func = $shop_detail['node_type'].'_trade_taskid_get';
        if (method_exists($this,$func)){
            $shop_id = $shop_detail['shop_id'];
            $rs = $this->$func($shop_id,$start_time,$end_time);
        }else{
            $rs['rsp'] = 'succ';
            $rs['msg'] = 'shop not support';
        }
        return $rs;
    }

    /**
     * 获取[支付宝]交易记录任务号
     * @access public
     * @param String $shop_id 店铺ID
     * @param String $start_time 开始时间
     * @param String $end_time 结束时间
     */
    public function taobao_trade_taskid_get($shop_id,$start_time,$end_time){
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');
        if (empty($shop_id) || empty($start_time) || empty($end_time)){
            $rs['msg'] = 'shop_id,start_time,end_time不能为空';
            return $rs;
        }

        $params = array(
            'fields' => 'create_time,type,business_type,balance,in_amount,out_amount,alipay_order_no,merchant_order_no,self_user_id,opt_user_id,memo',
            'start_time' => $start_time,
            'end_time' => $end_time,
            'type' => 'CHARGE,TRANSFER',#只获取在线支付(即交易)和信用卡手续费
        );
        $method = 'store.topats.user.accountreport.get';
        $callback = array();//实时接口不需要设置
        $log_title = '获取[支付宝]交易记录任务号:'.$start_time.'至'.$end_time;
        $write_log['log_type'] = 'store.trade.iostock';
        $write_log['log_title'] = $log_title;
        $result = $this->funcObj->request($method,$params,$callback,$log_title,$shop_id,$time_out=30,$queue=false,$addon='',$write_log,$mode='sync');

        #错误编码：w01160  参数start_time必须为前1个月以内
        $rs['rsp']      = $result['rsp'] == 'success' ? 'succ' : $result['rsp'];
        $rs['msg']      = $result['err_msg'];
        $rs['msg_code'] = $result['err_code'];
        $rs['msg_id']   = $result['msg_id'];
        if (isset($result['data']) && $result['data']){
            $data = $result['data']['alipay_topats_user_accountreport_get_response']['task'];
            $rs['data'] = array(
                'task_id' => $data['task_id'],
                'created' => $data['created'],
            );
        }else{
            $rs['data'] = array();
        }
        return $rs;
    }

    /**
     * 交易记录任务结果获取
     * @access public
     * @param String $node_id 节点ID
     * @param String $task_id 任务号
     */
    public function trade_taskresult_get($node_id,$task_id){
        $rs = array('rsp'=>'fail','msg'=>'手动失败','msg_code'=>'','msg_id'=>'','data'=>'');
        if (empty($node_id) || empty($task_id)){
            $rs['msg'] = 'node_id,task_id不能为空';
            return $rs;
        }

        $shop_detail = $this->funcObj->getShopByNodeID($node_id);
        $func = $shop_detail['node_type'].'_trade_taskresult_get';
        if (method_exists($this,$func)){
            $shop_id = $shop_detail['shop_id'];
            $rs = $this->$func($shop_id,$task_id);
        }else{
            $rs['rsp'] = 'succ';
            $rs['msg'] = 'shop not support';
        }
        return $rs;
    }

    /**
     * 获取[支付宝]交易记录任务结果
     * @access public
     * @param String $shop_id 店铺ID
     * @param String $task_id 任务号
     */
    public function taobao_trade_taskresult_get($shop_id,$task_id){
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');
        if (empty($shop_id) || empty($task_id)){
            $rs['msg'] = 'shop_id,task_id不能为空';
            return $rs;
        }

        $params = array(
            'task_id' => $task_id,
        );
        $method = 'store.topats.result.get';
        $callback = array();//实时接口不需要设置
        $log_title = '获取[支付宝]交易记录任务号('.$task_id.')结果';
        $write_log['log_type'] = 'store.trade.iostock';
        $write_log['log_title'] = $log_title;
        $result = $this->funcObj->request($method,$params,$callback,$log_title,$shop_id,$time_out=30,$queue=false,$addon='',$write_log,$mode='sync');

        if ($result['err_code'] == 'w01001'){#异步任务结果为空
            $result['rsp'] = 'success';
        }elseif (in_array($result['err_code'],array('w01151','w01136'))){
            #w01136:无效的任务号  w01151:任务号过期
            $result['rsp'] = 'success';
            $result['err_code'] = 'expired';
        }
        $rs['rsp']      = $result['rsp'] == 'success' ? 'succ' : $result['rsp'];
        $rs['msg']      = $result['err_msg'];
        $rs['msg_code'] = $result['err_code'];
        $rs['msg_id']   = $result['msg_id'];
        if (isset($result['data']) && $result['data']){
            $data = $result['data']['topats_result_get_response']['task'];
            $rs['data'] = array(
                'download_url' => $data['download_url'],
                'task_id' => $data['task_id'],
                'status' => $data['status'],
                'created' => $data['created']
            );
        }else{
            $rs['data'] = array();
        }
        return $rs;
    }
    

}