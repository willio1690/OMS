<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单组装厂
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
class erpapi_dealer_response_components_order_broker extends erpapi_dealer_response_components_order_abstract
{
    //组件集合
    private $_components = array();

    /**
     * 清组件
     *
     * @return object
     **/

    public function clearComponents()
    {
        $this->_components = array();
        
        return $this;
    }

    /**
     * 转标准格式
     *
     * @return void
     **/
    public function convert()
    {
        $newOrder = array();
        foreach ($this->_components as $_component) {
            $tmp = $_component->setPlatform($this->_platform)->convert();
        }
    }

    /**
     * 更新订单
     *
     * @return void
     **/
    public function update()
    {
        $newOrder = array();
        foreach ($this->_components as $_component) {
            $tmp = $_component->setPlatform($this->_platform)->update();
        }
    }

    /**
     * 注册一个组件
     *
     * @return object
     **/
    public function registerComponent($component_name, $stackIndex = null)
    {
        $component = kernel::single('erpapi_dealer_response_components_order_'.$component_name);

        if (false !== array_search($component, $this->_components, true)) {
            throw new Exception("订单组件已经注册过");
        }

        $stackIndex = (int) $stackIndex;

        if ($stackIndex) {
            if (isset($this->_components[$stackIndex])) {
                throw new Exception("组件键值已经存在"); 
            }
            $this->_components[$stackIndex] = $component;
        } else {
            $stackIndex = count($this->_components);
            while (isset($this->_components[$stackIndex])) {
                ++$stackIndex;
            }
            $this->_components[$stackIndex] = $component;
        }
        
        ksort($this->_components);

        return $this;
    }

    /**
     * 加组件
     *
     * @return object
     **/
    public function unregisterComponent($component)
    {

        if ($component instanceof erpapi_dealer_response_components_order_abstract) {
            $key = array_search($component, $this->_components, true);
            
            unset($this->_components[$key]);
        } elseif (is_string($component)) {
            foreach ($this->_components as $key => $_component)
            {
                $type = get_class($_component);
                
                if ($component == $type) {
                    unset($this->_components[$key]);
                }
            }
        }
        
        return $this;
    }
}