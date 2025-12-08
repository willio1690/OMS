<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class inventorydepth_mdl_log extends dbeav_model {

    /**
     * 保存日志
     *
     * @return void
     * @author 
     **/
    public function saveLog($data)
    {
        # 判断是否日志已经存在
        $id = $this->select()->columns('log_id')
                ->where('shop_id=?',$data['shop_id'])
                ->where('bn=?',$data['bn'])
                ->instance()->fetch_one();
        # 更新
        if ($id) {
            $this->update($data,array('log_id' => $id));
        }else{
            # 保存
            $this->insert($data);
        }
    }

}
