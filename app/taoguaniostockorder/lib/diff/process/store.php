<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店责任
 *
 * @package default
 * @author chenping@shopex.cn
 * @version Sun Apr  3 20:23:31 2022
 **/
class taoguaniostockorder_diff_process_store extends taoguaniostockorder_diff_process_abstract
{
    /**
     * 仓发店短发
     * @param $data
     * @return array|void
     */

    public function b2sLess($data)
     {
         return $this->storeAddStock($data);
     }
    
    /**
     * 仓发店超发
     * @param $data
     * @return array|void
     */
    public function b2sMore($data)
    {
        return $this->storeSubStock($data);
    
    }
    
    /**
     * 仓发店错发（错发逻辑拆分成超发和短发处理）
     * 
     * @return void
     * @author
     * */
//    public function b2sWrong($data){}
    
    /**
     * 店退仓短发
     * @param $data
     * @return array|void
     */
    public function s2bLess($data)
    {
        return $this->storeAddStock($data);
    }
    
    
    /**
     * 店退仓超发
     * @param $data
     * @return array|void
     */
    public function s2bMore($data)
    {
        return $this->storeSubStock($data);
    
    }
    
    /**
     * 店退仓错发
     * 
     * @return void
     * @author
     * */
//    public function s2bWrong($data){}
    
    /**
     * 店转店短发
     * @param $data
     * @return array|void
     */
    public function s2sLess($data)
    {
        return $this->storeAddStock($data);
    }
    
    
    /**
     * 店转店超发
     * @param $data
     * @return array|void
     */
    public function s2sMore($data)
    {
        return $this->storeSubStock($data);
    }
    
    /**
     * 仓转仓短发
     *
     * @return void
     * @author
     **/
    public function b2bLess($data){}
    
    /**
     * 仓转仓超发
     *
     * @return void
     * @author
     **/
    public function b2bMore($data){}
    
    /**
     * 店转店错发
     *
     * @return void
     * @author
     **/
//    public function s2sWrong($data){}
}