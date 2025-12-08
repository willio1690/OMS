<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 基础物料模型层
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */
class material_mdl_basic_material extends dbeav_model{
    //是否有导出配置
    var $has_export_cnf = true;
    //导出的文件名
    var $export_name = '基础物料';

    var $defaultOrder = array('bm_id',' DESC');

    
    /**
     * 基础物料列表项扩展字段
     */

    function extra_cols(){
        return array(
            'column_cost' => array('label'=>'成本价','width'=>'75','func_suffix'=>'cost'),
            'column_retail_price' => array('label'=>'零售价','width'=>'75','func_suffix'=>'retail_price'),
            'column_weight' => array('label'=>'重量','width'=>'75','func_suffix'=>'weight'),
            'column_volume' => array('label'=>'体积','width'=>'100','func_suffix'=>'volume'),
            'column_unit' => array('label'=>'包装单位','width'=>'75','func_suffix'=>'unit'),
            'column_barcode' => array('label'=>'条形码','width'=>'125','func_suffix'=>'barcode'),
            'column_specifications' => array('label'=>'物料规格','width'=>'120','func_suffix'=>'specifications'),
            'column_brand' => array('label'=>'物料品牌','width'=>'120','func_suffix'=>'brand'),
            'column_semi_material' => array('label'=>'关联半成品信息','width'=>'300','func_suffix'=>'semi_material'),
            'column_goods_type' => array('label'=>'物料类型','width'=>'120','func_suffix'=>'goods_type'),
            'column_season' => array('label'=>'季节','width'=>'120','func_suffix'=>'season'),
            'column_uppermatnm' => array('label'=>'材质','width'=>'120','func_suffix'=>'uppermatnm'),
            'column_gendernm' => array('label'=>'适用对象','width'=>'120','func_suffix'=>'gendernm'),
            'column_widthnm' => array('label'=>'鞋型','width'=>'120','func_suffix'=>'widthnm'),
            'column_modelnm' => array('label'=>'风格款式','width'=>'120','func_suffix'=>'modelnm'),
        );
    }

    /**
     * 条码扩展字段格式化
     */
    function extra_barcode($rows){
        return kernel::single('material_extracolumn_basicmaterial_barcode')->process($rows);
    }

    /**
     * 成本扩展字段格式化
     */
    function extra_cost($rows){
        return kernel::single('material_extracolumn_basicmaterial_cost')->process($rows);
    }

    /**
     * 售价扩展字段格式化
     */
    function extra_retail_price($rows){
        return kernel::single('material_extracolumn_basicmaterial_retailprice')->process($rows);
    }

    /**
     * 重量扩展字段格式化
     */
    function extra_weight($rows){
        return kernel::single('material_extracolumn_basicmaterial_weight')->process($rows);
    }

    /**
     * 体积扩展字段格式化
     */
    function extra_volume($rows){
        return kernel::single('material_extracolumn_basicmaterial_volume')->process($rows);
    }

    /**
     * 包装单位扩展字段格式化
     */
    function extra_unit($rows){
        return kernel::single('material_extracolumn_basicmaterial_unit')->process($rows);
    }

    /**
     * 商品类型扩展字段格式化
     */
    function extra_goods_type($rows){
        return kernel::single('material_extracolumn_basicmaterial_goodstype')->process($rows);
    }

    /**
     * 物料规格字段格式化
     */
    function extra_specifications($rows){
        return kernel::single('material_extracolumn_basicmaterial_specifications')->process($rows);
    }

    /**
     * 物料品牌字段格式化
     */
    function extra_brand($rows){
        return kernel::single('material_extracolumn_basicmaterial_brand')->process($rows);
    }

    /**
     * 半成品明细
     */
    function extra_semi_material($rows){
        return kernel::single('material_extracolumn_basicmaterial_semimaterial')->process($rows);
    }

   
    function extra_season($rows){
        return kernel::single('material_extracolumn_basicmaterial_season')->process($rows);
    }

    function extra_uppermatnm($rows){
        return kernel::single('material_extracolumn_basicmaterial_uppermatnm')->process($rows);
    }

    function extra_gendernm($rows){
        return kernel::single('material_extracolumn_basicmaterial_gendernm')->process($rows);
    }

    function extra_widthnm($rows){
        return kernel::single('material_extracolumn_basicmaterial_widthnm')->process($rows);
    }

    function extra_modelnm($rows){
        return kernel::single('material_extracolumn_basicmaterial_modelnm')->process($rows);
    }
    /**
     * 物料类型字段格式化
     * @param string $row 物料类型字段
     * @return string
     */
    function modifier_type($row){
        if($row == '1'){
            return '成品';
        }elseif($row == '2'){
            return '半成品';
        }else if($row == '3'){
            return '普通';
        }else if($row=='4'){
            return '礼盒';
        }elseif($row == '5'){
            return '虚拟';
        }
    }

    /**
     * 物料是否可见字段格式化
     * @param string $row 物料是否可见字段
     * @return string
     */
    function modifier_visibled($row){
        if($row == '1'){
            return '在售';
        }elseif($row == '2'){
            return '停售';
        }
    }
    
    /**
     * 是否全渠道字段格式化
     * 
     * @param string $row
     * @return string
     */
    function modifier_omnichannel($row){
        if($row == '1'){
            return '开启';
        }
        else 
        {
            return '关闭';
        }
    }

    //导出字段配置 移除不需要的字段
    /**
     * disabled_export_cols
     * @param mixed $cols cols
     * @return mixed 返回值
     */
    public function disabled_export_cols(&$cols){
        unset($cols['column_edit']);
    }

    /**
     * 导入模板的标题
     * 
     * @param Null
     * @return Array
     */
    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    /**
     * 导入导出的标题
     * 
     * @param Null
     * @return Array
     */
    function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'basicM':
                $this->oSchema['csv'][$filter] = array(
                    '*:基础物料名称' => 'material_name',
                    '*:基础物料编码' => 'material_bn',
                    '*:基础物料属性' => 'type',
                    '*:条码' => 'material_code',
                    '*:是否在售' => 'visibled',
                    '*:包装单位' => 'unit',
                    '*:零售价' => 'retail_price',
                    '*:成本价' => 'cost',
                    '*:重量' => 'weight',
                    '*:关联半成品信息' => 'at',
                    '*:是否启用保质期监控' => 'use_expire',
                    '*:预警天数配置' => 'warn_day',
                    '*:自动退出库存天数配置' => 'quit_day',
                    '*:开票税率' => 'tax_rate',
                    '*:开票名称' => 'tax_name',
                    '*:发票分类编码' => 'tax_code',
                    '*:基础物料类型' => 'cat_id',
                    '*:规格' => 'specifications',
                    '*:品牌' => 'brand_id',
                    '*:特殊扫码配置' => 'special_setting',
                    '*:特殊扫码开始位数' => 'first_num',
                    '*:特殊扫码结束位数' => 'last_num',
                    '*:是否自动生成销售物料' => 'create_material_sales',
                    '*:是否全渠道' => 'omnichannel',
                    '*:是否启用唯一码'=>'serial_number',
                    '*:基础物料款号'=>'material_spu',
                    );
                break;
            case 'exportBasicM':
                $this->oSchema['csv'][$filter] = array(
                    '*:基础物料名称' => 'material_name',
                    '*:基础物料编码' => 'material_bn',
                    '*:物料属性' => 'material_type',
                    '*:销售状态' => 'visible',
                    '*:重量' => 'weight',
                    '*:物料规格' => 'specifications',
                    '*:物料品牌' => 'brand_id',
                    '*:成本' => 'cost',
                    '*:零售价' => 'retail_price',
                    '*:包装单位' => 'unit',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
    }

    private $defaulttemplateColumn = array(
        '*:基础物料名称' => 'material_name',
        '*:基础物料编码' => 'material_bn',
        '*:基础物料属性' => 'type',
        '*:条码' => 'material_code',
        '*:是否在售' => 'visibled',
        '*:包装单位' => 'unit',
        '*:零售价' => 'retail_price',
        '*:成本价' => 'cost',
        '*:重量' => 'weight',
        '*:关联子商品信息' => 'at',
        '*:是否启用保质期监控' => 'use_expire',
        '*:预警天数配置' => 'warn_day',
        '*:自动退出库存天数配置' => 'quit_day',
        '*:开票税率' => 'tax_rate',
        '*:开票名称' => 'tax_name',
        '*:发票分类编码' => 'tax_code',
        '*:基础物料类型' => 'cat_id',
        '*:规格' => 'specifications',
        '*:品牌' => 'brand_id',
        '*:特殊扫码配置' => 'special_setting',
        '*:特殊扫码开始位数' => 'first_num',
        '*:特殊扫码结束位数' => 'last_num',
        '*:是否自动生成销售物料' => 'create_material_sales',
        '*:是否全渠道' => 'omnichannel',
        '*:是否启用唯一码'=>'serial_number',
        '*:基础物料款号'=>'material_spu',
        '*:颜色'=>'color',
        '*:尺码'=>'size',
        '*:季节'=>'season',
        '*:材质'=>'uppermatnm',
        '*:适用对象'=>'gendernm',
        '*:鞋型'=>'widthnm',
        '*:风格款式'=>'modelnm',
        '*:门店销售'=>'is_o2o_sales',
        '物料分类'=>'material_sort',
    );

    /**
     * templateColumn
     * @return mixed 返回值
     */
    public function templateColumn(){
        $customcols = kernel::single('material_customcols')->getcolstemplate();
        $templateColumn = $this->defaulttemplateColumn;
       
        if($customcols){
            
            $templateColumn = array_merge($templateColumn,$customcols);

        }

        return $templateColumn;

    }
    /**
     * 获取TemplateColumn
     * @return mixed 返回结果
     */
    public function getTemplateColumn() {
        
       
        $templateColumn = $this->templateColumn();
       
        
        $templateColumn = array_keys($templateColumn);
        return $templateColumn;
    }

    /**
     * 准备导入的参数定义
     * 
     * @param Null
     * @return Null
     */
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    /**
     * 准备导入的数据主体内容部分检查和处理
     * 
     * @param Array $data
     * @param Boolean $mark
     * @param String $tmpl
     * @param String $msg
     * @return Null
     */
    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    /**
     * 准备导入的数据明细内容部分检查和处理
     * 
     * @param Array $row
     * @param String $title
     * @param String $tmpl
     * @param Boolean $mark
     * @param Boolean $newObjFlag
     * @param String $msg
     * @return Null
     */
    function prepared_import_csv_row($row,&$title,&$tmpl,&$mark,&$newObjFlag,&$msg){

        if (empty($row)){
            return true;
        }
        $mark = false;
        
        $basicMaterialObj = app::get('material')->model('basic_material');
        $salesMaterialObj = app::get('material')->model('sales_material');
        $checkBasicLib    = kernel::single('material_basic_check');#检查数据有效性Lib类
        
        if( substr($row[0],0,1) == '*' ){
            $mark = 'title';
            $title = array_flip($row);
            foreach($this->templateColumn() as $k => $val) {
                if(!isset($title[$k])) {
                    $msg['error'] = '请使用正确的模板';
                    return false;
                }
            }
            
            //[防止重复]记录组织编码
            $this->material_bn_list      = array();
            $this->material_code_list    = array();
            $this->basicm_nums           = 1;
            $this->fileData              = [];
            return $title;
        }else{
            $arrData = array();
            foreach($this->templateColumn() as $k => $val) {

                if(strpos($val, '/')) {
                    list($v1, $v2) = explode('/', $val);
                    $arrData[$v1][$v2] = trim($row[$title[$k]]);
                }else{

                    $arrData[$val] = trim($row[$title[$k]]);
                }


            }

            if(!$arrData['material_name']){
                $msg['error'] = "基础物料名称必须填写,物料编码：". $arrData['material_bn'];
                return false;
            }

            if(!$arrData['material_bn']){
                $msg['error'] = "基础物料编码必须填写,物料名称：". $arrData['material_name'];
                return false;
            }

            if(!$arrData['type']){
                $msg['error'] = "基础物料属性必须填写,物料编码：". $arrData['material_bn'];
                return false;
            }

            if(!$arrData['material_code']){
                $msg['error'] = "条码必须填写,物料编码：". $arrData['material_bn'];
                return false;
            }

            if(!$arrData['visibled']){
                $msg['error'] = "是否在售必须填写,物料编码：". $arrData['material_bn'];
                return false;
            }

            if(isset($this->basicm_nums)){
                $this->basicm_nums ++;
                if($this->basicm_nums > 5000){
                    $msg['error'] = "导入的数量量过大,请减少到5000个以下！";
                    return false;
                }
            }

            //[防止重复]检查物料编号
            if(in_array($arrData['material_bn'], $this->material_bn_list))
            {
                $msg['error'] = 'Line '.$this->basicm_nums.'：物料编号【'. $arrData['material_bn'] .'】重复！';
                return false;
            }
            $this->material_bn_list[]    = $arrData['material_bn'];
            
            //[防止重复]检查物料条码
            if(in_array($arrData['material_code'], $this->material_code_list))
            {
                $msg['error'] = 'Line '.$this->basicm_nums.'：物料条码【'. $arrData['material_name'] .'】重复！';
                return false;
            }
            $this->material_code_list[]    = $arrData['material_code'];
            
            #数据检查
            if($arrData['visibled'] =="在售"){
                $arrData['visibled'] = 1;
            }elseif($arrData['visibled'] =="停售"){
                $arrData['visibled'] = 2;
            }else{
                $msg['error'] = "是否在售填写的内容错误：".$arrData['visibled'].",请填写在售或停售,物料编码：". $arrData['material_bn'];
                return false;
            }
            
            //基础物料类型判断
            if(!empty($arrData['cat_id'])){
                $goods_type_obj = app::get('ome')->model('goods_type');
                $typeRow    = $goods_type_obj->dump(array('name'=>$arrData['cat_id']), 'type_id');
                
                if($typeRow){
                    $arrData['cat_id'] = $typeRow['type_id'];
                } else {
                    $msg['error'] = "基础物料类型：".$arrData['cat_id']." 不存在,物料编码：". $arrData['material_bn'];
                    return false;
                }
            }

            // 基础物料品牌判断
            if(!empty($arrData['brand_id'])){
                $brand_obj    = app::get('ome')->model('brand');
                $brandRow     = $brand_obj->dump(array('brand_name'=>$arrData['brand_id']), 'brand_id');
                
                if($brandRow){
                    $arrData['brand_id']    = $brandRow['brand_id'];
                } else {
                    $msg['error'] = "基础物料品牌：".$arrData['brand_id']." 不存在,物料编码：". $arrData['material_bn'];
                    return false;
                }
            }

            if($arrData['type'] =="成品"){
                $arrData['type'] = 1;
            }elseif($arrData['type'] =="半成品"){
                $arrData['type'] = 2;
            }elseif($arrData['type'] == '普通'){
                $arrData['type'] = 3;
            }elseif($arrData['type'] == '礼盒'){
                $arrData['type'] = 4;
            }elseif($arrData['type'] == '虚拟商品' || $arrData['type'] == '虚拟'){
                $arrData['type'] = 5;
            }else{
                $msg['error'] = "基础物料属性填写的内容错误：".$arrData['type'].",请填写成品或半成品,物料编码：". $arrData['material_bn'];
                return false;
            }
            
            //如果是成品并且定义了组成所属的半成品信息
            if($arrData['type'] == 1){
                if(isset($arrData['at']) && !empty($arrData['at'])){
                    $tmp_basicMInfos = explode('|',$arrData['at']);
                    foreach($tmp_basicMInfos as $tmp_basicMInfo){
                        $tmp_bnInfo = explode(':',$tmp_basicMInfo);
                        $tmp_binds[$tmp_bnInfo[0]] = $tmp_bnInfo[1];
                    }
                    unset($arrData['at']);
            
                    $arrData['at'] = $tmp_binds;
                    foreach($arrData['at'] as $bn => $val){
                        $basicInfo = $basicMaterialObj->getList('bm_id', array('material_bn'=>$bn), 0, 1);
                        if(!$basicInfo){
                            $msg['error'] = "找不到关联的基础物料：".$bn .",物料编码：". $arrData['material_bn'];
                            return false;
                        }else{
                            $tmp_at[$basicInfo[0]['bm_id']] = $val;
                        }
                    }
                    unset($arrData['at']);
                    $arrData['at'] = $tmp_at;
                }
            }

            if($arrData['type'] == 4){
                if(isset($arrData['at']) && !empty($arrData['at'])){
                    $tmp_basicMInfos = explode('|',$arrData['at']);
                    foreach($tmp_basicMInfos as $tmp_basicMInfo){
                        $tmp_bnInfo = explode(':',$tmp_basicMInfo);
                        $tmp_binds[$tmp_bnInfo[0]] = $tmp_bnInfo[1];
                    }
                    unset($arrData['at']);
            
                    $arrData['at'] = $tmp_binds;
                    foreach($arrData['at'] as $bn => $val){
                        $basicInfo = $basicMaterialObj->getList('bm_id', array('material_bn'=>$bn,'type'=>array('1','3')), 0, 1);
                        if(!$basicInfo){
                            $msg['error'] = "礼盒关联的基础物料：".$bn ."须为普通,物料编码：". $arrData['material_bn'];
                            return false;
                        }else{
                            $tmp_at[$basicInfo[0]['bm_id']] = $val;
                        }
                    }
                    unset($arrData['at']);
                    $arrData['at'] = $tmp_at;
                } else {
                    $msg['error'] = "礼盒未关联普通基础物料,物料编码：". $arrData['material_bn'];
                    return false;
                }
            }

            if($arrData['use_expire'] =="开启"){
                $arrData['use_expire'] = 1;
            }elseif($arrData['use_expire'] =="关闭"){
                $arrData['use_expire'] = 2;
            }else{
                $msg['error'] = "是否启用保质期监控的内容错误：".$arrData['use_expire'].",请填写开启或关闭,物料编码：". $arrData['material_bn'];
                return false;
            }
            
            #特殊扫码配置
            if($arrData['special_setting'] == "开启"){
                $arrData['special_setting'] = 3;
            }elseif($arrData['special_setting'] == "关闭"){
                $arrData['special_setting'] = 4;
            }else{
                $msg['error'] = "特殊扫码配置的内容错误：".$arrData['special_setting'].",请填写开启或关闭,物料编码：". $arrData['material_bn'];
                return false;
            }
            
            if($arrData['special_setting'] == 3)
            {
                $arrData['first_num']    = intval($arrData['first_num']);
                $arrData['last_num']    = intval($arrData['last_num']);
            }
            else
            {
                $arrData['first_num']    = 1;
                $arrData['last_num']    = 1;
            }
            
            #是否自动生成销售物料
            if($arrData['create_material_sales'] == '是'){
                $arrData['create_material_sales'] = 1;
            }elseif($arrData['create_material_sales'] == '否' || empty($arrData['create_material_sales'])){
                $arrData['create_material_sales'] = 0;
            }else{
                $msg['error'] = "是否自动生成销售物料的内容错误：".$arrData['special_setting'].",请填写是、否或留空,物料编码：". $arrData['material_bn'];
                return false;
            }
            
            if(($arrData['create_material_sales'] === 1) && ($arrData['visibled'] === 2))
            {
                $msg['error'] = "开启自动生成销售物料,基础物料必须是在售状态,物料编码：". $arrData['material_bn'];
                return false;
            }
            
            if($arrData['create_material_sales'] === 1)
            {
                $salesMaterialItem    = $salesMaterialObj->dump(array('sales_material_bn'=>$arrData['material_bn']), 'sm_id');
                if($salesMaterialItem)
                {
                    $msg['error'] = "销售物料已经存在,无法自动生成;物料编码：". $arrData['material_bn'];
                    return false;
                }
            }
            
            #是否全渠道
            if($arrData['omnichannel'] =="开启"){
                $arrData['omnichannel'] = 1;
            }elseif($arrData['omnichannel'] =="关闭"){
                $arrData['omnichannel'] = 2;
            }else{
                $msg['error'] = "是否全渠道填写的内容错误：".$arrData['omnichannel']."，请填写开启或关闭";
                return false;
            }

            if($arrData['serial_number'] == '是'){
                $arrData['serial_number'] = 'true';
            }elseif($arrData['serial_number'] == '否'){
                $arrData['serial_number'] = 'false';
            }else{
                $msg['error'] = "是否启用唯一码填写的内容错误：".$arrData['omnichannel']."，请填写是或否";
                return false;
            }
            $is_o2o_sales = $arrData['is_o2o_sales']=='是' ? 1 : 0;


            #拼接数据
            $sdf    = array(
                        'material_name' => $arrData['material_name'],
                        'material_bn' => trim($arrData['material_bn']),
                        'type' => $arrData['type'],
                        'material_code' => trim($arrData['material_code']),
                        'visibled' => $arrData['visibled'],
                        'unit' => $arrData['unit'],
                        'retail_price' => $arrData['retail_price'] ? $arrData['retail_price'] : 0.00,
                        'cost' => $arrData['cost'] ? $arrData['cost'] : 0.00,
                        'weight' => $arrData['weight'] ? $arrData['weight'] : 0.00,
                        'at' => $arrData['at'],
                        'use_expire' => $arrData['use_expire'],
                        'warn_day' => $arrData['warn_day'],
                        'quit_day' => $arrData['quit_day'],
                        'tax_rate' => $arrData['tax_rate'],
                        'tax_name' => $arrData['tax_name'],
                        'tax_code' => $arrData['tax_code'],
                        'goods_type_id' => $arrData['cat_id'],
                        'specifications' => $arrData['specifications'],
                        'brand_id' => $arrData['brand_id'],
                        'special_setting' => $arrData['special_setting'],
                        'first_num' => $arrData['first_num'],
                        'last_num' => $arrData['last_num'],
                        'material_bn_crc32' => '',
                        'create_material_sales' => $arrData['create_material_sales'],
                        'omnichannel' => $arrData['omnichannel'],
                        'serial_number'=>$arrData['serial_number'],
                        'material_spu'=>trim($arrData['material_spu']),
                        'color'=>$arrData['color'],
                        'size'=>$arrData['size'],
                        'season'=>$arrData['season'],
                        'uppermatnm'=>$arrData['uppermatnm'],
                        'gendernm'=>$arrData['gendernm'],
                        'widthnm'=>$arrData['widthnm'],
                        'modelnm'=>$arrData['modelnm'],
                        'is_o2o_sales'=>$is_o2o_sales,
            );
            if($arrData['custom']){
                $sdf['custom'] = $arrData['custom'];
            }

            if($arrData['material_sort']){

                $cats = app::get('material')->model('basic_material_ext')->getCats($arrData['material_sort']);

                if($cats){
                    $sdf['cat_id'] = $cats['cat_id'];
                    $sdf['cat_path'] = $cats['cat_path'];
                }
            }

            #检查数据有效性
            $err_msg          = '';
            if(!$checkBasicLib->checkParams($sdf, $err_msg))
            {
                $msg['error']    = $err_msg .',物料编码：'. $sdf["material_bn"];
                return false;
            }
            
            $this->fileData['basicm']['contents'][] = $sdf;
            
            #销毁
            unset($row, $tmp_basicMInfos, $tmp_bnInfo, $tmp_binds);
            
        }
        return null;
    }

    /**
     * 完成基础物料的导入
     * 
     * @param Null
     * @return Null
     */
    function finish_import_csv(){

        $oQueue = app::get('base')->model('queue');
        $aP = $this->fileData;
        $pSdf = array();

        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();

        foreach ($aP['basicm']['contents'] as $k => $aPi){
            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            $pSdf[$page][] = $aPi;
        }

        foreach($pSdf as $v){
            $queueData = array(
                'queue_title'=>'基础物料导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'material',
                    'mdl' => 'basic_material'
                ),
                'worker'=>'material_basic_material_to_import.run',
            );
           
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        
        //记录日志
        $operationLogObj    = app::get('ome')->model('operation_log');
        $operationLogObj->write_log('basic_material_import@wms', 0, "批量导入基础物料,本次共导入". count($aP['basicm']['contents']) ."条记录!");
        
        return null;
    }


    function fgetlist_csv(&$data,$filter,$offset,$exportType = 1)
    {
        $brand_obj = app::get('ome')->model('brand');
        $basic_material_obj = app::get('material')->model('basic_material');

        if(!$data['title']){
            $title = array();
            foreach($this->io_title('exportBasicM') as $k => $v){
                $title[] = $v;
            }
            $data['contents']['title'] = '"' . implode('","', $title) . '"';
        }

        if($filter['isSelectedAll'] && $filter['isSelectedAll'] == '_ALL_'){
            $bm_ids = $basic_material_obj->getList('bm_id', array());
            foreach($bm_ids as $key => $vals){
                $bm_ids[$key] = $vals['bm_id'];
            }
            $basic_materials = kernel::single('material_basic_material')->getBasicMaterialByBmids($bm_ids);
            foreach($basic_materials as $basic_id => $basic){
                $brand_name = $brand_obj->dump(array('brand_id' => $basic['brand_id']));
                $material_row = array();
                $material_row['*:基础物料名称'] = str_replace(',','，',$basic['material_name']);
                $material_row['*:基础物料编码'] = $basic['material_bn'];
                $material_row['*:物料属性'] = $basic['type'] == '1' ? '成品' : '半成品';
                $material_row['*:销售状态'] = $basic['visibled'] == '1' ? '在售' : '停售';
                $material_row['*:重量'] = $basic['weight'];
                $material_row['*:物料规格'] = str_replace(',','，',$basic['specifications']);
                $material_row['*:物料品牌'] = empty($brand_name['brand_name']) ? ' - ' : $brand_name['brand_name'];
                $material_row['*:成本'] = $basic['cost'];
                $material_row['*:售价'] = $basic['retail_price'];
                $material_row['*:包装单位'] = $basic['unit'];

                $data['contents'][] = '"' . implode('","', $material_row) . '"';
            }

        } else {
            foreach($filter['bm_id'] as $k => $v){
                $basic_material = kernel::single('material_basic_material')->getBasicMaterialExt($v);
                $brand_name = $brand_obj->dump(array('brand_id' => $basic_material['brand_id']));
                if(!$basic_material) return false;
                $material_row = array();
                $material_row['*:基础物料名称'] = str_replace(',','，',$basic_material['material_name']);
                $material_row['*:基础物料编码'] = $basic_material['material_bn'];
                $material_row['*:物料属性'] = $basic_material['type'] == '1' ? '成品' : '半成品';
                $material_row['*:销售状态'] = $basic_material['visibled'] == '1' ? '在售' : '停售';
                $material_row['*:重量'] = $basic_material['weight'];
                $material_row['*:物料规格'] = str_replace(',','，',$basic_material['specifications']);
                $material_row['*:物料品牌'] = empty($brand_name['brand_name']) ? ' - ' : $brand_name['brand_name'];
                $material_row['*:成本'] = $basic_material['cost'];
                $material_row['*:售价'] = $basic_material['retail_price'];
                $material_row['*:包装单位'] = $basic_material['unit'];

                $data['contents'][] = '"' . implode('","', $material_row) . '"';
            }
        }


        return false;
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'barcode'=>app::get('ome')->_('条形码'),
            'fuzzy_material_bn'=>app::get('ome')->_('基础物料编码(模糊)'),
        );
        return $Options = array_merge($parentOptions,$childOptions);
    }

    function _filter($filter,$tableAlias=null,$baseWhere=null){
        if($filter['material_bn'] && is_string($filter['material_bn']) && strpos($filter['material_bn'], "\n") !== false){
            $filter['material_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['material_bn']))));
        }
        
        if($filter['barcode']){
            $codeObj = app::get('material')->model('codebase');
            $barcode_bm_ids = $codeObj->getlist('bm_id',array('code' => $filter['barcode']));
            
            if($barcode_bm_ids){
                $barcode_bm_id = array_column($barcode_bm_ids,'bm_id');
            }
            //当条形码不存在时
            if(empty($barcode_bm_id)){
                $barcode_bm_id['bm_id'] = -1;
            }
            
            unset($filter['barcode']);
        }
        if ($filter["goods_type"]){
            $mdl_basic_material_ext = app::get('material')->model('basic_material_ext');
            $rs_basic_material_ext = $mdl_basic_material_ext->getList("bm_id",array("cat_id"=>$filter["goods_type"]));
            if (!empty($rs_basic_material_ext)){
                $goods_type_bm_ids = array();
                foreach ($rs_basic_material_ext as $var_b_m_e){
                    $goods_type_bm_ids[] = $var_b_m_e["bm_id"];
                }
            }
            unset($filter['goods_type']);
        }
        //处理filter的bm_id字段 目前不存在barcode和goods_type同时为filter的情况
        $bm_ids = array();
        if(!empty($goods_type_bm_ids)){
            $bm_ids = $goods_type_bm_ids;
        }

       
        if(!empty($barcode_bm_id)){
            $bm_ids = $barcode_bm_id;
        }
        $where = array();
        $sqlstr = '';
        if(!empty($bm_ids)){
            $where[] = " " . ($tableAlias ? $tableAlias . '.' : '') . "bm_id in (".implode(',',$bm_ids).")";
        }
        
        if($filter['fuzzy_material_bn'] && is_string($filter['fuzzy_material_bn'])){
            // $where[] = ' material_bn like \'%' . $filter['fuzzy_material_bn'] . '%\' ';
            if (strpos($filter['fuzzy_material_bn'], "\n") !== false) {
                $filter['fuzzy_material_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['fuzzy_material_bn']))));
            } else {
                $filter['fuzzy_material_bn'] = [
                    trim($filter['fuzzy_material_bn'])
                ];
            }
            $where[] = ' material_bn REGEXP ' . kernel::database()->quote(implode('|', $filter['fuzzy_material_bn'])) . ' ';
            unset($filter['fuzzy_material_bn']);
        }

        if (isset($filter['cos_id'])) {
            $where[] = ' cos_id in (' . $filter['cos_id'] . ')';
            unset($filter['cos_id']);
        }
        
        if($where){
            $sqlstr.=implode(' AND ',$where)." AND ";
        }

      
        return $sqlstr.parent::_filter($filter,$tableAlias,$baseWhere);
    }


    /**
     * 检查Customcols
     * @param mixed $cols cols
     * @return mixed 返回验证结果
     */
    public function checkCustomcols($cols){

        $customcolsMdl = app::get('desktop')->model('customcols');
        $newcol = $oldcol = array();

        foreach($cols as $ck=>$cv){
           
             if(substr($ck,0,3)=='new'){
                $newcol[] = $cv;
             }else{
                $oldcol[$ck] = $cv;
             }
        }

        if($newcol){
            $newcolkeys = array_column($newcol, 'col_key');
            $customcols = $customcolsMdl->dump(array('col_key'=> $newcolkeys),'col_key');

            if($customcols){
                return [false,'自定义列:'.$customcols['col_key'].'已存在'];
            }
            $defaultcolumn = $this->defaulttemplateColumn;

            $intersection = array_intersect($newcolkeys, $defaultcolumn);

            if($intersection){
                return [false,'自定义列:'.implode(',',$intersection).'已存在'];
            }

        }

        return [true];
    }
}
