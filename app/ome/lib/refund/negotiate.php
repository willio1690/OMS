<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_refund_negotiate extends ome_abstract
{
    /**
     * 退款类型映射数组
     */
    static public $refund_type_list = array(
        1 => '仅退款', // REFUND
        2 => '部分退款', // PART_REFUND
        3 => '退货退款', // RETURN_AND_REFUND
        4 => '换货', // EXCHANGE
        5 => '维修', // REPAIR
        6 => '淘宝换货', // TAOBAO_EXCHANGE
        7 => '售后服务', // CUSTOMER_SERVICE
        8 => '极速换货', // FAST_EXCHANGE
        10 => '补寄', // RESHIPPING
        11 => '自动退款', // AUTO_REFUND
        12 => '整单退', // MAIN_ORDER_REFUND
        13 => '发票补寄', // INVOICE
        14 => '破损补寄', // BROKEN_RESHIPPING
        15 => '退货邮费单', // RETURN_GOODS_POST_FEE
        16 => '仅退邮费', // POST_FEE_REFUND
        17 => '零售合并退款', // RETAIL_COMBINE_REFUND
        18 => '价保服务', // PRICE_PROTECT
        20 => '赔付', // COMPENSATE
        30 => '主单仅退款', // MAIN_ORDER_ONLY_REFUND
        31 => '退运费', // RETURN_GOODS_POSTAGE
        32 => '邮费退差', // RETURN_PART_POSTAGE
    );
    
    /**
     * 获取商家协商页面数据
     *
     * @param $id 退货单ID或退款申请单ID
     * @param $source 来源类型：'return_product' 或 'refund_apply'
     * @return array
     */
    public function getMerchantNegotiationData($id, $source = 'return_product')
    {
        // 根据来源类型获取不同的数据
        if($source == 'refund_apply') {
            $oRefundApply = app::get('ome')->model('refund_apply');
            $detail = $oRefundApply->refund_apply_detail($id);
            $refund_id = $detail['refund_apply_bn'];
            $not_found_msg = '退款申请单不存在！';
            $local_msg = 'local来源的退款申请单不支持协商功能';
        } else {
            $oProduct = app::get('ome')->model('return_product');
            $detail = $oProduct->db_dump($id);
            $refund_id = $detail['return_bn'];
            $not_found_msg = '退货单不存在！';
            $local_msg = 'local来源的售后单不支持协商功能';
        }
        
        if(!$detail){
            return array('rsp' => 'fail', 'msg' => $not_found_msg);
        }
        
        if($detail['source'] == 'local') {
            return array('rsp' => 'fail', 'msg' => $local_msg);
        }
        
        if(!$detail['shop_id']) {
            return array('rsp' => 'fail', 'msg' => '缺少店铺信息，无法发起协商');
        }
        
        $order_id = $detail['order_id'];
        $shop_id = $detail['shop_id'];
        
        // 获取订单信息
        $oOrder = app::get('ome')->model('orders');
        $order_detail = $oOrder->db_dump($order_id);
        
        // 调用矩阵接口获取协商退货退款渲染数据
        $negotiation_data = array();
        try {
            // 先检测是否可以发起协商
            $can_apply_result = $this->checkAndUpdateNegotiateStatus($id, $source);
            // 如果检测失败，直接返回错误
            if($can_apply_result['rsp'] == 'fail'){
                return array('rsp' => 'fail', 'msg' => '协商状态检测失败: ' . $can_apply_result['msg']);
            }
            
            // 重新获取数据，因为协商状态可能已更新
            if($source == 'refund_apply') {
                $detail = $oRefundApply->refund_apply_detail($id);
            } else {
                $detail = $oProduct->db_dump($id);
                // 获取售后单明细数据
                $oProductItems = app::get('ome')->model('return_product_items');
                $items = $oProductItems->getList('*', array('return_id' => $id, 'disabled' => 'false'));
                $firstItem = !empty($items) ? $items[0] : array();
                
                // 获取基础物料信息
                $materialInfo = array();
                if (!empty($firstItem['product_id'])) {
                    $basicMaterialObj = app::get('material')->model('basic_material');
                    $materialInfo = $basicMaterialObj->dump(array('bm_id' => $firstItem['product_id']), 'material_bn,material_name');
                    
                    // 获取基础物料扩展信息（规格等）
                    if (!empty($materialInfo)) {
                        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
                        $extInfo = $basicMaterialExtObj->dump(array('bm_id' => $firstItem['product_id']), 'specifications');
                        $materialInfo = array_merge($materialInfo, $extInfo);
                    }
                }
                
                // 获取销售物料信息
                $salesMaterialInfo = array();
                if (!empty($firstItem['product_id'])) {
                    // 通过sales_basic_material关联表获取销售物料ID
                    $salesBasicMaterialObj = app::get('material')->model('sales_basic_material');
                    $salesBasicMaterial = $salesBasicMaterialObj->dump(array('bm_id' => $firstItem['product_id']), 'sm_id');
                    
                    if (!empty($salesBasicMaterial['sm_id'])) {
                        $salesMaterialObj = app::get('material')->model('sales_material');
                        $salesMaterialInfo = $salesMaterialObj->dump(array('sm_id' => $salesBasicMaterial['sm_id']), 'sales_material_bn,sales_material_name');
                    }
                }
                
                // 获取店铺信息
                $shopInfo = array();
                if (!empty($detail['shop_id'])) {
                    $oShop = app::get('ome')->model('shop');
                    $shopInfo = $oShop->dump($detail['shop_id'], 'shop_bn');
                }
            }
            
            // 检查协商状态，只有pending状态才允许获取协商数据
            $current_status = $detail['negotiate_sync_status'] ?: 'pending';
            if($current_status == 'none'){
                return array('rsp' => 'fail', 'msg' => '当前单据不允许发起协商');
            }
            
            // 获取协商渲染数据
            $params = array(
                'refund_id' => $refund_id,
                'negotiate_version' => 'newNegotiate',//协商版本
            );
            $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_getNegotiateReturnRenderData($params);
    
            if ($rsp_data['rsp'] == 'succ' && isset($rsp_data['data']['refund_negotiatereturn_render_response'])) {
                $negotiation_data = $rsp_data['data']['refund_negotiatereturn_render_response'];
                
                // 处理refund_type_list，将数字类型转换为可读的文本
                // 兼容两种格式：['string'] => [...] 和 [0] => [...]
                if (isset($negotiation_data['refund_type_list']) && is_array($negotiation_data['refund_type_list'])) {
                    $refund_type_list = reset($negotiation_data['refund_type_list']);
                    if (is_array($refund_type_list)) {
                        $refund_type_options = array();
                        foreach ($refund_type_list as $type_code) {
                            if (isset(self::$refund_type_list[$type_code])) {
                                $refund_type_options[$type_code] = self::$refund_type_list[$type_code];
                            }
                        }
                        $negotiation_data['refund_type_options'] = $refund_type_options;
                    }
                }
            } else {
                // 如果接口调用失败，返回失败信息给操作人
                $error_msg = '';
                // 优先取 error_response 下的 sub_msg
                if (isset($rsp_data['data']['error_response']['sub_msg']) && !empty($rsp_data['data']['error_response']['sub_msg'])) {
                    $error_msg = $rsp_data['data']['error_response']['sub_msg'];
                } 
                // 如果取不到，就取 err_msg
                elseif (isset($rsp_data['err_msg']) && !empty($rsp_data['err_msg'])) {
                    $error_msg = $rsp_data['err_msg'];
                } 
                // 最后使用默认错误信息
                else {
                    $error_msg = '获取协商渲染数据失败';
                }
                return array('rsp' => 'fail', 'msg' => $error_msg);
            }
        } catch (Exception $e) {
            // 接口调用失败时使用默认数据
            $negotiation_data = array();
        }
        
        // 构建申请单信息
        $info = array(
            'order_id' => $order_detail['order_bn'] ?: '',
            'apply_bn' => $source == 'refund_apply' ? ($detail['refund_apply_bn'] ?: '') : ($detail['return_bn'] ?: ''),
            'basic_material_code' => $source == 'refund_apply' ? '' : ($materialInfo['material_bn'] ?: ''),
            'basic_material_name' => $source == 'refund_apply' ? '退款申请' : ($materialInfo['material_name'] ?: ''),
            'sales_material_code' => $source == 'refund_apply' ? '' : ($salesMaterialInfo['sales_material_bn'] ?: ''),
            'specification' => $source == 'refund_apply' ? '' : ($materialInfo['specifications'] ?: ''),
            'apply_quantity' => $source == 'refund_apply' ? '1' : ($firstItem['num'] ?: '1'),
            'quantity' => $source == 'refund_apply' ? '1' : ($firstItem['num'] ?: '1'),
            'unit_price' => $source == 'refund_apply' ? ($detail['money'] ?: '0.00') : ($firstItem['price'] ?: '0.00'),
            'subtotal' => $source == 'refund_apply' ? ($detail['money'] ?: '0.00') : ($firstItem['amount'] ?: '0.00'),
            'shop_bn' => $shopInfo['shop_bn'] ?: '',
            'apply_reason' => $source == 'refund_apply' ? ($detail['memo'] ?: '') : ($detail['content'] ?: ''),
            'apply_remarks' => $detail['memo'] ?: '',
            'after_sales_reply' => '正在处理中'
        );
        
        // 检查是否已有协商记录（编辑模式）
        $negotiateModel = app::get('ome')->model('refund_negotiate');
        $existing_negotiate = $negotiateModel->getByReturnId($id, $source);
        $negotiate_data = array();
        
        if (!empty($existing_negotiate)) {
            $negotiate_data = $existing_negotiate[0];
        }
        
        return array('rsp' => 'succ', 'msg' => '获取协商数据成功', 'data' => array(
            'refund_info' => $info,
            'negotiation_data' => $negotiation_data,
            'negotiate_data' => $negotiate_data, // 添加已保存的协商数据
            'refund_id' => $id
        ));
    }
    
    /**
     * 处理商家协商提交数据
     *
     * @param $id 退货单ID或退款申请单ID
     * @param $post_data
     * @param $source 来源类型：'return_product' 或 'refund_apply'
     * @return array
     */
    public function processMerchantNegotiation($id, $post_data, $source = 'return_product')
    {
        // 验证必要参数
        if(empty($id)){
            return array('rsp' => 'fail', 'msg' => '无效的ID');
        }
        
        if(empty($post_data['negotiation_type'])){
            return array('rsp' => 'fail', 'msg' => '请选择协商类型');
        }
        
        if(empty($post_data['suggested_amount'])){
            return array('rsp' => 'fail', 'msg' => '请输入建议退款金额');
        }
        
        if(empty($post_data['suggested_reason'])){
            return array('rsp' => 'fail', 'msg' => '请选择建议原因');
        }
        
        // 根据来源类型获取不同的数据
        if($source == 'refund_apply') {
            $oRefundApply = app::get('ome')->model('refund_apply');
            $detail = $oRefundApply->refund_apply_detail($id);
            $refund_id = $detail['refund_apply_bn'];
            $not_found_msg = '退款申请单不存在';
        } else {
            $oProduct = app::get('ome')->model('return_product');
            $detail = $oProduct->db_dump($id);
            $refund_id = $detail['return_bn'];
            $not_found_msg = '退货单不存在';
        }
        
        if(!$detail){
            return array('rsp' => 'fail', 'msg' => $not_found_msg);
        }
        
        $order_id = $detail['order_id'];
        $shop_id = $detail['shop_id'];
        
        // 直接使用平台返回的negotiate_code作为数据库存储的negotiate_type
        $negotiate_type = intval($post_data['negotiation_type']);
        
        // 获取协商模型和图片模型
        $negotiateModel = app::get('ome')->model('refund_negotiate');
        $imageModel = app::get('image')->model('image');
        
        // 检查是否已存在协商记录
        $existing_negotiate = $negotiateModel->getByReturnId($id, $source);
        
        // 获取订单信息
        $orderModel = app::get('ome')->model('orders');
        $order_detail = $orderModel->db_dump($order_id);
        $order_bn = $order_detail ? $order_detail['order_bn'] : '';
        
        // 准备协商数据
        $negotiate_data = array(
            'refund_type' => $source == 'refund_apply' ? $negotiateModel::REFUND_TYPE_REFUND : $negotiateModel::REFUND_TYPE_RETURN,
            'original_id' => $id,
            'original_bn' => $refund_id,
            'order_id' => $order_id,
            'order_bn' => $order_bn,
            'shop_id' => $shop_id,
            'negotiate_type' => $negotiate_type,
            'negotiate_sync_status' => 'pending',
            'negotiate_sync_msg' => '',
            'negotiate_desc' => $post_data['negotiation_plan'] ?: '',
            'negotiate_text' => $post_data['recommended_script'] ?: '',
            'negotiate_refund_fee' => $post_data['suggested_amount'],
            'negotiate_reason_id' => $post_data['suggested_reason'],
            'negotiate_reason_text' => $post_data['suggested_reason_text'] ?: '',
            'negotiate_address_id' => $post_data['return_address'] ?? '',
            'negotiate_address_text' => $post_data['return_address_text'] ?? '',
            'refund_version' => $post_data['refund_version'] ?: '',
            'refund_type_code' => $post_data['refund_type'] ?: '', // 添加退款类型代码
        );
        
        // 保存或更新协商记录
        if (!empty($existing_negotiate)) {
            $result = $negotiateModel->updateNegotiate($existing_negotiate[0]['id'], $negotiate_data);
        } else {
            $result = $negotiateModel->createNegotiate($negotiate_data);
        }
        
        if($result){
            // 更新对应表的协商状态为running
            if($source == 'refund_apply') {
                $oRefundApply->update(array('negotiate_sync_status' => 'running'), array('apply_id' => $id));
            } else {
                $oProduct->update(array('negotiate_sync_status' => 'running'), array('return_id' => $id));
            }
            
            // =============== 处理协商凭证图片上传（参考基础物料编辑方式） ===============
            // 获取当前提交的图片ID（可能为空，表示用户删除了图片）
            $submittedImageId = !empty($post_data['current_image_id']) ? $post_data['current_image_id'] : null;
            
            // 获取数据库里原有的图片ID
            $originalImageId = null;
            if ($id) {
                $attachedImages = $imageModel->getAttachedImages($source == 'refund_apply' ? 'refund_nego' : 'return_nego', $id);
                if ($attachedImages) {
                    $originalImageId = $attachedImages[0]['image_id'];
                }
            }
            // 比较图片ID是否发生变化
            if ($submittedImageId !== $originalImageId) {
                // 图片ID发生变化，需要处理
                
                // 如果有原图片，先删除
                if ($originalImageId) {
                    $imageModel->detach($originalImageId, $source == 'refund_apply' ? 'refund_nego' : 'return_nego', $id, true);
                }
                
                // 如果有新图片上传，则上传新图片
                if (isset($_FILES['proof_images']) && !empty($_FILES['proof_images']['name']) && 
                    !empty($_FILES['proof_images']['tmp_name']) && 
                    $_FILES['proof_images']['size'] > 0 && 
                    $_FILES['proof_images']['error'] === UPLOAD_ERR_OK) {
                    
                    try {
                        $uploadFile = $_FILES['proof_images'];
                        $imageName = $uploadFile['name'];
                        $imageTmpPath = $uploadFile['tmp_name'];
                        
                        // 使用缩写的目标类型，避免字段长度超限
                        $targetType = $source == 'refund_apply' ? 'refund_nego' : 'return_nego';
                        
                        $imageResult = $imageModel->uploadAndAttach(
                            $imageTmpPath,           // 图片文件路径
                            $targetType,            // 目标类型
                            $id,                     // ID
                            $imageName,              // 图片名称
                            null,                    // 不生成不同尺寸
                            false                    // 不添加水印
                        );
                        
                        if (isset($imageResult['error'])) {
                            return array('rsp' => 'fail', 'msg' => '协商凭证图片上传失败：' . $imageResult['error']);
                        }
                        
                        if (!$imageResult) {
                            return array('rsp' => 'fail', 'msg' => '协商凭证图片上传失败，请重试');
                        }
                    } catch (Exception $e) {
                        return array('rsp' => 'fail', 'msg' => '协商凭证图片上传失败：' . $e->getMessage());
                    }
                }
                // 如果没有新图片上传且submittedImageId为空，说明用户删除了图片
            }
            // 如果图片ID没有变化，什么都不做，保持原图片
            
            // 在图片处理完成后，获取图片并进行 base64 编码
            $refuseProofBase64 = '';
            $attachedImages = $imageModel->getAttachedImages($source == 'refund_apply' ? 'refund_nego' : 'return_nego', $id);
            if (!empty($attachedImages)) {
                $imageInfo = $attachedImages[0];
                // 使用 full_url 获取完整的图片路径
                $imageUrl = isset($imageInfo['full_url']) ? $imageInfo['full_url'] : $imageInfo['url'];
                $refuseProofBase64 = $this->convertImageToBase64($imageUrl);
            }
            
            // 将 base64 编码的图片数据添加到协商数据中
            $negotiate_data['refuse_proof_base64'] = $refuseProofBase64;
            
            // 调用协商退货退款接口
            $api_result = $this->callRefundNegotiationApi($id, $negotiate_data, $detail, $source);
            
            // 如果接口调用失败，返回错误信息
            if ($api_result['rsp'] == 'fail') {
                return array('rsp' => 'fail', 'msg' => $api_result['msg']);
            }
            
            // 记录操作日志
            $log_type = $source == 'refund_apply' ? 'refund_apply@ome' : 'return@ome';
            app::get('ome')->model('operation_log')->write_log($log_type, $id, '商家协商信息已更新');
            
            return array('rsp' => 'succ', 'msg' => '协商信息保存成功');
        } else {
            return array('rsp' => 'fail', 'msg' => '协商信息保存失败');
        }
    }
    
    /**
     * 调用协商退货退款接口
     *
     * @param $id
     * @param $negotiate_data
     * @param $detail
     * @param $source
     * @return array
     */
    private function callRefundNegotiationApi($id, $negotiate_data, $detail, $source = 'return_product')
    {
        try {
            // 根据source类型获取refund_id
            $refund_id = ($source == 'refund_apply') ? $detail['refund_apply_bn'] : $detail['return_bn'];
            
            // 将完整数据传递给子类处理
            $api_params = array(
                'negotiate_data' => $negotiate_data,
                'detail' => $detail,
                'refund_id' => $refund_id,
                'source' => $source,
            );
            
            // 调用接口
            $result = kernel::single('erpapi_router_request')->set('shop', $detail['shop_id'])->finance_createRefundNegotiation($api_params);
            
            if ($result['rsp'] == 'succ') {
                // 更新协商状态为协商发起成功
                if($source == 'refund_apply') {
                    $oRefundApply = app::get('ome')->model('refund_apply');
                    $oRefundApply->update(array('negotiate_sync_status' => 'succ'), array('apply_id' => $id));
                } else {
                    $oProduct = app::get('ome')->model('return_product');
                    $oProduct->update(array('negotiate_sync_status' => 'succ'), array('return_id' => $id));
                }
                
                // 记录成功日志
                $log_type = $source == 'refund_apply' ? 'refund_apply@ome' : 'return@ome';
                app::get('ome')->model('operation_log')->write_log($log_type, $id, '协商退货退款接口调用成功');
                
                return array('rsp' => 'succ', 'msg' => '协商发起成功');
            } else {
                // 记录失败日志
                $error_msg = $this->formatApiErrorMsg($result);
                $log_type = $source == 'refund_apply' ? 'refund_apply@ome' : 'return@ome';
                app::get('ome')->model('operation_log')->write_log($log_type, $id, '协商退货退款接口调用失败: ' . $error_msg);
                
                return array('rsp' => 'fail', 'msg' => $error_msg);
            }
            
        } catch (Exception $e) {
            // 记录异常日志
            $error_msg = '接口调用异常: ' . $e->getMessage();
            $log_type = $source == 'refund_apply' ? 'refund_apply@ome' : 'return@ome';
            app::get('ome')->model('operation_log')->write_log($log_type, $id, '协商退货退款接口调用异常: ' . $e->getMessage());
            
            return array('rsp' => 'fail', 'msg' => $error_msg);
        }
    }
    
    /**
     * 将图片URL转换为base64编码
     *
     * @param string $imageUrl 图片URL
     * @return string base64编码的图片数据
     */
    private function convertImageToBase64($imageUrl)
    {
        try {
            $isHttpUrl = false;
            
            // 如果已经是完整URL（http或https），直接使用
            if (strpos($imageUrl, 'http') === 0) {
                $filePath = $imageUrl;
                $isHttpUrl = true;
            } else {
                // 如果是相对路径，转换为绝对路径
                $filePath = ROOT_DIR . '/' . ltrim($imageUrl, '/');
            }
            
            // 对于HTTP URL，跳过file_exists检查，直接尝试读取
            if (!$isHttpUrl && !file_exists($filePath)) {
                return '';
            }
            
            // 读取文件内容并转换为base64
            $imageData = file_get_contents($filePath);
            if ($imageData === false) {
                return '';
            }
            
            // 获取图片MIME类型
            if ($isHttpUrl) {
                // 对于HTTP URL，通过内容检测MIME类型
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_buffer($finfo, $imageData);
                finfo_close($finfo);
            } else {
                // 对于本地文件，通过文件路径检测MIME类型
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $filePath);
                finfo_close($finfo);
            }
            
            // 构建data URL格式的base64
            $base64Data = base64_encode($imageData);
            return 'data:' . $mimeType . ';base64,' . $base64Data;
            
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * 格式化API错误信息，提取用户友好的错误描述
     *
     * @param array $result API返回结果
     * @return string
     */
    private function formatApiErrorMsg($result)
    {
        // 优先取 error_response 下的 sub_msg
        if (isset($result['data']['error_response']['sub_msg']) && !empty($result['data']['error_response']['sub_msg'])) {
            return $result['data']['error_response']['sub_msg'];
        }
        
        // 如果有 err_msg 字段
        if (isset($result['err_msg']) && !empty($result['err_msg'])) {
            return $result['err_msg'];
        }
        
        // 如果有 data 字段且包含 error_response，提取 msg
        if (isset($result['data']['error_response']['msg']) && !empty($result['data']['error_response']['msg'])) {
            return $result['data']['error_response']['msg'];
        }
        
        // 最后使用 msg 字段
        if (isset($result['msg']) && !empty($result['msg'])) {
            return $result['msg'];
        }
        
        // 默认错误信息
        return '接口调用失败，请稍后重试';
    }
    
    /**
     * 检查并更新协商状态
     *
     * @param $id
     * @param $source
     * @return array
     */
    public function checkAndUpdateNegotiateStatus($id, $source = 'return_product')
    {
        // 根据来源类型获取不同的数据
        if($source == 'refund_apply') {
            $oRefundApply = app::get('ome')->model('refund_apply');
            $detail = $oRefundApply->refund_apply_detail($id);
            $refund_id = $detail['refund_apply_bn'];
            $not_found_msg = '退款申请单不存在！';
            $local_msg = 'local来源的退款申请单不支持协商功能';
        } else {
            $oProduct = app::get('ome')->model('return_product');
            $detail = $oProduct->db_dump($id);
            $refund_id = $detail['return_bn'];
            $not_found_msg = '退货单不存在！';
            $local_msg = 'local来源的售后单不支持协商功能';
        }
        
        if(!$detail){
            return array('rsp' => 'fail', 'msg' => $not_found_msg);
        }
        
        if($detail['source'] == 'local') {
            return array('rsp' => 'fail', 'msg' => $local_msg);
        }
        
        if(!$detail['shop_id']) {
            return array('rsp' => 'fail', 'msg' => '缺少店铺信息，无法发起协商');
        }
        
        $shop_id = $detail['shop_id'];
        
        try {
            // 调用矩阵接口查询是否可发起协商
            $params = array(
                'refund_id' => $refund_id
            );
            $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_getNegotiateCanApply($params);
            // 接口调用失败
            if ($rsp_data['rsp'] != 'succ' || !isset($rsp_data['data']['refund_negotiate_canapply_response'])) {
                $error_msg = '';
                // 优先取 error_response 下的 sub_msg
                if (isset($rsp_data['data']['error_response']['sub_msg']) && !empty($rsp_data['data']['error_response']['sub_msg'])) {
                    $error_msg = $rsp_data['data']['error_response']['sub_msg'];
                } 
                // 如果取不到，就取 err_msg
                elseif (isset($rsp_data['err_msg']) && !empty($rsp_data['err_msg'])) {
                    $error_msg = $rsp_data['err_msg'];
                } 
                // 最后使用默认错误信息
                else {
                    $error_msg = '未知错误';
                }
                return array('rsp' => 'fail', 'msg' => '接口调用失败: ' . $error_msg);
            }
            
            $can_apply_data = $rsp_data['data']['refund_negotiate_canapply_response'];
            $can_apply = $can_apply_data['can_apply'];
            
            // 根据can_apply结果设置协商状态
            $update_data = array(
                'negotiate_sync_status' => $can_apply ? 'pending' : 'none',
                'negotiate_sync_msg' => $can_apply ? '可以发起协商' : '平台不允许发起协商'
            );
            
            // 更新数据库
            if($source == 'refund_apply') {
                $result = $oRefundApply->update($update_data, array('apply_id' => $id));
            } else {
                $result = $oProduct->update($update_data, array('return_id' => $id));
            }
            
            if(!$result){
                return array('rsp' => 'fail', 'msg' => '协商状态更新失败');
            }
            
            // 记录操作日志
            $log_type = $source == 'refund_apply' ? 'refund_apply@ome' : 'return@ome';
            app::get('ome')->model('operation_log')->write_log($log_type, $id, '协商状态已更新: ' . ($can_apply ? '可协商' : '不可协商'));
            
            return array('rsp' => 'succ', 'msg' => '协商状态检查完成', 'data' => array(
                'can_apply' => $can_apply,
                'negotiate_sync_status' => $update_data['negotiate_sync_status']
            ));
            
        } catch (Exception $e) {
            return array('rsp' => 'fail', 'msg' => '检查协商状态时发生异常: ' . $e->getMessage());
        }
    }
}
