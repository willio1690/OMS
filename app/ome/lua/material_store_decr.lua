--[[
SKU物料总库存缓存在REDIS，通过LUA脚本扣减库存实现原子性

@author: chenping@shopex.cn
@time: 2024-07-07
--]]

-- 检查库存是否充足
local function check(sku_id, quantity, negative_stock)
    local data = redis.call('HMGET', sku_id, 'store', 'store_freeze')

    -- redis未缓存到库存, 返回错误
    if not data[1] then
        return {100, 'MISSING_SKU_STOCK', sku_id}
    end

    -- redis未缓存到冻结, 返回错误
    if not data[2] then
        return {100, 'MISSING_SKU_FREEZE', sku_id}
    end

    -- 库存不足, 返回错误, 允许负库存不判断库存是否充足
    -- php的redis回滚不会传negative_stock，需无脑回滚
    if negative_stock ~= nil then
        if (tonumber(data[1]) < math.abs(quantity) and negative_stock ~= "true") then
            return {200, 'INSUFFICIENT_SKU_STOCK', sku_id}
        end
    end

    -- -- 冻结不足, 返回错误
    -- if (tonumber(data[2]) < math.abs(quantity)) then
    --     return {200, 'INSUFFICIENT_FREEZE_IN_REDIS', sku_id}
    -- end

   return {0, 'success', data}
end

-- 更新库存函数
local function update(sku_id, quantity)
    -- 扣减库存
    local rs1 = redis.call('HINCRBY', sku_id, 'store', quantity)

    --[[
    -- 扣减冻结
    local rs2 = redis.call('HINCRBY', sku_id, 'store_freeze', quantity)
    ]]

    -- return rs1 and rs2
    return rs1
end

--[[
检查库存是否充足
KEYS格式: #节点ID##stock:material:#物料ID#, 示例: 1661661666#stock:material:1
--]]
for i, v in ipairs(KEYS) do
    local quantity = tonumber(ARGV[i])
    local negative_stock = ARGV[#KEYS+i]
    local result = check(v, quantity, negative_stock)

    if result[1] ~= 0 then
        return result
    end
end

-- 更新冻结
for i, v in ipairs(KEYS) do
    local result = update(v, tonumber(ARGV[i]))

    if not result then
        return {300, 'FAIL_SKU_FREEZE', v}
    end
end

return {0, 'success'}