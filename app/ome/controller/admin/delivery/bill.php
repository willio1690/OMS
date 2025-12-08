<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_delivery_bill extends desktop_controller
{
    public $name = "包裹中心";
    public $workground = "logistics_center";

    /**
     * 包裹列表页面
     */
    public function index()
    {
        $this->title = '包裹中心';

        $params = array(
            'title'                  => $this->title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => true,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'use_view_tab'           => true,
            'base_filter'            => [],
            'actions'                => [
                [
                    'label'  => '异常查询下载',
                    'href'   => $this->url . '&act=exceptionQueryPage',
                    'target' => 'dialog::{width:600,height:500,title:\'异常查询下载\'}',
                ],
                [
                    'label'  => '预警配置查询',
                    'href'   => $this->url . '&act=exceptionConfigQueryPage',
                    'target' => 'dialog::{width:800,height:600,title:\'预警配置查询\'}',
                ],
            ],

        );

        $this->finder('ome_mdl_delivery_bill', $params);
    }



    /**
     * 包裹列表视图定义
     */
    function _views(){
        $base_filter = array();
        
        $sub_menu = array(
            0 => array('label'=>app::get('ome')->_('全部'),'filter'=>array(),'optional'=>false),
            1 => array('label'=>app::get('ome')->_('揽收异常'),'filter'=>array('exception_code'=>'GOT_EXCEPTION'),'optional'=>false),
            2 => array('label'=>app::get('ome')->_('揽收更新异常'),'filter'=>array('exception_code'=>'GOT_UPDATE_EXCEPTION'),'optional'=>false),
            3 => array('label'=>app::get('ome')->_('运输派送异常'),'filter'=>array('exception_code'=>'TRANSPORT_EXCEPTION'),'optional'=>false),
            4 => array('label'=>app::get('ome')->_('派送更新异常'),'filter'=>array('exception_code'=>'DELIVERY_UPDATE_EXCEPTION'),'optional'=>false),
        );

        $deliveryBillModel = app::get('ome')->model('delivery_bill');
        
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $deliveryBillModel->count($v['filter']);
            $sub_menu[$k]['href'] = $this->url.'&act='.$_GET['act'].'&view='.$k;
        }

        return $sub_menu;
    }



    /**
     * 异常查询下载页面
     */
    public function exceptionQueryPage()
    {
        // 获取符合条件的店铺列表
        $shopModel = app::get('ome')->model('shop');
        $shops = $shopModel->getList('shop_id,shop_bn,name', [
            's_type' => 1,
            'node_type' => 'taobao',
            'filter_sql' => 'node_id is not null and node_id != ""',
        ]);

        $this->pagedata['shops'] = $shops;
        $this->pagedata['time_from'] = date('Y-m-d', strtotime('last month'));
        $this->pagedata['time_to'] = date('Y-m-d');

        $this->display('admin/delivery/bill/exception_query_page.html');
    }



    /**
     * 执行异常查询下载
     */
    public function doExceptionQuery()
    {
        $post = $_POST; 
        $pageNo = $_GET['pageNo'] ?? 1;

        $filter = array_filter($post);

        // 验证必填字段
        if (empty($filter['shop_id'])) {
            $this->splash('error', null, '请选择店铺');
        }
        
        if (empty($filter['exception_code'])) {
            $this->splash('error', null, '请选择一级异常类型');
        }

        // 构建请求参数
        $params = [
            'filter' => $filter,
            'page_no' => $pageNo,
            'page_size' => 50,
        ];

        // 调用库方法进行异常查询和更新
        $result = kernel::single('ome_delivery_bill')->queryExceptionAndUpdate($params, $filter['shop_id']);

        if ($result['rsp'] == 'fail') {
            $this->splash('error', null, $result['msg']);
        }

        $this->splash('success', null, $result['msg']);
    }

    /**
     * 异常查询下载Promise页面
     */
    public function exceptionQueryPromise()
    {
        $post = $_POST;  
        unset($post['baseApiUrl'], $post['_DTYPE_DATE']);

        $inputhtml = '';

        foreach ($post as $key => $value) {
            $params = array(
                'type' => 'hidden',
                'name' => $key,
                'value' => $value,
            );

            $inputhtml .= utils::buildTag($params, 'input');
        }

        $this->pagedata['inputhtml'] = $inputhtml;

        $filter = array_filter($post);

        // 验证必填字段
        if (empty($filter['shop_id'])) {
            $this->splash('error', null, '请选择店铺');
        }
        
        if (empty($filter['exception_code'])) {
            $this->splash('error', null, '请选择一级异常类型');
        }

        // 调用ERPAPI查询异常包裹
        $result = kernel::single('erpapi_router_request')->set('shop', $filter['shop_id'])->logistics_exception_query($filter);
        
        if ($result['rsp'] == 'fail') {
            $this->splash('error', null, $result['msg']);
        }

        $totalCount = $result['total_num'] ?? 0;
        $customLimit = $filter['page_size'] ?? 50; // 从filter中获取每页数量，默认50条

        parent::dialog_promise($totalCount, $customLimit);
    }

    /**
     * 预警配置查询页面
     */
    public function exceptionConfigQueryPage()
    {
        // 获取符合条件的店铺列表
        $shopModel = app::get('ome')->model('shop');
        $shops = $shopModel->getList('shop_id,shop_bn,name', [
            's_type' => 1,
            'node_type' => 'taobao',
            'filter_sql' => 'node_id is not null and node_id != ""',
        ]);

        $this->pagedata['shops'] = $shops;
        $this->display('admin/delivery/bill/exception_config_query_page.html');
    }

    /**
     * 执行预警配置查询
     */
    public function doExceptionConfigQuery()
    {
        try {
            $shop_id = $_POST['shop_id'] ?? '';
            
            if (empty($shop_id)) {
                $this->splash('error', null, '请选择店铺');
                return;
            }
            
            // 调用ERPAPI查询预警配置
            $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->logistics_exception_config_query([
                'shop_id' => $shop_id,
                'request_id' => $shop_id // 使用shop_id作为request_id，用于幂等性控制
            ]);
            
                       if ($result['rsp'] == 'succ') {
               $config_data = $result['data'] ?? [];
               
               // 定义配置项的中文名称和说明
               $configLabels = array(
                   'transporting_stop_warn_value' => array(
                       'name' => '运输停滞预警值',
                       'desc' => '被判断为"运输停滞"X前计入"即将超时"',
                       'unit' => '小时'
                   ),
                   'consign_fake_warn_value' => array(
                       'name' => '虚假发货预警值',
                       'desc' => '被判断为"虚假发货"X前计入"即将虚假发货"',
                       'unit' => '小时'
                   ),
                   'consign_delay_warn_value' => array(
                       'name' => '延迟发货预警值',
                       'desc' => '延迟发货前X小时计入"即将延迟"',
                       'unit' => '小时'
                   ),
                   'collected_stop_warn_value' => array(
                       'name' => '揽收后停滞预警值',
                       'desc' => '被判断为"揽收后停滞"X前计入"即将超时"',
                       'unit' => '小时'
                   ),
                   'deliverying_stop_warn_value' => array(
                       'name' => '派签停滞预警值',
                       'desc' => '被判断为"派签停滞"X前计入"即将超时"',
                       'unit' => '小时'
                   )
               );
               
               $this->pagedata['config_data'] = $config_data;
               $this->pagedata['config_labels'] = $configLabels;
               $this->display('admin/delivery/bill/exception_config_result.html');
           } else {
                $this->splash('error', null, '查询失败：' . ($result['msg'] ?? '未知错误'));
            }
            
        } catch (Exception $e) {
            $this->splash('error', null, '查询异常：' . $e->getMessage());
        }
    }


} 