<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
class material_autotask_timer_batchchannelmaterial
{
    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($params, &$error_msg=''){
        set_time_limit(0);
        ignore_user_abort(1);
    
        @ini_set('memory_limit','512M');
        $key = 'material_autotask_timer_batchchannelmaterial';
        $isRun = cachecore::fetch($key);
    
        if($isRun) {
            $error_msg = '正在运行，请稍后！';
            return false;
        }
        cachecore::store($key, 'running', 3600);
        
        //渠道列表
        $channelObj = app::get('wmsmgr')->model('channel');
        $wmsChannelObj = app::get('channel')->model('channel');
        $channel_list = $channelObj->getList('channel_id,wms_id',['node_type'=>'yjdf']);
        
        if ($channel_list) {
            foreach ($channel_list as $k => $v) {
	            $wmsInfo = $wmsChannelObj->dump(['channel_id'=>$v['wms_id']],'crop_config');
	
	            base_kvstore::instance('timer_batchchannelmaterial')->fetch('channel_material_last_exec_time_' . $v['channel_id'],$last_exec_time);
	
	            if ($last_exec_time) {
		            $start_ymdhis = $last_exec_time;
	            }else{
		            $start_ymdhis = time() - 1800;
	            }
	
	            $params = [
		            'scrollId'=>'',
		            'isInit'=>'on',
		            'start_ymdhis'=>date('Y-m-d H:i:s',$start_ymdhis),
		            'end_ymdhis'=>date('Y-m-d H:i:s',time()),
	            ];
	            
	            $crop_config = $wmsInfo['crop_config'];
	            
	            if (!$crop_config['sync_goods']) {
		            continue;
	            }
	            $params['wms_id'] = $v['wms_id'];
                $params['channelId'] = $v['channel_id'];
                $this->batchChannelMaterial($params);
            }
        }
        
        cachecore::delete($key);
        return true;
    }
    
    /**
     * batchChannelMaterial
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function batchChannelMaterial($params) {
        $rs = kernel::single('wmsmgr_sync_material')->syncMaterial($params);
        
        if ($rs['rsp'] == 'succ') {
            if (isset($rs['scrollId'])) {
                $params['scrollId']= $rs['scrollId'];
                $this->batchChannelMaterial($params);
            }
        }
	    base_kvstore::instance('timer_batchchannelmaterial')->store('channel_material_last_exec_time_' . $params['channelId'],time());
    }
}