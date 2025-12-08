<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016/5/17
 * @describe 添加商家备注 数据转换
 */
class erpapi_shop_response_remark extends erpapi_shop_response_abstract{

    /**
     * @param $params array
     * @return array (
     *              'tid' => '54554', #订单编号
     *              'remark' => '备注信息',
     *              'modified' => '' #修改时间
     *          )
     */
    protected function _formatParams($params) {
        return array();
    }
    #添加商家备注
    public function add($params){
        $sdf = $this->_formatParams($params);
        $this->__apilog['title'] = '接受(' . $this->__channelObj->channel['name'] . ')商家备注';
        if(empty($sdf)) {
            $this->__apilog['result']['msg'] = '不接受商家备注';
            return false;
        }
        $order_bn = $sdf['tid'];
        $shop_id = $this->__channelObj->channel['shop_id'];
        $this->__apilog['original_bn'] = $order_bn;
        $field = 'order_id,process_status,ship_status,custom_mark,mark_text';
        $tgOrder = $this->getOrder($field, $shop_id, $order_bn);
        if($tgOrder) {
            $tgOrder['mark_text'] = $tgOrder['mark_text'] ? unserialize($tgOrder['mark_text']) : array();
            $tgOrder['new_mark'] = array('op_name'=>$this->__channelObj->channel['name'],'op_content'=>$sdf['remark'],'op_time'=>$sdf['modified']);
            
            //brush特殊订单
            if($tgOrder['process_status'] == 'unconfirmed' && $tgOrder['order_type'] == 'brush') {
                $tgOrder['farm_id'] = $this->__judgeBrush($tgOrder);
            }
            
            return $tgOrder;
        } else {
            $this->__apilog['result']['msg'] = '缺少订单';
            return false;
        }
    }
    
    //brush特殊订单
    private function __judgeBrush(&$tgOrder)
    {
        $sdf = $tgOrder;
        
        $sdf['shop'] = array('shop_id' => $tgOrder['shop_id']);
        
        $member = app::get('ome')->model('members')->db_dump(array('member_id'=>$tgOrder['member_id']), 'member_id,uname');
        
        $sdf['member_info'] = array('uname'=>$member['uname']);
        
        $objData = app::get('ome')->model('order_objects')->getList('obj_id,bn,goods_id', array('order_id'=>$tgOrder['order_id']));
        
        $itemData = app::get('ome')->model('order_items')->getList('item_id,obj_id,product_id,bn,`delete`', array('order_id'=>$tgOrder['order_id']));
        
        $sdf['order_objects'] = array();
        foreach($objData as $v)
        {
            $v['order_items'] = array();
            
            $sdf['order_objects'][$v['obj_id']] = $v;
        }
        
        foreach($itemData as $v)
        {
            $sdf['order_objects'][$v['obj_id']]['order_items'][$v['item_id']] = $v;
        }
        
        if($tgOrder['custom_mark']) {
            $cmark = unserialize($tgOrder['custom_mark']);
            $cmark = array_pop($cmark);
            
            $sdf['custom_mark'] = $cmark['op_content'];
        }
        
        $sdf['mark_text'] = $tgOrder['new_mark']['op_content'];
        
        $sdf['consign'] = array('addr'=>$tgOrder['ship_addr']);
        
        //通过刷单规则,判断订单是否为刷单
        kernel::single('brush_order')->brush_confirm($sdf);
        
        //刷单规则ID
        if($sdf['order_type'] == 'brush') {
            $tgOrder['order_items'] = $itemData;
            $tgOrder['order_objects'] = $objData;
            
            return $sdf['brush']['farm_id'];
        }
        
        return false;
    }
}