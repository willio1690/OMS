<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *  前后端分离，盘点类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:26:39+08:00
 */
class erpapi_front_response_o2o_inventory extends erpapi_front_response_o2o_abstract
{
    /**
     * 盘点单创建，method=front.o2o.inventory.listing
     *
     * @return void
     * @author
     **/

    public function add($params)
    {
        $this->__apilog['title']       = '盘点单创建';
        $this->__apilog['original_bn'] = '';

        // 明细处理
        $items = @json_decode($params['items'], true);
        if (!$items) {
            $this->__apilog['result']['msg'] = '缺少调拨明细';
            return false;
        }

        $bn_list = array();
        foreach ($items as $key => $value) {
            if (!$value['bn']) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：缺少物料编码', $key);
                return false;
            }

            if (!is_numeric($value['nums']) || $value['nums'] <= 0) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：[%s]数量异常', $key, $value['bn']);
                return false;
            }

            $bn_list[] = $value['bn'];
        }

        // 基础物料
        $bm_list = app::get('material')->model('basic_material')->getList('bm_id,material_bn', array('material_bn' => $bn_list));
        $bm_list = array_column($bm_list, null, 'material_bn');

        // 获取账面数
        $bm_id_list = array_column($bm_list, 'bm_id');

        $bpModel       = app::get('ome')->model('branch_product');

        $pstore_list = array();
        foreach ($bpModel->getList('*', array('product_id' => $bm_id_list, 'branch_id' => $_SESSION['branch_id'])) as $value) {
            $pstore_list[$value['branch_id']][$value['product_id']] = $value;
        }

        foreach ($items as $key => $value) {
            $bm     = $bm_list[$value['bn']];
            $pstore = $pstore_list[$_SESSION['branch_id']][$bm['bm_id']];
            if (!$bm) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：[%s]货号不存在', $key, $value['bn']);

                return false;
            }

            if ($value['nums'] < $pstore['store_freeze']) {
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：[%s]库存数[%s]不能小于冻结数[%s]', $key, $value['bn'], $value['nums'], $pstore['store_freeze']);

                return false;
            }

            $items[$key]['bm_id'] = $bm['bm_id'];
            // $items[$key]['accounts_num']       = $value['nums'];
            // $items[$key]['accounts_share_num'] = 0;
            // $items[$key]['store']              = $value['nums'];
            // $items[$key]['share_store']        = 0;
        }

        $data = array(
            'items'     => $items,
            'branch_id' => $_SESSION['branch_id'],
        );
        return $data;
    }

    /**
     * 盘点列表查询，method=front.o2o.inventory.listing
     *
     * @return void
     * @author
     **/
    public function listing($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '盘点列表查询';
        $this->__apilog['original_bn'] = '';

        $filter = array();

        if (isset($params['page_no']) && !is_numeric($params['page_no'])) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误', $params['page_no']);

            return false;
        }

        $page_no = $params['page_no'] && $params['page_no'] > 0 ? $params['page_no'] : 1;

        if (isset($params['page_size']) && $params['page_size'] > self::MAX_LIMIT) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误，最大允许[%s]', $params['page_size'], self::MAX_LIMIT);

            return false;
        }

        $limit = $params['page_size'] ? $params['page_size'] : 100;

        if ($params['inventory_bn']) {
            $invMdl = app::get('o2o')->model('inventory');

            $inv = $invMdl->db_dump(array('inventory_bn' => $params['inventory_bn']), 'inventory_id');
            if (!$inv) {
                $this->__apilog['result']['msg'] = sprintf('[%s]盘点单号不存在', $params['inventory_bn']);

                return false;
            }

            $filter['inventory_id'] = $inv['inventory_id'];
        }

        $filter['offset']    = ($page_no - 1) * $limit;
        $filter['limit']     = $limit;
        $filter['branch_id'] = $_SESSION['branch_id'];

        return $filter;
    }

    /**
     * 盘点单详情查询，method=front.o2o.inventory.get
     *
     * @return void
     * @author
     **/
    public function get($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '门店详情查询';
        $this->__apilog['original_bn'] = $params['inventory_bn'];

        if (!$params['inventory_bn']) {
            $this->__apilog['result']['msg'] = '缺少盘点单编码';
            return false;
        }

        $filter = array(
            'inventory_bn' => $params['inventory_bn'],
            'branch_id'    => $_SESSION['branch_id'],
        );

        return $filter;

    }

    /**
     * 盘点单总数，method=front.o2o.inventory.count
     *
     * @return void
     * @author
     **/
    public function count($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '门店详情查询';
        $this->__apilog['original_bn'] = $params['inventory_bn'];

        $filter = array(
            'branch_id' => $_SESSION['branch_id'],
        );

        if ($params['inventory_bn']) {
            $invMdl = app::get('o2o')->model('inventory');

            $inv = $invMdl->db_dump(array('inventory_bn' => $params['inventory_bn']), 'inventory_id');

            $filter['inventory_id'] = (int) $inv['inventory_id'];
        }

        return $filter;
    }
}
