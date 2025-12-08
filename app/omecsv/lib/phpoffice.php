<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omecsv_phpoffice
{
    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function import($file, $callback, $post = [], $format = [], $output = true, $min = 1, $max = 65536, $cursheet = 0)
    {
        try {
            $inputFileName = $file['tmp_name']; 

            if (empty($inputFileName) OR !file_exists($inputFileName)) {
                throw new \Exception('文件不存在!');
            }

            $path = pathinfo($inputFileName);
            $excel = new \Vtiful\Kernel\Excel(['path' => $path['dirname']]);
            $excel->openFile($path['basename'])
                ->openSheet();
            $title = $excel->nextRow();
            $type = array_pad([], count($title), \Vtiful\Kernel\Excel::TYPE_STRING);
            foreach($format as $k => $v) {
                $type[array_search($v, $title)] = \Vtiful\Kernel\Excel::TYPE_TIMESTAMP;
            }
            $contents = $excel->setType($type)
                                ->getSheetData();
            $contents = array_merge([0 => $title], $contents);
            $highestRow = count($contents);
            foreach ($contents as $i => $row) {
                $key = $i + 1;
                foreach($format as $k => $v) {
                    $index = array_search($v, $title);
                    if($row[$index] && is_int($row[$index])) $row[$index] = date('Y-m-d H:i:s', $row[$index]);
                }
                $data = [
                    'line' => $key,
                    'contents' => $row
                ];

                $data['post'] = $post;
                $data['highestRow'] = $highestRow;

                if ($output) {
                    $this->_output("第{$key}行，{$data['contents'][0]}处理中...");
                }

                list($rs, $msg, $level) = call_user_func_array($callback, array_values($data));

                if ($rs == false) {
                    //if ($output) {
                    //    $this->_output("第{$key}行，{$data['contents'][0]}处理失败：{$msg}。", 'error');
                    //}
                    
                    //显示上一行报错信息
                    if($output == 'lastrow' && $msg){
                        $line_key = $i;
                        $this->_output("第{$line_key}行失败：{$msg}。", 'error');
                    }elseif($output){
                        //默认显示报错的下一行
                        $this->_output("第{$key}行，{$data['contents'][0]}处理失败：{$msg}。", 'error');
                    }
                    
                    return [false, $msg];
                }

                if ($output || $msg) {
                    //显示上一行报错信息
                    if($output == 'lastrow' && $msg){
                        if($highestRow == $key){
                            $line_key = $key;
                        }else{
                            $line_key = $i;
                        }
                        
                        $this->_output("第{$line_key}行，{$msg}；", 'error');
                    }else{
                        $this->_output("第{$key}行，{$data['contents'][0]}".$msg??'处理完成', $level??'notice');
                    }
                }
            }

            return [true, '成功'];
        } catch (\Exception $e) {
            $msg = $e->getMessage();

            $this->_output($msg);

            return [false, $msg];
        }
    }

    /**
     * 导出Excel文件，支持单工作表和多工作表
     * 
     * @param string $filename 文件名
     * @param array $data 数据，支持两种格式：
     *                    1. 单工作表：[0 => [标题行], 1 => [数据行1], ...]
     *                    2. 多工作表：[['sheet_name' => '工作表名', 'data' => [数据]], ...]
     * @return void
     * @author 
     **/
    public function export($filename, $data)
    {
        try {
            $config = [
                'path' => kernel::single('ome_func')->getTmpDir() // xlsx文件保存路径
            ];
            $excel  = new \Vtiful\Kernel\Excel($config);
            $fileTmpName = time().uniqid().rand(10,99).'.xlsx';
            
            // 检查是否为多工作表格式
            $isMultiSheet = false;
            if (!empty($data) && is_array($data)) {
                $firstItem = reset($data);
                if (is_array($firstItem) && isset($firstItem['sheet_name']) && isset($firstItem['data'])) {
                    $isMultiSheet = true;
                }
            }
            
            if ($isMultiSheet) {
                // 多工作表模式
                $firstSheet = true;
                foreach ($data as $sheetInfo) {
                    $sheetName = $sheetInfo['sheet_name'];
                    $sheetData = $sheetInfo['data'];
                    
                    if ($firstSheet) {
                        // 创建第一个工作表
                        $filePath = $excel->fileName($fileTmpName, $sheetName);
                        if ($sheetData) {
                            $filePath = $filePath->data($sheetData);
                        }
                        $firstSheet = false;
                    } else {
                        // 添加其他工作表
                        $filePath = $filePath->addSheet($sheetName);
                        if ($sheetData) {
                            $filePath = $filePath->data($sheetData);
                        }
                    }
                }
            } else {
                // 单工作表模式（保持向后兼容）
                $filePath = $excel->fileName($fileTmpName, 'sheet1');
                if ($data) {
                    $filePath = $filePath->data($data);
                }
            }
            
            $filePath = $filePath->output();
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Disposition: attachment;filename="' . $filename);
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            $savePath = 'php://output';
            if (copy($filePath, $savePath) === false) {
                // Throw exception
            }
            // Delete temporary file
            @unlink($filePath);

        } catch (Exception $e) {
            
        }
    }

    public function save($filename, $data)
    {
        try {
            $config = [
                'path' => kernel::single('ome_func')->getTmpDir() // xlsx文件保存路径
            ];
            $excel  = new \Vtiful\Kernel\Excel($config);
            $fileTmpName = time().uniqid().rand(10,99).'.xlsx';
            // fileName 会自动创建一个工作表，你可以自定义该工作表名称，工作表名称为可选参数
            $filePath = $excel->fileName($fileTmpName, 'sheet1');
            if($data) {
                $filePath = $filePath->data($data);
            }
            $filePath = $filePath->output();
            if (copy($filePath, $filename) === false) {
                // Throw exception
            }
            // Delete temporary file
            @unlink($filePath);

        } catch (Exception $e) {
            
        }
    }

    public function _output($msg, $level='notice')
    {
        $msg = addslashes($msg);
        echo sprintf("<script>parent.$('iMsg').setText('%s');</script>", $msg);

        if ($level != 'notice') {
        echo <<<JS
        
        <script>
            var c = parent.$('iMsg').clone().setStyle('color','#8a1f11')
            c.inject('iMsg','after')
        </script>
JS;
        }

        flush();
        ob_flush();
    }
}
