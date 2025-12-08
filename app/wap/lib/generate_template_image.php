<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 从数据库读取模板数据并生成图片
 * 读取 sdb_logisticsmanager_express_template 表中 template_id=50 的 template_data
 * 调用 jsontoimage.php 生成图片
 */
require_once(dirname(__FILE__) . '/jsontoimage.php');

class TemplateImageGenerator {
    
    private $db;
    private $converter;
    
    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct() {
        // 获取数据库连接
        $this->db = kernel::database();
    }
    
    /**
     * 获取数据库连接
     */
    public function getDb() {
        return $this->db;
    }
    
    /**
     * 从数据库读取模板数据
     * @param int $templateId 模板ID
     * @return array|false 模板数据或false
     */
    public function getTemplateData($templateId = 50) {
        $sql = "SELECT template_data FROM sdb_logisticsmanager_express_template WHERE template_id = ".$templateId;
        $result = $this->db->select($sql);
        
        if (empty($result)) {
            return false;
        }
        
        $templateData = $result[0]['template_data'];
        
        // 尝试解析JSON
        $data = json_decode($templateData, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        
        // 如果JSON解析失败，尝试反序列化
        $data = unserialize($templateData);
        if ($data === false) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * 生成图片
     * @param int $templateId 模板ID
     * @param string $outputFile 输出文件路径
     * @param array|string $inputData 输入数据（可选）
     * @return bool 是否成功
     */
    public function generateImage($templateId = 50, $outputFile = null, $inputData = array()) {
        // 获取模板数据
        $templateData = $this->getTemplateData($templateId);
        
        if (!$templateData) {
            echo "错误：无法获取模板ID {$templateId} 的数据\n";
            return false;
        }
        
        // 预处理输入数据
        $inputData = $this->preprocessInputData($inputData);
        
        // 如果没有指定输出文件，生成默认文件名
        if (!$outputFile) {
            $outputFile = dirname(__FILE__) . '/../statics/images/template_' . $templateId . '_' . date('YmdHis') . '.jpg';
        }
        
        try {
            // 创建转换器实例
            $this->converter = new JsonToImageConverter($templateData);
            
            // 如果有输入数据，设置数据
            if (!empty($inputData)) {
                $this->converter->setInputData($inputData);
            }
            
            // 创建图片
            $this->converter->createImage();
            
            // 保存图片
            $this->converter->saveImage($outputFile);
            
            return true;
            
        } catch (Exception $e) {
            echo "错误：生成图片失败 - " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * 预处理输入数据
     * @param array|string $inputData 输入数据
     * @return array 处理后的数据
     */
    public function preprocessInputData($inputData) {
        // 如果是字符串，尝试解析为JSON
        if (is_string($inputData)) {
            $decoded = json_decode($inputData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $inputData = $decoded;
            } else {
                // 如果不是JSON，当作单个值处理
                $inputData = array('value' => $inputData);
            }
        }
        
        // 确保是数组
        if (!is_array($inputData)) {
            $inputData = array();
        }
        
        // 数据标准化
        $inputData = $this->standardizeData($inputData);
        
        return $inputData;
    }
    
    /**
     * 数据标准化
     * @param array $data 原始数据
     * @return array 标准化后的数据
     */
    private function standardizeData($data) {
        // 字段映射表
        $fieldMappings = array(
            // 发货人信息
            'sender_name' => array('sender_name', 'ship_name', 'senderName', 'shipName', 'sender', 'ship'),
            'sender_phone' => array('sender_phone', 'ship_phone', 'senderPhone', 'shipPhone', 'sender_tel', 'ship_tel'),
            'sender_address' => array('sender_address', 'ship_address', 'senderAddress', 'shipAddress', 'sender_addr', 'ship_addr'),
            'sender_company' => array('sender_company', 'ship_company', 'senderCompany', 'shipCompany'),
            
            // 收货人信息
            'receiver_name' => array('receiver_name', 'consignee_name', 'receiverName', 'consigneeName', 'receiver', 'consignee'),
            'receiver_phone' => array('receiver_phone', 'consignee_phone', 'receiverPhone', 'consigneePhone', 'receiver_tel', 'consignee_tel'),
            'receiver_address' => array('receiver_address', 'consignee_address', 'receiverAddress', 'consigneeAddress', 'receiver_addr', 'consignee_addr'),
            'receiver_company' => array('receiver_company', 'consignee_company', 'receiverCompany', 'consigneeCompany'),
            
            // 订单信息
            'order_number' => array('order_number', 'orderNumber', 'order_id', 'orderId', 'order_bn', 'orderBn'),
            'tracking_number' => array('tracking_number', 'trackingNumber', 'express_no', 'expressNo', 'tracking_no', 'trackingNo'),
            
            // 商品信息
            'goods_name' => array('goods_name', 'goodsName', 'product_name', 'productName', 'item_name', 'itemName'),
            'goods_quantity' => array('goods_quantity', 'goodsQuantity', 'quantity', 'qty', 'item_quantity', 'itemQuantity'),
            'goods_weight' => array('goods_weight', 'goodsWeight', 'weight', 'item_weight', 'itemWeight'),
            
            // 其他信息
            'remark' => array('remark', 'note', 'comment', 'memo'),
            'date' => array('date', 'create_date', 'createDate', 'order_date', 'orderDate'),
            'time' => array('time', 'create_time', 'createTime', 'order_time', 'orderTime')
        );
        
        $standardizedData = array();
        
        // 应用字段映射
        foreach ($fieldMappings as $standardField => $variations) {
            foreach ($variations as $variation) {
                if (isset($data[$variation])) {
                    $standardizedData[$standardField] = $data[$variation];
                    break;
                }
            }
        }
        
        // 保留未映射的字段
        foreach ($data as $key => $value) {
            if (!isset($standardizedData[$key])) {
                $standardizedData[$key] = $value;
            }
        }
        
        return $standardizedData;
    }
    
    /**
     * 输出图片到浏览器
     * @param int $templateId 模板ID
     * @param array|string $inputData 输入数据（可选）
     */
    public function outputImage($templateId = 50, $inputData = array()) {
        // 获取模板数据
        $templateData = $this->getTemplateData($templateId);
        
        if (!$templateData) {
            echo "错误：无法获取模板ID {$templateId} 的数据\n";
            return false;
        }
        
        // 预处理输入数据
        $inputData = $this->preprocessInputData($inputData);
        
        try {
            // 创建转换器实例
            $this->converter = new JsonToImageConverter($templateData);
            
            // 如果有输入数据，设置数据
            if (!empty($inputData)) {
                $this->converter->setInputData($inputData);
            }
            
            // 创建图片
            $this->converter->createImage();
            
            // 输出图片到浏览器
            header('Content-Type: image/jpeg');
            $this->converter->outputImage();
            return true;
            
        } catch (Exception $e) {
            echo "错误：生成图片失败 - " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * 获取模板信息
     * @param int $templateId 模板ID
     * @return array|false 模板信息或false
     */
    public function getTemplateInfo($templateId = 50) {
        $sql = "SELECT template_id, template_name, template_type, template_width, template_height, template_data FROM sdb_logisticsmanager_express_template WHERE template_id =".$templateId;
        $result = $this->db->select($sql);
        
        if (empty($result)) {
            return false;
        }
        
        return $result[0];
    }
    
    /**
     * 获取图片数据
     * @param int $templateId 模板ID
     * @param array|string $inputData 输入数据
     * @return string|false 图片数据或false
     */
    public function getImageData($templateId = 50, $inputData = array()) {
        // 获取模板数据
        $templateData = $this->getTemplateData($templateId);
        
        if (!$templateData) {
            return false;
        }
        
        // 预处理输入数据
        $inputData = $this->preprocessInputData($inputData);
        
        try {
            // 创建转换器实例
            $this->converter = new JsonToImageConverter($templateData);
            
            // 如果有输入数据，设置数据
            if (!empty($inputData)) {
                $this->converter->setInputData($inputData);
            }
            
            // 创建图片
            $this->converter->createImage();
            
            // 使用转换器的getImageData方法获取图片数据
            $imageData = $this->converter->getImageData();
            
            return $imageData;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 生成Base64编码的图片
     * @param int $templateId 模板ID
     * @param array|string $inputData 输入数据
     * @return string|false Base64编码的图片或false
     */
    public function getBase64Image($templateId = 50, $inputData = array()) {
        $imageData = $this->getImageData($templateId, $inputData);
        
        if ($imageData === false) {
            return false;
        }
        
        return 'data:image/jpeg;base64,' . base64_encode($imageData);
    }
    
    /**
     * 上传图片到OSS
     * @param int $templateId 模板ID
     * @param array|string $inputData 输入数据
     * @param string $objectName OSS对象名称（可选）
     * @return array 上传结果
     */
    public function uploadToOSS($templateId = 50, $inputData = array(), $objectName = '') {
        try {
            // 获取图片数据
            $imageData = $this->getImageData($templateId, $inputData);
            
            if ($imageData === false) {
                return array(
                    'success' => false,
                    'message' => '图片生成失败'
                );
            }
            
            // 转换为Base64
            $base64Data = base64_encode($imageData);
            
            // 生成OSS对象名称
            if (empty($objectName)) {
                $objectName = 'template/' . date('Y/m/d/') . 'template_' . date('YmdHis') . '.jpg';
            } else {
                // 确保扩展名为jpg
                $objectName = preg_replace('/\.(png|gif|bmp)$/i', '.jpg', $objectName);
            }
            
            // 上传到OSS
            $result = $this->uploadFile($base64Data, $objectName);
            
            return $result;
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => '上传失败：' . $e->getMessage()
            );
        }
    }
    
    /**
     * 上传文件到OSS
     * @param string $base64Data Base64编码的图片数据
     * @param string $objectName OSS对象名称
     * @return array 上传结果
     */
    public function uploadFile($file_id, $filename)
    {
        if (empty($filename)) {
            return $this->error('文件参数错误');
        }

        $oss = kernel::single('base_storage_aliyunosssystem');
        try {
            $url = '';
            $oss_id = $oss->save($filename, $url, null, []);
            if (empty($url)) {
                throw new Exception('文件上传OSS失败');
            }

            # 删除本地文件
            // unlink($filename);
            # 返回参数
            $params = [
                'id' => $oss_id,           // 文件id
                'img_url' => $url,            // 远端文件URL地址
                'local_file' => $filename,  // 本地文件路径
            ];
            return $params;
        } catch (Exception $ex) {
            return $this->error($ex->getMessage());
        }
    }
}

// 命令行使用示例
if (php_sapi_name() === 'cli') {
    $generator = new TemplateImageGenerator();
    
    // 获取命令行参数
    $templateId = isset($argv[1]) ? (int)$argv[1] : 50;
    $outputFile = isset($argv[2]) ? $argv[2] : null;
    
    echo "正在生成模板ID {$templateId} 的图片...\n";
    
    // 获取模板信息
    $templateInfo = $generator->getTemplateInfo($templateId);
    if ($templateInfo) {
        echo "模板名称: " . $templateInfo['template_name'] . "\n";
        echo "模板类型: " . $templateInfo['template_type'] . "\n";
        echo "模板尺寸: " . $templateInfo['template_width'] . "x" . $templateInfo['template_height'] . "\n";
    }
    
    // 生成图片
    $result = $generator->generateImage($templateId, $outputFile);
    
    if ($result) {
        echo "图片生成成功！\n";
        if ($outputFile) {
            echo "输出文件: " . $outputFile . "\n";
        }
    } else {
        echo "图片生成失败！\n";
    }
}
?> 