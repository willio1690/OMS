<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_ctl_admin_shop extends desktop_controller {

    var $name = '淘宝O2O配置';
    var $workground = 'tbo2o_center';

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        die('index');
    }
    
    /**
     * 设置ting
     * @return mixed 返回操作结果
     */
    public function setting()
    {
        $shopObj    = app::get('ome')->model('shop');
        
        #淘宝店铺列表
        $filter      = array('taobao'=>'taobao', 'active'=>'true', 'disabled'=>'false', 'node_type'=>'taobao');
        $shopList    = $shopObj->getList('shop_id, shop_bn, name, node_id', $filter);
        if(empty($shopList))
        {
            $shopList[]    = array('shop_id'=>'', 'name'=>'没有可选店铺');
        }
        
        $tbo2oShopObj    = app::get('tbo2o')->model('shop');
        
        //主店铺信息
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        $shopRow = $tbo2o_shop;
        
        #保存数据
        if($_POST)
        {
            $this->begin();
            
            //check
            $_POST['shop_id']    = ($shopRow['shop_id'] ? $shopRow['shop_id'] : $_POST['shop_id']);
            if(empty($_POST['shop_id']))
            {
                $this->end(false,'请选择店铺');
            }
            
            $shop_info    = array();
            foreach ($shopList as $key => $val)
            {
                if($val['shop_id'] == $_POST['shop_id'])
                {
                    $shop_info    = $val;
                }
            }
            
            if(empty($shop_info))
            {
                $this->end(false,'未匹配到淘宝类型的店铺');
            }
            
            if(empty($_POST['company_name']))
            {
                $this->end(false,'请填写商户名称');
            }
            
            #更新
            $data    = array(
                        'shop_id'=>$shop_info['shop_id'],
                        'shop_bn'=>$shop_info['shop_bn'],
                        'shop_name'=>$shop_info['name'],
                        'company_name'=>$_POST['company_name'],
                        'company_content'=>$_POST['company_content'],
                        'branch_bn'=>$_POST['branch_bn'],
                    );
            
            if($shopRow)
            {
                $data['create_time']    = ($shopRow['create_time'] ? $shopRow['create_time'] : time());
                $tbo2oShopObj->update($data, array('id'=>$shopRow['id']));
            }
            else 
            {
                $data['create_time']    = time();
                $tbo2oShopObj->insert($data);
            }
            
            $this->end(true, '保存成功');
        }
        
        $this->pagedata['data']        = $shopRow;
        $this->pagedata['shopList']    = $shopList;
        $this->page('admin/shop/shop_setting.html');
    }
    
}