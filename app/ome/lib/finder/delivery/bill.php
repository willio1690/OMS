<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_delivery_bill
{
    var $addon_cols = "exception_status,exception_code,sub_exception_code,exception_type";

    var $column_exception_status = '异常状态';
    var $column_exception_code = '异常代码';
    var $column_sub_exception_code = '子异常代码';
    var $column_exception_type = '异常类型';





    /**
     * 异常状态列格式化
     */
    function column_exception_status($row)
    {
        $exception_status = $row[$this->col_prefix.'exception_status'];
        if ($exception_status == 0) {
            return '<span style="color: green;">否</span>';
        } else {
            return '<span style="color: red;">是</span>';
        }
    }

    /**
     * 异常代码列格式化
     */
    function column_exception_code($row)
    {
        $exception_code = $row[$this->col_prefix.'exception_code'];
        if (empty($exception_code)) {
            return '-';
        }
        
        $exception_map = [
            'GOT_EXCEPTION' => '揽收异常',
            'GOT_UPDATE_EXCEPTION' => '揽收更新异常',
            'TRANSPORT_EXCEPTION' => '运输派送异常',
            'DELIVERY_UPDATE_EXCEPTION' => '派送更新异常',
        ];
        
        return $exception_map[$exception_code] ?? $exception_code;
    }

    /**
     * 子异常代码列格式化
     */
    function column_sub_exception_code($row)
    {
        $sub_exception_code = $row[$this->col_prefix.'sub_exception_code'];
        if (empty($sub_exception_code)) {
            return '-';
        }
        
        $sub_exception_map = [
            'WILL_GOT_TIMEOUT' => '揽收即将超时',
            'WILL_CONSIGN_FAKE_TIMEOUT' => '即将虚假发货',
            'CONSIGN_DELAY' => '延迟发货',
            'CONSIGN_CLICKED_FAKE' => '虚假点击发货',
            'OUT_STOCK' => '缺货',
            'WILL_GOT_UPDATE_TIMEOUT' => '揽收更新即将超时',
            'COLLECTED_STOP' => '揽收后停滞',
            'WILL_TRANSPORT_TIMEOUT' => '运输派送即将超时',
            'TRANSPORTING_STOP' => '运输停滞',
            'WILL_DELIVERY_UPDATE_TIMEOUT' => '派送更新即将超时',
            'DELIVERING_STOP' => '派送停滞',
        ];
        
        return $sub_exception_map[$sub_exception_code] ?? $sub_exception_code;
    }

    /**
     * 异常类型列格式化
     */
    function column_exception_type($row)
    {
        $exception_type = $row[$this->col_prefix.'exception_type'];
        if (empty($exception_type)) {
            return '-';
        }
        
        $exception_type_map = [
            'WARNING' => '预警异常',
            'EXCEPTION' => '实际异常',
        ];
        
        return $exception_type_map[$exception_type] ?? $exception_type;
    }





    /**
     * 详情列 - 基本信息
     */
    public $detail_basic = '基本信息';
    /**
     * detail_basic
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_basic($id)
    {
        $render = app::get('ome')->render();
        $data = app::get('ome')->model('delivery_bill')->dump($id);
        
        $render->pagedata['data'] = $data;
        return $render->fetch('admin/delivery/bill/detail_basic.html');
    }

    /**
     * 详情列 - 包裹明细
     */
    public $detail_items = '包裹明细';
    /**
     * detail_items
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_items($id)
    {
        $render = app::get('ome')->render();
        
        // 获取包裹基本信息
        $delivery_bill = app::get('ome')->model('delivery_bill')->dump($id);
        
        // 获取包裹明细
        $delivery_package_model = app::get('ome')->model('delivery_package');
        $packages = $delivery_package_model->getList('*', [
            'delivery_id' => $delivery_bill['delivery_id'],
            'logi_no' => $delivery_bill['logi_no']
        ], 0, -1, 'package_id ASC');
        
        $render->pagedata['delivery_bill'] = $delivery_bill;
        $render->pagedata['packages'] = $packages;
        return $render->fetch('admin/delivery/bill/detail_items.html');
    }
} 