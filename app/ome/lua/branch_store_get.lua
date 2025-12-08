--[[
SKU仓库存缓存在REDIS，通过LUA脚本获取库存和冻结实现原子性

@author: chenping@shopex.cn
@time: 2024-07-07
--]]

-- 检查库存是否充足
local function get(sku_id)
    local data = redis.call('HMGET', sku_id, 'store', 'store_freeze')

    -- redis未缓存到库存, 返回错误
    if not data[1] then
        return {100, 'MISSING_STOCK_IN_REDIS', sku_id}
    end

    -- redis未缓存到冻结, 返回错误
    if not data[2] then
        return {100, 'MISSING_FREEZE_IN_REDIS', sku_id}
    end

    return {0, 'succes', data}
end

--[[
获取库存和冻结
KEYS格式: #节点号##stock:#仓库ID#:#物料ID#, 示例: 123456789#stock:1:1
--]]
for i, v in ipairs(KEYS) do
    local result = get(v)
    return result
end

