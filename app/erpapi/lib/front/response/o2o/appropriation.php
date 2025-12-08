<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *  前后端分离，调拨单类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:26:39+08:00
 */
class erpapi_front_response_o2o_appropriation extends erpapi_front_response_o2o_abstract
{
    /**
     * 调拨单创建，method=front.o2o.appropriation.add
     *
     * @return void
     * @author
     **/

    public function add($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '调拨单创建';
        $this->__apilog['original_bn'] = '';

        if (!$params['from_store']) {
            $this->__apilog['result']['msg'] = '缺少出货门店';
            return false;
        }

        if (!$params['to_store']) {
            $this->__apilog['result']['msg'] = '缺少到货门店';
            return false;
        }

        $branchMdl = app::get('ome')->model('branch');

        // 出货仓处理
        $from_store = $branchMdl->db_dump(array('branch_bn' => $params['from_store'], 'check_permission' => 'false'), 'branch_id,name,branch_bn');
        if (!$from_store) {
            $this->__apilog['result']['msg'] = sprintf('出货门店[%s]：未维护', $params['from_store']);
            return false;
        }

        // 进货仓处理
        $to_store = $branchMdl->db_dump(array('branch_bn' => $params['to_store'], 'check_permission' => 'false'), 'branch_id,name,branch_bn');
        if (!$to_store) {
            $this->__apilog['result']['msg'] = sprintf('出货门店[%s]：未维护', $params['to_store']);

            return false;
        }

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
                $this->__apilog['result']['msg'] = sprintf('行明细[%s]：数量异常', $key);
                return false;
            }

            $bn_list[] = $value['bn'];
        }

        $bm_list = app::get('material')->model('basic_material')->getList('bm_id,material_bn', array('material_bn' => $bn_list));

        $bm_list    = array_column($bm_list, null, 'material_bn');
        $bm_id_list = array_column($bm_list, 'bm_id');

        // 查询出库仓库存
        $bpModel       = app::get('ome')->model('branch_product');
        $product_store = array();
        foreach ($bpModel->getList('*', array('branch_id' => $from_store['branch_id'], 'product_id' => $bm_id_list)) as $value) {
            $product_store[$value['branch_id']][$value['product_id']] = $value['store'] - $value['store_freeze'];
        }
        foreach ($bpModel->getList('*', array('branch_id' => $to_store['branch_id'], 'product_id' => $bm_id_list)) as $value) {
            $product_store[$value['branch_id']][$value['product_id']] = $value['store'] - $value['store_freeze'];
        }

        // 判断出货仓是否有库存
        foreach ($items as $key => $value) {
            $bm_id = $bm_list[$value['bn']]['bm_id'];

            if (!$bm_id) {
                $this->__apilog['result']['msg'] = sprintf('行明细物料[%s]：未维护', $key);

                return false;
            }

            if ($value['nums'] > $product_store[$from_store['branch_id']][$bm_id]) {
                $this->__apilog['result']['msg'] = sprintf('出货门店[%s]：库存不足', $from_store['store_bn']);

                return false;
            }

            $items[$key]['product_id']      = $bm_id;
            $items[$key]['from_branch_id']  = $from_store['branch_id'];
            $items[$key]['to_branch_id']    = $to_store['branch_id'];
            $items[$key]['num']             = $value['nums'];
            $items[$key]['to_branch_num']   = $product_store[$to_store['branch_id']][$bm_id];
            $items[$key]['from_branch_num'] = $product_store[$from_store['branch_id']][$bm_id];

            unset($items[$key]['nums']);
        }

        $data = array(
            'items'    => $items,
            'is_check' => true,
            'memo'     => $params['memo'],
        );

        return $data;
    }

    /**
     * 调拨单列表查询，method=front.o2o.appropriation.listing
     *
     * @return void
     * @author
     **/
    public function listing($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '调拨单创建';
        $this->__apilog['original_bn'] = '';

        $filter = array(
            'to_branch_id' => $_SESSION['branch_id'],
        );

        $approMdl = app::get('taoguanallocate')->model('appropriation');

        if ($params['appropriation_no']) {
            $appro = $approMdl->db_dump(array('appropriation_no' => $params['appropriation_no']));

            if (!$appro) {
                $this->__apilog['result']['msg'] = sprintf('[%s]调拨单不存在', $params['appropriation_no']);
                return false;
            }

            $filter['appropriation_id'] = $appro['appropriation_id'];
        }

        if (isset($params['page_no']) && !is_numeric($params['page_no'])) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误', $params['page_no']);

            return false;
        }

        $page_no = $params['page_no'] && $params['page_no'] > 0 ? $params['page_no'] : 1;

        if (isset($params['page_size']) && $params['page_size'] > self::MAX_LIMIT) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误，最大允许[%s]', $params['page_size'], self::MAX_LIMIT);

            return false;
        }

        $page_size = $params['page_size'] ? $params['page_size'] : self::MAX_LIMIT;

        $filter['offset'] = ($page_no - 1) * $page_size;
        $filter['limit']  = $page_size;

        return $filter;
    }

    /**
     * 调拨单总数统计，method=front.o2o.appropriation.count
     *
     * @return void
     * @author
     **/
    public function count($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '调拨单总计';
        $this->__apilog['original_bn'] = '';

        $filter = array(
            'to_branch_id' => $_SESSION['branch_id'],
        );

        return $filter;
    }

    /**
     * 调拨单详情查询，method=front.o2o.appropriation.get
     *
     * @return void
     * @author
     **/
    public function get($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '调拨单详情查询';
        $this->__apilog['original_bn'] = $params['appropriation_no'];

        if (!$params['appropriation_no']) {
            $this->__apilog['result']['msg'] = '缺少调拨单号';

            return false;
        }

        $filter = array(
            'appropriation_no' => $params['appropriation_no'],
        );

        return $filter;
    }
}
