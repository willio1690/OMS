<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_sales_material_importAddV2  implements omecsv_data_split_interface
{
    // public $column_num = 45;
    public $current_order_bn = null;

    const IMPORT_TITLE = [
        ['label' => '*:销售物料编码', 'col' => 'sales_material_bn'],
        ['label' => '*:销售物料名称', 'col' => 'sales_material_name'],
        ['label' => '*:物料类型', 'col' => 'sales_material_type'],
        ['label' => '*:所属店铺', 'col' => 'shop_id'],
        ['label' => '*:关联基础物料编码', 'col' => 'material_bn'],
        ['label' => '开票税率', 'col' => 'tax_rate'],
        ['label' => '开票名称', 'col' => 'tax_name'],
        ['label' => '发票分类编码', 'col' => 'tax_code'],
                    ['label' => '零售价', 'col' => 'retail_price'],
        ['label' => '最低售价', 'col' => 'lowest_price'],
        ['label' => '包装单位', 'col' => 'unit'],
    ];

    const IMPORT_ITEM_TITLE = [];

    /**
     * 检查文件是否有效
     * @param $file_name 文件名
     * @param $file_type 文件类型
     * @param $queue_data 请求参数
     * @return array
     * @date 2024-06-06 3:52 下午
     */
    public function checkFile($file_name, $file_type, $queue_data)
    {
        $bmMdl = app::get('material')->model('basic_material');
        $checkSalesLib    = kernel::single('material_sales_check');#数据检验有效性Lib类

        $ioType = kernel::single('omecsv_io_split_' . $file_type);
        $rows   = $ioType->getData($file_name, 0, -1);

        $summaryTitle = array_merge(self::IMPORT_TITLE, self::IMPORT_ITEM_TITLE);
        $oSchema = array_column($summaryTitle, 'label', 'col');

        // 获取系统必填标题
        $requiredTitle = [];  // 必填标题
        foreach ($summaryTitle as $k => $v) {
            if ('*:' == substr($v['label'], 0, 2)) {
                $requiredTitle[] = $v;
            }
        }
        $requiredLabel = array_column($requiredTitle, 'label');

        $previousRow = []; // 前一条数据
        $importTitle = [];  // 导入的标题
        //[防止重复]记录组织编码
        $material_bn_list     = [];
        $material_code_list   = [];
        foreach ($rows as $key => $row) {
            if ($key == 0) {  
                $importTitle = $row;
                $_required_title = [];
                foreach ($row as $k => $v) {
                    if (in_array($v, $requiredLabel)) {
                        $_required_title[] = $v;
                    }
                }
                if (!kernel::single('ome_order_importV2')->checkTitle($_required_title, $requiredLabel)) {
                    return array(false, '导入模板不正确', $row);
                }
                if ($row[0]!='*:销售物料编码') {
                    return array(false, '导入模板第一列必须是"*:销售物料编码"', $row);
                }

            } else {

                // 如果当前行没有物料编码,或者物料编码与上一行相同，则主表数据以第一行的为准进行覆盖
                if ((!$row[0] || $row[0] == $previousRow['*:销售物料编码']) && $previousRow) {
                    $_num = 0;
                    foreach ($previousRow as $_k => $_v) {
                        // 非明细字段
                        if ('(明细)' !== substr($_k, -4)) {
                            $row[$_num] = $_v;
                        }
                        $_num++;
                    }
                }

                // 过滤掉非模版里的字段
                $titleKey = array();
                foreach ($importTitle as $k => $t) {
                    $titleKey[$k] = array_search($t, $oSchema);
                    if ($titleKey[$k] === false) {
                        unset($titleKey[$k]);
                    }
                }

                $buffer = array_combine($titleKey, $row);
                // 数据验证
                foreach ($buffer as $k => $v) {
                    if ('*:' == substr($oSchema[$k], 0, 2) && $v === '') {
                        return [false, sprintf('%s必填', $oSchema[$k])];
                    }
                }

                // 销售物料编码
                if (in_array($buffer['sales_material_bn'], $material_bn_list)){
                    return [false, sprintf('[%s]销售物料编码已经存在', $buffer['sales_material_bn']), $buffer];
                }
                $material_bn_list[] = $buffer['sales_material_bn'];

                // 销售物料类型
                switch ($buffer['sales_material_type']) {
                    case '普通':
                        $buffer['sales_material_type'] = 1;
                        break;
                    case '组合':
                        $buffer['sales_material_type'] = 2;
                        break;
                    case '赠品':
                        $buffer['sales_material_type'] = 3;
                        break;
                    case '福袋':
                        $buffer['sales_material_type'] = 4;
                        break;
                    case '多选一':
                        $buffer['sales_material_type'] = 5;
                        break;
                    case '礼盒':
                        $buffer['sales_material_type'] = 6;
                        break;
                    default:
                        return [false, sprintf('[%s]物料乐行不正确，请填写普通、组合、赠品、多选一', $buffer['type']), $buffer];
                        break;
                }

                // 所属店铺
                $shopMdl = app::get('ome')->model('shop');
                if ($buffer['shop_id'] == '全部店铺'){
                    $buffer['shop_id'] = '_ALL_';
                } elseif ($buffer['sales_material_type'] == '3' && $buffer['shop_id'] != '全部店铺'){
                    return [false, sprintf('赠品物料类型只能选择全部店铺,销售物料编码：%s', $buffer['sales_material_bn']), $buffer];
                } elseif ($buffer['shop_id']){
                    $shopInfo = $shopMdl->dump(array('name'=>$buffer['shop_id']), 'shop_id');
                    if (!$shopInfo){
                        return [false, sprintf('[%s]所属店铺不存在', $buffer['shop_id']), $buffer];
                    }
                    $buffer['shop_id'] = $shopInfo['shop_id'];
                } else {
                    return [false, sprintf('所属店铺不能为空,销售物料编码：%s', $buffer['sales_material_bn']), $buffer];
                }

                $arrBmId = [];

                if ($buffer['sales_material_type'] == 2){ //组合物料类型(至少关联一个基础物料)
                    $tmp_at = array();
                    if (!$buffer['material_bn']){
                        return [false, sprintf('组合物料类型必须关联基础物料,销售物料编码：%s', $buffer['sales_material_bn']), $buffer];
                    }
                    $tmp_salesMInfos = explode('|', $buffer['material_bn']);
                    foreach($tmp_salesMInfos as $tmp_basicMInfo){
                        $tmp_bnInfo    = explode(':', $tmp_basicMInfo);#格式：物料编号1:数量1:组合价格贡献占比|物料编号2:数量2:组合价格贡献占比
        
                        if (!isset($tmp_bnInfo[2])) {
                            $tmp_bnInfo[2] = 0;
                            $auto_calculation_percentage = true; #没有组合价格贡献占比参数，需要自动计算组合价格贡献占比
                        }
                        $tmp_binds[$tmp_bnInfo[0]]    = array(intval($tmp_bnInfo[1]), intval($tmp_bnInfo[2]));
                    }
                    unset($buffer['material_bn']);

                    #只有一种物料时，数量必须大于1
                    foreach ($tmp_binds as $bn => $val){
                        if((count($tmp_binds) == 1) && ($val[0] < 2)){
                            return [false, sprintf('组合物料类型只有一种基础物料时，数量必须大于1（例格式：物料编号1:数量2），销售物料编码：%s', $buffer['sales_material_bn']), $buffer];
                        }
                        if ($val[0] < 1){
                            return [false, sprintf('组合物料类型基础物料：%s的数量必须大于0（例格式：物料编号1:数量1|物料编号2:数量2），销售物料编码：%s', $bn, $buffer['sales_material_bn']), $buffer];
                        }
                    }
                    $buffer['material_bn'] = $tmp_binds;
                    foreach($buffer['material_bn'] as $bn => $val){
                        $basicInfo = $bmMdl->getList('bm_id', array('material_bn'=>$bn), 0, 1);
                        if(!$basicInfo){
                            return [false, sprintf('销售物料编码：%s行中找不到关联的基础物料：%s（例格式：物料编号1:数量1|物料编号2:数量2）', $buffer['sales_material_bn'], $bn), $buffer];
                        }else{
                            $tmp_at[$basicInfo[0]['bm_id']]    = $val;
                        }
                        $arrBmId[] = $basicInfo[0]['bm_id'];
                    }

                    $sum_cost = 0;
                    $material_rows = array();
                    if ($auto_calculation_percentage) { #需要自动计算组合价格百分比

                        #基础物料信息
                        $material_rows = $this->getBasicMaterial(array_keys($tmp_at));
                        if (array_sum(array_column($material_rows, 'cost')) == 0) {#基础物料成本价都为0
                            return [false, sprintf('销售物料编码：%s行中,基础物料的成本价都为0且没有填写组合价格贡献占比,请调整基础物料成本价或者填写组合价格贡献占比并累加起来必须等于100（例格式：物料编号1:数量1:组合价格贡献占比1|物料编号2:数量2:组合价格贡献占比2）', $buffer['sales_material_bn']),];
                        }

                        foreach ($tmp_at as $bm_id=> $tmp_at_item) { //计算总成本价多少
                            $cost = $material_rows[$bm_id]['cost'];
                            $number = $tmp_at_item[0];
                            $sum_cost += $cost * $number;
                        }
                    }

                    #组合价格贡献占比
                    $buffer['material_bn'] = array();
                    $tmp_pr = array();
                    $total = 100;
                    $count_basicM = count($tmp_at);
                    $count_i = 1;
                    foreach ($tmp_at as $key => $val){
                        if($count_basicM == 1){
                            #只有一种物料时
                            $buffer['material_bn'][$key]['number']    = $val[0];
                            $buffer['material_bn'][$key]['rate']      = $total;
                            #验证数据时使用
                            $tmp_pr[$key]    = $buffer['material_bn'][$key]['rate'];
                        }else{

                            if ($auto_calculation_percentage) {#自动计算组合价格百分比
                                $number = $val[0];
                                $cost = $material_rows[$key]['cost'];

                                $rate = $count_basicM == $count_i
                                    ? 100 - array_sum($tmp_pr)
                                    : round(($number * $cost) / $sum_cost * 100);

                                $buffer['material_bn'][$key]['number']  = $number;
                                $buffer['material_bn'][$key]['rate']    = $rate;
                                #验证数据时使用
                                $tmp_pr[$key] = $rate;
                                $count_i++;
                            } else {
                                $buffer['material_bn'][$key]['number']    = $val[0];
                                $buffer['material_bn'][$key]['rate']      = $val[1];
                                $tmp_pr[$key] = $val[1];
                            }
                        }
                    }
                }elseif($buffer['sales_material_type'] == 4){ //福袋类型
                    $csv_data_str = trim($buffer['material_bn']);
                    $tmp_lbr = array();
                    //福袋基础物料组
                    if(!$csv_data_str){
                        return [false, sprintf('福袋类型销售物料请至少填写一个关联的福袋组合规则,销售物料编码：%s', $buffer['sales_material_bn']), $buffer];
                    }
                    $luckybag_example_str = "，销售物料编码：".$buffer['sales_material_type']."。参考格式（组合名称A：物料编码1|物料编码2-sku数量|送件数量|单品售价#组合名称B：物料编码1|物料编码3-sku数量|送件数量|单品售价）。";
                    $tmp_luckybag_rules = explode('#', $csv_data_str);
                    foreach($tmp_luckybag_rules as $var_tlr){
                        $temp_lbr = array();
                        $tmp_luckybag_name = explode(':', $var_tlr);
                        if(count($tmp_luckybag_name) == 2){
                            if(!$tmp_luckybag_name[0]){
                                $msg['error'] = "福袋组合名称异常".$luckybag_example_str; break;
                            }
                            if(!$tmp_luckybag_name[1]){
                                $msg['error'] = "福袋组合内容异常".$luckybag_example_str; break;
                            }
                            $temp_lbr["name"] = $tmp_luckybag_name[0];
                            $tmp_luckybag_detail = explode('-', $tmp_luckybag_name[1]);
                            if(count($tmp_luckybag_detail) == 2){
                                if(!$tmp_luckybag_detail[0]){
                                    $msg['error'] = $temp_lbr["name"]."的福袋组合基础物料异常".$luckybag_example_str; break;
                                }
                                if(!$tmp_luckybag_detail[1]){
                                    $msg['error'] = $temp_lbr["name"]."的福袋组合规则参数异常".$luckybag_example_str; break;
                                }
                                $luckybag_bm_bns = explode("|", $tmp_luckybag_detail[0]);
                                $rs_bm_ids = $bmMdl->getList("bm_id",array("material_bn"=>$luckybag_bm_bns));
                                if(empty($rs_bm_ids)){
                                    $msg['error'] = $temp_lbr["name"]."的福袋组合基础物料不存在".$luckybag_example_str; break;
                                }
                                if(count($rs_bm_ids) < count($luckybag_bm_bns)){
                                    $msg['error'] = $temp_lbr["name"]."的福袋组合基础物料编码重复或不存在".$luckybag_example_str; break;
                                }
                                foreach($rs_bm_ids as $var_bi){
                                    $temp_lbr["bm_ids"][] = $var_bi["bm_id"];
                                    $arrBmId[] = $var_bi["bm_id"];
                                }
                                $luckybag_params = explode("|", $tmp_luckybag_detail[1]);
                                if(count($luckybag_params) != 3){
                                    $msg['error'] = $temp_lbr["name"]."的福袋组合参数异常".$luckybag_example_str; break;
                                }
                                $temp_lbr["sku_num"] = $luckybag_params[0];
                                $temp_lbr["send_num"] = $luckybag_params[1];
                                $temp_lbr["single_price"] = $luckybag_params[2];
                            }else{
                                $msg['error'] = $temp_lbr["name"]."格式错误，".$luckybag_example_str; break;
                            }
                        }else{
                            $msg['error'] = "格式错误，".$luckybag_example_str; break;
                        }
                        $tmp_lbr[] = $temp_lbr;
                    }
                    if($msg['error']){
                        return [false, $msg['error'], $buffer];
                    }
                }elseif($buffer['sales_material_type'] == 5){ //多选一类型
                    $csv_data_str = trim($buffer['material_bn']);
                    //福袋基础物料组
                    if(!$csv_data_str){
                        return [false, sprintf('多选一类型销售物料请至少填写一个关联的基础物料规则,销售物料编码：%s', $buffer['sales_material_bn']), $buffer];
                    }
                    $tmp_sort = array();
                    $pickone_example_str = "，销售物料编码：".$buffer['sales_material_type']."。参考格式（随机#sku01:0|sku02:0 或 排序#sku01:10|sku02:20）。";
                    $tmp_pickone_rules = explode('#', $csv_data_str);
                    $count_pickone_data = count($tmp_pickone_rules);
                    if($count_pickone_data <= 1 || $count_pickone_data > 2){
                        return [false, sprintf('多选一关联基础物料信息异常%s', $pickone_example_str), $buffer];
                    }
                    if($tmp_pickone_rules[0] == "随机"){
                        $pickone_select_type = 1;
                    }elseif($tmp_pickone_rules[0] == "排序"){
                        $pickone_select_type = 2;
                    }else{
                        return [false, sprintf('多选一关联基础物料选择方式信息异常%s', $pickone_example_str), $buffer];
                    }
                    $tmp_pickone_items = explode('|', $tmp_pickone_rules[1]);
                    $arr_bm_id = array();
                    foreach($tmp_pickone_items as $var_p_i){
                        $current_pickone_items = explode(':',$var_p_i);
                        if(count($current_pickone_items) != 2){
                            $msg['error'] = "多选一关联基础物料明细信息异常".$pickone_example_str; break;
                        }
                        $rs_basic = $bmMdl->dump(array("material_bn"=>$current_pickone_items[0]),"bm_id");
                        if(empty($rs_basic)){
                            $msg['error'] = "基础物料编码".$current_pickone_items[0]."不存在".$pickone_example_str; break;
                        }
                        if(in_array($rs_basic["bm_id"],$arr_bm_id)){
                            $msg['error'] = "基础物料编码".$current_pickone_items[0]."重复了".$pickone_example_str; break;
                        }
                        $arr_bm_id[] = $rs_basic["bm_id"];
                        $arrBmId[] = $rs_basic["bm_id"];
                        $tmp_sort[$rs_basic["bm_id"]] = $current_pickone_items[1] ? $current_pickone_items[1] : 0;
                    }
                    if($msg['error']){
                        return [false, $msg['error'], $buffer];
                    }
                }else{ #普通、赠品物料类型(默认关联的基础物料可以为空)
                    if($buffer['material_bn']){
                        $basic_filter = array('material_bn'=>$buffer['material_bn']);
                        if ($buffer['sales_material_type'] ==6){
                            $basic_filter['type'] = 4;
                        }
                        $basicInfo    = $bmMdl->getList('bm_id', $basic_filter, 0, 1);
                        if(!$basicInfo){
                            return [false, sprintf('销售物料编码：%s行中找不到关联的基础物料：%s', $buffer['sales_material_bn'], $buffer['material_bn']), $buffer];
                        }else{
                            $buffer['material_bn']    = $basicInfo[0]['bm_id'];
                            $arrBmId[]    = $basicInfo[0]['bm_id'];
                        }
                    }
                }

                #销售物料扩展信息
                $buffer['cost']    = floatval($buffer['cost']);
                $buffer['retail_price']    = floatval($buffer['retail_price']);
                $buffer['weight']    = intval($buffer['weight']);
                $buffer['unit']    = trim($buffer['unit']);
                if($buffer['brand_id']) {
                    $brandInfo = app::get('ome')->model('brand')->db_dump(['brand_name'=>$buffer['brand_id']], 'brand_id');
                    if(empty($brandInfo)) {
                        return [false, sprintf('销售物料编码：%s品牌【%s】不存在！', $buffer['sales_material_bn'], $buffer['brand_id']), $buffer];
                    }
                    $basicBrandInfo = app::get('material')->model('basic_material_ext')->getList('brand_id', ['bm_id'=>$arrBmId]);
                    $basicBrandInfo = array_column($basicBrandInfo, 'brand_id');
                    if(!in_array($brandInfo['brand_id'], $basicBrandInfo)) {
                        return [false, sprintf('销售物料编码：%s品牌【%s】填写错误，品牌名称与子商品品牌不一致！', $buffer['sales_material_bn'], $buffer['brand_id']), $buffer];
                    }
                    $buffer['brand_id'] = $brandInfo['brand_id'];
                }
                #赠品物料类型_成本价和售价必须等于0
                if($buffer['sales_material_type'] == 3){
                    if(($buffer['cost'] != 0) || ($buffer['retail_price'] != 0)){
                        return [false, sprintf('销售物料编码：%s赠品物料类型成本价和售价必须等于0', $buffer['sales_material_bn']), $buffer];
                    }
                }

                #拼接数据
                $sdf = array(
                    'sales_material_name' => $buffer['sales_material_name'],
                    'sales_material_bn' => trim($buffer['sales_material_bn']),
                    'sales_material_type' => $buffer['sales_material_type'],
                    'shop_id' => $buffer['shop_id'],
                    'is_bind' => 2,
                    'bind_bm_id' => $buffer['material_bn'],
                    'at' => $tmp_at,
                    'pr' => $tmp_pr,
                    'lbr' => $tmp_lbr,
                    'sort' => $tmp_sort,
                    'pickone_select_type' => $pickone_select_type,
                    'sales_material_bn_crc32' => '',
                    'cost' => $buffer['cost'],
                    'retail_price' => $buffer['retail_price'],
                    'weight' => $buffer['weight'],
                    'unit' => $buffer['unit'],
                    'brand_id' => $buffer['brand_id'],
                );
                #数据有效性检查
                $err_msg = '';
                if(!$checkSalesLib->checkParams($sdf, $err_msg)){
                    return [false, $err_msg .",物料编码:". $buffer['sales_material_type'], $buffer];
                }

                $previousRow = array_combine($importTitle, $row); // 在最后，保存前一条数据,给下个循环使用,因为同一张订单主表信息只有第一条有
            }
        }
        //导入文件内容验证
        return array(true, '文件模板匹配', $rows[0]);
    }

    /**
     * 获取基础物料信息
     * @param $bm_ids
     * @return array
     */
    private function getBasicMaterial($bm_ids)
    {
        $basic_material_ext_model = app::get('material')->model('basic_material_ext');

        $rows = $basic_material_ext_model->getList('bm_id,cost,retail_price', array('bm_id|in'=> $bm_ids), 0, -1);

        $material_rows = array();

        foreach ($rows as $row) {
            $material_rows[$row['bm_id']] = array(
                'cost'=> $row['cost'],
                'retail_price'=> $row['retail_price'],
            );
        }

        return $material_rows;
    }

    /**
     * 每页切分数量
     * @param $key
     * @return int|int[]
     * @date 2024-09-05 6:03 下午
     */
    public function getConfig($key = '')
    {
        $config = array(
            'page_size' => 200,
        );
        return $key ? $config[$key] : $config;
    }

    /**
     * 是否是同一个订单明细行检测
     * @param $row
     * @return bool
     * @date 2024-09-05 4:59 下午
     */
    public function is_split($row)
    {
        $is_split = false;
        if ($row['0']) {
            if ($row['0'] !== $this->current_order_bn) {
                if ($this->current_order_bn !== null) {
                    $is_split = true;
                }
                $this->current_order_bn = $row['0'];//物料编码
            }
        }
        return $is_split;
    }

    /**
     * 订单切片导入逻辑处理
     * @param $cursor_id
     * @param $params
     * @param $errmsg
     * @return bool[]
     * @date 2024-09-05 9:58 上午
     */
    public function process($cursor_id, $params, &$errmsg)
    {
        @ini_set('memory_limit', '128M');
        $oFunc = kernel::single('omecsv_func');
        $queueMdl     = app::get('omecsv')->model('queue');
    
        $oFunc->writelog('处理任务-开始', 'settlement', $params);
        //业务逻辑处理
        $data = $params['data'];
        $sdf = [];
        $offset      = intval($data['offset']) + 1;//文件行数 行数默认从1开始
        $splitCount  = 0;//执行行数
        if($data){
            $previousRow = []; // 前一条数据
            
            foreach($data as $row){
                $res = $this->getSdf($row, $offset, $params['title'], $previousRow);
                
                if ($res['status'] and $res['data']) {
                    $tmp = $res['data'];
                    $this->_formatData($tmp);
                    $sdf[] = $tmp;
                } elseif (!$res['status']) {
                    array_push($errmsg, $res['msg']);
                }
                
                //包含表头
                if ($res['status']) {
                    $splitCount++;
                }
                $offset++;
            }
        }
        unset($data);
        //创建订单
        if ($sdf) {
            list($result,$msgList) = $this->implodeMaterial($sdf);
            if($msgList){
                $errmsg = array_merge($errmsg, $msgList);
            }
            $queueMdl->update(['original_bn' => 'material_add', 'split_count' => $splitCount], ['queue_id' => $cursor_id]);
        }
        
        //任务数据统计更新等
        $oFunc->writelog('处理任务-完成', 'settlement', 'Done');
        return [true];
    }
    

     /**
      * 导入文件表头定义
      * @date 2024-06-06 3:52 下午
      */
    
     public function getSdf($row, $offset = 1, $title, &$previousRow)
     {
        $bmMdl = app::get('material')->model('basic_material');
        $res = array('status' => true, 'data' => array(), 'msg' => '');

        $row = array_map('trim', $row);
         
        $summaryTitle = array_merge(self::IMPORT_TITLE, self::IMPORT_ITEM_TITLE);
        $oSchema = array_column($summaryTitle, 'label', 'col');
         
        $titleKey = array();
        foreach ($title as $k => $t) {
            $titleKey[$k] = array_search($t, $oSchema);
            if ($titleKey[$k] === false) {
                unset($titleKey[$k]);
            }
        }

        // $row_num = count($row);
        // if ($this->column_num <= $row_num && $row[0] != '*:销售物料编码') {
        if ($row[0] != '*:销售物料编码') {

            // 如果当前行没有物料编码,或者物料编码与上一行相同，则主表数据以第一行的为准进行覆盖
            if ((!$row[0] || $row[0] == $previousRow['*:销售物料编码']) && $previousRow) {
                $_num = 0;
                foreach ($previousRow as $_k => $_v) {
                    // 非明细字段
                    if ('(明细)' !== substr($_k, -4)) {
                        $row[$_num] = $_v;
                    }
                    $_num++;
                }
            }
            $buffer = array_combine($titleKey, $row);
            // 销售物料类型
            switch ($buffer['sales_material_type']) {
                case '普通':
                    $buffer['sales_material_type'] = 1;
                    break;
                case '组合':
                    $buffer['sales_material_type'] = 2;
                    break;
                case '赠品':
                    $buffer['sales_material_type'] = 3;
                    break;
                case '福袋':
                    $buffer['sales_material_type'] = 4;
                    break;
                case '多选一':
                    $buffer['sales_material_type'] = 5;
                    break;
                case '礼盒':
                    $buffer['sales_material_type'] = 6;
                    break;
            }

            // 所属店铺
            $shopMdl = app::get('ome')->model('shop');
            if ($buffer['shop_id'] == '全部店铺'){
                $buffer['shop_id'] = '_ALL_';
            } elseif ($buffer['shop_id']){
                $shopInfo = $shopMdl->dump(array('name'=>$buffer['shop_id']), 'shop_id');
                $buffer['shop_id'] = $shopInfo['shop_id'];
            }

            $arrBmId = [];

            if ($buffer['sales_material_type'] == 2){ //组合物料类型(至少关联一个基础物料)
                $tmp_at = array();
                $tmp_salesMInfos = explode('|', $buffer['material_bn']);
                foreach($tmp_salesMInfos as $tmp_basicMInfo){
                    $tmp_bnInfo    = explode(':', $tmp_basicMInfo);#格式：物料编号1:数量1:组合价格贡献占比|物料编号2:数量2:组合价格贡献占比
    
                    if (!isset($tmp_bnInfo[2])) {
                        $tmp_bnInfo[2] = 0;
                        $auto_calculation_percentage = true; #没有组合价格贡献占比参数，需要自动计算组合价格贡献占比
                    }
                    $tmp_binds[$tmp_bnInfo[0]]    = array(intval($tmp_bnInfo[1]), intval($tmp_bnInfo[2]));
                }
                unset($buffer['material_bn']);

                $buffer['material_bn'] = $tmp_binds;
                foreach($buffer['material_bn'] as $bn => $val){
                    $basicInfo = $bmMdl->getList('bm_id', array('material_bn'=>$bn), 0, 1);
                    $tmp_at[$basicInfo[0]['bm_id']]    = $val;
                    $arrBmId[] = $basicInfo[0]['bm_id'];
                }

                $sum_cost = 0;
                $material_rows = array();
                if ($auto_calculation_percentage) { #需要自动计算组合价格百分比

                    #基础物料信息
                    $material_rows = $this->getBasicMaterial(array_keys($tmp_at));

                    foreach ($tmp_at as $bm_id=> $tmp_at_item) { //计算总成本价多少
                        $cost = $material_rows[$bm_id]['cost'];
                        $number = $tmp_at_item[0];
                        $sum_cost += $cost * $number;
                    }
                }

                #组合价格贡献占比
                $buffer['material_bn'] = array();
                $tmp_pr = array();
                $total = 100;
                $count_basicM = count($tmp_at);
                $count_i = 1;
                foreach ($tmp_at as $key => $val){
                    if($count_basicM == 1){
                        #只有一种物料时
                        $buffer['material_bn'][$key]['number']    = $val[0];
                        $buffer['material_bn'][$key]['rate']      = $total;
                        #验证数据时使用
                        $tmp_pr[$key]    = $buffer['material_bn'][$key]['rate'];
                    }else{

                        if ($auto_calculation_percentage) {#自动计算组合价格百分比
                            $number = $val[0];
                            $cost = $material_rows[$key]['cost'];

                            $rate = $count_basicM == $count_i
                                ? 100 - array_sum($tmp_pr)
                                : round(($number * $cost) / $sum_cost * 100);

                            $buffer['material_bn'][$key]['number']  = $number;
                            $buffer['material_bn'][$key]['rate']    = $rate;
                            #验证数据时使用
                            $tmp_pr[$key] = $rate;
                            $count_i++;
                        } else {
                            $buffer['material_bn'][$key]['number']    = $val[0];
                            $buffer['material_bn'][$key]['rate']      = $val[1];
                            $tmp_pr[$key] = $val[1];
                        }
                    }
                }
            }elseif($buffer['sales_material_type'] == 4){ //福袋类型
                $csv_data_str = trim($buffer['material_bn']);
                $tmp_lbr = array();
                //福袋基础物料组
                $luckybag_example_str = "，销售物料编码：".$buffer['sales_material_type']."。参考格式（组合名称A：物料编码1|物料编码2-sku数量|送件数量|单品售价#组合名称B：物料编码1|物料编码3-sku数量|送件数量|单品售价）。";
                $tmp_luckybag_rules = explode('#', $csv_data_str);
                foreach($tmp_luckybag_rules as $var_tlr){
                    $temp_lbr = array();
                    $tmp_luckybag_name = explode(':', $var_tlr);
                    if(count($tmp_luckybag_name) == 2){
                        if(!$tmp_luckybag_name[0]){
                            $msg['error'] = "福袋组合名称异常".$luckybag_example_str; break;
                        }
                        if(!$tmp_luckybag_name[1]){
                            $msg['error'] = "福袋组合内容异常".$luckybag_example_str; break;
                        }
                        $temp_lbr["name"] = $tmp_luckybag_name[0];
                        $tmp_luckybag_detail = explode('-', $tmp_luckybag_name[1]);
                        if(count($tmp_luckybag_detail) == 2){
                            if(!$tmp_luckybag_detail[0]){
                                $msg['error'] = $temp_lbr["name"]."的福袋组合基础物料异常".$luckybag_example_str; break;
                            }
                            if(!$tmp_luckybag_detail[1]){
                                $msg['error'] = $temp_lbr["name"]."的福袋组合规则参数异常".$luckybag_example_str; break;
                            }
                            $luckybag_bm_bns = explode("|", $tmp_luckybag_detail[0]);
                            $rs_bm_ids = $bmMdl->getList("bm_id",array("material_bn"=>$luckybag_bm_bns));
                            if(empty($rs_bm_ids)){
                                $msg['error'] = $temp_lbr["name"]."的福袋组合基础物料不存在".$luckybag_example_str; break;
                            }
                            if(count($rs_bm_ids) < count($luckybag_bm_bns)){
                                $msg['error'] = $temp_lbr["name"]."的福袋组合基础物料编码重复或不存在".$luckybag_example_str; break;
                            }
                            foreach($rs_bm_ids as $var_bi){
                                $temp_lbr["bm_ids"][] = $var_bi["bm_id"];
                                $arrBmId[] = $var_bi["bm_id"];
                            }
                            $luckybag_params = explode("|", $tmp_luckybag_detail[1]);
                            if(count($luckybag_params) != 3){
                                $msg['error'] = $temp_lbr["name"]."的福袋组合参数异常".$luckybag_example_str; break;
                            }
                            $temp_lbr["sku_num"] = $luckybag_params[0];
                            $temp_lbr["send_num"] = $luckybag_params[1];
                            $temp_lbr["single_price"] = $luckybag_params[2];
                        }else{
                            $msg['error'] = $temp_lbr["name"]."格式错误，".$luckybag_example_str; break;
                        }
                    }else{
                        $msg['error'] = "格式错误，".$luckybag_example_str; break;
                    }
                    $tmp_lbr[] = $temp_lbr;
                }
            }elseif($buffer['sales_material_type'] == 5){ //多选一类型
                $csv_data_str = trim($buffer['material_bn']);
                //福袋基础物料组
                $tmp_sort = array();
                $pickone_example_str = "，销售物料编码：".$buffer['sales_material_type']."。参考格式（随机#sku01:0|sku02:0 或 排序#sku01:10|sku02:20）。";
                $tmp_pickone_rules = explode('#', $csv_data_str);
                $count_pickone_data = count($tmp_pickone_rules);

                if($tmp_pickone_rules[0] == "随机"){
                    $pickone_select_type = 1;
                }elseif($tmp_pickone_rules[0] == "排序"){
                    $pickone_select_type = 2;
                }
                $tmp_pickone_items = explode('|', $tmp_pickone_rules[1]);
                $arr_bm_id = array();
                foreach($tmp_pickone_items as $var_p_i){
                    $current_pickone_items = explode(':',$var_p_i);
                    if(count($current_pickone_items) != 2){
                        $msg['error'] = "多选一关联基础物料明细信息异常".$pickone_example_str; break;
                    }
                    $rs_basic = $bmMdl->dump(array("material_bn"=>$current_pickone_items[0]),"bm_id");
                    if(empty($rs_basic)){
                        $msg['error'] = "基础物料编码".$current_pickone_items[0]."不存在".$pickone_example_str; break;
                    }
                    if(in_array($rs_basic["bm_id"],$arr_bm_id)){
                        $msg['error'] = "基础物料编码".$current_pickone_items[0]."重复了".$pickone_example_str; break;
                    }
                    $arr_bm_id[] = $rs_basic["bm_id"];
                    $arrBmId[] = $rs_basic["bm_id"];
                    $tmp_sort[$rs_basic["bm_id"]] = $current_pickone_items[1] ? $current_pickone_items[1] : 0;
                }

            }else{ #普通、赠品物料类型(默认关联的基础物料可以为空)
                if($buffer['material_bn']){
                    $basic_filter = array('material_bn'=>$buffer['material_bn']);
                    if ($buffer['sales_material_type'] ==6){
                        $basic_filter['type'] = 4;
                    }
                    $basicInfo    = $bmMdl->getList('bm_id', $basic_filter, 0, 1);
                    if($basicInfo){
                        $buffer['material_bn']    = $basicInfo[0]['bm_id'];
                        $arrBmId[]    = $basicInfo[0]['bm_id'];
                    }
                }
            }

            #销售物料扩展信息
            $buffer['cost']    = floatval($buffer['cost']);
            $buffer['retail_price']    = floatval($buffer['retail_price']);
            $buffer['weight']    = intval($buffer['weight']);
            $buffer['unit']    = trim($buffer['unit']);
            if($buffer['brand_id']) {
                $brandInfo = app::get('ome')->model('brand')->db_dump(['brand_name'=>$buffer['brand_id']], 'brand_id');

                $basicBrandInfo = app::get('material')->model('basic_material_ext')->getList('brand_id', ['bm_id'=>$arrBmId]);
                $basicBrandInfo = array_column($basicBrandInfo, 'brand_id');

                $buffer['brand_id'] = $brandInfo['brand_id'];
            }

            #拼接数据
            $sdf = array(
                'sales_material_name' => $buffer['sales_material_name'],
                'sales_material_bn' => trim($buffer['sales_material_bn']),
                'sales_material_type' => $buffer['sales_material_type'],
                'shop_id' => $buffer['shop_id'],
                'is_bind' => 2,
                'bind_bm_id' => $buffer['material_bn'],
                'at' => $tmp_at,
                'pr' => $tmp_pr,
                'lbr' => $tmp_lbr,
                'sort' => $tmp_sort,
                'pickone_select_type' => $pickone_select_type,
                'sales_material_bn_crc32' => '',
                'cost' => $buffer['cost'],
                'retail_price' => $buffer['retail_price'],
                'weight' => $buffer['weight'],
                'unit' => $buffer['unit'],
                'brand_id' => $buffer['brand_id'],
            );
            $res['data'] = $sdf;
        }
        
        $previousRow = array_combine($title, $row); // 在最后，保存前一条数据,给下个循环使用,因为同一张订单主表信息只有第一条有
        return $res;
     }

    /**
     * _formatData
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function _formatData(&$data)
     {
        foreach ($data as $k => $str) {
            $data[$k] = str_replace(array("\r\n", "\r", "\n", "\t"), "", $str);
        }
     }

    function implodeMaterial($contents)
    {
        $importObj    = app::get('material')->model('sales_material');
        $salesBasicMaterialObj = app::get('material')->model('sales_basic_material');
        $salesMaterialExtObj = app::get('material')->model('sales_material_ext');
        $salesMaterialShopFreezeObj = app::get('material')->model('sales_material_shop_freeze');

        foreach ($contents as $v){
            //销售物料主表sales_material
            $importData = array(
                    'sales_material_name' => $v['sales_material_name'],
                    'sales_material_bn' => $v['sales_material_bn'],
                    'sales_material_bn_crc32' => $v['sales_material_bn_crc32'],
                    'shop_id' => $v['shop_id'],
                    'sales_material_type' => $v['sales_material_type'],
                    'is_bind' => $v['is_bind'],
                    'create_time' => time(),
                    'disabled' => 'false',
            );
            $is_save    = $importObj->save($importData);

            if($is_save)
            {
                $is_bind = false;

                //如果有关联物料就做绑定操作
                if(($v['sales_material_type'] == 1 || $v['sales_material_type'] == 3 || $v['sales_material_type'] == 6) && !empty($v['bind_bm_id']))
                {
                    //普通或赠品销售物料关联
                    $addBindData = array(
                                        'sm_id' => $importData['sm_id'],
                                        'bm_id' => $v['bind_bm_id'],
                                        'number' => 1,
                                    );
                    $salesBasicMaterialObj->insert($addBindData);
                    $bm_id = $v['bind_bm_id'];
                    $is_bind = true;
                }
                elseif(($v['sales_material_type'] == 2) && !empty($v['bind_bm_id']))
                {
                    //促销销售物料关联
                    foreach($v['bind_bm_id'] as $bm_key => $bm_val)
                    {
                        $addBindData = array(
                                            'sm_id' => $importData['sm_id'],
                                            'bm_id' => $bm_key,
                                            'number' => $bm_val['number'],
                                            'rate' => (string)$bm_val['rate'],
                                        );
                        $salesBasicMaterialObj->insert($addBindData);

                        $addBindData = null;
                    }
                    $bm_id = $bm_key;

                    $is_bind = true;
                }elseif($v['sales_material_type'] == 4 && !empty($v['lbr'])){ //福袋
                    $mdl_material_luckbag_rules = app::get('material')->model('luckybag_rules');
                    foreach($v['lbr'] as $var_lbr){
                        $addBindData = array(
                                "lbr_name" => $var_lbr["name"],
                                "sm_id" => $importData['sm_id'],
                                "bm_ids" => implode(",", $var_lbr["bm_ids"]),
                                "sku_num" => $var_lbr["sku_num"],
                                "send_num" => $var_lbr["send_num"],
                                "price" => $var_lbr["single_price"],
                        );
                        $mdl_material_luckbag_rules->insert($addBindData);
                    }
                    $bm_id = $var_lbr["bm_ids"];
                    $is_bind = true;
                }elseif($v['sales_material_type'] == 5 && !empty($v['sort'])){ //多选一
                    $mdl_ma_pickone_ru = app::get('material')->model('pickone_rules');
                    $select_type = $v["pickone_select_type"]; //默认1为“随机” 2为排序
                    foreach($v['sort'] as $key_bm_id => $val_sort){
                        $current_insert_arr = array(
                                "sm_id" => $importData['sm_id'],
                                "bm_id" => $key_bm_id,
                                "sort" => $val_sort ? $val_sort : 0,
                                "select_type" => $select_type,
                        );
                        $mdl_ma_pickone_ru->insert($current_insert_arr);
                    }
                    $bm_id = $key_bm_id;
                    $is_bind = true;
                }

                //如果有绑定物料数据，设定销售物料为绑定状态
                if($is_bind){
                    $importObj->update(array('is_bind'=>1), array('sm_id'=>$importData['sm_id']));
                }
                if($v['brand_id']) {
                    $brand_id = $v['brand_id'];
                } else {
                    $brandInfo = app::get('material')->model('basic_material_ext')->db_dump(['bm_id'=>$bm_id], 'brand_id');
                    $brand_id = $brandInfo['brand_id'];
                }
                //保存销售物料扩展信息
                $addExtData = array(
                        'sm_id' => $importData['sm_id'],
                        'cost' => floatval($v['cost']),
                        'retail_price' => floatval($v['retail_price']),
                        'weight' => floatval($v['weight']),
                        'unit' => $v['unit'],
                        'brand_id' => $brand_id,
                );
                $salesMaterialExtObj->insert($addExtData);

                //保存销售物料店铺级冻结
                if($v['shop_id'] != '_ALL_')
                {
                    $addStockData = array(
                            'sm_id' => $importData['sm_id'],
                            'shop_id' => $v['shop_id'],
                            'shop_freeze' => 0,
                    );
                    $salesMaterialShopFreezeObj->insert($addStockData);
                }
            }else{
                $m = $importObj->db->errorinfo();
                if(!empty($m)){
                    $errmsg.=$m.";";
                }
            }
        }
        return [true, $errmsg];
    }

    /**
     * 获取Title
     * @param mixed $filter filter
     * @param mixed $ioType ioType
     * @return mixed 返回结果
     */
    public function getTitle($filter=null,$ioType='csv'){
        $summaryTitle = array_merge(self::IMPORT_TITLE, self::IMPORT_ITEM_TITLE);
        return array_column($summaryTitle, 'label');
    }

}