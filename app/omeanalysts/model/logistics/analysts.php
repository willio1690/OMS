<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_mdl_logistics_analysts extends dbeav_model {

	function analysts_data($data = null){
        $db = kernel::database();
        
        $data['to'] = $data['to'] + 86400;
        $sqlstr = '';
        if ($data['branch_id']){
        	$sqlstr.= ' AND branch_id='.$data['branch_id'];
        }
        $sql = "select sum(delivery_num) as delivery_num,sum(embrace_num) as embrace_num,sum(sign_num) as sign_num,sum(problem_num) as problem_num,sum(timeout_num) as timeout_num,logi_id from sdb_omeanalysts_logistics_analysts where trace_date >= ".$data['from']." AND trace_date <= ".$data['to']."".$sqlstr." GROUP BY logi_id";

        $rows = $db->select($sql);
        return $rows;
    }
}




?>