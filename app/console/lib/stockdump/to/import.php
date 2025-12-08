<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_stockdump_to_import {

    function run(&$cursor_id, $params, &$errmsg= '')
    {

        $stockObj = app::get('console')->model('stockdump');
        $data = $params['sdfdata'];
        
        $options = array(
            'op_name'=>$data['op_name'],
            'from_branch_id'=>$data['from_branch_id'],
            'to_branch_id'=>$data['to_branch_id'],
            'memo'=>$data['memo'],
        );

        $appro_data = [];
        $result = $stockObj->to_savestore($data['items'],$options, $appro_data, $errmsg);

        if ($result) {
            $errmsg = null;

            kernel::single('console_iostockdata')->notify_stockdump($result['stockdump_id'],'create');
        }
        return false;
    }
}
