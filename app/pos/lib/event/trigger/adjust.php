<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_event_trigger_adjust
{
    /**
     * 
     * 调整单财务审核
     * @param 
     */
    public function check($adjust_id)
    {

       
        $adjustMdl = app::get('console')->model('adjust'); 

        $adjusts = $adjustMdl->dump(array('id'=>$adjust_id),'adjust_bn,branch_id');
        $store_id = kernel::single('ome_branch')->isStoreBranch($adjusts['branch_id']);
        $params = array(
            'adjust_bn'      =>  $adjusts['adjust_bn'],
            
        );

        $channel_type = 'store';
        $channel_id = $store_id;

        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->adjust_check($params);

        app::get('ome')->model('operation_log')->write_log('adjust@console',$adjust_id,'审核库存调整单,返回'.serialize($result));
        if($result['rsp'] == 'succ'){
            $updateData = array(
                'sync_status'=>'1',

            );
            $rs = [true,'成功'];
        }else{
            $updateData = array(
                'sync_status'=>'2',

            );
            $rs = [false,'失败'];
        }
        //保存日志
        return $rs;
    }
    
  
    /**
     * cancel
     * @param mixed $adjust_id ID
     * @return mixed 返回值
     */
    public function cancel($adjust_id){
        $adjustMdl = app::get('console')->model('adjust'); 

        $adjusts = $adjustMdl->dump(array('id'=>$adjust_id),'adjust_bn,branch_id');
        $store_id = kernel::single('ome_branch')->isStoreBranch($adjusts['branch_id']);
        $params = array(
            'adjust_bn'      =>  $adjusts['adjust_bn'],
            
        );

        $channel_type = 'store';
        $channel_id = $store_id;
      
        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->adjust_cancel($params);

        app::get('ome')->model('operation_log')->write_log('adjust@console',$adjust_id,'拒绝库存调整单,返回'.serialize($result));
        if($result['rsp'] == 'succ'){
            $updateData = array(
                'sync_status'=>'1',

            );
            $rs = [true,'成功'];
        }else{
            $updateData = array(
                'sync_status'=>'2',

            );
            $rs = [false,'失败'];
        }
        //保存日志
        return $rs;
    }
}
