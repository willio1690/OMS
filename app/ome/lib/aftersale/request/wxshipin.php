<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request_wxshipin extends ome_aftersale_abstract{


    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->_render = app::get('ome')->render();
    }

   


    /**
     * 售后保存前的扩展
     * 
     * @param array $data
     * @return array
     */
    function pre_save_return($data)
    {
        set_time_limit(0);
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $status = $data['status'];

        // 只有接收申请和拒绝才发起请求
        $allowStatusList = [ '5','3'];
        
        if(!in_array($status,$allowStatusList) && $data['return_type']=='change'){
            return $rs;
        }

        if($status == '3'){
            $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,'6','sync');
        }

        if($status == '5'){
            $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,'9','sync');
        }

        if ($rsp && $rsp['rsp'] == 'fail') {
            $rs['rsp'] = 'fail';
            $rs['msg'] = $rsp['msg'];
        }
        
        return $rs;
    }

   
}
?>
