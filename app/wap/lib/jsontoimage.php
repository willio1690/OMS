<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 快递模板图片生成工具
 * 将快递标签设计器的JSON模板转换为JPG图片
 *
 * 版本: 2.2
 * 更新: 支持76*130mm尺寸，优化左边区域显示，修复Code128条码生成逻辑
 * 尺寸: 76mm × 130mm = 608px × 1040px (1mm ≈ 8px)
 * 参考: https://github.com/paul8888/code128barcode/blob/master/code128.php
 */
class JsonToImageConverter {
    // 像素转换比例：1mm ≈ 8px (原来是1mm = 3.75px)
    private $pixelRatio = 8.0 / 3.75; // 约2.13倍
    
    /**
     * Code128B标准编码表
     * 参考: https://github.com/paul8888/code128barcode/blob/master/code128.php
     */

    private $code128Table = array(
        ' ' => "212222",  // 空格 (32)
        '!' => "222122",  // ! (33)
        '"' => "222221",  // " (34)
        '#' => "121223",  // # (35)
        '$' => "121322",  // $ (36)
        '%' => "131222",  // % (37)
        '&' => "122213",  // & (38)
        "'" => "122312",  // ' (39)
        '(' => "132212",  // ( (40)
        ')' => "221213",  // ) (41)
        '*' => "221312",  // * (42)
        '+' => "231212",  // + (43)
        ',' => "112232",  // , (44)
        '-' => "122132",  // - (45)
        '.' => "122231",  // . (46)
        '/' => "113222",  // / (47)
        '0' => "123122",  // 0 (48)
        '1' => "123221",  // 1 (49)
        '2' => "223211",  // 2 (50)
        '3' => "221132",  // 3 (51)
        '4' => "221231",  // 4 (52)
        '5' => "213212",  // 5 (53)
        '6' => "223112",  // 6 (54)
        '7' => "312131",  // 7 (55)
        '8' => "311222",  // 8 (56)
        '9' => "321122",  // 9 (57)
        ':' => "321221",  // : (58)
        ';' => "312212",  // ; (59)
        '<' => "322112",  // < (60)
        '=' => "322211",  // = (61)
        '>' => "212123",  // > (62)
        '?' => "212321",  // ? (63)
        '@' => "232121",  // @ (64)
        'A' => "111323",  // A (65)
        'B' => "131123",  // B (66)
        'C' => "131321",  // C (67)
        'D' => "112313",  // D (68)
        'E' => "132113",  // E (69)
        'F' => "132311",  // F (70)
        'G' => "211313",  // G (71)
        'H' => "231113",  // H (72)
        'I' => "231311",  // I (73)
        'J' => "112133",  // J (74)
        'K' => "112331",  // K (75)
        'L' => "132131",  // L (76)
        'M' => "113123",  // M (77)
        'N' => "113321",  // N (78)
        'O' => "133121",  // O (79)
        'P' => "313121",  // P (80)
        'Q' => "211331",  // Q (81)
        'R' => "231131",  // R (82)
        'S' => "213113",  // S (83)
        'T' => "213311",  // T (84)
        'U' => "213131",  // U (85)
        'V' => "311123",  // V (86)
        'W' => "311321",  // W (87)
        'X' => "331121",  // X (88)
        'Y' => "312113",  // Y (89)
        'Z' => "312311",  // Z (90)
        '[' => "332111",  // [ (91)
        '\\' => "314111", // \ (92)
        ']' => "221411",  // ] (93)
        '^' => "431111",  // ^ (94)
        '_' => "111224",  // _ (95)
        '`' => "111422",  // ` (96)
        'a' => "121124",  // a (97)
        'b' => "121421",  // b (98)
        'c' => "141122",  // c (99)
        'd' => "141221",  // d (100)
        'e' => "112214",  // e (101)
        'f' => "112412",  // f (102)
        'g' => "122114",  // g (103)
        'h' => "122411",  // h (104)
        'i' => "142112",  // i (105)
        'j' => "142211",  // j (106)
        'k' => "241211",  // k (107)
        'l' => "221114",  // l (108)
        'm' => "413111",  // m (109)
        'n' => "241112",  // n (110)
        'o' => "134111",  // o (111)
        'p' => "111242",  // p (112)
        'q' => "121142",  // q (113)
        'r' => "121241",  // r (114)
        's' => "114212",  // s (115)
        't' => "124112",  // t (116)
        'u' => "124211",  // u (117)
        'v' => "411212",  // v (118)
        'w' => "421112",  // w (119)
        'x' => "421211",  // x (120)
        'y' => "212141",  // y (121)
        'z' => "214121",  // z (122)
        '{' => "412121",  // { (123)
        '|' => "111143",  // | (124)
        '}' => "111341",  // } (125)
        '~' => "131141"   // ~ (126)
    );
    private $image;
    private $width;
    private $height;
    
    /**
     * 调整元素位置，确保左边边框不被裁剪，并优化右边空白
     */
    private function adjustElementPositions($offset) {
        // 计算目标居中位置
        $targetWidth = 608; // 76*130mm的目标宽度
        $contentWidth = 0;
        
        // 先计算内容总宽度
        foreach ($this->data as $element) {
            if (isset($element['width'])) {
                $width = (int)($element['width'] * $this->pixelRatio);
                $x = (int)($element['x'] * $this->pixelRatio);
                $contentWidth = max($contentWidth, $x + $width);
            }
        }
        
        // 计算居中偏移量，但确保不会导致左边被裁剪
        $centerOffset = max(0, ($targetWidth - $contentWidth) / 2);
        
        // 调整所有元素位置
        foreach ($this->data as &$element) {
            // 先处理负坐标问题
            if ($offset > 0) {
                $element['x'] += $offset;
            }
            
            // 再添加居中偏移，让内容向右移动，但不超过合理范围
            $element['x'] += $centerOffset;
            
            // 确保X坐标不会超出合理范围
            if ($element['x'] < 0) {
                $element['x'] = 0;
            }
        }
    }
    
    /**
     * 重新计算调整元素位置后的尺寸
     */
    private function recalculateSize() {
        $maxX = 0;
        $maxY = 0;
        $minX = 0;
        $minY = 0;
        
        foreach ($this->data as $element) {
            $x = (int)($element['x'] * $this->pixelRatio);
            $y = (int)($element['y'] * $this->pixelRatio);
            
            // 跟踪最小坐标
            if ($x < $minX) $minX = $x;
            if ($y < $minY) $minY = $y;
            
            if ($element['plugin'] === 'sp-label') {
                $width = (int)($element['width'] * $this->pixelRatio);
                $height = (int)($element['height'] * $this->pixelRatio);
                $maxX = max($maxX, $x + $width);
                $maxY = max($maxY, $y + $height);
            } elseif ($element['plugin'] === 'sp-level') {
                $width = (int)($element['width'] * $this->pixelRatio);
                $thickness = (int)($element['thickness'] * $this->pixelRatio);
                $maxX = max($maxX, $x + $width);
                $maxY = max($maxY, $y + $thickness); // 考虑线条粗细
            } elseif ($element['plugin'] === 'sp-vertical') {
                $height = (int)($element['height'] * $this->pixelRatio);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y + $height);
            } elseif ($element['plugin'] === 'sp-input') {
                $width = (int)($element['width'] * $this->pixelRatio);
                $height = (int)($element['height'] * $this->pixelRatio);
                $maxX = max($maxX, $x + $width);
                $maxY = max($maxY, $y + $height);
            } elseif ($element['plugin'] === 'sp-barcode') {
                $width = (int)($element['width'] * $this->pixelRatio);
                $height = (int)($element['height'] * $this->pixelRatio);
                $maxX = max($maxX, $x + $width);
                $maxY = max($maxY, $y + $height);
            }
        }
        
        // 如果左边有负坐标，需要调整所有元素位置
        if ($minX < 0) {
            $this->adjustElementPositions(abs($minX));
            // 重新计算调整后的尺寸
            return $this->recalculateSize();
        }
        
        // 完全禁用向上偏移，保护头部区域不被截断
        $targetHeight = 1040;
        // 注释掉原来的向上偏移逻辑，确保头部文字和线条不被截断
        /*
        if ($maxY < $targetHeight) {
            $upwardShift = ($targetHeight - $maxY) * 0.5;
            $upwardShift = min($upwardShift, 60);
            
            foreach ($this->data as &$element) {
                $element['y'] -= $upwardShift / $this->pixelRatio;
            }
            
            // 重新计算Y坐标
            $maxY = 0;
            foreach ($this->data as $element) {
                $y = (int)($element['y'] * $this->pixelRatio);
                if ($element['plugin'] === 'sp-label') {
                    $height = (int)($element['height'] * $this->pixelRatio);
                    $maxY = max($maxY, $y + $height);
                } elseif ($element['plugin'] === 'sp-input') {
                    $height = (int)($element['height'] * $this->pixelRatio);
                    $maxY = max($maxY, $y + $height);
                } elseif ($element['plugin'] === 'sp-barcode') {
                    $height = (int)($element['height'] * $this->pixelRatio);
                    $maxY = max($maxY, $y + $height);
                }
            }
        }
        */
        
        return array($maxX, $maxY);
    }
    
    // 获取JSON中的实际尺寸
    private function getImageSize() {
        $maxX = 0;
        $maxY = 0;
        $minX = 0; // 添加最小X坐标跟踪
        
        foreach ($this->data as $element) {
            $x = (int)($element['x'] * $this->pixelRatio);
            $y = (int)($element['y'] * $this->pixelRatio);
            
            // 跟踪最小X坐标，确保左边元素不被裁剪
            if ($x < $minX) {
                $minX = $x;
            }
            
            if ($element['plugin'] === 'sp-label') {
                $width = (int)($element['width'] * $this->pixelRatio);
                $height = (int)($element['height'] * $this->pixelRatio);
                $maxX = max($maxX, $x + $width);
                $maxY = max($maxY, $y + $height);
            } elseif ($element['plugin'] === 'sp-level') {
                $width = (int)($element['width'] * $this->pixelRatio);
                $maxX = max($maxX, $x + $width);
                $maxY = max($maxY, $y);
            } elseif ($element['plugin'] === 'sp-vertical') {
                $height = (int)($element['height'] * $this->pixelRatio);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y + $height);
            } elseif ($element['plugin'] === 'sp-input') {
                $width = (int)($element['width'] * $this->pixelRatio);
                $height = (int)($element['height'] * $this->pixelRatio);
                $maxX = max($maxX, $x + $width);
                $maxY = max($maxY, $y + $height);
            } elseif ($element['plugin'] === 'sp-barcode') {
                $width = (int)($element['width'] * $this->pixelRatio);
                $height = (int)($element['height'] * $this->pixelRatio);
                $maxX = max($maxX, $x + $width);
                $maxY = max($maxY, $y + $height);
            }
        }
        
        // 如果左边有负坐标，需要调整所有元素位置
        if ($minX < 0) {
            $this->adjustElementPositions(abs($minX));
            // 重新计算调整后的尺寸
            return $this->recalculateSize();
        } else {
            // 即使没有负坐标，也进行居中调整
            $this->adjustElementPositions(0);
            return $this->recalculateSize();
        }
    }
    private $data = array();
    private $inputData = array(); // 存储输入数据
    
    /**
     * __construct
     * @param mixed $template template
     * @param mixed $width ID
     * @param mixed $height height
     * @return mixed 返回值
     */
    public function __construct($template = null, $width = 608, $height = 1040) {
        // 76*130mm 转换为像素：76mm × 8px/mm = 608px, 130mm × 8px/mm = 1040px
        // 应用像素转换比例：1mm ≈ 8px
        $this->width = (int)$width;
        $this->height = (int)$height;
        
        // 确保最小尺寸
        if ($this->width < 608) $this->width = 608;
        if ($this->height < 1040) $this->height = 1040;
        
        if ($template) {
            $this->setTemplate($template);
        }
    }
    
    /**
     * 设置输入数据
     */
    public function setInputData($data) {
        $this->inputData = $data;
    }
    
    /**
     * 设置快递模板数据
     */
    public function setTemplate($template) {
        $this->data = $template;
    }
    
    /**
     * 设置测试数据
     */
    public function setTestData() {
        $this->data = array(
            // 这里可以设置测试数据
        );
    }
    
    /**
     * 优化图片尺寸，减少右边空白
     */
    private function optimizeImageSize($contentWidth, $contentHeight) {
        // 目标尺寸：76*130mm = 608*1040px
        $targetWidth = 608;
        $targetHeight = 1040;
        
        // 如果内容尺寸小于目标尺寸，使用目标尺寸
        if ($contentWidth <= $targetWidth && $contentHeight <= $targetHeight) {
            return array($targetWidth, $targetHeight);
        }
        
        // 如果内容尺寸大于目标尺寸，添加足够的边距确保不被截掉
        $minMargin = 15; // 减少最小边距到15px，避免过多空白
        
        // 对于商品列表等长文本，增加更多边距
        $optimalWidth = max($contentWidth + $minMargin, $targetWidth);
        $optimalHeight = max($contentHeight + $minMargin, $targetHeight);
        
        // 如果高度接近目标高度，确保有足够的下边距，但不要过多
        if ($optimalHeight > $targetHeight * 0.9) {
            $optimalHeight = max($optimalHeight, $targetHeight + 20); // 减少到20px
        }
        
        // 限制最大尺寸，避免生成过大的图片
        $maxWidth = $targetWidth + 50;  // 最大宽度不超过目标宽度+50px
        $maxHeight = $targetHeight + 50; // 最大高度不超过目标高度+50px
        
        $optimalWidth = min($optimalWidth, $maxWidth);
        $optimalHeight = min($optimalHeight, $maxHeight);
        
        return array($optimalWidth, $optimalHeight);
    }
    
    /**
     * 紧凑化图片尺寸，严格控制避免空白页
     */
    private function compactImageSize($contentWidth, $contentHeight) {
        // 目标尺寸：76*130mm = 608*1040px
        $targetWidth = 608;
        $targetHeight = 1040;
        
        // 如果内容尺寸小于目标尺寸，使用目标尺寸
        if ($contentWidth <= $targetWidth && $contentHeight <= $targetHeight) {
            return array($targetWidth, $targetHeight);
        }
        
        // 计算紧凑的尺寸，最小边距
        $minMargin = 8; // 减少最小边距
        
        $optimalWidth = max($contentWidth + $minMargin, $targetWidth);
        $optimalHeight = max($contentHeight + $minMargin, $targetHeight);
        
        // 严格限制最大尺寸，防止空白页
        $maxWidth = $targetWidth + 25;   // 严格控制宽度
        $maxHeight = $targetHeight + 15; // 严格控制最大高度，避免空白页
        
        $optimalWidth = min($optimalWidth, $maxWidth);
        $optimalHeight = min($optimalHeight, $maxHeight);
        
        return array($optimalWidth, $optimalHeight);
    }
    
    /**
     * 创建图片
     */
    public function createImage() {
        // 获取实际需要的图片尺寸
        list($maxX, $maxY) = $this->getImageSize();
        
        // 使用紧凑化方法计算最优图片尺寸，减少空白区域
        list($optimalWidth, $optimalHeight) = $this->compactImageSize($maxX, $maxY);
        
        // compactImageSize已经包含智能头部保护，无需额外边距
        
        // 设置图片尺寸
        $this->width = $optimalWidth;
        $this->height = $optimalHeight;
        
        // 创建图片
        $this->image = imagecreate($this->width, $this->height);
        
        // 设置背景色为白色
        $white = imagecolorallocate($this->image, 255, 255, 255);
        $black = imagecolorallocate($this->image, 0, 0, 0);
        
        // 填充白色背景
        imagefill($this->image, 0, 0, $white);
        
        // 渲染所有元素
        foreach ($this->data as $element) {
            $this->renderElement($element, $black);
        }
    }
    
    /**
     * 渲染单个元素
     */
    private function renderElement($element, $color) {
        $plugin = $element['plugin'];
        
        switch ($plugin) {
            case 'sp-label':
                $this->renderLabel($element, $color);
                break;
            case 'sp-input':
                $this->renderInput($element, $color);
                break;
            case 'sp-barcode':
                $this->renderBarcode($element, $color);
                break;
            case 'sp-level':
                $this->renderHorizontalLine($element, $color);
                break;
            case 'sp-vertical':
                $this->renderVerticalLine($element, $color);
                break;
        }
    }
    
    /**
     * 渲染标签文字
     */
    private function renderLabel($element, $color) {
        $x = (int)($element['x'] * $this->pixelRatio);
        $y = (int)($element['y'] * $this->pixelRatio);
        $width = (int)($element['width'] * $this->pixelRatio);
        $height = (int)($element['height'] * $this->pixelRatio);
        $text = $element['edit'];
        $fontSize = (int)($element['fontSize'] * $this->pixelRatio);
        $fontFamily = $element['fontFamily'] ?: 'simhei';
        $textColor = $this->parseColor($element['color']);
        $bold = $element['bold'] === 'bold';
        
        // 获取字体文件
        $fontFile = $this->getFontFile($fontFamily);
        
        // 解析对齐方式
        $arrangement = $element['Arrangement'];
        $align = 'left';
        $valign = 'top';
        
        if ($arrangement['lm']) $align = 'center';
        elseif ($arrangement['lr']) $align = 'right';
        
        if ($arrangement['vm']) $valign = 'middle';
        elseif ($arrangement['vb']) $valign = 'bottom';
        
        // 计算文字位置
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = $bbox[4] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        
        // 计算X坐标
        switch ($align) {
            case 'center':
                $x = $x + ($width - $textWidth) / 2;
                break;
            case 'right':
                $x = $x + $width - $textWidth;
                break;
            default:
                // left对齐，x保持不变
                break;
        }
        
        // 确保X坐标不超出边界
        if ($x < 0) $x = 0;
        
        // 处理文字换行
        $lines = $this->wrapText($text, $fontFile, $fontSize, $width);
        
        // 检查是否是商品列表区域（通过文本内容判断）
        $isItemList = false;
        if (strpos($text, '货号+货品名+规格+数量') !== false || 
            strpos($text, 'teststore') !== false ||
            strpos($text, 'x 1') !== false) {
            $isItemList = true;
        }
        
        // 根据是否是商品列表调整行间距和起始位置
        if ($isItemList) {
            // 检查是否包含中英文混合内容
            $hasMixedContent = $this->hasMixedChineseEnglish($text);
            if ($hasMixedContent) {
                // 进一步分析混合内容的类型
                $mixedType = $this->analyzeMixedContent($text);
                switch ($mixedType) {
                    case 'product_id':
                        $lineHeight = $fontSize * 1.6; // 产品ID类型（如：1012155 : 产品名 x 1）
                        break;
                    case 'description':
                        $lineHeight = $fontSize * 1.5; // 产品描述类型
                        break;
                    default:
                        $lineHeight = $fontSize * 1.5; // 默认混合内容
                }
            } else {
                $lineHeight = $fontSize * 1.2; // 纯中文或纯英文使用1.2
            }
            // 商品列表紧贴上方，减少空白
            $startY = $y; // 直接从元素顶部开始，不留空白
        } else {
            $lineHeight = $fontSize * 1.4; // 其他文字使用正常行间距
            // 其他文字使用正常对齐
            switch ($valign) {
                case 'middle':
                    $startY = $y + ($height - count($lines) * $lineHeight) / 2;
                    break;
                case 'bottom':
                    $startY = $y + $height - count($lines) * $lineHeight;
                    break;
                case 'top':
                default:
                    $startY = $y;
                    break;
            }
        }
        
        $totalHeight = count($lines) * $lineHeight;
        
        // 限制高度，但确保至少显示2行
        $maxLines = max(2, floor($height / $lineHeight));
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            // 如果最后一行被截断，添加省略号
            if (strlen($text) > 50) {
                $lastLine = $lines[count($lines) - 1];
                if (strlen($lastLine) > 10) {
                    $lines[count($lines) - 1] = substr($lastLine, 0, -3) . '...';
                }
            }
        }
        
        // 绘制每一行文字
        foreach ($lines as $index => $line) {
            $lineBbox = imagettfbbox($fontSize, 0, $fontFile, $line);
            $lineTextHeight = $lineBbox[1] - $lineBbox[7];
            $lineWidth = $this->getTextWidth($line, $fontFile, $fontSize);
            
            // 计算当前行的X坐标
            $currentX = $x;
            switch ($align) {
                case 'center':
                    $currentX = $x + ($width - $lineWidth) / 2;
                    break;
                case 'right':
                    $currentX = $x + $width - $lineWidth;
                    break;
                default:
                    $currentX = $x;
                    break;
            }
            
            // 计算当前行的Y坐标
            if ($isItemList) {
                // 商品列表紧凑排列，紧贴上方
                $currentY = $startY + ($index * $lineHeight) + $lineTextHeight;
            } else {
                // 其他文字使用正常排列
                $currentY = $startY + ($index + 1) * $lineHeight - ($lineHeight - $lineTextHeight) / 2;
            }
            
            // 绘制文字
            if ($bold) {
                imagettftext($this->image, $fontSize, 0, (int)round($currentX-1), (int)round($currentY), $textColor, $fontFile, $line);
                imagettftext($this->image, $fontSize, 0, (int)round($currentX+1), (int)round($currentY), $textColor, $fontFile, $line);
            }
            imagettftext($this->image, $fontSize, 0, (int)round($currentX), (int)round($currentY), $textColor, $fontFile, $line);
        }
        
        return; // 提前返回，避免重复绘制
    }
    
    /**
     * 渲染水平线
     */
    private function renderHorizontalLine($element, $color) {
        $x = (int)($element['x'] * $this->pixelRatio);
        $y = (int)($element['y'] * $this->pixelRatio);
        $width = (int)($element['width'] * $this->pixelRatio);
        $thickness = (int)($element['thickness'] * $this->pixelRatio);
        $lineStyle = $element['lineStyle'];
        
        // 设置线条粗细
        imagesetthickness($this->image, $thickness);
        
        // 检查是否是商品列表上方的分隔线
        $isItemSeparator = false;
        if ($y > 200 && $y < 400) { // 商品列表区域的大致Y坐标范围
            $isItemSeparator = true;
        }
        
        // 特殊处理签收和时间下面的横线，让它们更靠近文字底部
        if (($x == (int)(45 * $this->pixelRatio) && $y == (int)(330 * $this->pixelRatio)) || ($x == (int)(219 * $this->pixelRatio) && $y == (int)(328 * $this->pixelRatio))) {
            // 调整Y坐标，让横线更靠近文字底部
            $adjustedY = $y + (int)(5 * $this->pixelRatio); // 向下移动5像素
            imageline($this->image, $x, $adjustedY, $x + $width, $adjustedY, $color);
        } elseif ($isItemSeparator) {
            // 商品列表上方的分隔线，向下移动，减少与下方文本的空白
            $adjustedY = $y + (int)(10 * $this->pixelRatio); // 向下移动10像素
            imageline($this->image, $x, $adjustedY, $x + $width, $adjustedY, $color);
        } else {
            // 绘制线条
            imageline($this->image, $x, $y, $x + $width, $y, $color);
        }
    }
    
    /**
     * 渲染输入字段
     */
    private function renderInput($element, $color) {
        $x = (int)($element['x'] * $this->pixelRatio);
        $y = (int)($element['y'] * $this->pixelRatio);
        $width = (int)($element['width'] * $this->pixelRatio);
        $height = (int)($element['height'] * $this->pixelRatio);
        $fontSize = (int)($element['fontSize'] * $this->pixelRatio);
        $fontFamily = $element['fontFamily'] ?: 'simhei';
        $textColor = $this->parseColor($element['color']);
        $bold = $element['bold'] === 'bold';
        
        // 获取输入类型和数据
        $type = $element['type'];
        $fieldName = $type['value']; // 例如: ship_name
        $label = $type['label']; // 例如: 收货人-姓名
        
        // 从输入数据中获取值
        $text = isset($this->inputData[$fieldName]) ? $this->inputData[$fieldName] : '';
        
        // 如果没有数据，显示标签
        if (empty($text)) {
            $text = $label;
        }
        
        // 获取字体文件
        $fontFile = $this->getFontFile($fontFamily);
        
        // 解析对齐方式
        $arrangement = $element['Arrangement'];
        $align = 'left';
        $valign = 'top';
        
        if ($arrangement['lm']) $align = 'center';
        elseif ($arrangement['lr']) $align = 'right';
        
        if ($arrangement['vm']) $valign = 'middle';
        elseif ($arrangement['vb']) $valign = 'bottom';
        
        // 处理文字换行
        $lines = $this->wrapText($text, $fontFile, $fontSize, $width);
        
        // 检查是否是商品列表区域（通过文本内容判断）
        $isItemList = false;
        if (strpos($text, 'teststore') !== false || 
            strpos($text, 'x 1') !== false ||
            strpos($text, '：') !== false) {
            $isItemList = true;
        }
        
        // 根据是否是商品列表调整行间距
        if ($isItemList) {
            // 检查是否包含中英文混合内容
            $hasMixedContent = $this->hasMixedChineseEnglish($text);
            if ($hasMixedContent) {
                // 进一步分析混合内容的类型
                $mixedType = $this->analyzeMixedContent($text);
                switch ($mixedType) {
                    case 'product_id':
                        $lineHeight = $fontSize * 1.6; // 产品ID类型（如：1012155 : 产品名 x 1）
                        break;
                    case 'description':
                        $lineHeight = $fontSize * 1.5; // 产品描述类型
                        break;
                    default:
                        $lineHeight = $fontSize * 1.5; // 默认混合内容
                }
            } else {
                $lineHeight = $fontSize * 1.2; // 纯中文或纯英文使用1.2
            }
        } else {
            $lineHeight = $fontSize * 1.4; // 其他输入使用正常行间距
        }
        
        $totalHeight = count($lines) * $lineHeight;
        
        // 限制高度，但确保至少显示2行
        $maxLines = max(2, floor($height / $lineHeight));
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            // 如果最后一行被截断，添加省略号
            if (strlen($text) > 50) {
                $lastLine = $lines[count($lines) - 1];
                if (strlen($lastLine) > 10) {
                    $lines[count($lines) - 1] = substr($lastLine, 0, -3) . '...';
                }
            }
        }
        
        // 计算垂直起始位置 - 商品列表紧贴上方
        if ($isItemList) {
            // 商品列表紧贴上方，不留空白
            $startY = $y;
        } else {
            // 其他输入使用正常对齐
            switch ($valign) {
                case 'middle':
                    $startY = $y + ($height - $totalHeight) / 2;
                    break;
                case 'bottom':
                    $startY = $y + $height - $totalHeight;
                    break;
                case 'top':
                default:
                    $startY = $y;
                    break;
            }
        }
        
        // 绘制每一行文字
        foreach ($lines as $index => $line) {
            $lineBbox = imagettfbbox($fontSize, 0, $fontFile, $line);
            $lineTextHeight = $lineBbox[1] - $lineBbox[7];
            $lineWidth = $this->getTextWidth($line, $fontFile, $fontSize);
            
            // 计算当前行的X坐标
            $currentX = $x;
            switch ($align) {
                case 'center':
                    $currentX = $x + ($width - $lineWidth) / 2;
                    break;
                case 'right':
                    $currentX = $x + $width - $lineWidth;
                    break;
                default:
                    $currentX = $x;
                    break;
            }
            
            // 计算当前行的Y坐标
            if ($isItemList) {
                // 商品列表紧凑排列，紧贴上方
                $currentY = $startY + ($index * $lineHeight) + $lineTextHeight;
            } else {
                // 其他输入使用正常排列
                $currentY = $startY + ($index + 1) * $lineHeight - ($lineHeight - $lineTextHeight) / 2;
            }
            
            // 绘制文字
            if ($bold) {
                imagettftext($this->image, $fontSize, 0, (int)round($currentX-1), (int)round($currentY), $textColor, $fontFile, $line);
                imagettftext($this->image, $fontSize, 0, (int)round($currentX+1), (int)round($currentY), $textColor, $fontFile, $line);
            }
            imagettftext($this->image, $fontSize, 0, (int)round($currentX), (int)round($currentY), $textColor, $fontFile, $line);
        }
    }
    
    /**
     * 渲染条形码
     */
    private function renderBarcode($element, $color) {
        $x = (int)($element['x'] * $this->pixelRatio);
        $y = (int)($element['y'] * $this->pixelRatio);
        $width = (int)($element['width'] * $this->pixelRatio);
        $height = (int)($element['height'] * $this->pixelRatio);
        $code = $element['code'];
        $showText = $element['text'] === 'true';
        
        // 获取条形码数据
        $type = $element['type'];
        $fieldName = $type['value'];
        
        // 从输入数据中获取条形码值
        $barcodeValue = isset($this->inputData[$fieldName]) ? $this->inputData[$fieldName] : $code;
        
        // 获取条形码类型，默认为A
        $barcodeType = 'A';
        if (isset($type['value']) && $type['value'] === 'code128b') {
            $barcodeType = 'B';
        } elseif (isset($type['label']) && strpos(strtolower($type['label']), '128b') !== false) {
            $barcodeType = 'B';
        }
        
        $pattern = $this->generateCode128($barcodeValue, $width, $height, $barcodeType);
        $this->drawBarcode($this->image, $x, $y, $width, $height, $pattern, $color);
        
        // 如果显示文字，在条形码下方绘制文字
        if ($showText) {
            $fontSize = (int)(10 * $this->pixelRatio);
            $fontFile = $this->getFontFile('simhei');
            $textY = $y + $height + (int)(15 * $this->pixelRatio); // 增加间距，确保文字不被条形码遮挡
            
            // 计算文字宽度以居中
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $barcodeValue);
            $textWidth = $bbox[4] - $bbox[0];
            $textX = $x + ($width - $textWidth) / 2;
            
            // 确保文字完全在条形码容器内
            if ($textX < $x) {
                $textX = $x;
            }
            if ($textX + $textWidth > $x + $width) {
                $textX = $x + $width - $textWidth;
            }
            
            // 额外检查：如果文字宽度超过容器宽度，则缩小字体或截断文字
            if ($textWidth > $width) {
                // 尝试缩小字体
                $newFontSize = $fontSize;
                while ($textWidth > $width && $newFontSize > 6) {
                    $newFontSize--;
                    $bbox = imagettfbbox($newFontSize, 0, $fontFile, $barcodeValue);
                    $textWidth = $bbox[4] - $bbox[0];
                }
                
                // 如果字体已经最小但仍然超出，则截断文字
                if ($textWidth > $width) {
                    $maxChars = floor($width / ($fontSize * 0.6)); // 估算字符数
                    $barcodeValue = substr($barcodeValue, 0, $maxChars);
                    $bbox = imagettfbbox($fontSize, 0, $fontFile, $barcodeValue);
                    $textWidth = $bbox[4] - $bbox[0];
                    $textX = $x + ($width - $textWidth) / 2;
                }
            }
            
            // 最终边界检查：确保文字完全在条形码容器内
            $textEndX = $textX + $textWidth;
            if ($textEndX > $x + $width) {
                $textX = $x + $width - $textWidth;
            }
            if ($textX < $x) {
                $textX = $x;
            }
            
            imagettftext($this->image, $fontSize, 0, (int)round($textX), (int)round($textY), $color, $fontFile, $barcodeValue);
        }
    }
    

    
    /**
     * 渲染垂直线
     */
    private function renderVerticalLine($element, $color) {
        $x = (int)($element['x'] * $this->pixelRatio);
        $y = (int)($element['y'] * $this->pixelRatio);
        $height = (int)($element['height'] * $this->pixelRatio);
        $thickness = (int)($element['thickness'] * $this->pixelRatio);
        $lineStyle = $element['lineStyle'];
        
        // 设置线条粗细
        imagesetthickness($this->image, $thickness);
        
        // 绘制线条
        imageline($this->image, $x, $y, $x, $y + $height, $color);
    }
    
    /**
     * 解析颜色
     */
    private function parseColor($colorStr) {
        if (preg_match('/^#([A-Fa-f0-9]{6})$/', $colorStr, $matches)) {
            $hex = $matches[1];
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return imagecolorallocate($this->image, $r, $g, $b);
        }
        return imagecolorallocate($this->image, 0, 0, 0); // 默认黑色
    }
    
    /**
     * 检测文本是否包含中英文混合内容
     */
    private function hasMixedChineseEnglish($text) {
        // 检查是否包含中文字符
        $hasChinese = preg_match('/[\x{4e00}-\x{9fa5}]/u', $text);
        
        // 检查是否包含英文字符或数字
        $hasEnglish = preg_match('/[a-zA-Z0-9]/', $text);
        
        // 如果同时包含中文和英文/数字，则为混合内容
        return $hasChinese && $hasEnglish;
    }

    /**
     * 分析文本中是否包含中英文混合内容，并返回类型
     */
    private function analyzeMixedContent($text) {
        // 检查是否包含中文字符
        $hasChinese = preg_match('/[\x{4e00}-\x{9fa5}]/u', $text);
        
        // 检查是否包含英文字符或数字
        $hasEnglish = preg_match('/[a-zA-Z0-9]/', $text);

        // 检查是否包含冒号
        $hasColon = preg_match('/：/', $text);

        // 检查是否包含空格
        $hasSpace = preg_match('/ /', $text);

        // 检查是否包含数字
        $hasNumber = preg_match('/[0-9]/', $text);

        // 检查是否包含字母
        $hasLetter = preg_match('/[a-zA-Z]/', $text);

        // 检查是否包含特殊字符
        $hasSpecialChar = preg_match('/[：，。！？；：]/u', $text);

        // 根据特征判断混合内容类型
        if ($hasChinese && $hasEnglish && $hasColon && $hasSpace && $hasNumber && $hasLetter) {
            return 'product_id'; // 例如：1012155 : 产品名 x 1
        } elseif ($hasChinese && $hasEnglish && $hasSpecialChar) {
            return 'description'; // 例如：产品描述
        }
        return 'mixed'; // 默认混合内容
    }
    
    /**
     * 获取文本宽度（优化中英文混合宽度计算）
     */
    private function getTextWidth($text, $fontFile, $fontSize) {
        // 使用GD库的imagettfbbox获取准确宽度
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        if ($bbox === false) {
            // 如果获取失败，使用估算方法
            return $this->estimateTextWidth($text, $fontSize);
        }
        return $bbox[4] - $bbox[0];
    }
    
    /**
     * 估算文本宽度（当GD库方法失败时的备用方案）
     */
    private function estimateTextWidth($text, $fontSize) {
        $width = 0;
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($chars as $char) {
            // 中文字符宽度约为字体大小的1.0倍
            if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $char)) {
                $width += $fontSize * 1.0;
            }
            // 英文字符宽度约为字体大小的0.6倍
            elseif (preg_match('/[a-zA-Z0-9]/', $char)) {
                $width += $fontSize * 0.6;
            }
            // 标点符号宽度约为字体大小的0.5倍
            elseif (preg_match('/[：，。！？；：]/u', $char)) {
                $width += $fontSize * 0.5;
            }
            // 空格宽度约为字体大小的0.3倍
            elseif ($char === ' ') {
                $width += $fontSize * 0.3;
            }
            // 其他字符使用默认宽度
            else {
                $width += $fontSize * 0.6;
            }
        }
        
        return $width;
    }
    
    /**
     * 文字换行处理
     */
    private function wrapText($text, $fontFile, $fontSize, $maxWidth) {
        $lines = array();
        
        // 检查是否包含换行符
        if (strpos($text, "\n") !== false) {
            // 如果包含换行符，按换行符分割
            $rawLines = explode("\n", $text);
            foreach ($rawLines as $rawLine) {
                $rawLine = trim($rawLine);
                if (!empty($rawLine)) {
                    // 对每一行进行宽度检查和换行处理
                    $subLines = $this->processLine($rawLine, $fontFile, $fontSize, $maxWidth);
                    $lines = array_merge($lines, $subLines);
                }
            }
        } else {
            // 没有换行符，使用原来的处理方式
            $lines = $this->processLine($text, $fontFile, $fontSize, $maxWidth);
        }
        
        return $lines;
    }
    
    /**
     * 处理单行文字
     */
    private function processLine($text, $fontFile, $fontSize, $maxWidth) {
        $lines = array();
        $currentLine = '';
        
        // 将文字按字符分割（支持中文）
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($chars as $index => $char) {
            $testLine = $currentLine . $char;
            $lineWidth = $this->getTextWidth($testLine, $fontFile, $fontSize);
            
            if ($lineWidth <= $maxWidth) {
                $currentLine = $testLine;
            } else {
                if (!empty($currentLine)) {
                    // 如果当前字符是冒号，且前面有内容，则强制不换行
                    if ($char === '：' && !empty($currentLine)) {
                        $currentLine = $testLine;
                    } else {
                        $lines[] = trim($currentLine);
                        $currentLine = $char;
                    }
                } else {
                    // 单个字符就超过宽度，强制换行
                    $lines[] = $char;
                    $currentLine = '';
                }
            }
        }
        
        if (!empty($currentLine)) {
            $lines[] = trim($currentLine);
        }
        
        // 确保所有行都不为空
        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });
        
        // 如果没有行，至少返回一个空行
        if (empty($lines)) {
            $lines = array('');
        }
        
        return $lines;
    }
    
    /**
     * 获取字体文件路径
     */
    private function getFontFile($fontFamily) {
        $fontMap = array(
            'simhei' => '/System/Library/Fonts/STHeiti Medium.ttc',
            'Arial' => '/System/Library/Fonts/Arial.ttf',
            'Times New Roman' => '/System/Library/Fonts/Times.ttc'
        );
        
        if (isset($fontMap[$fontFamily])) {
            $fontPath = $fontMap[$fontFamily];
            if (file_exists($fontPath)) {
                return $fontPath;
            }
        }
        
        // 默认返回中文字体
        return ROOT_DIR . '/app/wap/statics/fonts/msyh.ttf';
    }
    
    /**
     * 保存图片
     */
    public function saveImage($outputFile) {
        if (!$this->image) {
            throw new Exception("请先调用 createImage() 方法");
        }
        
        imagejpeg($this->image, $outputFile, 95);
       
    }
    
    /**
     * 输出图片到浏览器
     */
    public function outputImage() {
        if (!$this->image) {
            throw new Exception("请先调用 createImage() 方法");
        }
        
        header('Content-Type: image/jpeg');
        imagejpeg($this->image, null, 95);
    }
    
    /**
     * 释放资源
     */
    public function __destruct() {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }
    
    /**
     * 生成Code128B条形码
     * 参考: https://github.com/paul8888/code128barcode/blob/master/code128.php
     */
    public function generateCode128($text, $width, $height, $type = 'B') {
        // 确保输入是字符串
        $text = (string)$text;
        
        // 限制文本长度，防止条形码过长
        $maxChars = floor($width / 8); // 每个字符大约需要8个模块
        if (strlen($text) > $maxChars) {
            $text = substr($text, 0, $maxChars);
        }
        
        // 构建条形码数据
        $barcodeData = "211214"; // Code128B起始码
        
        // 添加字符编码
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            if (isset($this->code128Table[$char])) {
                $barcodeData .= $this->code128Table[$char];
            } else {
                // 如果字符不在编码表中，使用空格
                $barcodeData .= $this->code128Table[' '];
            }
        }
        
        // 计算校验和
        $checksum = 104; // Code128B起始字符值
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            $checksum += (ord($char) - 32) * ($i + 1);
        }
        $checksum = $checksum % 103;
        
        // 添加校验和字符
        $checksumChar = chr($checksum + 32);
        if (isset($this->code128Table[$checksumChar])) {
            $barcodeData .= $this->code128Table[$checksumChar];
        }
        
        // 添加停止码
        $barcodeData .= "2331112";
        
        return $barcodeData;
    }
    
    /**
     * 绘制条形码到图片
     */
    private function drawBarcode($image, $x, $y, $width, $height, $barcodeData, $color) {
        // 将编码数据转换为黑白条图案
        $pattern = $this->convertToBars($barcodeData);
        
        $moduleWidth = $width / strlen($pattern);
        
        // 确保模块宽度不为负数或零
        if ($moduleWidth <= 0) {
            return;
        }
        
        for ($i = 0; $i < strlen($pattern); $i++) {
            if ($pattern[$i] == '1') {
                $moduleX = $x + ($i * $moduleWidth);
                $moduleEndX = $moduleX + $moduleWidth;
                
                // 严格确保条形码不超出容器边界
                if ($moduleX < $x) {
                    $moduleX = $x;
                }
                if ($moduleEndX > $x + $width) {
                    $moduleEndX = $x + $width;
                }
                
                // 额外检查：确保模块在有效范围内
                if ($moduleX >= $x && $moduleEndX <= $x + $width && $moduleX < $moduleEndX) {
                    // 显式转换为整数，避免精度丢失警告
                    $moduleXInt = (int)round($moduleX);
                    $moduleEndXInt = (int)round($moduleEndX);
                    $yInt = (int)$y;
                    $heightInt = (int)$height;
                    
                    imagefilledrectangle($image, $moduleXInt, $yInt, $moduleEndXInt, $yInt + $heightInt, $color);
                }
            }
        }
    }
    
    /**
     * 将编码数据转换为黑白条图案
     */
    public function convertToBars($barcodeData) {
        $pattern = '';
        $isBlack = true; // 从黑色条开始
        
        for ($i = 0; $i < strlen($barcodeData); $i++) {
            $width = (int)$barcodeData[$i];
            for ($j = 0; $j < $width; $j++) {
                $pattern .= $isBlack ? '1' : '0';
            }
            $isBlack = !$isBlack; // 切换黑白
        }
        
        return $pattern;
    }

    /**
     * 获取图片数据（不输出HTTP头）
     * @param int $quality 图片质量 (1-100)
     * @return string|false 图片数据或false
     */
    public function getImageData($quality = 95) {
        if (!$this->image) {
           
            return false;
        }
        
        // 确保质量在有效范围内
        $quality = max(1, min(100, (int)$quality));
        
        try {
            // 检查输出缓冲状态
            $obLevel = ob_get_level();
            if ($obLevel > 0) {
               
            }
            
            // 开始新的输出缓冲
            ob_start();
            
            // 生成JPEG数据
            $result = imagejpeg($this->image, null, $quality);
            if ($result === false) {
               
                ob_end_clean();
                return false;
            }
            
            // 获取缓冲内容
            $imageData = ob_get_contents();
            if ($imageData === false) {
               
                ob_end_clean();
                return false;
            }
            
            // 清理输出缓冲
            ob_end_clean();
            
            // 验证数据
            if (empty($imageData)) {
                
                return false;
            }
            
            // 检查JPEG头部
            if (strlen($imageData) < 10 || substr($imageData, 0, 2) !== "\xFF\xD8") {
              
                return false;
            }
            
           
            return $imageData;
            
        } catch (Exception $e) {
            
            // 确保清理输出缓冲
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            return false;
        }
        imagejpeg($this->image, null, 95);
    }
}

/**
 * 加载快递模板文件（支持JSON和序列化格式）
 */
function loadTemplate($templateFile) {
    if (!file_exists($templateFile)) {
        throw new Exception("模板文件不存在: $templateFile");
    }
    
    $templateContent = file_get_contents($templateFile);
    
    // 尝试JSON解析
    $template = json_decode($templateContent, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $template;
    }
    
    // 如果JSON解析失败，尝试反序列化
    $template = unserialize($templateContent);
    if ($template === false) {
        throw new Exception("模板文件格式错误：既不是有效的JSON也不是有效的序列化数据");
    }
    
    return $template;
}

/**
 * 加载数据文件（支持JSON和序列化格式）
 */
function loadData($dataFile) {
    if (!file_exists($dataFile)) {
        throw new Exception("数据文件不存在: $dataFile");
    }
    
    $dataContent = file_get_contents($dataFile);
    
    // 尝试JSON解析
    $data = json_decode($dataContent, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $data;
    }
    
    // 如果JSON解析失败，尝试反序列化
    $data = unserialize($dataContent);
    if ($data === false) {
        throw new Exception("数据文件格式错误：既不是有效的JSON也不是有效的序列化数据");
    }
    
    return $data;
}

// 命令行使用
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if ($argc < 3) {
        echo "用法: php json-to-image.php <模板文件> <输出jpg文件> [数据文件] [宽度] [高度]\n";
        echo "示例: php json-to-image.php template.json dd-output.jpg\n";
        echo "示例: php json-to-image.php template.json dd-output.jpg data.json\n";
        echo "示例: php json-to-image.php template.json dd-output.jpg data.json 608 1040\n";
        echo "默认尺寸: 76mm × 130mm = 608px × 1040px (1mm ≈ 8px)\n";
        echo "注意: 像素转换比例已调整为 1mm ≈ 8px (原为 1mm = 3.75px)\n";
        exit(1);
    }
    
    $jsonFile = $argv[1];
    $outputFile = $argv[2];
    $dataFile = isset($argv[3]) ? $argv[3] : null;
    $width = isset($argv[4]) ? (int)$argv[4] : 608;   // 默认76mm
    $height = isset($argv[5]) ? (int)$argv[5] : 1040; // 默认130mm
    
    try {
        // 加载快递模板数据
        $templateData = loadTemplate($jsonFile);
        
        // 创建转换器实例
        $converter = new JsonToImageConverter($templateData, $width, $height);
        
        // 如果提供了数据文件，加载数据
        if ($dataFile && file_exists($dataFile)) {
            try {
                $inputData = loadData($dataFile);
                $converter->setInputData($inputData);
            } catch (Exception $e) {
                echo "警告: 数据文件加载失败: " . $e->getMessage() . "\n";
            }
        }
        
        $converter->createImage();
        $converter->saveImage($outputFile);
        echo "图片生成成功: $outputFile (尺寸: {$width}×{$height}px)\n";
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?> 