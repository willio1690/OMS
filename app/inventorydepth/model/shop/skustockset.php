<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2021/8/12 11:24:17
 * @describe: model层
 * ============================
 */
class inventorydepth_mdl_shop_skustockset extends dbeav_model {

    private $templateColumn = array(
        '记录ID' => 'skus_id',
        '系统仓库ID' => 'branch_bn',
        'OMS商品编码' => 'shop_product_bn',
        '平台商品ID' => 'shop_iid',
        '平台SKUID' => 'shop_sku_id',
        '平台独立库存' => 'stock_only',
    );

    public function getTemplateColumn() {
        return array_keys($this->templateColumn);
    }
    public function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
        $this->smBranchStock = [];
        $this->import_data = [];
        $this->smBmIds = [];
    }
    private $smBranchStock;
    private $import_data;
    private $smBmIds;
    public function prepared_import_csv_row($row,&$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
    {
        if(empty($row) || empty(array_filter($row))) return false;
        if( $row[0] == '记录ID' ){
            $title = array_flip($row);
            foreach($this->templateColumn as $k => $val) {
                if(!isset($title[$k])) {
                    $msg['error'] = '在导出模板(或另存为xls)中，填入平台独立库存导入，请勿更改模板内容';
                    return false;
                }
            }
            return false;
        }
        if (empty($title)) {
            $msg['error'] = "在导出模板(或另存为xls)中，填入平台独立库存导入，请勿更改模板内容";
            return false;
        }
        $arrData = array();
        foreach($this->templateColumn as $k => $val) {
            $arrData[$val] = trim(str_replace(['"', "'"], '', $row[$title[$k]]));
            if(!isset($arrData[$val]) || $arrData[$val] == '') {
                $msg['warning'][] = '不能都为空！';
                return false;
            }
        }
        $fields = 'id,shop_product_bn,shop_sku_id,shop_iid,shop_title';
        $shopSku = app::get('inventorydepth')->model('shop_skus')->db_dump(['id'=>$arrData['skus_id']], $fields);
        if(empty($shopSku)) {
            $msg['warning'][] = '记录ID不存在';
            return false;
        }
        if($arrData['shop_product_bn'] != $shopSku['shop_product_bn']) {
            $msg['warning'][] = 'OMS商品编码与记录ID不匹配';
            return false;
        }
        if($arrData['shop_sku_id'] != $shopSku['shop_sku_id']) {
            $msg['warning'][] = '平台SKUID与记录ID不匹配';
            return false;
        }
        if($arrData['shop_iid'] != $shopSku['shop_iid']) {
            $msg['warning'][] = '平台商品ID与记录ID不匹配';
            return false;
        }
        $branchInfo = app::get('ome')->model('branch')->db_dump(['branch_bn'=>$arrData['branch_bn']], 'branch_id,branch_bn');
        if(empty($branchInfo)) {
            $msg['warning'][] = '系统仓库ID不存在';
            return false;
        }
        if(!isset($this->smBranchStock[$shopSku['shop_product_bn']])) {
            $smStockRows = kernel::single('material_sales_material')->getSmBranchStock(['sales_material_bn'=>$shopSku['shop_product_bn']]);
            foreach ($smStockRows as $v) {
                $this->smBranchStock[$shopSku['shop_product_bn']][$v['branch_id']] = $v;
            }
        }
        $smBranchStock = $this->smBranchStock[$shopSku['shop_product_bn']][$branchInfo['branch_id']];
        $stock_only = $arrData['stock_only'];
        if(($smBranchStock['valid_stock'] - $smBranchStock['store_used']) < $stock_only) {
            $msg['warning'][] = '平台独立库存'.$stock_only.'比可用库存'.($smBranchStock['valid_stock'] - $smBranchStock['store_used']).'大，变为0，库存：'.intval($smBranchStock['store']).'，冻结库存：'.intval($smBranchStock['store_freeze']).'，已用：'.intval($smBranchStock['store_used']);
            $stock_only = 0;
        }
        $this->smBranchStock[$shopSku['shop_product_bn']][$branchInfo['branch_id']]['store_used'] += $stock_only;
        $sdf = [
            'skus_id' => $arrData['skus_id'],
            'shop_product_bn' => $shopSku['shop_product_bn'],
            'branch_id' => $branchInfo['branch_id'],
            'branch_bn' => $arrData['branch_bn'],
            'stock' => (int) $smBranchStock['store'],
            'freeze' => (int) $smBranchStock['store_freeze'],
            'stock_only' => $stock_only,
            'last_modify' => time()
        ];
        foreach ($smBranchStock['bm_ids'] as $v) {
            $this->smBmIds[$v] = $v;
        }
        $this->import_data[$arrData['branch_bn'].'|-|'.$arrData['shop_product_bn']][] = $sdf;
        return true;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    public function finish_import_csv(){
        if(empty($this->import_data)) {
            return null;
        }
        $basicMaterialStockObj = app::get('material')->model('basic_material_stock');
        if($this->smBmIds) $basicMaterialStockObj->update(['max_store_lastmodify_upset_sql'=>'UNIX_TIMESTAMP()'], ['bm_id'=>$this->smBmIds]);
        foreach ($this->import_data as $key => $value) {
            list($branch_bn, $shop_product_bn) = explode('|-|', $key);
            $this->delete(['branch_bn'=>$branch_bn,'shop_product_bn'=>$shop_product_bn]);
            $sql = ome_func::get_insert_sql($this, $value);
            $this->db->exec($sql);
        }
        return null;
    }
}