<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_mdl_delivery_extension extends dbeav_model{

    
    
    function create($data){
        
        $delivery_bn = $data['delivery_bn'];
        $original_delivery_bn = $data['original_delivery_bn'];
        $extension = $this->dump(array('delivery_bn'=>$data['delivery_bn']));

        if(!$extension){
            $SQL = "INSERT INTO sdb_console_delivery_extension(delivery_bn,original_delivery_bn) VALUES('$delivery_bn','$original_delivery_bn')";

            $this->db->exec($SQL);
        }
    }

}