<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *  前后端分离，出入库单类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:26:39+08:00
 */
class erpapi_front_response_o2o_iso extends erpapi_front_response_o2o_abstract
{
    /**
     * 出入库单确认，method=front.o2o.iso.check
     *
     * @return void
     * @author
     **/

    public function check($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '出入库单确认';
        $this->__apilog['original_bn'] = $params['iso_bn'];

        if (!$params['iso_bn']) {
            $this->__apilog['result']['msg'] = '缺少出入库单号';
            return false;
        }

        $isoMdl = app::get('taoguaniostockorder')->model('iso');
        $iso    = $isoMdl->db_dump(array('iso_bn' => $params['iso_bn']));
        if (!$iso) {
            $this->__apilog['result']['msg'] = '确认失败：单据号不存在';

            return false;
        }

        if ($iso['iso_status'] != '1') {
            $columns = $isoMdl->_columns();

            $this->__apilog['result']['msg'] = '确认失败：' . $columns['iso_status']['type'][$iso['iso_status']];

            return false;
        }

        if ($iso['check_status'] != '1') {
            $this->__apilog['result']['msg'] = '确认失败：出入库单已确认';

            return false;
        }

        $filter = array(
            'iso_id' => $iso['iso_id'],
            'iso_bn' => $iso['iso_bn'],
        );

        return $filter;
    }

    /**
     * 出入库单取消，method=front.o2o.iso.cancel
     *
     * @return void
     * @author
     **/
    public function cancel($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '出入库单取消';
        $this->__apilog['original_bn'] = $params['iso_bn'];

        if (!$params['iso_bn']) {
            $this->__apilog['result']['msg'] = '缺少出入库单号';
            return false;
        }

        $isoMdl = app::get('taoguaniostockorder')->model('iso');
        $iso    = $isoMdl->db_dump(array('iso_bn' => $params['iso_bn']));
        if (!$iso) {
            $this->__apilog['result']['msg'] = '取消失败：单据号不存在';

            return false;
        }

        if ($iso['iso_status'] != '1') {
            $columns                         = $isoMdl->_columns();
            $this->__apilog['result']['msg'] = '取消失败：' . $columns['iso_status'][$iso['iso_status']];

            return false;
        }

        if (!in_array($iso['branch_id'], (array) $_SESSION['branch_id']) && !in_array($iso['extrabranch_id'], (array) $_SESSION['branch_id'])) {
            $this->__apilog['result']['msg'] = '确认失败：非本门店出入库单';

            return false;
        }

        $filter = array(
            'iso_id' => $iso['iso_id'],
            'iso_bn' => $iso['iso_bn'],
        );

        return $filter;
    }

    /**
     * 出入库单详情查询，method=front.o2o.iso.get
     *
     * @return void
     * @author
     **/
    public function get($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '出入库单详情查询';
        $this->__apilog['original_bn'] = $params['iso_bn'];

        if (!$params['iso_bn']) {
            $this->__apilog['result']['msg'] = '缺少出入库单号';
            return false;
        }

        $filter = array(
            'iso_bn'    => $params['iso_bn'],
            'branch_id' => $_SESSION['branch_id'],
        );

        return $filter;
    }

    /**
     * 出入库单列表查询，method=front.o2o.iso.listing
     * 只返回本门店权限数据
     *
     * @return void
     * @author
     **/
    public function listing($params)
    {
        $this->__apilog['title']       = '出入库单列表查询';
        $this->__apilog['original_bn'] = '';

        if (isset($params['page_no']) && !is_numeric($params['page_no'])) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误', $params['page_no']);

            return false;
        }

        $page_no = $params['page_no'] && $params['page_no'] > 0 ? $params['page_no'] : 1;

        if (isset($params['page_size']) && $params['page_size'] > self::MAX_LIMIT) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误，最大允许[%s]', $params['page_size'], self::MAX_LIMIT);

            return false;
        }

        $limit = $params['page_size'] ? $params['page_size'] : MAX_LIMIT;

        $filter = array(
            'branch_id' => $_SESSION['branch_id'],
            'offset'    => ($page_no - 1) * $limit,
            'limit'     => $limit,
        );

        $branchMdl = app::get('ome')->model('branch');
        if ($params['extra_branch_bn']) {
            $branch = $branchMdl->db_dump(array('branch_bn' => $params['extra_branch_bn'], 'check_permission' => 'false'), 'branch_id');
            if (!$branch) {
                $this->__apilog['result']['msg'] = sprintf('[%s]仓库编码不存在', $params['extra_branch_bn']);
                return false;
            }

            $filter['extrabranch_id'] = $branch['branch_id'];
        } elseif (!$params['extra_branch_type'] || $params['extra_branch_type'] == 'store') {
            foreach ($branchMdl->getList('branch_id', array('b_type' => '1')) as $value) {
                $filter['extrabranch_id|notin'][] = $value['branch_id'];
            }
        }

        if ($params['iso_bn']) {
            $isoMdl = app::get('taoguaniostockorder')->model('iso');

            $iso = $isoMdl->db_dump(array('iso_bn' => $params['iso_bn']), 'iso_id');

            if (!$iso) {
                $this->__apilog['result']['msg'] = '出入库单不存在';
                return false;
            }

            $filter['iso_id'] = $iso['iso_id'];
        }

        if (isset($params['type_id'])) {
            $filter['type_id'] = $params['type_id'];
        }

        return $filter;
    }

    /**
     * 出入库单列表查询，method=front.o2o.iso.count
     * 只返回本门店权限数据
     *
     * @return void
     * @author
     **/
    public function count($params)
    {
        $this->__apilog['title']       = '出入库单数量';
        $this->__apilog['original_bn'] = '';

        $filter = array(
            'branch_id' => $_SESSION['branch_id'],
        );

        $branchMdl = app::get('ome')->model('branch');
        if ($params['extra_branch_bn']) {
            $branch = $branchMdl->db_dump(array('branch_bn' => $params['extra_branch_bn'], 'check_permission' => 'false'), 'branch_id');
            if (!$branch) {
                $this->__apilog['result']['msg'] = sprintf('[%s]仓库编码不存在', $params['extra_branch_bn']);
                return false;
            }

            $filter['extrabranch_id'] = $branch['branch_id'];
        } elseif (!$params['extra_branch_type'] || $params['extra_branch_type'] == 'store') {
            foreach ($branchMdl->getList('branch_id', array('b_type' => '1')) as $value) {
                $filter['extrabranch_id|notin'][] = $value['branch_id'];
            }
        }

        if ($params['iso_bn']) {
            $isoMdl = app::get('taoguaniostockorder')->model('iso');

            $iso = $isoMdl->db_dump(array('iso_bn' => $params['iso_bn']), 'iso_id');

            $filter['iso_id'] = (int) $iso['iso_id'];
        }

        if (isset($params['type_id'])) {
            $filter['type_id'] = $params['type_id'];
        }

        return $filter;
    }

    /**
     * 出入库单配货完成，通知物流取货，method=front.o2o.iso.ready
     * 只返回本门店权限数据
     *
     * @return void
     * @author
     **/
    public function ready($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '出入库单打包完成';
        $this->__apilog['original_bn'] = $params['iso_bn'];

        if (!$params['iso_bn']) {
            $this->__apilog['result']['msg'] = '缺少出入库单号';
            return false;
        }

        $isoMdl = app::get('taoguaniostockorder')->model('iso');

        $iso = $isoMdl->db_dump(array('iso_bn' => $params['iso_bn'], 'branch_id' => $_SESSION['branch_id']), 'iso_id,iso_bn,check_status,iso_status,type_id');

        if (!$iso) {
            $this->__apilog['result']['msg'] = sprintf('配货失败：[%s]不存在', $params['iso_bn']);

            return false;
        }

        if ($iso['check_status'] != '2') {
            $this->__apilog['result']['msg'] = sprintf('配货失败：[%s]未审核', $iso['iso_bn']);

            return false;
        }

        if ($iso['iso_status'] != '1') {
            $columns = $isoMdl->_columns();

            $this->__apilog['result']['msg'] = '配货失败：' . $columns['iso_status']['type'][$iso['iso_status']];

            return false;
        }

        if ($iso['process_status'] == 'ready') {
            $this->__apilog['result']['msg'] = '配货失败：待提货';

            return false;
        }

        $corp = array();

        // 只有出库的时候才需要呼叫物流
        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);
        if ($io == '0') {

            if (!$params['logi_code']) {
                $this->__apilog['result']['msg'] = '缺少物流编码';

                return false;
            }

            $corp = app::get('ome')->model('dly_corp')->db_dump(array('type' => $params['logi_code']), 'corp_id,name,tmpl_type,channel_id,type');
            if (!$corp) {
                $this->__apilog['result']['msg'] = '配货失败：物流公司编码不存在';

                return false;
            }
        }

        $filter = array(
            'iso_id' => $iso['iso_id'],
            'corp'   => $corp,
        );

        return $filter;
    }

    /**
     * 出入库单出库，method=front.o2o.iso.confirm
     *
     * @return void
     * @author
     **/
    public function confirm($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '出入库单出库';
        $this->__apilog['original_bn'] = $params['iso_bn'];

        if (!$params['iso_bn']) {
            $this->__apilog['result']['msg'] = '缺少出入库单号';
            return false;
        }

        $isoMdl = app::get('taoguaniostockorder')->model('iso');

        $iso = $isoMdl->db_dump(array('iso_bn' => $params['iso_bn'], 'branch_id' => $_SESSION['branch_id']));

        if (!$iso) {
            $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]不存在', $params['iso_bn']);

            return false;
        }

        // 更新审核状态
        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);
        if ($io == '1' && $iso['check_status'] == '1' && $iso['iso_status'] == '1') {
            $isoMdl->update(array('check_status' => '2'), array('iso_id' => $iso['iso_id']));

            $iso['check_status'] = '2';
        }

        if ($iso['check_status'] != '2') {
            $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]未审核', $iso['iso_bn']);

            return false;
        }

        if ($iso['iso_status'] == '2' || $iso['iso_status'] == '3') {
            $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]已出库', $iso['iso_bn']);

            return false;
        }

        if ($iso['iso_status'] == '4') {
            $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]已取消', $iso['iso_bn']);

            return false;
        }

        $iso['io_status'] = $params['status'] ? $params['status'] : 'FINISH';

        $corp = array();
        // 只有出库的时候才需要呼叫物流
        if ($io == '0') {

            if (!$params['logi_code']) {
                $this->__apilog['result']['msg'] = '缺少物流编码';

                return false;
            }

            $corp = app::get('ome')->model('dly_corp')->db_dump(array('type' => $params['logi_code']), 'corp_id,name,tmpl_type,channel_id,type');
            if (!$corp) {
                $this->__apilog['result']['msg'] = '配货失败：物流公司编码不存在';

                return false;
            }

            $iso['corp'] = $corp;

        }

        $params['items'] = $params['items'] ? @json_decode($params['items'], true) : array();

        $items = array();
        foreach ($params['items'] as $key => $value) {
            if ($value['nums'] < 0 || !is_numeric($value['nums'])) {
                $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]库存非法[%s]', $value['bn'], $value['nums']);
                return false;
            }

            $items[$value['bn']] = array(
                'bn'         => $value['bn'],
                'normal_num' => $value['nums'],
            );
        }

        if ($items) {
            $items = array_column($items, null, 'bn');
        }

        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');
        $item_list  = $isoItemMdl->getList('*', array('iso_id' => $iso['iso_id']));

        // 如果传了明细，判断库存
        if ($items) {
            foreach ($item_list as $key => $value) {
                $nums = $value['nums'] - $value['normal_num'] - $value['defective_num'];

                if ($io == '0' && $nums < $items[$value['bn']]['normal_num']) {
                    $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]库存不足', $value['bn']);
                    return false;
                }
            }

            $iso['items'] = $items;
        } else {
            $items = array();
            foreach ($item_list as $key => $value) {
                $items[] = array(
                    'bn'         => $value['bn'],
                    'normal_num' => ($value['nums'] - $value['normal_num'] - $value['defective_num']),
                );
            }

            $iso['items'] = $items;
        }

        return $iso;
    }
}
