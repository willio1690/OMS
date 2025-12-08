<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class omevirtualwms_callback{

    var $sync_method = array(
        'store.wms.saleorder.get',//询问发货单是否能撤销
        'store.wms.saleorder.cancel',//发货单撤销
        'store.wms.returnorder.cancel',//退货单撤销
        'store.wms.transferorder.cancel',//转储单撤销
        'store.wms.inorder.cancel',//入库单撤销
        'store.wms.outorder.cancel',//出库单撤销
    );
    
    /**
     * 调用
     * @access public
     * @return Array 标准的callback返回结构
     */
    function call(){
        $params = $_POST;
        $url = kernel::base_url(1).'/index.php/openapi/virtualwms/call';
        $params['url'] = $url;
        $return = array(
            'rsp' => 'fail',//状态
            'res' => '',//错误码
            'err_msg' => '',//错误消息
            'data' => '',//业务数据
            'msg_id' => ''
        );

        $method = $params['method'];
        if (empty($method)){
            $return['err_msg'] = 'method 参数不能为空';
            return json_encode($return);
        }

        ome_kvstore::instance('omevirtualwms')->fetch('callback', $callback);
        $return['rsp'] = !in_array($method,$this->sync_method) ? $callback['async']['rsp'] : $callback['sync']['rsp'];
        $return['res'] = $callback['res'] ? $callback['res'] : $callback['err_msg'];
        $return['err_msg'] = $callback['err_msg'];
        $return['msg_id'] = in_array($return['rsp'],array('running','succ')) ? md5(uniqid()) : '';

        //任务记录
        if (!in_array($method,$this->sync_method) && $return['msg_id']){
            $callbackModel = app::get('omevirtualwms')->model('callback');
            $callback_sdf = array(
                'msg_id' => $return['msg_id'],
                'callback_url' => $params['callback_url'],
                'url' => $params['url'],
                'method' => $method,
                'params' => $params,
                'createtime' => time()
            );
            $callbackModel->save($callback_sdf);
        }
        echo json_encode($return);exit;
    }

}