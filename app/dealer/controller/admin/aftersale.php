<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_ctl_admin_aftersale extends desktop_controller
{

    public $name       = '单据';
    public $workground = 'invoice_center';

    private $_businessLib = null;
    
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
    {
        parent::__construct($app);
        
        //lib
        $this->_businessLib = kernel::single('dealer_business');
    }

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $this->title = '代发售后单';


        $base_filter = $this->getFilters();
        $params = array(
            'title'               => $this->title,
            'use_buildin_recycle' => false,
            'use_buildin_filter'  => true,
            'orderBy'             => 'aftersale_time desc',
            'base_filter'         => $base_filter,
        );

        if (isset($_GET['view']) && $_GET['view'] != 0) {
            $params['use_buildin_export'] = true;
        }
       

        $this->finder('dealer_mdl_aftersale', $params);
    }

    /**
     * _views
     * @param mixed $base_filter base_filter
     * @return mixed 返回值
     */
    public function _views($base_filter)
    {
        $mdl_aftersale = app::get('dealer')->model('aftersale');

        $base_filter = $this->getFilters();

        $sub_menu = array(
            0 => array('label' => app::get('base')->_('全部'), 'filter' => array(), 'optional' => false),
            1 => array('label' => app::get('base')->_('退货单'), 'filter' => array('return_type' => 'return'), 'optional' => false),
            2 => array('label' => app::get('base')->_('换货单'), 'filter' => array('return_type' => 'change'), 'optional' => false),
            3 => array('label' => app::get('base')->_('退款单'), 'filter' => array('return_type' => 'refund'), 'optional' => false),
        );

        $i = 0;
        foreach ($sub_menu as $k => $v) {
            if (isset($v['filter'])){
                $v['filter'] = array_merge($base_filter, $v['filter']);
            }

            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon']  = $mdl_aftersale->count($v['filter']);
            $sub_menu[$k]['href']   = 'index.php?app=dealer&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&view=' . $i++;
        }
        return $sub_menu;
    }


    /**
     * 公共filter条件
     * 
     * @return array
     */
    public function getFilters()
    {
        $base_filter = array();
        $base_filter['betc_id|than'] = 0; //贸易公司ID
        $base_filter['cos_id|than'] = 0; //组织权限
        $cosData = $this->_businessLib->getOperationCosIds();

        if($cosData[1]){
            $base_filter['cos_id'] = $cosData[1];
        }
        return $base_filter;
    }
}
