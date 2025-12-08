<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 银联处理
 *
 * @category 
 * @package 
 * @author sunjing<sunjing@shopex.cn>
 * @version $Id: Z
 */
class erpapi_unionpay_request_unionpay extends erpapi_unionpay_request_abstract
{

    const _TO_NODE_ID = '1705101437';
    /**
     * 物流查询
     *
     * @return void
     * @author
     **/
    public function query($sdf)
    {
        $params = array(
            'to_node_id'    => self::_TO_NODE_ID,
            'tid'           => $sdf['tid'],
            'company_code'  => $sdf['company_code'],
            'company_name'  => $sdf['company_name'],
            'order_id'      => $sdf['logistic_code'],
            'device_type'   =>  'Android',
        );

        $title = sprintf('查询物流信息[%s]',$params['order_id']);

        $result= $this->__caller->call(STORE_LOGISTICS_TRACE_GET,$params,array(),$title,5,$params['logistic_code']);
        if($result['rsp'] == 'succ'){
            $data = json_decode($result['data'],true);
            $data = json_decode($data['data'],true);
            return array('rsp'=>'succ','data'=>$data['wlInfoList']);
        }
    }


    /**
     * 绑定
     *
     * @return void
     * @author 
     **/
    public function bind()
    {
        return ['rsp'=>'fail', 'msg'=>'不支持绑定'];
    }

    private function _gen_bind_sign($params)
    {
        $token = base_certificate::token();

        ksort($params);
        $str = '';
        foreach ($params as $key =>$value) {
            $str .= $value;
        }

        $sign = md5($str.$token);

        return $sign;
    }

    /**
     * 绑定
     *
     * @return void
     * @author
     **/
    public function unbind()
    {
        $params = array(
            'app'           => 'app.changeBindRelStatus',
            'from_node'       => base_shopnode::node_id('ome'),
            'from_certi_id' => base_certificate::certi_id(),
            'node_type'     => 'ums',
            'to_node'       => self::_TO_NODE_ID,
            'status'        =>  'del',
            'reason'        =>  '重新解绑啦',
        );

        $params['certi_ac']=$this->_gen_bind_sign($params);

        $title = '银联解除绑定';

        $callback = array();

        $result = $this->__caller->call(SHOP_LOGISTICS_BIND, $params, $callback, $title,4);

        if ($result['res'] == 'succ' ) {
            base_kvstore::instance('ome/bind/unionpay')->store('ome_bind_unionpay', false);
            return true;
        } else {
            return false;
        }
    }
}