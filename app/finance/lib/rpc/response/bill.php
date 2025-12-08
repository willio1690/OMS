<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */



/**
* 对账接口类
*/
class finance_rpc_response_bill extends ome_rpc_response{

    public function bill_list()
    {
        $params = $_POST;
        $res = array('res'=>'fail','msg'=>'','data'=>array());

        $params['start_time'] = strtotime($params['start_time']);

        $params['end_time'] = strtotime($params['end_time']);

        if(!$params['start_time'])
        {
            $res['msg'] = '没有开始时间';
            echo json_encode($res);exit;
        }

        if(!$params['end_time'])
        {
            $res['msg'] = '没有结束时间';
            echo json_encode($res);exit;
        }


        $page = $params['page'] ? intval($params['page']) : 1;

        $offset = ($page-1)*$limit;
        $limit = $params['limit']?$params['limit']:500;


        $mdlBill = app::get('financebase')->model('bill');

        $filter = array('status'=>2,'verification_time|between'=>array($params['start_time'],$params['end_time']));
        
        $count = $mdlBill->count($filter);

        $list = $mdlBill->getList('*',$filter,$offset,$limit);

        $res['data']['limit'] = $limit;
        $res['data']['total'] = $count;
        $res['data']['page'] = $page;
        $res['data']['list'] = $list;

        $res['res'] = 'succ';


        echo json_encode($res);exit;
    }

}