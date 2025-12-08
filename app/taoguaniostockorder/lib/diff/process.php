<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 差异定责
 *
 * @package default
 * @author chenping@shopex.cn
 * @version Sun Apr  3 20:17:03 2022
 **/
class taoguaniostockorder_diff_process
{
    /**
     * 流程（b2s：仓发店，s2b：店退仓，s2s：店转店，b2b：仓转仓）
     * 
     * @var string
     * */
    private $_flow;

    /**
     * 原因（less：短发，more：超发，wrong：错发，lost：丢失）
     * 
     * @var string
     * */
    private $_reason;

    /**
     * 责任方（branch：仓库，store：门店，logistics：物流）
     * 
     * @var string
     * */
    private $_responsible;

        /**
     * __construct
     * @param mixed $args args
     * @return mixed 返回值
     */

    public function __construct($args)
    {
        $this->_flow = $args['flow'];

        $this->_reason = $args['reason'];

        $this->_responsible = $args['responsible'];
    }

    /**
     * 定则入口
     * @param array $data
     * @return void
     * @author
     **/
    public function rulerule($data)
    {
        try {
            $object = kernel::single('taoguaniostockorder_diff_process_' . $this->_responsible);

            if (!($object instanceof taoguaniostockorder_diff_process_abstract)) {
                return [false, '差异处理类必须继承父类'];
            }

            $method = $this->_flow . ucfirst($this->_reason);

            if (!method_exists($object, $method)) {
                return [false, '差异处理类缺少处理[' . $method . ']场景'];
            }

            return call_user_func_array(array($object, $method), array($data));
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }
}
