<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/1/29
 * @Describe: pos 其他出入库lib类型
 */
class pos_iso
{
    /**
     * 创建pos 其他出入库单据
     * @Author: xueding
     * @Vsersion: 2023/1/29 下午1:27
     * @param $data
     * @return array
     */

    public function create($data)
    {
        $isoMdl      = app::get('pos')->model('iso');
        $isoItemsMdl = app::get('pos')->model('iso_items');
        $isoItems    = $data['iso_items'];
        if ($data && $isoItems) {
            unset($data['iso_items']);
            if (!$data['iso_id']) {
                $isoId = $isoMdl->insert($data);
            } else {
                $isoId = $data['iso_id'];
            }
            if ($isoId) {
                foreach ($isoItems as $key => $val) {
                    $val['iso_id'] = $isoId;
                    $itemsRes      = $isoItemsMdl->insert($val);
                }
                if ($itemsRes) {
                    return [true, '创建成功'];
                } else {
                    return [false, '创建库存变动出入库明细失败'];
                }
            }
        }
        return [false, '创建库存变动出入库明细失败'];
    }
    
    /**
     * 导入出入库单据
     * @Author: xueding
     * @Vsersion: 2023/1/29 下午7:28
     * @param $import_file
     * @param int $io
     * @return mixed
     */
    public function doImport($import_file, $io = 0)
    {
        return kernel::single('omecsv_phpoffice')->import($import_file, function ($line, $buffer, $post) use ($io) {
            static $title;
            
            $tmpTitle = $this->getTitle($io);
            if ($line == 1) {
                return [true];
            }
            
            if ($line == 2) {
                $title = $buffer;
                
                // 验证模板是否正确
                if (array_filter($title) != array_values($tmpTitle)) {
                    return [false, '导入模板不正确'];
                }
                return [true];
            }
            
            $row = array_combine(array_keys($tmpTitle), $buffer);
            
            // 数据验证
            foreach ($row as $k => $v) {
                if ('*' == mb_substr($tmpTitle[$k], 0,1) && !$v) {
                    return [false, sprintf('%s必填', $tmpTitle[$k])];
                }
            }
            
            $data       = [
                'iso_bn'     => $row['iso_bn'],
                'stock_unit' => $row['stock_unit'],
                'created'    => kernel::single('ome_func')->date2time($row['created']),
                'salesman'   => $row['salesman'],
                'remark'     => $row['remark'],
                'iso_type'   => $io,
            ];
            $posIsoInfo = app::get('pos')->model('iso')->db_dump(['iso_bn' => $row['iso_bn']], 'iso_id');
            if ($posIsoInfo) {
                $data['iso_id'] = $posIsoInfo['iso_id'];
            }
            // todo.XueDing: stock_unit 门店判断是否存在
            $branchBn   = $row['branch_bn'];
            $branchInfo = app::get('ome')->model('branch')->db_dump(['branch_bn' => $branchBn], 'branch_id');
            if (!$branchInfo) {
                return [false, '未找到对应仓库信息'];
            }
            $db           = kernel::database();
            $sql          = "SELECT m.bm_id,m.material_name,me.retail_price FROM sdb_material_basic_material AS m,sdb_material_basic_material_ext AS me WHERE m.bm_id = me.bm_id AND m.material_bn = '" . $row['goods_bn'] . "'";
            $materialInfo = $db->selectrow($sql);
            if (!$materialInfo) {
                return [false, '未找到对应商品物料信息编码：' . $row['goods_bn']];
            }
            
            $data['branch_id']   = $branchInfo['branch_id'];
            $stockItems          = [
                'goods_bn'          => $row['goods_bn'],
                'specifications'    => $row['specifications'],
                'goods_attr'        => $row['goods_attr'],
                'storage_code'      => $row['storage_code'],
                'basic_calc_unit'   => $row['basic_calc_unit'],
                'plan_qty'          => $row['plan_qty'],
                'actual_qty'        => $row['actual_qty'],
                'assist_calc_unit'  => $row['assist_calc_unit'],
                'plan_qty_assist'   => $row['plan_qty_assist'],
                'actual_qty_assist' => $row['actual_qty_assist'],
                'item_remark'       => $row['item_remark'],
                'batch_code'        => $row['batch_code'],
                'product_date'      => $row['product_date'],
                'expire_date'       => $row['expire_date'],
                'bm_id'             => $materialInfo['bm_id'],
                'product_id'        => $materialInfo['bm_id'],
                'goods_name'        => $materialInfo['material_name'],
                'price'             => $materialInfo['retail_price'],
            ];
            $data['iso_items'][] = $stockItems;
            
            return $this->create($data);
        }, $_POST);
    }
    
    /**
     * 创建iso单据
     * @Author: xueding
     * @Vsersion: 2023/1/29 下午7:29
     */
    public function createIostock()
    {
        $isoMdl                = app::get('pos')->model('iso');
        $isoItemsMdl           = app::get('pos')->model('iso_items');
        $isoList               = $isoMdl->getList('*', ['create_iso_status' => '0']);
        $isoIds                = array_column($isoList, 'iso_id');
        $isoItemsList          = $isoItemsMdl->getList('*', ['iso_id' => $isoIds]);
        $isoItemsList          = ome_func::filter_by_value($isoItemsList, 'iso_id');
        $iostockorder_instance = kernel::single('console_iostockorder');
        $op                    = kernel::single('ome_func')->getDesktopUser();
        foreach ($isoList as $key => $val) {
            $msg     = '创建单据成功';
            $ioTitle = '入库单';
            $typeId  = ome_iostock::DIRECT_STORAGE;
            if (!$val['iso_type']) {
                $ioTitle = '出库单';
                $typeId  = ome_iostock::DIRECT_LIBRARAY;
            }
            $isoItem = $isoItemsList[$val['iso_id']];
            if ($isoItem) {
                foreach ($isoItem as $v) {
                    $products[$v['product_id']] = [
                        'bn'    => $v['goods_bn'],
                        'name'  => $v['goods_name'],
                        'nums'  => $v['actual_qty'],
                        'unit'  => $v['specifications'],
                        'price' => $v['price'],
                    ];
                }
            }
            $data  = array(
                'iostockorder_name' => date('Ymd') . '库存变动其他' . $ioTitle,
                'supplier'          => '',
                'supplier_id'       => 0,
                'branch'            => $val['branch_id'],
                'type_id'           => $typeId,
                'iso_price'         => 0,
                'memo'              => (string)$val['remark'],
                'operator'          => $op['op_name'],
                'original_bn'       => $val['iso_bn'],
                'original_id'       => $val['iso_id'],
                'products'          => $products,
                'appropriation_no'  => '',
                'bill_type'         => 'normal',
                'check'             => 'Y',
                'confirm'           => 'Y',
            );
            $IoRes = $iostockorder_instance->save_iostockorder($data, $msg);
            if ($IoRes) {
                $isoMdl->update(['create_iso_status' => '1']);
            } else {
                $isoMdl->update(['create_iso_status' => '2', 'create_iso_msg' => $msg]);
            }
        }
    }
    
    /**
     * 获取Title
     * @param mixed $iso_type iso_type
     * @return mixed 返回结果
     */
    public function getTitle($iso_type = '0')
    {
        $stockIn = [
            'iso_bn'            => '单据编码',
            'stock_unit'        => '*入库单位编码',
            'created'           => '单据日期',
            'salesman'          => '业务员编码',
            'remark'            => '备注',
            'goods_bn'          => '*商品编码',
            'specifications'    => '规格编码',
            'goods_attr'        => '规格属性',
            'branch_bn'         => '*仓库编码',
            'storage_code'      => '库位',
            'basic_calc_unit'   => '基本计量单位',
            'plan_qty'          => '*应收数量(基本单位)',
            'actual_qty'        => '*实收数量(基本单位)',
            'assist_calc_unit'  => '辅助计量单位',
            'plan_qty_assist'   => '*应收数量(辅助单位)',
            'actual_qty_assist' => '*实收数量(辅助单位)',
            'batch_code'        => '批次号',
            'product_date'      => '生产日期',
            'expire_date'       => '有效期至',
            'item_remark'       => '明细行备注',
        ];
        
        $stockOut = [
            'iso_bn'            => '单据编码',
            'stock_unit'        => '*出库单位',
            'created'           => '*单据日期',
            'salesman'          => '*业务员',
            'remark'            => '备注',
            'goods_bn'          => '*商品编码',
            'specifications'    => '规格编码',
            'goods_attr'        => '规格属性',
            'branch_bn'         => '*出库仓库',
            'storage_code'      => '库位',
            'basic_calc_unit'   => '基本计量单位',
            'actual_qty'        => '*出库数量(基本单位)',
            'assist_calc_unit'  => '辅助计量单位',
            'actual_qty_assist' => '出库数量(辅助单位)',
            'batch_code'        => '批次号',
            'product_date'      => '生产日期',
            'expire_date'       => '有效期至',
            'item_remark'       => '明细行备注',
        ];
        if ($iso_type == '1') {
            return $stockIn;
        } elseif ($iso_type == '0') {
            return $stockOut;
        } else {
            return array();
        }
    }
}