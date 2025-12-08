<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 自配送面单图片生成器
 * 使用GD库生成自配送面单图片
 * @author system
 * @package wap_cloudprint_waybill
 */
class wap_cloudprint_waybill_image_generator {
    
    private $width = 375;
    private $height = 600;
    private $image;
    private $font_path;
    
    /**
     * 构造函数 - 使用msyh.ttf字体优化
     */

    public function __construct($width = 375, $height = 600) {
        $this->width = $width;
        $this->height = $height;
        
        // 创建图片
        $this->image = imagecreatetruecolor($this->width, $this->height);
        
        // 设置图片属性
        imagealphablending($this->image, true);
        imagesavealpha($this->image, false);
        
        // 定义颜色
        $this->white = imagecolorallocate($this->image, 255, 255, 255);
        $this->black = imagecolorallocate($this->image, 0, 0, 0);
        $this->gray = imagecolorallocate($this->image, 128, 128, 128);
        
        // 使用msyh.ttf字体
        $this->font_path = $this->getFontPath();
        
        // 清空图片为白色背景
        imagefill($this->image, 0, 0, $this->white);
    }
    
    /**
     * 获取字体路径 - 优先使用msyh.ttf
     */
    private function getFontPath() {
        // 优先使用项目中的msyh.ttf字体
        $project_fonts = array(
            ROOT_DIR . '/assets/font/msyh.ttf',  // 微软雅黑字体（优先）
        );
        
        foreach ($project_fonts as $font_path) {
            if (file_exists($font_path)) {
                return $font_path;
            }
        }
        
        // 系统字体路径
        $system_fonts = array(
            '/System/Library/Fonts/STHeiti Light.ttc', // macOS
            '/System/Library/Fonts/STHeiti Medium.ttc', // macOS
            '/System/Library/Fonts/PingFang.ttc', // macOS
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', // Linux
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf', // Linux
            'C:/Windows/Fonts/msyh.ttf', // Windows
            'C:/Windows/Fonts/simsun.ttc', // Windows
            'C:/Windows/Fonts/simfang.ttf' // Windows
        );
        
        foreach ($system_fonts as $font_path) {
            if (file_exists($font_path)) {
                return $font_path;
            }
        }
        
        return null;
    }
    
    /**
     * 绘制文本
     */
    private function drawText($x, $y, $text, $size = 12, $color = null, $bold = false) {
        if ($color === null) {
            $color = $this->black;
        }
        
        // 确保文本是UTF-8编码
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        
        if ($this->font_path && function_exists('imagettftext')) {
            // 使用TrueType字体
            $font_size = $size;
            $angle = 0;
            
            // 获取文本边界框
            $bbox = imagettfbbox($font_size, $angle, $this->font_path, $text);
            if ($bbox !== false) {
                $text_width = $bbox[4] - $bbox[0];
                $text_height = $bbox[1] - $bbox[5];
                
                // 绘制文本
                imagettftext($this->image, $font_size, $angle, $x, $y + $text_height, $color, $this->font_path, $text);
            } else {
                // 如果TrueType字体失败，使用默认字体
                imagestring($this->image, $size, $x, $y, $text, $color);
            }
        } else {
            // 使用默认字体
            imagestring($this->image, $size, $x, $y, $text, $color);
        }
    }
    
    /**
     * 绘制竖排文字
     */
    private function drawVerticalText($x, $y, $text, $size = 12, $color = null, $bold = false) {
        if ($color === null) {
            $color = $this->black;
        }
        
        // 确保文字是UTF-8编码
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        
        if ($this->font_path && function_exists('imagettftext')) {
            // 使用TrueType字体绘制竖排文字
            $characters = mb_str_split($text);
            $char_y = $y;
            
            foreach ($characters as $char) {
                $bbox = imagettfbbox($size, 0, $this->font_path, $char);
                if ($bbox !== false) {
                    $char_width = $bbox[2] - $bbox[0];
                    $char_height = $bbox[1] - $bbox[5];
                    
                    // 绘制单个字符
                    imagettftext($this->image, $size, 0, $x, $char_y + $char_height, $color, $this->font_path, $char);
                    $char_y += $char_height + 1; // 减少字符间距，确保在边框内
                }
            }
        } else {
            // 使用默认字体绘制竖排文字
            $characters = mb_str_split($text);
            $char_y = $y;
            
            foreach ($characters as $char) {
                imagestring($this->image, 3, $x, $char_y, $char, $color);
                $char_y += 12; // 减少字符间距
            }
        }
    }
    
    /**
     * 绘制矩形
     */
    private function drawRect($x, $y, $width, $height, $color = null) {
        if ($color === null) {
            $color = $this->black;
        }
        imagerectangle($this->image, $x, $y, $x + $width, $y + $height, $color);
    }
    
    /**
     * 绘制填充矩形
     */
    private function drawFilledRect($x, $y, $width, $height, $color = null) {
        if ($color === null) {
            $color = $this->gray; // 使用灰色背景
        }
        imagefilledrectangle($this->image, $x, $y, $x + $width, $y + $height, $color);
    }
    
    /**
     * 绘制线条
     */
    private function drawLine($x1, $y1, $x2, $y2, $color = null) {
        if ($color === null) {
            $color = $this->black;
        }
        imageline($this->image, $x1, $y1, $x2, $y2, $color);
    }
    
    /**
     * 绘制垂直分隔线
     */
    private function drawVerticalLine($x, $y1, $y2, $color = null) {
        if ($color === null) {
            $color = $this->black;
        }
        imageline($this->image, $x, $y1, $x, $y2, $color);
    }
    
    /**
     * 绘制水平分隔线
     */
    private function drawHorizontalLine($x1, $x2, $y, $color = null) {
        if ($color === null) {
            $color = $this->black;
        }
        imageline($this->image, $x1, $y, $x2, $y, $color);
    }
    
    /**
     * 绘制换行文字
     */
    private function drawWrappedText($x, $y, $text, $max_width, $size = 12, $color = null, $bold = false, $line_height = 15) {
        if ($color === null) {
            $color = $this->black;
        }
        
        // 确保文字是UTF-8编码
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        
        // 分割文字为行
        $lines = $this->wrapText($text, $max_width, $size);
        
        $current_y = $y;
        foreach ($lines as $line) {
            $this->drawText($x, $current_y, $line, $size, $color, $bold);
            $current_y += $line_height;
        }
        
        return $current_y - $y; // 返回总高度
    }
    
    /**
     * 文字换行处理
     */
    private function wrapText($text, $max_width, $font_size) {
        if (!$this->font_path || !function_exists('imagettftext')) {
            // 如果没有字体，使用简单的字符分割
            $chars_per_line = intval($max_width / ($font_size * 0.6));
            return str_split($text, $chars_per_line);
        }
        
        $lines = array();
        $current_line = '';
        $words = mb_str_split($text);
        
        foreach ($words as $word) {
            $test_line = $current_line . $word;
            $bbox = imagettfbbox($font_size, 0, $this->font_path, $test_line);
            
            if ($bbox !== false) {
                $line_width = $bbox[2] - $bbox[0];
                
                if ($line_width <= $max_width) {
                    $current_line = $test_line;
                } else {
                    if (!empty($current_line)) {
                        $lines[] = $current_line;
                        $current_line = $word;
                    } else {
                        // 单个字符就超宽，强制换行
                        $lines[] = $word;
                        $current_line = '';
                    }
                }
            } else {
                // 如果无法获取边界框，使用简单分割
                $current_line .= $word;
                if (mb_strlen($current_line) >= 20) {
                    $lines[] = $current_line;
                    $current_line = '';
                }
            }
        }
        
        if (!empty($current_line)) {
            $lines[] = $current_line;
        }
        
        return $lines;
    }
    
    /**
     * 获取文本宽度
     */
    private function getTextWidth($text, $size) {
        if (!$this->font_path || !function_exists('imagettftext')) {
            return mb_strlen($text) * ($size * 0.6); // 默认宽度
        }
        
        $bbox = imagettfbbox($size, 0, $this->font_path, $text);
        if ($bbox !== false) {
            return $bbox[4] - $bbox[0];
        }
        return mb_strlen($text) * ($size * 0.6); // 默认宽度
    }
    
    /**
     * 生成面单图片 - 根据PDF截图优化文字显示（字体再调小一号）
     */
    public function generateWaybillImage($data) {
        // 清空图片为白色背景
        imagefill($this->image, 0, 0, $this->white);
        
        // 根据HTML模板，采用375*600布局
        $x = 10; // 起始X坐标
        $y = 10; // 起始Y坐标
        $width = $this->width - 20; // 可用宽度
        
        // 1. 头部Logo区域 (高度35px) - 使用白色背景
        $this->drawFilledRect($x, $y, $width, 35, $this->white);
        
        // 主标题居中，使用原始字体大小
        $title_text = '自配送面单';
        $title_width = $this->getTextWidth($title_text, 14);
        $title_x = $x + ($width - $title_width) / 2;
        $this->drawText($title_x, $y + 10, $title_text, 14, $this->black, true);
        
        // 英文副标题居中
        $subtitle_text = 'Self-Delivery Waybill';
        $subtitle_width = $this->getTextWidth($subtitle_text, 8);
        $subtitle_x = $x + ($width - $subtitle_width) / 2;
        $this->drawText($subtitle_x, $y + 25, $subtitle_text, 8, $this->gray);
        $y += 40;
        
        // 2. 订单信息区域 (高度40px)
        $order_bg = imagecolorallocate($this->image, 245, 245, 245);
        $this->drawFilledRect($x, $y, $width, 40, $order_bg);
        $this->drawText($x + 8, $y + 10, '订单号：' . $data['order']['order_bn'], 11, $this->black, true);
        $this->drawText($x + 8, $y + 25, '下单时间：' . $data['order']['order_createtime'], 8, $this->black);
        $y += 45;
        
        // 3. 收件和寄件信息区域 (总高度90px) - 统一边框设计
        $this->drawRect($x, $y, $width, 90, $this->black);
        
        // 左侧标签列 (宽度20px) - 使用白色背景
        $label_width = 20;
        $label_bg = $this->white; // 改为白色背景
        
        // 收件人区域 (高度50px) - 添加完整边框
        $this->drawFilledRect($x, $y, $label_width, 50, $label_bg);
        // 为收件标签区域添加完整边框
        $this->drawVerticalLine($x, $y, $y + 50, $this->black); // 左边框
        $this->drawVerticalLine($x + $label_width, $y, $y + 50, $this->black); // 右边框
        $this->drawHorizontalLine($x, $x + $label_width, $y, $this->black); // 上边框
        $this->drawHorizontalLine($x, $x + $label_width, $y + 50, $this->black); // 下边框
        // 竖排显示"收件" - 调整位置确保在边框内
        $this->drawVerticalText($x + 6, $y + 12, '收件', 8, $this->black, true);
        
        // 右侧内容列
        $content_x = $x + $label_width;
        $content_width = $width - $label_width;
        
        // 姓名和电话，加粗显示
        $name_phone = $data['delivery']['ship_name'] . ' ' . $data['delivery']['ship_mobile'];
        $this->drawText($content_x + 8, $y + 10, $name_phone, 11, $this->black, true);
        
        // 地址信息，分行显示
        $address = $data['delivery']['ship_province'] . $data['delivery']['ship_city'] . $data['delivery']['ship_district'] . $data['delivery']['ship_addr'];
        $this->drawText($content_x + 8, $y + 28, $address, 8, $this->black);
        
        // 添加垂直分隔线
        $this->drawVerticalLine($content_x, $y, $y + 50, $this->black);
        
        // 添加水平分隔线 - 分隔收件和寄件区域
        $this->drawHorizontalLine($x, $x + $width, $y + 50, $this->black);
        
        // 寄件人区域 (高度40px) - 添加完整边框
        $this->drawFilledRect($x, $y + 50, $label_width, 40, $label_bg);
        // 为寄件标签区域添加完整边框
        $this->drawVerticalLine($x, $y + 50, $y + 90, $this->black); // 左边框
        $this->drawVerticalLine($x + $label_width, $y + 50, $y + 90, $this->black); // 右边框
        $this->drawHorizontalLine($x, $x + $label_width, $y + 50, $this->black); // 上边框
        $this->drawHorizontalLine($x, $x + $label_width, $y + 90, $this->black); // 下边框
        // 竖排显示"寄件" - 调整位置确保在边框内
        $this->drawVerticalText($x + 6, $y + 62, '寄件', 8, $this->black, true);
        
        // 右侧内容列
        $this->drawText($content_x + 8, $y + 60, $data['stores']['store_bn'].' '.$data['stores']['mobile'], 11, $this->black, true);
        $this->drawText($content_x + 8, $y + 75, $data['stores']['store_addr'], 8, $this->black);
        
        // 添加垂直分隔线
        $this->drawVerticalLine($content_x, $y + 50, $y + 90, $this->black);
        $y += 95;
        
        // 5. 商品信息 (高度60px)
        $this->drawRect($x, $y, $width, 60, $this->black);
        $this->drawText($x + 8, $y + 10, '商品信息：', 11, $this->black, true);
        
        $item_y = $y + 25;
        foreach ($data['delivery_item'] as $item) {
            $this->drawText($x + 8, $item_y, $item['product_name'] . ' x' . $item['number'] . '件', 8, $this->black);
            $item_y += 16;
        }
        // 订单金额只显示一次，在商品信息下方
        $this->drawText($x + 8, $item_y, '订单金额：¥' . $data['order']['total_amount'], 8, $this->black);
        $y += 65;
        
        // 6. 条码区域 (高度70px)
        $this->drawRect($x, $y, $width, 70, $this->black);
        
        // 条码标题居中
        $barcode_title = '订单条码';
        $barcode_title_width = $this->getTextWidth($barcode_title, 14);
        $barcode_title_x = $x + ($width - $barcode_title_width) / 2;
        $this->drawText($barcode_title_x, $y + 10, $barcode_title, 14, $this->black, true);
        
        // 订单号居中显示
        $order_bn = $data['order']['order_bn'];
        $order_bn_width = $this->getTextWidth($order_bn, 11);
        $order_bn_x = $x + ($width - $order_bn_width) / 2;
        $this->drawText($order_bn_x, $y + 30, $order_bn, 11, $this->black, true);
        
        // 扫描提示居中显示
        $scan_text = '请扫描条码确认收货';
        $scan_text_width = $this->getTextWidth($scan_text, 8);
        $scan_text_x = $x + ($width - $scan_text_width) / 2;
        $this->drawText($scan_text_x, $y + 50, $scan_text, 8, $this->gray);
        $y += 75;
        
        // 7. 签收区域 (高度80px)
        $this->drawRect($x, $y, $width, 80, $this->black);
        
        // 使用换行文字显示长文本
        $signature_text = '快件送达收件人地址，经收件人或收件人允许的代收人签字，视为送达。';
        $text_width = $width - 16; // 留出左右边距
        $text_height = $this->drawWrappedText($x + 8, $y + 10, $signature_text, $text_width, 8, $this->black, false, 12);
        
        // 签收人和时间行 - 使用下划线样式
        $sign_y = $y + 10 + $text_height + 12; // 在文字下方留出间距
        
        // 签收人部分
        $this->drawText($x + 8, $sign_y, '签收人：', 8, $this->black);
        // 绘制下划线 (60px宽度)
        $this->drawHorizontalLine($x + 55, $x + 115, $sign_y + 12, $this->black);
        
        // 时间部分，右对齐
        $time_text = '时间：';
        $time_text_width = $this->getTextWidth($time_text, 8);
        $time_x = $x + $width - 120 - $time_text_width; // 右对齐
        $this->drawText($time_x, $sign_y, $time_text, 8, $this->black);
        // 绘制下划线 (80px宽度)
        $this->drawHorizontalLine($time_x + $time_text_width + 5, $time_x + $time_text_width + 85, $sign_y + 12, $this->black);
        $y += 85;
        
        // 8. 备注信息 (高度50px) - 增加高度确保内容在框内
        $this->drawRect($x, $y, $width, 50, $this->black);
        $this->drawText($x + 8, $y + 10, '备注：', 8, $this->black, true);
        
        // 使用换行文字显示备注内容
        $remark_text = '请保持电话畅通，我们会提前联系您';
        $remark_text2 = '如有疑问请联系客服：400-888-8888';
        $text_width = $width - 16; // 留出左右边距
        
        // 第一行备注
        $this->drawWrappedText($x + 8, $y + 22, $remark_text, $text_width, 6, $this->black, false, 10);
        
        // 第二行备注 - 调整位置确保在框内
        $this->drawWrappedText($x + 8, $y + 35, $remark_text2, $text_width, 6, $this->black, false, 10);
        $y += 55;
        
        return $this->image;
    }
    
    /**
     * 保存图片到文件
     */
    public function saveToFile($filename, $data = array()) {
        $image = $this->generateWaybillImage($data);
        
        // 确保目录存在
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // 修改文件扩展名为jpg
        $filename = preg_replace('/\.(png|gif|bmp)$/i', '.jpg', $filename);
        
        // 保存为JPEG格式，100%质量
        imagejpeg($image, $filename, 100);
        
        return $filename;
    }
    
    /**
     * 输出图片到浏览器
     */
    public function outputToBrowser($data = array()) {
        $image = $this->generateWaybillImage($data);
        
        // 设置HTTP头为JPEG
        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename="waybill.jpg"');
        
        // 输出JPEG图片，100%质量
        imagejpeg($image, null, 100);
    }
    
    /**
     * 获取图片数据
     */
    public function getImageData($data = array()) {
        $image = $this->generateWaybillImage($data);
        
        // 获取JPEG图片数据，100%质量
        ob_start();
        imagejpeg($image, null, 100);
        $image_data = ob_get_contents();
        ob_end_clean();
        
        return $image_data;
    }
    
    /**
     * 生成Base64编码的图片
     */
    public function getBase64Image($data = array()) {
        $image_data = $this->getImageData($data);
        return 'data:image/jpeg;base64,' . base64_encode($image_data);
    }
    
    /**
     * 上传图片到OSS
     * @param array $data 面单数据
     * @param string $object_name OSS对象名称（可选）
     * @return array 上传结果
     */
    public function uploadToOSS($data, $object_name = '') {
        // 引入OSS上传器
        require_once APP_DIR . '/wap/lib/cloudprint/oss_uploader.php';
        
        try {
            // 生成图片
            $this->generateWaybillImage($data);
            
            // 获取图片数据
            ob_start();
            imagejpeg($this->image, null, 100);
            $image_data = ob_get_contents();
            ob_end_clean();
            
            // 转换为Base64
            $base64_data = base64_encode($image_data);
            
            // 创建OSS上传器
            $uploader = new wap_cloudprint_oss_uploader();
            
            // 生成OSS对象名称，使用jpg扩展名
            if (empty($object_name)) {
                $object_name = 'waybill/' . date('Y/m/d/') . 'waybill_' . date('YmdHis') . '.jpg';
            } else {
                // 确保扩展名为jpg
                $object_name = preg_replace('/\.(png|gif|bmp)$/i', '.jpg', $object_name);
            }
            
            // 上传到OSS
            $result = $uploader->uploadImage($base64_data, $object_name, true);
            
            return $result;
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => '上传失败：' . $e->getMessage()
            );
        }
    }
    
    /**
     * 测试OSS连接
     * @return array 连接测试结果
     */
    public function testOSSConnection() {
        require_once APP_DIR . '/wap/lib/cloudprint/oss_uploader.php';
        
        $uploader = new wap_cloudprint_oss_uploader();
        return $uploader->testConnection();
    }
    
    /**
     * 清理资源
     */
    public function __destruct() {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }
    
    /**
     * 获取示例数据
     */
    public function getSampleData() {
        return array(
            'order' => array(
                'order_bn' => 'SD-2024-001',
                'total_amount' => '299.00',
                'order_createtime' => '2024-01-15 14:30:25'
            ),
            'delivery' => array(
                'ship_name' => '张三',
                'ship_mobile' => '138****8888',
                'ship_province' => '北京市',
                'ship_city' => '朝阳区',
                'ship_district' => '建国门外大街',
                'ship_addr' => '1号国贸大厦A座1001室',
                'delivery_bn' => 'SD-2024-001'
            ),
            'delivery_item' => array(
                array(
                    'product_name' => '时尚羽绒服',
                    'number' => '1',
                    'price' => '299.00'
                )
            ),
            'shop_info' => array(
                'shop_name' => '品牌服装店',
                'shop_mobile' => '177****2358',
                'shop_address' => '北京市海淀区中关村大街1号'
            )
        );
    }

     /**
      * 上传文件
      * @param $filename
      * @return void
      */
     function uploadFile($file_id, $filename)
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
            unlink($filename);
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

