<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class crm_rpc_request extends ome_rpc_request{
    
    private function rpc_sync($title,$method,$params,$time_out=10,$write_log=array()){
         $rst = app::get('ome')->matrix()->set_realtime(true)
                ->set_timeout($time_out)
                ->call($method, $params);

         $log_id = $this->write_log($title,$method,null,$params,$write_log);

         if($rst){
             $result['result'] = ($rst->rsp == 'succ')?'success':$rst->rsp;
             $result['msg_id'] = $rst->msg_id;
             $result['msg'] = ($rst->res == 'e00090')?'响应超时':$rst->err_msg;
             $result['data'] = json_decode($rst->data,1);
             $this->write_log(null,$method,$log_id,$result,null,true);
         }

         return $result;
    }
    
    //第三方应用 同步接口
    public function call($method,$title,$params, $time_out=10,$write_log=array()){
         
         $res = array('result'=>'fail','msg'=>'节点不存在','data'=>array());

         $node = $this->get_node();

         if($node){
            $Ofunc = kernel::single('ome_rpc_func');
            $app_xml = $Ofunc->app_xml();
            $params['from_api_v'] = $app_xml['api_ver'];
            $params['to_api_v'] = $Ofunc->fetch_shop_api_v($node[0]['node_id']);
            $params['to_node_id'] = $node['node_id'];
            $params['node_type'] = $node['node_type'];
            $rst = $this->rpc_sync($title,$method,$params,$time_out,$write_log);
            if($rst['result'] == 'success'){
                $res['result'] = 'succ';
                $res['data'] = $rst['data'];
                $res['msg'] = $rst['msg'];
            }else{
                $res['msg'] = $rst['msg'];
            }
         }
         
         return $res;
    }

    private function write_log($title = '',$api_name,$log_id = '',$params = array(),$write_log = array(),$append = false){

         $oApi_log = app::get('ome')->model('api_log');
         
         if($append){
             return $oApi_log->update(array('msg_id'=>$params['msg_id'],'msg'=>$params['msg'],'status'=>$params['result']),array('log_id'=>$log_id));
         }else{
             $log_id = $oApi_log->gen_id();    
             $callback = array(
                                'class'   => $write_log['class'],
                                'method'  => $write_log['method'],
                                '2'       => array(
                                    'log_id'  => $log_id,
                                ),
             );

             $oApi_log->write_log($log_id,$title,'ome_rpc_request','rpc_request',array($api_name, $params, $callback),'','request','running','','','api.store.gift.rule',$write_log['order_bn']);
             return $log_id;
         }
    }

    private function get_node(){

        $channelObj = app::get('channel')->model('channel');
        $channel_info = $channelObj->dump(array('channel_type'=>'crm'),'node_id,node_type');

        if ($channel_info['node_id']){
            $node = array(
                'node_id' => $channel_info['node_id'],
                'node_type' => $channel_info['node_type']
            );
        }

        return $node;
    }

	private function msgcod($code){
	    $msglist = array(
			 'x001'=>'参数不完整',
			 'x002'=>'对应的会员信息不存在',
			 'x003'=>'该会员没有设置相应的赠品',
	         'x004'=>'会员等级未设置，赠品获取失败',
		);

		return $msglist[$code];
	}
}