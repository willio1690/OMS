<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
 
/**
* WMS渠道处理LIb类
*/
class wmsmgr_channel
{
    /**
    * 绑定关系回调
    */
    public function bindCallback($result)
    {
        // 验证签名
        $data = $_POST;
        if (empty($data['certi_ac'])) {
            die('0'); // certi_ac 不存在
        }
        
        $certi_ac = $data['certi_ac'];
        unset($data['certi_ac']);
        $sign = base_certificate::getCertiAC($data);
        
        if ($certi_ac != $sign) {
            die('0'); // 签名错误
        }
        
        $channel_id = $result['channel_id'];
        $nodes = $_POST;
        $status = $nodes['status'];
        $node_id = $nodes['node_id'];
        $node_type = $nodes['node_type'];
        $api_v = $nodes['api_v'];
        $filter = array('channel_id'=>$channel_id);
        
        $Obj_channel = kernel::single('channel_channel');
        $shopdetail = $Obj_channel->dump(array('node_id'=>$node_id), 'node_id');
        if ($status=='bind' and !$shopdetail['node_id']){
            if ($node_id){
                #绑定
                $Obj_channel->bind($node_id,$node_type,$filter);
                
                #更新
                $data = array('api_version'=>$api_v,'addon'=>$nodes);
                $Obj_channel->update($data, $filter);

                die('1');
            }
        }elseif ($status=='unbind'){
            $Obj_channel->unbind($filter);
            die('1');
        }
        die('0');
    }
    
    /**
     * 检查是否Saas系统
     *
     * @return boolean
     */
    public function checkIsSaas()
    {
        if (defined('SERVICE_IN_SAAS') && constant('SERVICE_IN_SAAS')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 判断是否允许添加新渠道
     * 
     * @return bool
     */
    public function checkAddChannel()
    {
        $channelMdl = app::get('wmsmgr')->model('channel');
        
        $isAddChannel = true;
        $isSaas = $this->checkIsSaas();
        
        //已绑定的渠道总数
        $countNum = $channelMdl->count();
        
        //[开普勒]添加京东云交易服务次数限制
        //@todo：京东直营客户不限制服务次数;
        $base_host = kernel::single('base_request')->get_host();
        if(strpos($base_host, 'jdzy') === false && $isSaas){
            $available_nums = 1; //默认允许添加1个渠道
            
            //请求saasMonitor接口获取服务授权次数
            $result = kernel::single('ome_addedservice')->get_service('kepler_channel');
            if(!$result){
                //未购买服务;
            }else{
                $used_times = intval($result['used_times']); //已授权次数
                $available_times = intval($result['available_times']); //可授权次数
                if($available_times >= 1){
                    $available_nums = $available_times;
                }
            }
            
            //是否允许添加新渠道
            if($available_nums <= $countNum){
                $isAddChannel = false;
            }
        }
        
        return $isAddChannel;
    }
    
    /**
     * 更新授权服务已使用次数(Monitor每次固定+1授权次数)
     * 
     * @param string $serviceName
     * @param int $authNum
     * @return bool
     */
    public function updateServiceAuthNums($serviceName, $authNum=1)
    {
        $result = false;
        
        $isSaas = $this->checkIsSaas();
        if($isSaas){
            $result = kernel::single('ome_addedservice')->update_service($serviceName, array('used_times'=>$authNum));
        }
        
        return $result;
    }
}
