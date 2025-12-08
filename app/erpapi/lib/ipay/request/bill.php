<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_ipay_request_bill extends erpapi_ipay_request_abstract
{
    protected function __getQueryApi($sdf)
    {
        $node_type = $sdf['node_type'] ?? '';
        switch ($node_type) {
            case 'luban':
                $api_method = STORE_DOWNLOAD_SHOP_ACCOUNT_ITEM;
                break;
            default :
                $api_method = SHOP_QIANBAO_BILL_DETAIL_QUERY;
                break;
        }
        return $api_method;
    }
    
    /**
     * query
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function query($sdf)
    {
        return $this->__caller->call($this->__getQueryApi($sdf), $sdf, array (), '拉取账单',10,$this->__channelObj->channel['channel_bn']);
    }
    
    protected function __getDownloadurlApi($sdf)
    {
        $node_type = $sdf['node_type'] ?? '';
        switch ($node_type) {
            case 'luban':
                $api_method = STORE_DOWNLOAD_SHOP_ACCOUNT_ITEM_FILE;
                break;
            default :
                $api_method = SHOP_ALIPAY_BILL_GET;
                break;
        }
        return $api_method;
    }

    /**
     * downloadurl
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function downloadurl($sdf)
    {
    	return $this->__caller->call($this->__getDownloadurlApi($sdf), $sdf, array (), '下载账单URL',10,$this->__channelObj->channel['channel_bn']);
    }
}