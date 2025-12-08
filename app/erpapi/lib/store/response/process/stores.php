<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_stores
{
    /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params){

        $filter = $params['filter'];

        $offset = $params['offset'];
        $limit = $params['limit'];
        $storeMdl = app::get('o2o')->model('store');

        $count = $storeMdl->count($filter);

        $rows = $storeMdl->getList('*', $filter, $offset, $limit);

        $lists = [];

        foreach ($rows as $row) {
            $area = $row['area'] ? explode(':',$row['area']) : '';


           

            $store = [
                'store_bn'       => $row['store_bn'],
                'store_name'     => $row['name'],
                'area'           => $area[1], 
                'addr'           => $row['addr'],  
                'open_hours'     => $row['open_hours'],
                'addr'           => $row['addr'],
                'zip'            => $row['zip'],
                'contacter'      => $row['contacter'],
                'mobile'         => $row['mobile'],
                'tel'            => $row['tel'],
                'status'         => $row['status'],
                'store_type'     => $row['store_type'],
                'store_mode'     => $row['store_mode'],
           
               
            ];

            $lists[] = $store;
        }

    
        $rs = array('rsp'=>'succ','data'=>array('lists'=>$lists,'count'=>$count));
        return $rs;

    }

    
}

?>