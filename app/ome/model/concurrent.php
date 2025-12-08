<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_mdl_concurrent extends dbeav_model{
    
   function is_pass($id,$type){
       if(@$this->db->exec('INSERT INTO sdb_ome_concurrent(`id`,`type`,`current_time`)VALUES("'.$id.'","'.$type.'","'.time().'")')){
           return true;
       }else{
           return false;
       }
   }
    
}