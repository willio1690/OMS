<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_organization extends erpapi_store_response_abstract
{
    

    /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params){
        $filter = array();

     
        $filter['last_modify|between'] = array(strtotime($params['start_time']), strtotime($params['end_time']));
        $page_no                       = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit                         = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 40 : intval($params['page_size']);

        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }


        $data = array(
            'filter'    =>  $filter,
            'offset'    =>  $offset,
            'limit'     =>  $limit,

        );
        return $data;
    }
}

?>