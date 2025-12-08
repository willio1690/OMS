<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 店铺模式
 */
class omeauto_auto_type_shopmode extends omeauto_auto_type_abstract implements omeauto_auto_type_interface
{
    //店铺模式
    static public $_shopModes = array(
        'shop_self' => '直销店铺(默认)',
        'shop_yjdf' => '一件代发店铺',
    );
    
    /**
     * 在显示前为模板做一些数据准备工作
     * 
     * @param object $tpl
     * @return void
     */
    public function _prepareUI(& $tpl, $val)
    {
        $shopModeList = array();
        foreach (self::$_shopModes as $key => $val)
        {
            $shopModeList[] = array('key'=>$key, 'label'=>$val);
        }
        
        $tpl->pagedata['shopModeList'] = $shopModeList;
    }
    
    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params)
    {
        if (empty($params['shop_mode'])) {
            return "你还没有选择店铺发货模式,请检查!";
        }
        
        return true;
    }
    
    /**
     * 生成规则字串
     * 
     * @param Array $params
     * @return String
     */
    public function roleToString($params)
    {
        $shop_mode = trim($params['shop_mode']);
        $shop_mode_name = self::$_shopModes[$shop_mode];
        
        $caption = sprintf('店铺模式：%s', $shop_mode_name);
        $role = array('role'=>'shopmode', 'caption'=>$caption, 'content'=>array('type'=>$shop_mode));
        
        return json_encode($role);
    }
    
    /**
     * 检查订单数据是否符合审单要求
     *
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item)
    {
        if(empty($this->content)){
            return false;
        }
        
//        //循环检查审单的订单是否符合要求
//        foreach($item->getOrders() as $order)
//        {
//            //检查hold单规则(店铺发货模式是：一件代发店铺,则不允许hold单)
//            if(strtolower($this->content['type']) == 'shop_yjdf'){
//                return false;
//            }
//        }
        
        //检查hold单规则(店铺发货模式是：一件代发店铺,则不允许hold单)
        if(strtolower($this->content['type']) == 'shop_yjdf'){
            return false;
        }
        
        return true;
    }
}