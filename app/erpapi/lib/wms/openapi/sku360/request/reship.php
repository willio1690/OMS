<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 退货单推送
 *
 * @category
 * @package
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_request_reship extends erpapi_wms_request_reship
{
    protected function _format_reship_create_params($sdf)
    {
        $data['orders'][0] = array(
            'order_code' => $sdf['reship_bn'],
            'msg' => $sdf['memo'],
            'back_order' => $sdf['order_bn'],
        );
        $items = array();
        if ($sdf['items']){
            foreach ($sdf['items'] as $k => $v){
                $items[] = array(
                    'product_code' => $v['bn'],
                    'qty' => $v['num'],
                );
            }
        }
        $data['orders'][0]['skus'] = $items;
        $params['data'] = json_encode($data);
        return $params;
    }

    /**
     * 退货单创建
     *
     * @return void
     * @author
     **/

    public function reship_create($sdf){
        $reship_bn = $sdf['reship_bn'];

        // 判断是否已被删除
        $iscancel = kernel::single('console_service_commonstock')->iscancel($reship_bn);
        if ($iscancel) {
            $this->succ('退货单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'].'退货单添加';

        $params = $this->_format_reship_create_params($sdf);

        $callback = array();

        $response = $this->__caller->call(WMS_RETURNORDER_CREATE, $params, $callback, $title, 10, $reship_bn);
        $callback_params = array(
            'reship_bn'=>$reship_bn,
            'method' => WMS_RETURNORDER_CREATE
        );
        if($response) {
            $this->reship_create_callback($response, $callback_params);
        }
    }

    protected function _format_reship_cancel_params($sdf)
    {
        $params['data'] = json_encode(array(
            'order_code' => $sdf['reship_bn'],
        ));

        return $params;
    }

    /**
     * 退货单创建取消
     *
     * @return void
     * @author
     **/
    public function reship_cancel($sdf){
        $reship_bn = $sdf['reship_bn'];

        $title = $this->__channelObj->wms['channel_name'].'退货单取消'.$reship_bn;

        $params = $this->_format_reship_cancel_params($sdf);

        return $this->__caller->call(WMS_RETURNORDER_CANCEL, $params, null, $title, 10, $reship_bn);
    }
}
