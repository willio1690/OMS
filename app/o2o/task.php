<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_task{

    function post_install($options){

        $dlyCorpLib = kernel::single('ome_interface_dlycorp');
        $o2o_pickup_data = array(
            'type' => 'o2o_pickup',
            'name' => '门店自提',
            'd_type' => 2,
            'firstunit' => 0,#首重重量
            'firstprice' => 0,#首重费用
            'continueunit' => 1,#续重重量(注意：被除数不能为0)
            'continueprice' => 0,#续重费用
        );
        $save_dlycorp = $dlyCorpLib->save($o2o_pickup_data);

        $o2o_ship_data = array(
            'type' => 'o2o_ship',
            'name' => '门店配送',
            'd_type' => 2,
            'firstunit' => 0,#首重重量
            'firstprice' => 0,#首重费用
            'continueunit' => 1,#续重重量(注意：被除数不能为0)
            'continueprice' => 0,#续重费用
        );
        $save_dlycorp = $dlyCorpLib->save($o2o_ship_data);
        
        #自动插入线下服务端数据
        $serverObj    = app::get('o2o')->model('server');
        $type_list    = o2o_conf_server::getTypeList('wap');
        
        if($type_list)
        {
            $save_data    = array(
                    'server_bn'    => 'h5wap',
                    'name'    => $type_list['label'],
                    'type'    => $type_list['type'],
                    'remote_url'    => '',
                    'sign_key'    => '',
            );
            $serverObj->save($save_data);
        }

        //初始化数据
        kernel::single('base_initial', 'o2o')->init();
    }

    function post_uninstall(){
        $dlyCorpLib = kernel::single('ome_interface_dlycorp');
        $dlyCorpLib->delete(array('type'=>array('o2o_pickup','o2o_ship'),'d_type'=>2));
    }

}
