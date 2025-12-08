<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoguaniostockorder_iso_to_import implements omecsv_data_split_interface
{
    function run(&$cursor_id, $params)
    {


        $ioOrderLib = kernel::single('taoguaniostockorder_iostockorder');
        $iso_obj = app::get('taoguaniostockorder')->model('iso');
        $item_obj = app::get('taoguaniostockorder')->model('iso_items');


        $page = $params['sdfdata'];

        foreach ($page as $key => $datalist) {

            foreach ($datalist as $k => $iso_data) {

                //生成出入库单号
                $iostockorder_bn = $ioOrderLib->get_iostockorder_bn($iso_data['type_id']);
                $iso_data['iso_bn'] = $iostockorder_bn;
                $iso_items = $iso_data['iso_item'];
                unset($iso_data['iso_item']);
                kernel::single('taoguaniostockorder_iso_to_import')->saveIsoDate($iso_obj, $iso_data, $iso_items);

                foreach ($iso_items as $v) {
                    $v['iso_id'] = $iso_data['iso_id'];
                    $v['iso_bn'] = $iso_data['iso_bn'];

                    $result = $item_obj->save($v);


                }

            }

        }


        return false;
    }

    #组织iso数据，并保存数据
    function saveIsoDate($iso_obj, &$iso_data, $iso_items)
    {
        foreach ($iso_items as $item => $itemVal) {

            $iso_data['product_cost'] += ($itemVal['price'] * $itemVal['nums']);
        }

        $iso_data['name'] = $iso_data['name'];#入库单名称
        $iso_data['iso_bn'] = $iso_data['iso_bn'];
        $iso_data['type_id'] = $iso_data['type_id'];#出入库类型
        $iso_data['branch_id'] = $iso_data['branch_id'];#出入库仓库
        $iso_data['original_bn'] = '';
        $iso_data['original_id'] = 0;
        $iso_data['supplier_id'] = $iso_data['supplier_id'];
        $iso_data['supplier_name'] = $iso_data['supplier_name'];#供应商
        $iso_data['extrabranch_id'] = $iso_data['extrabranch'];#外部仓库
        $iso_data['product_cost'] = $iso_data['product_cost'];#商品总额
        $iso_data['iso_price'] = $iso_data['iso_price'] ? $iso_data['iso_price'] : 0;#出入库费用
        $iso_data['oper'] = $iso_data['oper'];#经办人
        $iso_data['create_time'] = time();
        $iso_data['operator'] = $iso_data['operator'];#网站操作人员
        $iso_data['memo'] = $iso_data['memo'];#备注
        $iso_data['emergency'] = $iso_data['emergency'];#是否紧急

        $res = $iso_obj->save($iso_data);
        return null;


    }
    
    public $column_num = 16;//表头列数
    public $current_iso_bn = null;//切片标识
    public $current_name = null;//切片标识
    public $iso_type = '';//类型
    public $io = '';//出=0、入=1
    
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
        set_time_limit(0);
        @ini_set('memory_limit', '128M');
        $oFunc    = kernel::single('omecsv_func');
        $queueMdl = app::get('omecsv')->model('queue');
        
        $oFunc->writelog('处理任务-开始', 'settlement', $params);
        //业务逻辑处理
        $data           = $params['data'];
        $this->iso_type = $params['type'];
        $this->io       = isset($params['io']) ? $params['io'] : $params['queue_data']['io'];
        $sdf            = [];
        $offset         = intval($data['offset']) + 1;//文件行数 行数默认从1开始
        $splitCount     = 0;//执行行数
        if ($data) {
            foreach ($data as $row) {
                $res = $this->getSdf($row, $offset, $params['title']);
                
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
        //创建出入库单
        if ($sdf) {
            list($result, $msgList) = $this->implodeIostock($sdf);
            if ($msgList) {
                $errmsg = array_merge($errmsg, $msgList);
            }
            $queueMdl->update(['original_bn' => 'iostock', 'split_count' => $splitCount], ['queue_id' => $cursor_id]);
        }
        
        //任务数据统计更新等
        $oFunc->writelog('处理任务-完成', 'settlement', 'Done');
        return [true];
    }
    
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
        $this->io = $queue_data['io'];
        if (is_array($queue_data) && isset($queue_data['filename']) && $queue_data['filename']) {
            //检测队列里是否包含同文件名的未完成任务
            $queue_name = sprintf("%s_导入文件_分派任务", $queue_data['filename']);
            $queueInfo  = app::get('omecsv')->model('queue')->db_dump(['queue_name' => $queue_name, 'queue_mode' => 'assign', 'status' => ['ready', 'process']], 'queue_id,queue_no,queue_name,create_time,at_time');
            if ($queueInfo) {
                return array(false, sprintf('导入文件名称【%s】已存在队列中，队列号：%s', $queue_data['filename'], $queueInfo['queue_no']));
            }
        }
        $ioType   = kernel::single('omecsv_io_split_' . $file_type);
        
        $rows  = $ioType->getData($file_name, 0, -1);
        $title = array_values($this->getTitle($this->io));
        
        $rows[0][0] = str_replace("\u{FEFF}", "", $rows[0][0]);
        $plateTitle = $rows[0];
        foreach ($title as $v) {
            if (array_search($v, $plateTitle) === false) {
                return array(false, '文件模板错误：列【' . $v . '】未包含在' . implode('、', $plateTitle));
            }
        }
        
        //检测数据
        $offset = 1;//文件行数 行数默认从1开始
        $sdf    = [];
        $errmsg = [];
        foreach ($rows as $row) {
            $res = $this->getSdf($row, $offset, $rows[0]);
            if ($res['status'] and $res['data']) {
                $tmp = $res['data'];
                $this->_formatData($tmp);
                $sdf[] = $tmp;
            } elseif (!$res['status']) {
                array_push($errmsg, $res['msg']);
            }
            $offset++;
        }
        if ($errmsg) {
            return array(false, '文件内容错误信息：' . implode('；' . "\n", $errmsg));
        }
        
        
        list($dataList, $errMsg) = $this->addCheckFormat($sdf, true);
        if ($errMsg) {
            return array(false, '文件内容错误信息：' . implode('；', $errMsg));
        }
        
        return array(true, '文件模板匹配', $rows[0]);
    }
    
    /**
     * @param $data
     * @param bool $is_check 是否只做参数验证
     * @return array[]
     * @author db
     * @date 2024-10-11 10:58 上午
     */
    public function addCheckFormat($data, $is_check = true)
    {
        $branch_obj          = app::get('ome')->model('branch');
        $iostock_type_obj    = app::get('ome')->model('iostock_type');
        $extrabranchObj      = app::get('ome')->model('extrabranch');
        $supplier_obj        = app::get('purchase')->model('supplier');
        $isoMdl              = app::get('taoguaniostockorder')->model('iso');
        $barcodeMdl          = app::get('material')->model('barcode');
        $ioOrderLib          = kernel::single('taoguaniostockorder_iostockorder');
        $productLib          = kernel::single('ome_goods_product');
        $iostockorderLib     = kernel::single('console_iostockorder');
        $basicMaterialObj    = app::get('material')->model('basic_material');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $isoTypeMdl = app::get('ome')->model('iso_type');
    
        //入库类型
        $arr_iso_type = $ioOrderLib->get_create_iso_type($this->io, true);
        
        //入库类型
        $iostockTypeList = $iostock_type_obj->getList('type_id,type_name');
        $iostockTypeList = array_column($iostockTypeList, null, 'type_name');
    
        //业务类型
        $isoTypeList    = $isoTypeMdl->getList('type_id,type_name,bill_type, bill_type_name');
        $newIsoTypeList = [];
        foreach ($isoTypeList as $type) {
            $newIsoTypeList[$type['type_id']][] = $type;
        }
        
        $extraBranchList = $extrabranchObj->getList('branch_id,branch_bn,name');
        $extraBranchList = array_column($extraBranchList, null, 'name');
        
        //供应商
        $supplierNameList = array_column($data, 'supplier_name');
        $supplierList     = $supplier_obj->getList('supplier_id,name', array('name' => $supplierNameList));
        $supplierList     = array_column($supplierList, null, 'name');
        
        $branchNames = array_column($data, 'branch_id');
        $branchList  = $branch_obj->getList('branch_id,branch_bn,type,name', ['name' => $branchNames]);
        $branchList  = array_column($branchList, null, 'name');
        $branchIds   = array_column($branchList, 'branch_id');
        
        //基础物料主信息
        $bns         = array_column($data, 'bn');
        $productList = $basicMaterialObj->getList('*', array('material_bn' => $bns));
        $productList = array_column($productList, null, 'material_bn');
        $bmIds       = array_column($productList, 'bm_id');
        //基础物料扩展信息
        $bMaterialList = $basicMaterialExtObj->getList('bm_id,retail_price', array('bm_id' => $bmIds));
        $bMaterialList = array_column($bMaterialList, null, 'bm_id');
        
        //基础物料条形码
        $barcodeList = $barcodeMdl->getList('bm_id,code', array('bm_id' => $bmIds));
        $barcodeList = array_column($barcodeList, null, 'bm_id');
        
        //初始化数组
        $isoList = $isoNameList = $errMsg = $branchStoreList = [];
        
        //获取获取单仓库-多个基础物料的可用库存 出库时,检查库存
        $branchStoreList = [];
        if ($branchIds && $bmIds) {
            foreach ($branchIds as $branch_id) {
                $libBranchProduct            = kernel::single('ome_branch_product');
                $branchStoreList[$branch_id] = $libBranchProduct->getAvailableStore($branch_id, $bmIds);
            }
        }
        
        foreach ($data as $key => $isoSdf) {
            $linekey = $isoSdf['iso_no'] ? $isoSdf['iso_no'] : $isoSdf['name'];
            
            //入库类型
            if (!isset($iostockTypeList[$isoSdf['type_id']])) {
                $errMsg[] = sprintf('请确认出入库类型,单据：%s', $linekey);
                continue;
            }
            
            if (false === array_search($iostockTypeList[$isoSdf['type_id']]['type_id'], $arr_iso_type)) {
                $errMsg[] = sprintf('出入库类型没有找到,单据号：%s', $linekey);
                continue;
            }
            
            $iso_type_id = $iostockTypeList[$isoSdf['type_id']]['type_id'];
    
            if ($isoSdf['bill_type']) {
                $isoTypeData = $newIsoTypeList[$iso_type_id] ?? [];
                if (!$isoTypeData) {
                    $errMsg[] = sprintf('业务类型%s不存在,单据号：%s', $isoSdf['bill_type'], $linekey);
                    continue;
                }
        
                $isoTypeData = array_column($isoTypeData, null, 'bill_type_name');
                if (!isset($isoTypeData[$isoSdf['bill_type']])) {
                    $errMsg[] = sprintf('业务类型%s错误,单据号：%s', $isoSdf['bill_type'], $linekey);
                    continue;
                }
                $isoSdf['bill_type'] = $isoTypeData[$isoSdf['bill_type']]['bill_type'];
            }
            
            //检测数据时不需要 生成单据号 $is_check = false 时 需要生成单据号
            if (!$is_check && !$isoSdf['iso_no']) {
                $linekey = $isoSdf['iso_no'] = $isoNameList[$isoSdf['name']] = isset($isoNameList[$isoSdf['name']]) ? $isoNameList[$isoSdf['name']] : $iostockorderLib->get_iostockorder_bn($iso_type_id);
            }
            
            //单据号
            $iso_no = trim($linekey);
            
            if (!isset($isoList[$iso_no])) {
                $isoList[$iso_no] = [
                    'iso_no'            => $iso_no,
                    'iostockorder_name' => $isoSdf['name'],
                    'supplier'          => $isoSdf['supplier_name'],//供应商
                    'iso_price'         => $isoSdf['iso_price'],//出库费用
                    'operator'          => $isoSdf['oper'],//经手人
                    'memo'              => $isoSdf['memo'],
                    'bill_type'         => $isoSdf['bill_type'],////单据业务类型
                    'business_bn'       => $isoSdf['business_bn'],//业务单号
                    'type_id'           => $iso_type_id,//出库类型
                    'extra_ship_addr'   => $isoSdf['extra_ship_addr'],
                    'extra_ship_name'   => $isoSdf['extra_ship_name'],
                    'extra_ship_mobile' => $isoSdf['extra_ship_mobile'],
                ];
                
                //外部仓库
                if (empty($isoSdf['extrabranch'])) {
                    $errMsg[] = sprintf('外部仓库不能为空,单据号：%s', $linekey);
                    continue;
                }
                
                if (!isset($extraBranchList[$isoSdf['extrabranch']])) {
                    $errMsg[] = sprintf('请填写正确的外部仓库名称,单据号：%s', $linekey);
                    continue;
                }
                
                if (isset($extraBranchList[$isoSdf['extrabranch']])) {
                    $isoList[$iso_no]['extrabranch_id'] = $extraBranchList[$isoSdf['extrabranch']]['branch_id'];
                }
                $isoList[$iso_no]['extrabranch_bn'] = $isoSdf['extrabranch'];
                
                //供应商 (供应商非必填)
                if (!empty($isoSdf['supplier_name'])) {
                    if (!isset($supplierList[$isoSdf['supplier_name']])) {
                        $errMsg[] = sprintf('供应商没有找到,单据号：%s', $linekey);
                        continue;
                    }
                    $isoList[$iso_no]['supplier_id'] = $supplierList[$isoSdf['supplier_name']]['supplier_id'];
                }
                
                //出入库费用
                if (empty($isoSdf['iso_price'])) {
                    $isoList[$iso_no]['iso_price'] = 0;
                } else {
                    $_iso_price = $productLib->valiPositive($isoSdf['iso_price']);
                    if (!$_iso_price) {
                        $errMsg[] = sprintf('出入库费用必须大于等于0,单据号：%s', $linekey);
                        continue;
                    }
                }
                
                //检测是否紧急数据
                if ($isoSdf['emergency'] == '是') {
                    $emergency = 'true';
                } elseif ($isoSdf['emergency'] == '否') {
                    $emergency = 'false';
                } else {
                    $errMsg[] = sprintf('请填写是否紧急：是/否,单据号：%s', $linekey);
                    continue;
                }
                $isoList[$iso_no]['emergency'] = $emergency == 'true' ? $emergency : '';
    
    
                //出入库仓库
                if (!isset($branchList[$isoSdf['branch_id']])) {
                    $errMsg[] = sprintf('请填写正确的仓库名称,单据号：%s', $linekey);
                    continue;
                }
                
                //判断是否残损
                if (in_array($branchList[$isoSdf['branch_id']]['type'], array('damaged')) || in_array($iso_type_id, array('5', '50'))) {
                    if ($branchList[$isoSdf['branch_id']]['type'] == 'damaged' && !in_array($iso_type_id, array('5', '50'))) {
                        $errMsg[] = sprintf('残损出入库和仓库类型必须一致,单据号：%s', $linekey);
                        continue;
                    }
                    
                    if ($branchList[$isoSdf['branch_id']]['type'] != 'damaged' && in_array($iso_type_id, array('5', '50'))) {
                        $errMsg[] = sprintf('残损出入库和仓库类型必须一致,单据号：%s', $linekey);
                        continue;
                    }
                }
                $isoList[$iso_no]['branch'] = $branchList[$isoSdf['branch_id']]['branch_id'];
                
                //若填了发货省市区进行强制校验是否
                if ($isoSdf['area_state'] || $isoSdf['area_city'] || $isoSdf['area_district']) {
                    $area = $isoSdf['area_state'] . '/' . $isoSdf['area_city'] . '/' . $isoSdf['area_district'];
                    list($res, $err_msg, $newArea) = kernel::single('eccommon_regions')->checkRegion($area);
                    if (!$res) {
                        $errMsg[] = sprintf("发货地址【%s】不在地址库！", $err_msg);
                        continue;
                    }
                    $isoList[$iso_no]['extra_ship_area'] = $newArea;
                }
                
            }
            
            //明细行数据
            $item = [
                'bn'    => $isoSdf['bn'],
                'name'  => $isoSdf['product_name'],
                'nums'  => $isoSdf['nums'],
                'unit'  => '',
                'price' => $isoSdf['price'],
            ];
            $bn   = trim($isoSdf['bn']);//货号
            if (empty($bn)) {
                $errMsg[] = sprintf('单据号：%s 货号不能为空!', $linekey);
                continue;
            }
            
            if ($isoList[$iso_no]['items'][$bn]) {
                $errMsg[] = sprintf('单据号：%s,货号: %s 已经存在!', $linekey, $bn);
                continue;
            }
            
            if (!isset($productList[$bn])) {
                $errMsg[] = sprintf('单据号：%s,货号: %s 不存在!', $linekey, $bn);
                continue;
            }
            $productInfo          = $productList[$bn];
            $productInfo['price'] = $bMaterialList[$bn]['retail_price'] ?? 0;
            $bm_id                = $productInfo['bm_id'];
            $item['product_id']   = $bm_id;
            
            //条形码
            if (!empty($isoSdf['barcode'])) {
                if ($isoSdf['barcode'] != $barcodeList[$bm_id]['code']) {
                    $errMsg[] = sprintf('单据号：%s,货号: %s 条形码不存在!', $linekey, $bn);
                    continue;
                }
            }
            
            //数量
            $_nums = $productLib->valiPositive($isoSdf['nums']);
            if (!$_nums) {
                $errMsg[] = sprintf('单据号：%s,货号: %s 数量必须大于0!', $linekey, $bn);
                continue;
            }
            $item['nums'] = intval($isoSdf['nums']);
            
            //价格
            if ($isoSdf['price']) {
                $_price = $productLib->valiPositive($isoSdf['price']);
                if (!$_price) {
                    $errMsg[] = sprintf('单据号：%s,货号: %s 价格必须大于等于0!', $linekey, $bn);
                    continue;
                }
            } else {
                $isoSdf['price'] = (float)$productInfo['price'];
            }
            $item['price'] = (float)$isoSdf['price'];
            
            //出库时,检查库存
            if ($this->io == '0') {
                $storeList = $branchStoreList[$isoList[$iso_no]['branch']];
                $store     = $storeList[$bm_id] ?? 0;
                if (empty($store)) {
                    $errMsg[] = sprintf('单据号：%s,货号: %s 出库仓库没有该货号库存!', $linekey, $bn);
                    continue;
                }
                if ($isoSdf['nums'] > $store) {
                    $errMsg[] = sprintf('单据号：%s,货号: %s 出库数量不能大于库存数：%s !', $linekey, $bn, $store);
                    continue;
                }
            }
            
            
            //拼接数据
            if(isset($isoList[$iso_no]['products'][$bm_id])){
                $errMsg[] = sprintf('单据号：%s,货号: %s 重复 !', $linekey, $bn);
                continue;
            }
            $isoList[$iso_no]['products'][$bm_id] = $item;
            unset($data[$key]);
        }
    
        if ($is_check) {
            $nameList    = array_column($isoList, 'iostockorder_name');
            $uniqueNames = array_unique($nameList);// 去除重复值
            $uniqueNames = array_filter($uniqueNames, function ($value) {
                return !empty($value);// 去除空值
            });
            //检测出入库单据是否有同名称的未审核单据
            if ($uniqueNames) {
                $type_id = $this->io == '1' ? '70' : '7';
                $isoNameList = $isoMdl->getList( 'name,iso_bn',['iso_status' => '1', 'check_status' => '1', 'name' => $uniqueNames, 'type_id' => $type_id],0,10);
                if ($isoNameList) {
                    foreach ($isoNameList as $iso) {
                        $errMsg[] = sprintf('出入单名称【%s】已存在，出入库单号：%s', $iso['name'], $iso['iso_bn']);
                    }
                }
            }
        }
        
        //检测导入单号是否已存在 并 过滤掉已存在单据
        $isoBns    = array_keys($isoList);
        $isoBnList = $isoMdl->getList('iso_bn', ['iso_bn' => $isoBns]);
        if ($isoBnList) {
            foreach ($isoBnList as $val) {
                unset($isoList[$val['iso_bn']]);
            }
            $errMsg[] = sprintf('单据号：%s,已存在!', implode('，', array_column($isoBnList, 'iso_bn')));
        }
        
        unset($iostockTypeList, $extraBranchList, $supplierList, $branchList, $productList, $bMaterialList, $barcodeList, $storeList, $branchStoreList);
        
        return [$isoList, $errMsg];
    }
    
    /**
     * 导入文件表头定义
     * @date 2024-06-06 3:52 下午
     */
    
    public function getSdf($row, $offset = 1, $title)
    {
        $this->getTitle($this->io);
        $row = array_map('trim', $row);
        
        $oSchema = array_flip($this->oSchema['csv'][$this->io]);
        
        $titleKey = array();
        foreach ($title as $k => $t) {
            $titleKey[$k] = array_search($t, $oSchema);
            if ($titleKey[$k] === false) {
                return array('status' => false, 'msg' => '未定义字段`' . $t . '`');
            }
        }
        
        $res = array('status' => true, 'data' => array(), 'msg' => '');
        
        $row_num = count($row);
        if ($this->column_num <= $row_num and $row[0] != '*:单据号') {
            $countKey = count($titleKey);
            $countRow = count($row);
            if($countRow > $countKey){
                $row = array_slice($row, 0, $countKey);
            }
            
            $tmp = array_combine($titleKey, $row);
            if (empty($tmp['iso_bn']) && empty($tmp['name'])) {
                $res['status'] = false;
                $res['msg']    = sprintf("LINE %d :单据号、入库单名称不能都为空！", $offset);
                
                return $res;
            }
            //判断参数不能为空
            foreach ($tmp as $k => $v) {
                if (in_array($k, array('emergency', 'supplier_name', 'branch_id', 'type_id', 'extrabranch', 'bn', 'nums'))) {
                    if (!$v) {
                        $res['status'] = false;
                        $res['msg']    = sprintf("LINE %d : %s 不能为空！", $offset, $oSchema[$k]);
                        return $res;
                    }
                }
            }
            $res['data'] = $tmp;
        }
        
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
    
    function implodeIostock($contents)
    {
        $oFunc = kernel::single('omecsv_func');
        //格式化数据结构
        list($dataList, $errMsg) = $this->addCheckFormat($contents, false);
        foreach ($dataList as $key => $iso_data) {
            //开启事务
            kernel::database()->beginTransaction();
            $iso_no            = $iso_data['iso_no'];
            $iso_data['io_bn'] = $iso_no;
            $iso_id            = kernel::single('console_iostockorder')->save_iostockorder($iso_data, $msg);
            if (!$iso_id) {
                $errMsg[] = sprintf('保存入库单主数据失败,iso_no: %s,错误信息：%s', $iso_no, $msg);
                $oFunc->writelog(sprintf('导入失败: 保存入库单主数据失败：【%s】', $iso_no), 'settlement', $msg);
                kernel::database()->rollBack(); //回滚
                continue;
            }
            //事务提交
            kernel::database()->commit();
        }
        
        return [true, $errMsg];
    }
    
    function getTitle($filter = null, $ioType = 'csv')
    {
        switch ($filter) {
            case '0'://出
                $this->oSchema['csv'][0] = array(
                    '*:单据号'    => 'iso_no',//编号关联商品,支持一次导入多张出库单
                    '*:出库单名称'  => 'name',//出入库单名称
                    '*:是否紧急出库' => 'emergency',
                    '*:供应商'    => 'supplier_name',
                    '*:出货仓库'   => 'branch_id',
                    '*:出库类型'   => 'type_id',
                    '*:出库费用'   => 'iso_price',
                    '*:经办人'    => 'oper',
                    '*:备注'     => 'memo',
                    '*:外部仓库'   => 'extrabranch',
                    '*:业务类型'   => 'bill_type',
                    '*:业务单号'   => 'business_bn',
                    '*:货号'     => 'bn',
                    '*:货品名称'   => 'product_name',
                    '*:货品条形码'  => 'barcode',
                    '*:数量'     => 'nums',
                    '*:价格'     => 'price',
                    '*:收货地址省份'=>'area_state',
                    '*:收货地址城市'=>'area_city',
                    '*:收货地址区/县'=>'area_district',
                    '*:收货人详细地址'=>'extra_ship_addr',
                    '*:收货人姓名'=>'extra_ship_name',
                    '*:收货人手机'=>'extra_ship_mobile',
                );
                break;
            case '1'://入
                $this->oSchema['csv'][1] = array(
                    '*:单据号'    => 'iso_no',//编号关联商品,支持一次导入多张入库单
                    '*:入库单名称'  => 'name',//出入库单名称
                    '*:是否紧急入库' => 'emergency',
                    '*:供应商'    => 'supplier_name',
                    '*:入库仓库'   => 'branch_id',
                    '*:入库类型'   => 'type_id',
                    '*:入库费用'   => 'iso_price',
                    '*:经办人'    => 'oper',
                    '*:备注'     => 'memo',
                    '*:外部仓库'   => 'extrabranch',
                    '*:业务类型'   => 'bill_type',
                    '*:业务单号'   => 'business_bn',
                    '*:货号'     => 'bn',
                    '*:货品名称'   => 'product_name',
                    '*:货品条形码'  => 'barcode',
                    '*:数量'     => 'nums',
                    '*:价格'     => 'price',
                    '*:发货地址省份'=>'area_state',
                    '*:发货地址城市'=>'area_city',
                    '*:发货地址区/县'=>'area_district',
                    '*:发货人详细地址'=>'extra_ship_addr',
                    '*:发货人姓名'=>'extra_ship_name',
                    '*:发货人手机'=>'extra_ship_mobile',
                );
                break;
            default:
                break;
        }
        
        $this->ioTitle[$ioType][$filter] = array_keys($this->oSchema[$ioType][$filter]);
        return $this->ioTitle[$ioType][$filter];
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
        if ($row['0'] !== $this->current_iso_bn) {
            if ($this->current_iso_bn !== null) {
                $is_split = true;
            }
            $this->current_iso_bn = $row['0'];//单据号
        }
        
        if (empty($row['0']) && $row['1'] !== $this->current_name) {
            if ($this->current_name !== null) {
                $is_split = true;
            }
            $this->current_name = $row['1'];//入库单名称
        }
        return $is_split;
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
            'max_direct_count' => 200,
        );
        return $key ? $config[$key] : $config;
    }

}
