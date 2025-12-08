<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单备注
 */
class omeauto_order_label_memo extends omeauto_order_label_abstract implements omeauto_order_label_interface
{
    /**
     * 检查订单数据是否符合要求
     *
     * @param array $orderInfo
     * @param string $error_msg
     * @return bool
     */
    public function vaild($orderInfo, &$error_msg=null)
    {
        if(empty($this->content)){
            $error_msg = '没有设置基础物料类型规则';
            return false;
        }
        
        $funcLib = kernel::single('ome_func');
        
        $memo_text = explode('#', $this->content['memo_text']);
        if(empty($memo_text)) {
            $error_msg = '没有设置备注关键字';
            return false;
        }
        
        //买家备注
        $isMember = false;
        if(in_array('custom', $this->content['memo_type']) && $memo_text) {
            $custom_mark = $orderInfo['custom_mark'];
            $custom_mark = $funcLib->format_memo($custom_mark);
            
            //check
            foreach ((array)$custom_mark as $markKey => $markVal)
            {
                foreach ($memo_text as $memoKey => $memo)
                {
                    if($this->content['memo_scope'] == 'all') {
                        //包含全部关键字
                        if($markVal['op_content'] == $memo){
                            $isMember = true;
                            
                            //unset
                            unset($memo_text[$memoKey]);
                            
                            //满足一个规则,就跳出
                            break 2;
                        }
                    }elseif($this->content['memo_scope'] == 'part'){
                        //包含部分关键字
                        if(preg_match("/{$memo}/", $markVal['op_content'])) {
                            $isMember = true;
                            
                            //unset
                            unset($memo_text[$memoKey]);
                            
                            //满足一个规则,就跳出
                            break 2;
                        }
                    }
                }
            }
        }
        
        //客服备注
        $isKefu = false;
        if(in_array('shop', $this->content['memo_type']) && $memo_text) {
            $mark_text = $orderInfo['mark_text'];
            $mark_text = $funcLib->format_memo($mark_text);
            
            //check
            foreach ((array)$mark_text as $markKey => $markVal)
            {
                foreach ($memo_text as $memoKey => $memo)
                {
                    if($this->content['memo_scope'] == 'all') {
                        //包含全部关键字
                        if($markVal['op_content'] == $memo){
                            $isKefu = true;
                            
                            //unset
                            unset($memo_text[$memoKey]);
                            
                            //满足一个规则,就跳出
                            break 2;
                        }
                    }elseif($this->content['memo_scope'] == 'part'){
                        //包含部分关键字
                        if(preg_match("/{$memo}/", $markVal['op_content'])) {
                            $isKefu = true;
                            
                            //unset
                            unset($memo_text[$memoKey]);
                            
                            //满足一个规则,就跳出
                            break 2;
                        }
                    }
                }
            }
        }
        
        //result
        if($isMember || $isKefu){
            return true;
        }
        
        $error_msg = '备注关键字没有匹配到规则';
        
        return false;
    }
}