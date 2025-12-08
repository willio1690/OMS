<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 来源平台
 */
class omeauto_auto_type_platform extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 在显示前为模板做一些数据准备工作
     * 
     * @param object $tpl
     * @return void
     */
    public function _prepareUI(& $tpl, $val) {

        $shop_type = kernel::database()->select("SELECT shop_type FROM sdb_ome_shop WHERE shop_type<>'' GROUP BY shop_type");

        $shopList = array();
        foreach ($shop_type as $row) {
            if($row['shop_type'] == 'haoshiqi') {
                $shopList[] = array('key' => 'haoshiqi', 'label' => '好食期-所有');
                $shopList[] = array('key' => 'haoshiqi_taobao', 'label' => '好食期-淘宝');
                $shopList[] = array('key' => 'haoshiqi_douyin', 'label' => '好食期-抖音');
                $shopList[] = array('key' => 'haoshiqi_kuaishou', 'label' => '好食期-快手');
            } else {
                $shopList[] = array('key' => $row['shop_type'], 'label' => ome_shop_type::shop_name($row['shop_type']));
            }
        }


        /*if ($data) {
            if ($data['shop_type']) {
                $tpl->pagedata['current_shop_type'] = $data['shop_type'];
                $checked = $data['shop'];
                $shop = $this->_get_shop($data['shop_type'], $checked);
                $tpl->pagedata['shop'] = $shop;
            }
        } else {
            $shop = $this->_get_shop($shop_type[0]['key'], $checked);
            $tpl->pagedata['shop'] = $shop;
        }*/

        $tpl->pagedata['shop_type'] = $shopList;
    }

    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {

        if (empty($params['shop_type'])) {

            return "你还没有选择订单的来源平台\n\n请选择以后再试！！";
        }

        /*if (empty($params['shop']) && !is_array($params['shop'])) {

            return "你还没有选择指定来源平台下店铺\n\n请勾选以后再试！！";
        }*/

        return true;
    }
    
    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {
        
        if (!empty($this->content)) {
            $contentType = strtolower($this->content['type']);
            foreach ($item->getOrders() as $order) {
                //检查订单类型
                if(in_array($contentType, ['haoshiqi_taobao', 'haoshiqi_douyin', 'haoshiqi_kuaishou'])) {
                    if(strtolower($order['shop_type']) != 'haoshiqi'){
                        return false;
                    }
                    $orderReceiver = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$order['order_id']],'encrypt_source_data');
                    if(empty($orderReceiver)) {
                        return false;
                    }
                    $encrypt_source_data = @json_decode($orderReceiver['encrypt_source_data'], 1);
                    if(!$encrypt_source_data['is_consignee_encrypt']) {
                        return false;
                    }
                    if($contentType == 'haoshiqi_taobao' && !$encrypt_source_data['taobao_oaid']) {
                        return false;
                    }
                    if($contentType == 'haoshiqi_douyin' && !$encrypt_source_data['douyin_open_address_id']) {
                        return false;
                    }
                    if($contentType == 'haoshiqi_kuaishou' && !$encrypt_source_data['third_info']) {
                        return false;
                    }
                } elseif (strtolower($order['shop_type']) != $contentType) {
                    return false;
                }
                /*if (!empty($this->content[shop])) {
                    if (in_array($order['shop_id'], $this->content['shop'])) {
                        return true;
                    }
                }*/
            }
            return true;
        } else {
            
            return false;
        }
    }

    /**
     * 生成规则字串
     * 
     * @param Array $params
     * @return String
     */
    public function roleToString($params) {
        
        $shoptype = ome_shop_type::get_shop_type();
        $shoptype['shopex_b2b_taobao'] = '分销王-淘宝';
        $shoptype['shopex_b2b_360buy'] = '分销王-京东';
        $shoptype['haoshiqi_taobao'] = '好食期-淘宝';
        $shoptype['haoshiqi_douyin'] = '好食期-抖音';
        $shoptype['haoshiqi_kuaishou'] = '好食期-快手';
        $caption = '';
        /*foreach ($rows as $row) {
            
            $caption .= ", ".$row['name'];
        }*/
        $caption = sprintf('来自 %s 平台下的订单', $shoptype[$params['shop_type']]);
        
        $role = array('role' => 'platform', 'caption' => $caption, 'content'=> array('type' => $params['shop_type']));
        
        return json_encode($role);
    }

    function _get_shop($shop_type, $checked=array()) {
        $shop = array();
        if ($shop_type) {
            $rows = app::get('ome')->model('shop')->getList("shop_id,name", array("shop_type" => $shop_type), 0, -1);
            if ($rows) {
                foreach ($rows as $v) {
                    $shop[] = array(
                        'shop_id' => $v['shop_id'],
                        'shop_name' => $v['name'],
                        'checked' => ($checked && in_array($v['shop_id'], $checked)) ? 'checked' : '',
                    );
                }
            }
        }
        return $shop;
    }

    function getShopByType($params) {
        $shop_type = $params[0];
        $role = $params[1];
        $tpl = kernel::single('base_render');
        $tpl->pagedata['role'] = $role;
        $tpl->pagedata['init'] = json_decode(base64_decode($role), true);
        $shop = $this->_get_shop($shop_type);
        $tpl->pagedata['current_shop_type'] = $shop_type;
        $tpl->pagedata['shop'] = $shop;

        echo $tpl->fetch("order/type/platform/shop.html", 'omeauto');
    }

}