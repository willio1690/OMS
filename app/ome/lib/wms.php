<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_wms{

   public static  $wms_status = array(
      
        'ready'    => array('10','20','30'),
        'succ'     =>array('40'),
        
    );



    /**
     * notify
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function notify($delivery_id){

       
       
        ome_delivery_notice::notify($delivery_id);
            
        
    }

    /**
     * 获取PlatformBranchs
     * @param mixed $branch_id ID
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getPlatformBranchs($branch_id,$type){

        $branchs = app::get('ome')->model('branch_relation')->dump(array ('branch_id'=>$branch_id,'type' => $type));
        return $branchs;
    }

    /**
     * 获取Platform
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getPlatform($type){

        $branchs = app::get('ome')->model('branch_relation')->dump(array('type' => $type));
        return $branchs;
    }
}