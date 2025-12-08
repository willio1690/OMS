<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#pda拣货认领处理
class openapi_api_function_v1_pda_pick extends openapi_api_function_v1_pda_abstract {


    private function _check_delivery($delivery_list)
    {
        $delivery_id = array (0); $delivery_list_sdf = array ();
        foreach ($delivery_list as $key => $value) {
            $delivery_id[] = $value['delivery_id'];

            $delivery_list_sdf[$value['delivery_id']] = $value;
        }

        // 查询订单
        $delivery_order = array ();
        $deliveryOrderMdl = app::get('ome')->model('delivery_order');
        foreach ($deliveryOrderMdl->getList('*', array ('delivery_id' => $delivery_id)) as $value) {
            $delivery_order[$value['order_id']] = $value['delivery_id'];
        }

        if ($order_id = array_keys($delivery_order)) {
            $orderMdl   = app::get('ome')->model('orders');
            $pdaPickMdl = app::get('wms')->model('pda_pick');
            foreach ($orderMdl->getList('order_id,pay_status,pause,disabled,process_status,abnormal', array ('order_id' => $order_id)) as $order) {

                if (in_array($order['pay_status'],array('5','6','7')) 
                    || $order['pause'] == 'true' 
                    || $order['disabled'] == 'true' 
                    || $order['process_status'] == 'cancel' 
                    || $order['abnormal'] == 'true') {
                    if ($delivery_order[$order['order_id']]) {
                        $pdaPickMdl->delete(array ('delivery_id' => $delivery_order[$order['order_id']]));
                    }

                }
            }
        }

        return $delivery_list_sdf;
    }

    #拣货认领
    /**
     * 获取Delivery
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getDelivery($params,&$code,&$sub_msg){
    	if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
    		$sub_msg = '未登录或登录过期,请先登录';
    		return false;
    	}
    	if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
    	    $sub_msg = '设备未授权';
    	    return false;
    	}
    	if(empty($params['delivery_bn']) && empty($params['ident'])){
    	    $sub_msg = '发货单号和批次号至少填一个！';
    	    return false;
    	}
    	$pick_delivery_bn = explode(';',trim($params['delivery_bn']));
    	#如果没有传发货单号，则通过批次号先找打发货单号
    	if(empty($pick_delivery_bn[0])){
    	    $print_ident = trim($params['ident']);
    	    $obj_queue = app::get('ome')->model('print_queue_items');
    	    $print_queue_items = $obj_queue->getList('delivery_id',array('ident'=>$print_ident));
    	    if(empty( $print_queue_items)){
    	        $sub_msg = '批次号不存在或已删除！';
    	        return false;
    	    }
    	    $pick_delivery_ids = array_map('current',$print_queue_items);
    	    $filter = array('delivery_id'=> $pick_delivery_ids);
    	}else{
    	    $filter = array('delivery_bn'=>$pick_delivery_bn);
    	}

        // 只取有效订单
        $deliveryFilter = $filter;
        $deliveryFilter['status'] = 0;
        $deliveryFilter['disabled'] = 'false';

        $obj_delivery = app::get('wms')->model('delivery');
        $pick_dly_infos = $obj_delivery->getList("delivery_id,delivery_bn",$deliveryFilter);
        if(empty($pick_dly_infos)){
            $sub_msg = '没有发货单据!'; return false;
        }

        // 过滤掉已经退款的
        $pick_dly_infos = $this->_check_delivery($pick_dly_infos);
        if(empty($pick_dly_infos)){
            $sub_msg = '没有发货单据!'; return false;
        }

    	#只要有一个已拣，全部拦截
    	$records = app::get('wms')->model('pda_pick')->getList("delivery_bn",$filter);
    	if($records){
    		$exist_delivery_bns = array_map('current', $records);
    		$sub_msg = '单号'.implode(';',  $exist_delivery_bns).'已领取过！';
    		return false;
    	}


    	$delivery_data = array();
    	$dly_ids = array();
    	foreach($pick_dly_infos as $v){
    	    $delivery_id = sprintf("%s", $v['delivery_id']);
    		$delivery_data[$delivery_id] = $v['delivery_bn'];
    		$dly_ids[] =  $delivery_id;
    	}
    	#必须保持和打印单据时，一直的类型和顺序
        $dly_ids = $obj_delivery->printOrderByByIds($dly_ids);

    	$queue_data = kernel::single('ome_queue')->fetchPrintQueue($dly_ids);

    	$op_info = kernel::single('ome_func')->getDesktopUser();
    	if($queue_data['items']){
            $pickMdl = app::get('wms')->model('pda_pick');
    		foreach($queue_data['items'] as $id=>$ident){
    			$delivery_bn = $delivery_data[$id];
                $pda_pick_data[$id]['ident']       = $ident;
                $pda_pick_data[$id]['delivery_id'] = $id;
                $pda_pick_data[$id]['delivery_bn'] = $delivery_bn;
                $pda_pick_data[$id]['create_time'] = time();
                $pda_pick_data[$id]['op_id']       = $op_info['op_id'];

                $pickinfo = $pickMdl->db_dump(array ('delivery_id' => $id), 'id');

                if ($pickinfo) {
                    $pickMdl->update($pda_pick_data[$id], array ('id' => $pickinfo['id']));
                } else {
                    $pickMdl->insert($pda_pick_data[$id]);
                }
    		}
    		// $obj_pda_pick = app::get('wms')->model('pda_pick');
    		// $sql = kernel::single('ome_func')->get_insert_sql($obj_pda_pick,$pda_pick_data);
    		// $rs1 = $obj_pda_pick->db->exec($sql);
    		$sql = "update sdb_wms_delivery set pick_status='1'" . ' where delivery_id in ( '.implode(',',$dly_ids).')';
    		$rs2 = $obj_delivery->db->exec($sql);

    		$result = array();
		    foreach($pda_pick_data as $items){
		        $result['items'][] = array('delivery_bn'=>$items['delivery_bn'],'ident'=>$items['ident']);
		    }
		    $result['ident'] = $queue_data['idents'][0];

    		unset($delivery_data);
    	}
    	if($result['items']){
            #更新progress状态
            $status_data['status'] = 'progress';
            app::get('wms')->model('delivery')->update($status_data,array('delivery_id|in'=>$dly_ids, 'status'=>array('ready', 'progress')));
            
            app::get('ome')->model('operation_log')->batch_write_log('delivery_modify@ome', 'pda拣货已领取', time(), array('delivery_id'=>$dly_ids));
    		$result['message'] = 'success';
    	}else{
    		$result['message'] = 'fail';
    	}
    	return $result;
    }
    #关联篮子号和发货单号
    function bindbox($params,&$code,&$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $pick_box_items = json_decode($params['items'],true);
        if(empty($pick_box_items)){
            $sub_msg = '篮子号数据有误';
            return false;
        }
        $delivery_box_relation = array();
        foreach($pick_box_items as $val){
            $delivery_bn = $val['delivery_bn'];
            $basket_no  = $val['basket_no'];
            #有一个数据有误，不再继续
            if(empty( $delivery_bn) || empty($basket_no)){
                $sub_msg = '明细数据有误';
                return false;
            }
            $delivery_box_relation[$delivery_bn] = $basket_no;
        }
        $delivery_bns = array_keys($delivery_box_relation);
        $obj_pda_pick = app::get('wms')->model('pda_pick');
        $pda_pick_data = $obj_pda_pick->getList("id,delivery_id,delivery_bn,box", array('delivery_bn'=>$delivery_bns));
        if(empty($pda_pick_data)){
            $sub_msg = '找不到拣货单据:';
            return false;
        }
        $exist_box_deliveryBns = $up_data = array();
        $dly_ids = array();
        foreach($pda_pick_data as $val){
            #检查发货单是否已经绑定过篮子号
            if(!empty($val['box'])){
                $exist_box_deliveryBns[] = $val['delivery_bn'];
            }else{
                $dly_ids[] = $val['delivery_id'];
                $pick_id = $val['id'];
                $up_data[$pick_id]['id'] = $pick_id;
                $up_data[$pick_id]['box'] = $delivery_box_relation[$val['delivery_bn']];
            }
        }
        if($exist_box_deliveryBns){
            $sub_msg = '存在已绑定篮子号发货单:'.implode(';', $exist_box_deliveryBns);
            return false;
        }
        #以上数据监测完毕，开始存入数据
        foreach($up_data as $data){
            $obj_pda_pick->save($data);
        }
        app::get('ome')->model('operation_log')->batch_write_log('delivery_modify@ome', 'pda拣货已绑定篮子号', time(), array('delivery_id'=>$dly_ids));
        $result['message'] = 'success';
        return $result;
    }

    /**
     * 接口:生成备货单信息
     * 
     * @return void
     * @author 
     * */
    public function getPickinList($params, &$code, &$sub_msg)
    {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录'; return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';return false;
        }
        $response = array('list' => array());
        // 获取批次号查询
        $printqModel = app::get('ome')->model("print_queue");
        $delivery_id = $printqModel->findQueueDeliveryId($params['batch_no'],'delivery_id');

        if(!$delivery_id) return $response;
        $delivery_id = explode(',', $delivery_id);

        $delivery_status_info = app::get('wms')->model('delivery')->getList('process_status, pick_status',array('delivery_id'=>$delivery_id));

        if (empty($delivery_status_info)) {
            $sub_msg ='单据不存在！';
            return false;
        }

        // 已拣货的，不能再获取
        if( $delivery_status_info[0][pick_status] == '2' ){
            $sub_msg ='单据已拣货完成，不能再获取！';
            return false;
        }
        // 已校验的，不能再获取
        if( ($delivery_status_info[0][process_status] & 2) == 2 ){
            $sub_msg ='单据已校验完成，不能再获取！';
            return false;
        }

        $print_data = kernel::single('wms_delivery_print')->getPrintDatas(array('filter'=>array('delivery_id'=>$delivery_id,'type'=>'normal')),'stock','pda',true,$msg);

        if ($print_data === false) { 
            if (is_string($msg)) {
                $sub_msg = $msg;
            } else if (is_array($msg)) {
                if ($msg['error_msg']) $sub_msg = $msg['error_msg'];
                if ($msg['warn_msg'])  $sub_msg = $msg['warn_msg'];
            }

            return false; 
        }

        // 盒子号，会造成快递单号与盒子号不匹配，先去掉
        // $pdaPickList = array ();
        // $pdaPickMdl = app::get('wms')->model('pda_pick');
        // foreach ($pdaPickMdl->getList('*', array ('delivery_id' => $delivery_id)) as $key => $value) {
        //     $pdaPickList[$value['delivery_id']] = $value;
        // }
        // foreach ($print_data['identInfo']['ids'] as $key => $value) {
        //     if ($pdaPickList[$key]) $print_data['identInfo']['ids'][$key] = $pdaPickList[$key]['box'];
        // }


        $PrintStockLib = kernel::single('wms_delivery_print_stock');
        $format_data = kernel::single('wms_delivery_print_stock')->format($print_data, '',$errmsg);
        foreach ($format_data['rows'] as $key => $value) {
            $value['name'] = $this->charFilter($value['name']);

            $value['product_name'] = $value['name'];

            $response['list'][$key] = $value;
        }

        $response['delivery_total_nums']     = $format_data['delivery_total_nums'];
        $response['delivery_total_price']    = $format_data['delivery_total_price'];
        $response['delivery_discount_price'] = $format_data['delivery_discount_price'];
        $response['picking_list_price']      = $format_data['picking_list_price'];
        $response['shop_name']               = $format_data['shop_name'];
        $response['branch_memo']             = $format_data['branch_memo'];
        $response['memo']                    = $format_data['memo'];
        $response['mark_text']               = $format_data['mark_text'];

        return $response;
    }
    #获取备货单
        /**
     * 获取StockList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getStockList($params, &$code, &$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录'; return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        #获取操作员管辖所有自建仓库
        $all_owner_branch = $this->get_all_branchs('1');
        if(empty($all_owner_branch)){
            $sub_msg = '操作员无仓库管辖权限,请到OMS设置';
            return false;
        }

        $branch_id = array(0);
        foreach ( $all_owner_branch as $k => $branch){
            $branch_id[] = $branch['branch_id'];
        }

        $pageNo   = intval($params['page_no']);
        $pageSize = intval($params['page_size']);
        $offset   = $pageNo > 0 ? $pageNo - 1 : 0;
        $limit    = $pageSize && $pageSize < 100 ? $pageSize : 100;

        $sql = 'SELECT count(distinct(q.ident)) AS _c FROM sdb_wms_delivery AS d, sdb_ome_print_queue_items AS q 
                WHERE d.delivery_id = q.delivery_id 
                AND d.status = "0"
                AND d.type="normal"
                AND d.process_status IN ("0", "1")
                AND d.pick_status = "0"
                AND d.branch_id IN ('.implode(',',$branch_id).')';

        $countrow = kernel::database()->selectrow($sql);
        if (!$countrow) return array('list'=>array(), 'count' => 0);


        $sql = 'SELECT q.ident, d.branch_id,count(d.delivery_id) AS _c FROM sdb_wms_delivery AS d, sdb_ome_print_queue_items AS q 
                WHERE d.delivery_id = q.delivery_id 
                AND d.status = "0"
                AND d.type="normal"
                AND d.process_status IN ("0", "1")
                AND d.pick_status = "0"
                AND d.branch_id IN('.implode(',', $branch_id).') 
                GROUP BY q.ident';
        
        $data = kernel::database()->selectlimit($sql, $limit, $offset);

        if (!$data) return array('list'=>array(), 'count' => 0);

        $list = array();
        foreach ($data as $value) {
            $list[] = array(
                'delivery_ident' => $value['ident'],
                'delivery_nums' => $value['_c'],
                'branch_id' => $value['branch_id'],
            );
        }

        return array('list' => $list, 'count' => $countrow['_c']);
    }

}