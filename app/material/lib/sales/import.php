<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_sales_import
{
    const IMPORT_TITLE = [
        '销售物料编码' => 'sales_material_bn',
        '开票税率'   => 'tax_rate',
        '开票名称'   => 'tax_name',
        '发票分类编码' => 'tax_code',
    ];

    /**
     * 获取ExcelTitle
     * @return mixed 返回结果
     */
    public function getExcelTitle()
    {
        return ['销售物料导入发票信息模板.xlsx', [array_keys(self::IMPORT_TITLE)]];
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function processExcelRow($import_file, $post)
    {
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

            $smMdl           = app::get('material')->model('sales_material');
            $bmMdl           = app::get('material')->model('basic_material');
            $basicSmMdl      = app::get('material')->model('sales_basic_material');
            $smInfo          = $smMdl->db_dump(['sales_material_bn' => $buffer['sales_material_bn']], '*');
            if ($smInfo) {
                //更新销售物料
                $rs = $smMdl->update($buffer, ['sm_id' => $smInfo['sm_id']]);

                if ($rs === false) {
                    return [true, $smMdl->db->errorinfo(), 'warnning'];
                }
                //保存日志 压入原始销售物料信息 供快照查看
                $log_memo        = serialize($smInfo);
                $operationLogObj = app::get('ome')->model('operation_log');
                $operationLogObj->write_log('sales_material_edit@wms', $smInfo['sm_id'], $log_memo);

                //更新销售物料关联的基础物料开票信息
                if ($smInfo['sales_material_type'] == '1' && $smInfo['shop_id'] == '_ALL_' && $bmIds = $basicSmMdl->getList('bm_id', ['sm_id' => $smInfo['sm_id']])) {
                    foreach ($bmIds as $val) {
                        $bmInfo = $bmMdl->db_dump($val['bm_id']);
                        $bmMdl->update($buffer, ['bm_id' => $val['bm_id']]);
                        //logs
                        $log_memo        = serialize($bmInfo);
                        $operationLogObj = app::get('ome')->model('operation_log');
                        $operationLogObj->write_log('basic_material_edit@wms', $val['bm_id'], $log_memo);
                    }

                }
            } else {
                return [true, '系统没有该销售物料编码', 'warnning'];
            }

            return [true];
        }, []);
    }
}
