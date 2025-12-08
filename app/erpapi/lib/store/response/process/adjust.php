<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_adjust
{
    

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){

        list($rs, $rsData) = kernel::single('console_adjust')->dealSave($params);


        if(!$rs) {
            $result = array('rsp' => 'fail', 'msg' => '调账失败'.$rsData['msg']);
        }else{
            $result = array('rsp' => 'succ', 'msg' => '调账成功');
        }
        return $result;

    }

}

?>