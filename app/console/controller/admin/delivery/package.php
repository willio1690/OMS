<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 物流包裹明细列表
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class console_ctl_admin_delivery_package extends desktop_controller
{
    var $name = '物流包裹明细列表';
    var $workground = 'console_center';
    
    /**
     * 物流包裹列表
     */
    function index()
    {
        $actions = array();
        $base_filter = array('status|noequal'=>'cancel');
        
        //view
        $_GET['view'] = intval($_GET['view']);
        switch($_GET['view']){
            case 0:
            case 4:
                $base_filter = array();
            break;
        }
        
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>true,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'actions' => $actions,
            'title' => '物流包裹明细列表',
            'base_filter' => $base_filter,
        );
        
        $this->finder('console_mdl_delivery_package', $params);
    }

    function _views()
    {
        $dlyPackageObj = app::get('console')->model('delivery_package');
        
        $base_filter = array();
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'), 'filter'=>array(), 'optional'=>false),
            1 => array('label'=>app::get('base')->_('未发货'), 'filter'=>array('status'=>'accept'), 'optional'=>false),
            2 => array('label'=>app::get('base')->_('已发货'), 'filter'=>array('status'=>'delivery'), 'optional'=>false),
            3 => array('label'=>app::get('base')->_('拦截追回'), 'filter'=>array('status'=>array('return_back')), 'optional'=>false),
            4 => array('label'=>app::get('base')->_('已取消'), 'filter'=>array('status'=>array('cancel')), 'optional'=>false),
        );
        
        foreach($sub_menu as $k => $v)
        {
            if(!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $dlyPackageObj->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=console&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        
        return $sub_menu;
    }
}
