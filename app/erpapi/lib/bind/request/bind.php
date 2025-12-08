<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2018/3/20
 * @describe 绑定相关
 */
class erpapi_bind_request_bind extends erpapi_bind_request_abstract
{

    public function bind($sdf = array(), $force = false, $needResponse = false)
    {
        if ($force === false) {
            $binding = null;
            base_kvstore::instance('binding')->fetch($this->__channelObj->channel['node_type'], $binding);

            if ($binding) {
                return true;
            }
        }

        $params = array(
            'app'           => 'app.applyNodeBind',
            'node_id'       => base_shopnode::node_id('ome'),
            'from_certi_id' => base_certificate::certi_id(),
            'callback'      => '',
            'sess_callback' => '',
            'api_url'       => kernel::base_url(1).kernel::url_prefix().'/api',
            'node_type'     => $this->__channelObj->channel['node_type'],
            'to_node'       => $this->__channelObj->channel['to_node'],
            'shop_name'     => $this->__channelObj->channel['shop_name'],
        );
        if($sdf) {
            $params = array_merge($params, $sdf);
        }
        
        $params['certi_ac'] = $this->_gen_bind_sign($params);
        
        $title = $this->__channelObj->channel['title'] . '绑定';
        
        $callback = array();
        $result = $this->__caller->call(SHOP_LOGISTICS_BIND, $params, $callback, $title,10);
        $response = json_decode($result['response'],true);

        if ($response['res'] == 'succ' || $response['msg']['errorDescription'] == '绑定关系已存在,不需要重复绑定' ) {
            base_kvstore::instance('binding')->store($params['node_type'],true);
            // 回参用于更新节点id
            if($needResponse){
                return $this->succ($response['msg'],200, $response);
            }
            return true;
        } else {
            return false;
        }
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

    public function unbind($sdf = array())
    {
        $params = array(
            'app'           => 'app.changeBindRelStatus',
            'from_node'     => base_shopnode::node_id('ome'),
            'from_certi_id' => base_certificate::certi_id(),
            'node_type'     => $this->__channelObj->channel['node_type'],
            'to_node'       => $this->__channelObj->channel['to_node'],
            'status'        => 'del',
            'reason'        => '解除绑定关系',
        );
        
        if($sdf) {
            $params = array_merge($params, $sdf);
        }
        
        $params['certi_ac'] = $this->_gen_bind_sign($params);
        
        $title = $this->__channelObj->channel['title'] . '解除绑定';
        
        $callback = array();
        $result = $this->__caller->call(SHOP_LOGISTICS_BIND, $params, $callback, $title,4);
        if ($result['res'] == 'succ' ) {
            return true;
        } else {
            return false;
        }
    }
}