--[[
SKU物料总库存缓存在REDIS，通过LUA脚本增加库存实现原子性

@author: chenping@shopex.cn
@time: 2024-07-07
--]]

-- 检查库存是否充足
local function check(sku_id, quantity)
    local data = redis.call('HMGET', sku_id, 'store', 'store_freeze')

    -- redis未缓存到库存, 返回错误
    if not data[1] then
        return {100, 'MISSING_SKU_STOCK', sku_id}
    end

   return {0, 'success', data}
end

-- 更新库存函数
local function update(sku_id, quantity)
    -- 增加库存
    return redis.call('HINCRBY', sku_id, 'store', quantity)
end

--[[
检查库存是否充足
KEYS格式: #节点ID##stock:material:#物料ID#, 示例: 1661661666#stock:material:1
--]]
for i, v in ipairs(KEYS) do
    local result = check(v, tonumber(ARGV[i]))

    if result[1] ~= 0 then
        return result
    end
end

-- 更新冻结
for i, v in ipairs(KEYS) do
    local result = update(v, tonumber(ARGV[i]))

    if not result then
        return {300, 'FAIL_SKU_STOCK', v}
    end
end

return {0, 'success'}