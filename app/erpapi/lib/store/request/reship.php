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
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_store_request_reship extends erpapi_store_request_abstract
{

    /**
     * 退货单创建
     * 
     * @return void
     * @author
     * */

    public function reship_create($sdf)
    {
        $reship_bn = $sdf['reship_bn'];

        // 判断是否已被删除
        $iscancel = kernel::single('console_service_commonstock')->iscancel($reship_bn);
        if ($iscancel) {
            $this->succ('退货单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'] . '退货单添加';

        $params = $this->_format_reship_create_params($sdf);

        $callback = array(
            'class'  => get_class($this),
            'method' => 'reship_create_callback',
            'params' => array('reship_bn' => $reship_bn, 'obj_bn' => $reship_bn, 'obj_type' => 'reship'),
        );

        $params['need_encrypt'] = 'true';

        return $this->__caller->call(WMS_RETURNORDER_CREATE, $params, $callback, $title, 10, $reship_bn, true, $gateway);
    }

        /**
     * reship_create_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function reship_create_callback($response, $callback_params)
    {
        // 更新外部编码
        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = @json_decode($response['data'], true);
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $reship_bn = $callback_params['reship_bn'];
        if ($data['wms_order_code'] && $reship_bn) {
            $reshipObj = app::get('ome')->model('reship');
            $reshipObj->db->exec("UPDATE sdb_ome_reship SET out_iso_bn='" . $data['wms_order_code'] . "' WHERE reship_bn='" . $reship_bn . "'");
        }

        $callback_params['obj_bn']   = $reship_bn;
        $callback_params['obj_type'] = 'reship';
        return $this->callback($response, $callback_params);
    }

    protected function _format_reship_create_params($sdf)
    {
        $sdf['item_total_num'] = $sdf['line_total_count'] = count($sdf['items']);

        $items = array('item' => array());
        if ($sdf['items']) {
            sort($sdf['items']);
            foreach ((array) $sdf['items'] as $k => $v) {
                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'      => $v['bn'],
                    'item_name'      => $v['name'],
                    'item_quantity'  => (int) $v['num'],
                    'item_price'     => $v['price'] ? (float) $v['price'] : 0, // TODO: 商品价格
                    'item_line_num'  => ($k + 1), // TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '', //可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号)
                    'item_id'        => $v['bn'], // 商品ID
                    'is_gift'        => '0', // TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '', // TODO: 商品备注
                    'inventory_type' => '1', // TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
            }
        }

        $create_time = preg_match('/-|\//', $sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s", $sdf['create_time']);
        $reship_bn   = $sdf['reship_bn'];

        $obj_dly_corp  = app::get('ome')->model('dly_corp');
        $dly_corp_info = $obj_dly_corp->dump(array('name' => $sdf['logi_name']), 'corp_id,type,name');

        //[兼容]老的逻辑,查询corp_id
        if (empty($dly_corp_info)) {
            $dly_corp_info = $obj_dly_corp->dump(array('corp_id' => $sdf['logi_name']), 'type,name');
        }

        if (!empty($dly_corp_info)) {
            $logi_code = $dly_corp_info['type'];
            $logi_name = $dly_corp_info['name'];
        }

        $params = array(
            'uniqid'              => self::uniqid(),
            'wms_supplier'        => '', //    TODO: 服务提供商编号
            'out_order_code'      => $reship_bn,
            'warehouse_code'      => $sdf['branch_bn'],
            'orig_order_code'     => $sdf['original_delivery_bn'],
            'created'             => $create_time,
            'logistics_no'        => $sdf['logi_no'],
            //'logistics_code'      => $sdf['logi_name'],//物流公司

            'logistics_code'      => $logi_code, //物流公司编号
            'logistics_name'      => $logi_name, //物流公司名称
            'remark'              => $sdf['memo'],
            'platform_order_code' => $sdf['order_bn'], //订单号
            'wms_order_code'      => $reship_bn,
            'is_finished'         => 'true',
            'current_page'        => '1', // 当前批次,用于分批同步
            'total_page'          => '1', // 总批次,用于分批同步
            'receiver_name'       => $sdf['receiver_name'],
            'receiver_zip'        => $sdf['receiver_zip'],
            'receiver_state'      => $sdf['receiver_state'],
            'receiver_city'       => $sdf['receiver_city'],
            'receiver_district'   => $sdf['receiver_district'],
            'receiver_address'    => $sdf['receiver_addr'],
            'receiver_phone'      => $sdf['receiver_tel'],
            'receiver_mobile'     => $sdf['receiver_mobile'],
            'receiver_email'      => $sdf['receiver_email'],
            'sign_code'           => '', // TODO: 节点标识，请求唯一标识
            'dest_plan'           => '', // TODO: 目的计划点
            'line_total_count'    => $sdf['line_total_count'], // TODO: 订单行项目数量
            'storage_code'        => $sdf['storage_code'], // 库内存放点编号
            'items'               => json_encode($items),
        );

        return $params;
    }

    /**
     * reship_check
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function reship_check($sdf){

    }

    /**
     * reship_cancel
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function reship_cancel($sdf)
    {
        return $this->error('接口方法不存在', 'w402');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    protected function _format_reship_cancel_params($sdf)
    {
        $params = array(
            'out_order_code' => $sdf['reship_bn'],
            'warehouse_code' => $sdf['branch_bn'],
        );

        return $params;
    }

    /**
     * 退货单查询
     *
     * @return void
     * @author
     **/
    public function reship_search($sdf)
    {
        return $this->error('接口方法不存在', 'w402');
    }

    //同步WMS仓储的售后原因
    public function reship_resaon($sdf)
    {
        return $this->error('接口方法不存在', 'w402');
    }

    //同步第三方仓储WMS异常错误码
    public function reship_errorcode($sdf)
    {
        return $this->error('接口方法不存在', 'w402');
    }
}
