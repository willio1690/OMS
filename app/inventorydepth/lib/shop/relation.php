<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 
*/
class inventorydepth_shop_relation
{
    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @description 店铺建立绑定关系后续操作
     * @access public
     * @param String $shop_id 店铺ID
     * @return void
     */
    public function bind($shop_id) 
    {
        $remote_ip = kernel::single('base_component_request')->get_remote_ip();
        $storeConfig = ome_shop_type::get_store_config();

        $applyModel = $this->app->model('regulation_apply');
        $filter = array(
            'type' => '1',
            'shop_id' => $shop_id,
            'condition' => 'stock',
        );
        $exApply = $applyModel->getList('*',$filter,0,1);
        
        // 如果存在对应的应用规则，不再处理
        if ($exApply) return;

        $shop = $this->app->model('shop')->select()->columns('*')->where('shop_id=?',$shop_id)->instance()->fetch_row();
        
        //不支持库存回写的店铺类型
        if(in_array($shop['shop_type'], inventorydepth_shop_api_support::$no_write_back_stock)) {
            return;
        }
        
        # 生成相应的店铺规则
        $regulation = array(
            'condition' => 'stock',
            'bn' => $shop['shop_bn'],
            'heading' => $shop['name'].'规则',
            'using' => 'true',
            'content' =>  array (
                'filters' =>array (
                  0 =>array (
                    'object' => 'actual_stock',
                    'comparison' => 'bthan',
                    'compare_increment' => '0',
                  ),
                ),
                'stockupdate' => '1',
                'result' => '{可售库存}',
            ),
            'operator' => 16777215,
            'update_time' => time(),
            'operator_ip' => $remote_ip,
            'type' => '1',
        );
        if ($exApply[0]['regulation_id']) {
            $regulation['regulation_id'] = $exApply[0]['regulation_id'];
        }
        if ($storeConfig[$shop['shop_type']] && $storeConfig[$shop['shop_type']] == 'on') {
            $regulation['content']['result'] = '{可售库存}+{店铺预占}';
        }

        $reguModel = $this->app->model('regulation');
        $reguModel->save($regulation);

        $apply = array(
            'bn' => $shop['shop_bn'],
            'heading' => $shop['name'].'应用',
            'condition' => 'stock',
            'style' => 'stock_change',
            'start_time' => time(),
            'end_time' => strtotime('2030-12-12'),
            'shop_id' => $shop['shop_id'],
            'using' => 'true',
            'al_exec' => 'false',
            'operator' => 16777215,
            'update_time' => time(),
            'operator_ip' => $remote_ip,
            'regulation_id' => $regulation['regulation_id'],
            'apply_goods' => '_ALL_',
            'priority' => 10,
            'type' => '1',
        );
        if ($exApply) {
            $apply['id'] = $exApply[0]['id'];
        }
        $applyModel->save($apply);


        kernel::single('inventorydepth_shop')->setStockConf($shop['shop_id'],'false');
        kernel::single('inventorydepth_shop')->setFrameConf($shop['shop_id'],'false');
    }

    /**
     * @description 解除绑定关系后续操作
     * @access public
     * @param void
     * @return void
     */
    public function unbind($shop_id) 
    {
        # 删除相应的店铺规则
        // $applyModel = $this->app->model('regulation_apply');
        // $filter = array(
        //     'type' => '1',
        //     'shop_id' => $shop_id,
        //     'condition' => 'stock',
        // );
        // $apply = $applyModel->getList('*',$filter,0,1);
        
        // if ($apply[0]['regulation_id']) {
        //     $this->app->model('regulation')->delete(array('regulation_id'=>$apply[0]['regulation_id']));
        // }

        // $applyModel->delete(array('id'=>$apply[0]['id']));
    }

}