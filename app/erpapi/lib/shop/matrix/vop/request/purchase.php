<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会JIT相关接口
 */
class erpapi_shop_matrix_vop_request_purchase extends erpapi_shop_request_purchase 
{
    /**
     * 获取ReturnInfo
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */

    public function getReturnInfo($sdf)
    {
        $shop_id      = $this->__channelObj->channel['shop_id'];
        $param = [
            'warehouse' => $sdf['warehouse'],
            'st_create_time' => $sdf['start_date'],
            'ed_create_time' => $sdf['end_date'],
            'page' => $sdf['page_no'],
            'limit' => $sdf['page_size'],
        ];
        //组织参数
        $vop_param    = $this->_format_api_params('getReturnInfo', 1, $param);
        $vop_param['vop_type'] = 'vreturn';
        
        $title        = '同步退供单';
        $primary_bn   = 'vopreturn';
        
        $rsp    = $this->__caller->call(SHOP_COMMONS_VOP_JIT, $vop_param, array(), $title, 10, $primary_bn);

        //数据处理
        if($rsp['data']){
            $data    = @json_decode($rsp['data'], 1);
            if (is_array($data) && $data['msg']) {
                $data['msg']  = json_decode($data['msg'],1);
                $rsp['data']  = $data['msg']['result']['returnInfos'];
                $rsp['total'] = $data['msg']['result']['total'];
            }
        }
        
        return $rsp;
    }

    /**
     * 获取ReturnDetail
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getReturnDetail($sdf)
    {
        $shop_id      = $this->__channelObj->channel['shop_id'];
        $param = [
            'warehouse' => $sdf['warehouse'],
            'return_sn' => $sdf['return_sn'],
        ];
        //组织参数
        $vop_param    = $this->_format_api_params('getReturnDetail', 1, $param);
        $vop_param['vop_type'] = 'vreturn';
        
        $title        = '同步退供单详情';
        $primary_bn   = $sdf['return_sn'];
        
        $rsp    = $this->__caller->call(SHOP_COMMONS_VOP_JIT, $vop_param, array(), $title, 10, $primary_bn);
        


        if($rsp['data'])
        {
            $data    = json_decode($rsp['data'], 1);
            if ($data['msg']) {
                $data['msg']  = json_decode($data['msg'],1);
                $rsp['data']  = $data['msg']['result']['returnDeliveryInfos'][0];
                $rsp['total'] = $data['msg']['result']['total'];
            }
        }
        
        return $rsp;
    }

    /**
     * returnConfirmBySn
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function returnConfirmBySn($sdf)
    {
        $shop_id      = $this->__channelObj->channel['shop_id'];
        $param = [
            'return_sn_list' => json_encode([$sdf['return_sn']]),
        ];
        //组织参数
        $vop_param    = $this->_format_api_params('returnConfirmBySn', 0, $param);
        
        $title        = '退供单供应商确认';
        $primary_bn   = $sdf['return_sn'];
        
        $rsp    = $this->__caller->call(SHOP_COMMONS_VOP_JIT, $vop_param, array(), $title, 10, $primary_bn);
        
        return $rsp;
    }

    /**
     * 获取Download
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getDownload($sdf){
        $param = [
            'file_id'        => $sdf['file_id'],
        ];
        
        $title        = 'VOP下载文件';
        $primary_bn   = 'vopdownload';
        
        $rsp    = $this->__caller->call(SHOP_VOP_DOWNLOAD, $param, array(), $title, 10, $primary_bn);

        //数据处理
        if($rsp['data'])
        {
            $data    = json_decode($rsp['data'], 1);
            if ($data['msg']) {
                $data['msg']  = json_decode($data['msg'],1);
                $rsp['data']  = $data['msg'];
            }
        }
        return $rsp;
    }
    
    /**
     * 获取唯品会销售单列表
     * API文档：https://vop.vip.com/home#/api/method/detail/vipapis.inventory.InventoryService-1.0.0/getInventoryOccupiedOrders
     * 
     * @param $sdf
     * @return array
     */
    public function getInventoryOccupiedOrders($sdf)
    {
        $title        = '获取唯品会销售单列表';
        $primary_bn   = 'getInventoryOccupiedOrders'. date('md', time());
        
        //page
        $page = ($sdf['page'] ? $sdf['page'] : 1);
        
        //page_size
        $page_size = ($sdf['page_size'] ? $sdf['page_size'] : 50);
        
        //params
        $param = [
            'start_time' =>  $sdf['start_time'], // 时间戳(唯品会是：st_query_time)
            'end_time' =>  $sdf['end_time'], // 时间戳(唯品会是：et_query_time)
            'page_no' => $page, //矩阵字段名是：page_no(唯品会是：page)
            'page_size' => $page_size, //矩阵字段名是：page_size(唯品会是：limit)
        ];
        
        //request
        $rsp = $this->__caller->call(SHOP_VOP_INVENTORY, $param, array(), $title, 10, $primary_bn);
        if($rsp['rsp'] == 'succ'){
            $rsp = $this->getInventoryOccupiedOrders_callback($rsp);
        }
        
        return $rsp;
    }
    
    /**
     * [格式化]唯品会平台已经成交的销售单数据
     * 
     * @param $response
     * @param $callback_params
     * @return array
     */
    public function getInventoryOccupiedOrders_callback($response, $callback_params=NULL)
    {
        $rsp = $response['rsp'];
        $total = 0;
        $has_next = false;
        
        //format
        $occupied_orders = array();
        if($rsp == 'succ'){
            //json
            if(is_array($response['data'])){
                $data = $response['data'];
            }else{
                $data = json_decode($response['data'], true);
            }
            
            //content
            if(is_array($data['msg'])){
                $data = $data['msg'];
            }else{
                $data = json_decode($data['msg'], true);
            }
            
            if(isset($data['result']['occupied_orders'])){
                $occupied_orders = $data['result']['occupied_orders'];
                $total = count($occupied_orders);
                
                //是否有下一页数据
                if(empty($data['result']['has_next']) || $data['result']['has_next'] === 'false'){
                    $has_next = false;
                }elseif($data['result']['has_next'] == true || $data['result']['has_next'] === 'true'){
                    $has_next = true;
                }
            }
        }
        
        //result
        $result = array(
            'rsp' => $response['rsp'],
            'res' => $response['res'],
            'msg_id' => $response['msg_id'],
            'total' => $total,
            'has_next' => $has_next,
            'dataList' => $occupied_orders,
        );
        
        return $result;
    }

    // 时效订单结果反馈
    /**
     * 获取OrderFeedback
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getOrderFeedback($sdf)
    {
        $param = [
            'tid'   => $sdf[0]['order_sn'],
        ];
        foreach ($sdf as $k => $v) {
            $param['barcodes'][] = [
                'occupied_order_sn' => $v['occupied_order_sn'],
                'barcode'           => $v['barcode'],
                'cooperation_no'    => $v['cooperation_no'],
                'amount'            => $v['num'],
                'warehouse'         => $v['warehouse'],
            ];
        }
        
        $title        = '时效订单结果反馈';
        $primary_bn   = 'vopfeedback';
        
        $rsp    = $this->__caller->call(SHOP_VOP_FEEFBACK, $param, array(), $title, 10, $primary_bn);

        return $rsp;
    }
    
    /**
     * 获取唯品会平台成交后已取消订单列表
     * API文档：https://vop.vip.com/home#/api/method/detail/vipapis.inventory.InventoryService-1.0.0/getInventoryCancelledOrders
     * 
     * @param $sdf
     * @return array
     */
    public function getInventoryCancelledOrders($param)
    {
        $title = '获取唯品会已取消的订单列表';
        $primary_bn = 'getInventoryCancelledOrders'. date('md', time());
        
        //data
        $requestData = $param;
        
        //params
        $requestParams = array(
            //'vop_service' => 'vipapis.inventory.InventoryService', //服务名
            'vop_type' => 'Inventory',
            'vop_method' => 'getInventoryCancelledOrders', //方法名
            'is_multiple_params' => 0, //是否多个参数：默认1,必须填写0
            'data' => json_encode($requestData),
        );
        
        //request
        $rsp = $this->__caller->call(SHOP_COMMONS_VOP_JIT, $requestParams, array(), $title, 10, $primary_bn);
        if($rsp['rsp'] == 'succ'){
            $rsp = $this->getInventoryCannelOrders_callback($rsp);
        }
        
        return $rsp;
    }
    
    /**
     * [格式化]唯品会平台成交后已取消订单数据
     * 
     * @param $response
     * @param $callback_params
     * @return array
     */
    public function getInventoryCannelOrders_callback($response, $callback_params=NULL)
    {
        $rsp = $response['rsp'];
        $total = 0;
        $has_next = false;
        
        //format
        $occupied_orders = array();
        if($rsp == 'succ'){
            //json
            if(is_array($response['data'])){
                $data = $response['data'];
            }else{
                $data = json_decode($response['data'], true);
            }
            
            //content
            if(is_array($data['msg'])){
                $data = $data['msg'];
            }else{
                $data = json_decode($data['msg'], true);
            }
            
            if(isset($data['result']['occupied_orders'])){
                $occupied_orders = $data['result']['occupied_orders'];
                $total = count($occupied_orders);
                
                //是否有下一页数据
                if(empty($data['result']['has_next']) || $data['result']['has_next'] === 'false'){
                    $has_next = false;
                }elseif($data['result']['has_next'] == true || $data['result']['has_next'] === 'true'){
                    $has_next = true;
                }
            }
        }
        
        //result
        $result = array(
            'rsp' => $response['rsp'],
            'res' => $response['res'],
            'msg_id' => $response['msg_id'],
            'total' => $total,
            'has_next' => $has_next,
            'dataList' => $occupied_orders,
        );
        
        return $result;
    }
}
