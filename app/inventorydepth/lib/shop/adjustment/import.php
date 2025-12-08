<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class inventorydepth_shop_adjustment_import
{
    const IMPORT_TITLE = [
        '销售物料编码' => 'bn',
        '发布数量'   => 'num',
    ];

    public function getExcelTitle()
    {
        return ['导入发布库存.xlsx', [array_keys(self::IMPORT_TITLE)]];
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function processExcelRow($import_file, $post)
    {
        if(empty($post['shop_id'])) {
            return [false, '未选择店铺'];
        }
        if(empty($post['mode'])) {
            return [false, '未选择发布类型'];
        }
        $post['batch_queue'] = time().uniqid();
        $post['file_name'] =  $_FILES['import_file']['name'];
        // 读取文件
        return kernel::single('omecsv_phpoffice')->import($import_file, function ($line, $buffer, $post, $highestRow) {
            static $title;

            if ($line == 1) {
                $title = $buffer;

                // 验证模板是否正确
                if (array_filter($title) != array_keys(self::IMPORT_TITLE)) {

                    return [false, '导入模板不正确'];
                }

                return [true];
            }
            if(count($buffer) < count(self::IMPORT_TITLE)) {
                return [true, '导入列不够', 'warnning'];
            }
            $buffer = array_combine(array_values(self::IMPORT_TITLE), array_slice($buffer, 0, count(self::IMPORT_TITLE)));
            if(!app::get('material')->model('sales_material')->db_dump(['sales_material_bn'=>$buffer['bn']], 'sm_id')) {
                return [true, '该销售物料：'.$buffer['bn'].'不存在', 'warning'];
            }
            if(!is_numeric($buffer['num'])){
                return [true, '发布数量必须为数字', 'warning'];
            }
            $inData = [
                'sales_material_bn' => $buffer['bn'],
                'shop_id' => $post['shop_id'],
                'batch_queue' => $post['batch_queue'],
                'quantity' => $buffer['num'],
                'mode' => $post['mode'],
                'op_name' => kernel::single('ome_func')->getDesktopUser()['op_name'],
                'file_name' => $post['file_name'],
            ];
            app::get('inventorydepth')->model('stock_release')->insert($inData);
            if(!$inData['id']) {
                return [true, '该销售物料：'.$buffer['bn'].'写入失败', 'warning'];
            }
            $memo = '导入发布:'.$buffer['num'];
            $stock = array(
                'bn' => $buffer['bn'],
                'quantity' => $buffer['num'],
                'stock_release_id' => $inData['id']
            );

            $shop = app::get('ome')->model('shop')->db_dump(['shop_id'=>$post['shop_id']], 'node_type');
            if (kernel::single('inventorydepth_sync_set')->isUseSkuid($shop)){
                $skuModel = app::get('inventorydepth')->model('shop_skus');
                $skuRow = $skuModel->getList('shop_sku_id', ['shop_id'=>$post['shop_id'], 'shop_product_bn'=>$buffer['bn']]);
                if($skuRow && 1 == count($skuRow)) {
                    $stock['sku_id'] = $skuRow[0]['shop_sku_id'];
                }
            }

            if($post['mode'] == 'inc') {
                $memo = '增量'.$memo;
                $stockApiModel = app::get('ome')->model('api_stock_log');
                $apiRow = $stockApiModel->db_dump(array('product_bn'=>$buffer['bn'], 'shop_id'=>$post['shop_id']), 'store');
                $stock['quantity'] += $apiRow['store'];
                $stock['inc_quantity'] = $buffer['num'];
                $stock['quantity_type'] = 'inc';
            }
            if($stock['quantity'] < 0) {
                app::get('inventorydepth')->model('stock_release')->update([
                    'sync_status' => 'fail',
                    'sync_msg' => '回写数为负数，不可回写'
                ], ['id'=>$inData['id']]);
                return [true];
            }

            $stock['memo'] = $memo;
            $stocks[] = $stock;
            $result = kernel::single('inventorydepth_service_shop_stock')->items_quantity_list_update($stocks,$post['shop_id'],true);
            if($result['rsp'] != 'running') {
                $result['msg'] = $result['msg'] ? : $result['err_msg'];
                app::get('inventorydepth')->model('stock_release')->update([
                    'sync_status' => ($result['rsp'] == 'succ' ? 'succ' : 'fail'),
                    'sync_msg' => ($result['msg'] ? mb_strcut($result['msg'], 0, 200, 'utf8') : '')
                ], ['id'=>$inData['id']]);
            }
            return [true];
        }, $post);
    }
}
