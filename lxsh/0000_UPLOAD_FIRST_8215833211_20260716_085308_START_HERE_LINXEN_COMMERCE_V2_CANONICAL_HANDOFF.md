# START HERE — LIN XÉN COMMERCE / STOREFRONT V2

**Document type:** Canonical architecture + build handoff contract  
**Project:** Dự án 3MG ERP – Laravel / Lin Xén Storefront V2  
**Version:** `linxen_commerce_v2_handoff_v1`  
**Generated:** 2026-07-16  
**Status:** Canonical domain model confirmed; implementation not yet completed

---

## 0. Cách sử dụng tài liệu này trong phiên chat mới

Upload file này vào phiên chat chịu trách nhiệm xây Lin Xén V2 và gửi đúng yêu cầu:

> Đọc toàn bộ file START HERE này trước. Xác nhận lại canonical model, các boundary bắt buộc và kế hoạch triển khai. Không dùng Lin Xén V1 hoặc `/api/storefront/*` cũ làm chuẩn. Không tạo patch trước khi chỉ rõ phase, repo đích, file liên quan, migration cần thiết và tác động runtime.

Tài liệu này là điểm bắt đầu bắt buộc. Khi source runtime mâu thuẫn với tài liệu, phiên build phải:

1. Dừng suy luận.
2. Đọc source ERP V2 hiện tại.
3. Nêu rõ điểm mâu thuẫn.
4. Cập nhật contract trước khi viết code.

---

# 1. Mục tiêu hệ thống

Xây **Lin Xén Storefront V2** theo logic ERP V2 hiện tại:

- Mobile-first.
- Tối ưu conversion từ Meta/social.
- Catalog dạng grid sang trọng.
- Có feed vuốt liên tục kiểu TikTok cho sản phẩm/content.
- PDP có ảnh lớn, chọn màu, chọn size, giá/sale, tồn theo size, mua nhanh.
- Storefront chỉ là presentation/client.
- ERP là owner của product identity, màu, size, SKU, giá, tồn, media, customer, quote, order và attribution.

Hai codebase trên cùng server:

```text
ERP V2:      /var/www/3mgate
Storefront:  /var/www/linxen
```

Quy ước patch:

```text
ERP patch:         sh/
Storefront patch:  lxsh/
```

---

# 2. Các nguyên tắc không được vi phạm

## 2.1 Không lấy legacy làm chuẩn

Không dùng các thành phần sau làm canonical truth:

```text
App\Http\Controllers\Api\Storefront\*
/api/storefront/*
CollectionControllerV1
StorefrontCollection legacy
CollectionEngine legacy
RsCollectionService legacy
OldErpLookupController
logic KiotViet master/child V1
logic “size S là master, M/L/XL là child”
```

Không copy logic Lin Xén V1 rồi đổi tên endpoint.

## 2.2 Storefront không sở hữu dữ liệu thương mại

Storefront không được làm source of truth cho:

```text
product identity
color identity
size-SKU mapping
price
promotion
stock
shipping fee
order total
customer ownership
campaign IDs
```

Client không được gửi các giá trị canonical để ERP tin trực tiếp:

```text
unit_price
sale_price
discount
shipping_fee
subtotal
grand_total
branch_id
sold_by_id
sale_channel_id
raw Meta campaign/ad IDs
```

## 2.3 Không dùng ID mơ hồ

Không đặt API field chỉ là `product_id` nếu chưa nói rõ đó là:

```text
Research Set ID
Product Variant Group ID
Variant Group Item ID
local kiotviet_products.id
kiotviet_products.product_id
kiotviet_products.kiotviet_id
provider productId
```

Public API dùng opaque public ID, ERP resolve nội bộ.

---

# 3. Canonical ERP V2 commerce model

## 3.1 Sơ đồ tổng

```text
Commerce Product Projection
keyed by research_sets.id
        ↓
Product Variant Group
product_variant_groups.id
một màu thương mại của mẫu
        ↓
Variant Group Item
product_variant_group_items.id
binding màu + size + SKU
        ↓
Sellable KiotViet SKU
kiotviet_products.product_id / kiotviet_id
        ↓
Price + branch stock + approved sale media
```

## 3.2 Entity product mà khách nhìn thấy

Product card và PDP là một **projection theo Research Set**.

```text
Internal aggregate key: research_sets.id
Model:                  App\Models\Design\ResearchSet
Table:                  research_sets
```

Research Set đang là aggregate nối:

- Thiết kế.
- Tên thương mại.
- Category/DNA.
- TechPack/BOM.
- Nhóm màu.
- SKU KiotViet.
- Media.
- Pricing/stock projection.
- Marketing context.

Research Set không phải order line.

### Public identity đề xuất

```text
product_public_id = rs_{research_sets.id}
```

Ví dụ:

```text
rs_4451
```

Slug gắn với ID:

```text
camila-vay-mini-rs-4451
```

Slug có thể đổi; RS ID phải ổn định khi thêm/bỏ màu hoặc thay SKU.

## 3.3 Gate RS hiện hành

Source Product Advisor/POS V2 đang dùng:

```text
research_sets.status = approved
research_sets.is_active = 1
```

Không dùng `lifecycle_stage` làm hard commerce gate cho tới khi ERP định nghĩa lại contract vì có dữ liệu approved/active nhưng lifecycle còn draft.

---

# 4. Màu, size và SKU

## 4.1 Màu

Canonical chain:

```text
product_variant_groups.color_code
→ fashion_colors.code
→ fashion_colors.name + fashion_colors.hex
```

Variant Group là một nhóm SKU cùng màu của một Research Set.

```text
Table: product_variant_groups
Primary key: id
Owner link: research_set_id
```

Public color ID đề xuất:

```text
pvg_{product_variant_groups.id}
```

Ví dụ:

```text
pvg_1891
```

Product Color Truth chỉ là fidelity/reference nội bộ cho AI và review. Nó không phải catalog color identity.

## 4.2 Size và sellable SKU

Exact mapping:

```text
research_sets.id
→ product_variant_groups.research_set_id
→ product_variant_group_items.variant_group_id
→ product_variant_group_items.size
→ product_variant_group_items.product_id
→ kiotviet_products.product_id
```

Table binding:

```text
product_variant_group_items
```

Một order line phải resolve tới exact KiotViet sellable SKU.

Public SKU ID đề xuất:

```text
sku_{opaque_internal_mapping}
```

Trong giai đoạn đầu có thể dùng:

```text
sku_{kiotviet_products.product_id}
```

nhưng API handler phải xác minh lại:

```text
connection
provider kiotviet_id
active
allows_sale
RS mapping
color group
size
branch stock
```

Không gửi RS ID hoặc Variant Group ID như order line.

---

# 5. Runtime scope Lin Xén đã xác nhận

```text
Storefront:
  storefronts.id        = 1
  storefronts.code      = linxen
  storefronts.domain    = linxen.vn
  storefronts.name      = LIN XÉN

KiotViet connection:
  kiotviet_public_connections.id = 2
  retailer_code                  = 3mgroup
  branch_id                      = 332318

Brand:
  brand_id                       = 7

Price channel:
  storefront

Seller channel:
  seller_channels.id             = 5
  kiotviet_channel_id            = 346490
  name                           = LIN XÉN
  brand_id                       = 7

Configured seller:
  sold_by_id                     = 506115
```

Canonical connection model:

```text
App\Models\KiotViet\KiotVietPublicConnection
table: kiotviet_public_connections
```

## 5.1 Khoảng trống hiện tại

ERP chưa có một DB mapping duy nhất:

```text
storefront
→ brand
→ KiotViet connection
→ branch
→ seller channel
→ soldBy
→ price channel
→ category allowlist
→ catalog policy
```

Phải xây một `StorefrontScopeResolver` DB-backed.

Không hard-code toàn bộ trong controller. Không nhận `brand=linxen` rồi query toàn ERP.

---

# 6. Pricing contract

Canonical service hiện tại:

```text
app/Services/Pricing/PriceResolverService.php
App\Services\Pricing\PriceResolverService
```

## 6.1 Original price

Nguồn ưu tiên:

```text
product_prices
```

Scope:

```text
product_id
is_active = 1
channel IN (all, storefront)
priority
```

Field:

```text
retail_price
→ base_price
```

## 6.2 Promotion rules

Tables:

```text
product_price_rules
product_price_rule_items
```

Rule hợp lệ:

```text
status = active
channel = all/storefront/NULL
starts_at <= now hoặc NULL
ends_at >= now hoặc NULL
```

Scope precedence:

```text
Research Set rule
→ fallback SKU/product rule
```

Calculation precedence:

```text
override_price
→ fixed rule
→ percent rule
→ original price
```

## 6.3 Checkout rule

Catalog/PDP có thể hiển thị fallback có kiểm soát.

Checkout phải fail closed nếu không resolve được giá bằng `channel=storefront`.

Không commit bằng:

```text
research_sets.suggested_price
raw client price
old Storefront price
```

---

# 7. Stock contract

Primary table:

```text
product_stock_availabilities
```

Exact scope:

```text
product_id + branch_id
```

Lin Xén branch:

```text
332318
```

Fields:

```text
on_hand
reserved
available
snapshot_at
```

Rule:

```text
available = max(0, available)
```

Fallback nếu projection không có available:

```text
available = max(0, on_hand - reserved)
```

Fallback table:

```text
kiotviet_product_inventories
```

`warehouse_location_items` là physical-bin evidence, không phải customer-facing sellable stock.

Mọi Commerce query phải filter branch. Không aggregate tồn của nhiều branch.

Response chuẩn:

```json
{
  "size": "M",
  "sellable_sku_id": "sku_54369629",
  "sku": "SP14546158",
  "branch_id": 332318,
  "physical": 5,
  "reserved": 0,
  "available": 5,
  "sellable": true,
  "stock_source": "product_stock_availabilities"
}
```

---

# 8. Media contract

Primary table:

```text
erp_v2_marketing_rs_media
```

Media có thể scope theo:

```text
research_set_id
variant_group_id
variant_group_item_id
product_id
color_code
size_code
image_scope
```

## 8.1 Gate public bắt buộc

```text
deleted_at IS NULL
status = approved
usable_for_sale = 1
is_customer_facing = 1
```

Representative:

```text
is_primary = 1            đại diện RS
is_primary_for_scope = 1  đại diện màu/SKU scope
```

## 8.2 Được public

- Ảnh thật sản phẩm đã duyệt.
- Ảnh production sample đã được đánh dấu sale/customer-facing.
- Ảnh người mẫu mặc.
- Product Clarity.
- Opening.
- Sales Inbox.
- 3mMedia crop/final output đã duyệt.
- Video đã duyệt theo cùng contract.

## 8.3 Không được public

- Board/source/contact sheet.
- Moodboard/reference.
- TechPack image.
- Fit Look/Fit Proxy.
- Product Color Truth source.
- Product Material Truth source.
- Background/model reference.
- Pending/rejected/deleted/failed media.
- Internal AI audit assets.

Không dùng `source_type` đơn lẻ để quyết định public. Dùng business gates.

Storefront nên trả original approved URL; không mặc định dùng ad `marked_url`.

Cần xây `CommercePublicMediaResolver`. Không gọi nguyên trạng Product Advisor gallery.

---

# 9. Catalog eligibility

## 9.1 Listing

Một product được xuất hiện khi:

```text
RS approved + active
site scope match
có active color group
có active SKU + allows_sale
giá storefront hợp lệ
có approved sale/customer-facing media
```

Có thể vẫn listing khi hết hàng nếu site policy cho phép browse.

## 9.2 PDP

Có thể mở PDP khi product còn publishable, kể cả hết hàng:

```text
catalog_visible = true
pdp_visible = true
purchasable = false
```

## 9.3 Add to cart

Bắt buộc:

```text
exact color group
exact size binding
exact sellable SKU
SKU active
allows_sale = 1
storefront price > 0
available at branch 332318 > 0
```

## 9.4 Checkout

Phải revalidate:

```text
site scope
SKU identity
RS/color/size binding
price/promotion
branch stock
address ownership
shipping quote
quote expiry
customer/session
idempotency
```

---

# 10. Customer, auth và address

## 10.1 Canonical customer

```text
App\Models\Customer\CustomerUser
table: customer_users
primary key: id
```

Provider mirror:

```text
kiotviet_customers
local key: id
provider key: kiotviet_id
connection scope: connection_id
```

Correct chain:

```text
CustomerUser
→ local KiotVietCustomer mapping
→ kiotviet_customers.kiotviet_id
→ provider order.customerId
```

## 10.2 Mixed historical mapping warning

`customer_users.kiotviet_customer_id` đang có dữ liệu lẫn:

- Provider ID.
- Local `kiotviet_customers.id`.

Source mới ghi local ID.

Phải có adapter:

```text
try local kiotviet_customers.id
→ verify connection_id
→ fallback match normalized phone + connection
→ use kiotviet_customers.kiotviet_id
```

Không gửi thẳng `customer_users.kiotviet_customer_id` sang provider.

## 10.3 Auth boundary chưa hoàn chỉnh

`CustomerUser` hiện chỉ extends Eloquent `Model`, chưa chứng minh:

- Authenticatable.
- HasApiTokens.
- Sanctum customer guard.
- OTP.
- Token expiry/rotation/revocation.
- Storefront audience.

Không dùng:

```text
X-Customer-Phone
phone query string
public order code without ownership
```

Commerce middleware phải resolve:

```text
authenticated_customer_user_id
authenticated_storefront_id
```

## 10.4 Address ownership

Model:

```text
App\Models\Customer\CustomerShippingAddress
table: customer_shipping_addresses
owner: customer_user_id
```

Strict authorization:

```text
WHERE customer_user_id = authenticated_customer_user_id
```

Không dùng phone fallback trong authenticated API.

Guest checkout phải tạo/match `CustomerUser` guest trước khi gắn address.

---

# 11. Quote, reservation và order aggregate

## 11.1 Hiện chưa có Commerce Quote canonical

Chưa có production modules:

```text
commerce_quotes
commerce_quote_items
commerce_stock_reservations
CommerceQuoteService
CommerceOrder aggregate
```

Giai đoạn đầu có thể optimistic:

```text
create quote
→ check price + stock
→ short TTL
→ commit rechecks price + stock
→ fail/requote if changed
```

Giai đoạn đầy đủ cần reservation owner và release:

```text
commerce_quotes
commerce_quote_items
commerce_stock_reservations
expires_at
released_at
committed_at
```

Không update `product_stock_availabilities.reserved` trực tiếp nếu chưa có ownership/release contract.

## 11.2 Provider writer hiện có

```text
App\Services\KiotViet\Public\KiotVietOrderService
```

Reusable primitives:

```text
createOrder()
fetchDetail()
fetchDetailByCode()
syncOrder()
cancelOrder()
```

Nó là provider adapter, không phải Commerce boundary.

## 11.3 Không gọi createOrder trực tiếp từ API

Hiện thiếu:

```text
idempotency key
local order intent
quote binding
reprice
stock recheck
ambiguous timeout handling
provider reconciliation
customer/storefront ownership
attribution snapshot
```

`kiotviet_orders` là provider mirror, chưa phải local commerce order aggregate.

Cần:

```text
Commerce Order
→ Commerce Order Items
→ Provider Attempt
→ KiotViet Order Mirror
```

State machine:

```text
created
validated
committing_provider
provider_created
provider_unknown
failed_retryable
failed_terminal
canceled
```

## 11.4 Idempotency

Unique logical key:

```text
storefront_id + idempotency_key
```

Nếu provider timeout/unknown:

```text
status = provider_unknown
```

Không retry create ngay.

Reconcile bằng:

- Deterministic local order reference trong provider description.
- Customer.
- Branch.
- Time window.
- Exact line fingerprint.
- Total.
- `fetchDetail`/`fetchDetailByCode`.
- Recent order sync.

Chỉ retry create sau khi xác nhận provider chưa có đơn.

---

# 12. Shipping

Hiện chưa có shipping resolver canonical.

`30.000đ` và surcharge `FS` chỉ là fallback hiện hành, không phải source of truth.

Cần server-side resolver theo:

```text
storefront
connection
branch
location
ward
shipping method
COD/non-COD
cart weight/value nếu cần
effective time
```

Client chỉ gửi:

```json
{
  "address_id": "address_2",
  "shipping_method": "standard"
}
```

ERP trả shipping fee trong quote.

---

# 13. Attribution / 3MADS / Meta

Best current source:

```text
erp_v2_marketing_meta_launch_items
```

Fields:

```text
tracking_ref
research_set_id
selected_media_id
meta_campaign_id
meta_adset_id
meta_creative_id
meta_ad_id
public_mark_code
lookup_suffix
```

`tracking_ref` có unique index và phù hợp làm đầu mối attribution.

Storefront giữ signed opaque tokens:

```text
first_touch_token
last_touch_token
```

ERP resolve token thành campaign/adset/creative/ad/launch item và lưu snapshot vào local Commerce Order.

Không nhận raw Meta IDs từ browser làm truth.

---

# 14. API boundary đề xuất

```text
GET  /commerce/v2/sites/{site}/bootstrap

GET  /commerce/v2/sites/{site}/catalog/products
GET  /commerce/v2/sites/{site}/catalog/products/{productPublicId}
GET  /commerce/v2/sites/{site}/catalog/collections
GET  /commerce/v2/sites/{site}/catalog/search

POST /commerce/v2/sites/{site}/checkout/quotes
POST /commerce/v2/sites/{site}/orders

GET   /commerce/v2/customer/profile
PATCH /commerce/v2/customer/profile
GET   /commerce/v2/customer/addresses
POST  /commerce/v2/customer/addresses
PATCH /commerce/v2/customer/addresses/{addressPublicId}
DELETE /commerce/v2/customer/addresses/{addressPublicId}

GET   /commerce/v2/customer/orders
GET   /commerce/v2/customer/orders/{orderPublicId}
```

## 14.1 Quote input

```json
{
  "site": "linxen",
  "items": [
    {
      "sellable_sku_id": "sku_54369629",
      "quantity": 1
    }
  ],
  "shipping_address_id": "address_2",
  "shipping_method": "standard",
  "payment_method": "cod",
  "attribution": {
    "first_touch_token": "att_first_xxx",
    "last_touch_token": "att_last_xxx"
  }
}
```

## 14.2 Order input

```json
{
  "quote_id": "qt_xxx",
  "idempotency_key": "ordreq_xxx",
  "payment_method": "cod"
}
```

Order commit không nhận lại price, stock, totals hay shipping fee.

---

# 15. Example mapping thực tế

Research Set:

```text
research_set_id = 4451
rs_code         = RS260522002
name            = Camila – Váy Mini Cổ Tròn Tay Cộc Nhún 2 Bên Hông
```

Runtime context:

```text
mapped SKU range = SP14546152 ... SP14546175
price             = 799000
stock snapshot    = 64
```

Ví dụ màu Kem, size M:

```text
Research Set          4451 / RS260522002
Variant Group         1891
Color                 cream / Kem
Size                  M
KiotViet product_id   54369629
SKU                   SP14546158
Price                 799000
Branch                332318
On hand               5
Reserved              0
Available             5
```

Correct checkout line:

```json
{
  "sellable_sku_id": "sku_54369629",
  "quantity": 1
}
```

ERP resolve lại RS, color, size, price, stock và provider ID.

---

# 16. Services/source nên tái sử dụng

| Source | Vai trò | Cách dùng |
|---|---|---|
| `app/Services/Pricing/PriceResolverService.php` | Price truth | Tái sử dụng với `channel=storefront` |
| `CustomerMessagingStockContextBuilder.php` | Tham chiếu mapping variant/stock | Extract thành shared Commerce stock/SKU service |
| `ProductAdvisorController.php` | Tham chiếu product projection | Không gọi controller; extract service |
| `POSV2Controller.php` | Tham chiếu Variant Group/size/SKU | Không dùng controller làm API |
| `AdImageCodeResolverService.php` | Ad code/launch/media → RS | Dùng cho landing/attribution |
| `KiotVietPublicConnection` | Connection + branch | Tái sử dụng |
| `KiotVietPublicAuthService` | Provider token | Tái sử dụng |
| `KiotVietCustomerService` | Match/create provider customer | Tái sử dụng sau customer adapter |
| `KiotVietOrderService` | Provider adapter | Bao bọc bằng canonical order writer |
| `CustomerMessagingOrderRequestService` | Pattern locking/state | Tham khảo; không dùng trực tiếp cho web checkout |

---

# 17. Modules bắt buộc phải xây

ERP V2:

```text
StorefrontScopeResolver
CommerceCatalogProjectionService
CommerceSellableSkuResolver
CommercePriceService
CommerceBranchStockService
CommercePublicMediaResolver
CommerceCustomerIdentityAdapter
CommerceCustomerAuthService
CommerceShippingQuoteService
CommerceQuoteService
CommerceStockReservationService
CommerceOrderIntentService
CanonicalKiotVietOrderWriter
CommerceOrderReconciliationService
CommerceAttributionService
```

Suggested tables, subject to explicit approval before migration:

```text
storefront_commerce_scopes
commerce_quotes
commerce_quote_items
commerce_stock_reservations
commerce_orders
commerce_order_items
commerce_provider_attempts
commerce_order_attributions
```

Do not create migration until the user explicitly approves schema.

---

# 18. Recommended implementation phases

## Phase 0 — Contract and source audit

- Confirm model/table paths.
- Confirm brand 7 record.
- Confirm product ID versus provider ID mapping.
- Confirm `storefront_commerce_scopes` design.
- Freeze API public IDs and error format.

## Phase 1 — Read-only Catalog API

Build:

```text
site/bootstrap
catalog/products
catalog/products/{id}
catalog/search
```

Include:

- RS projection.
- Active colors.
- Exact sizes/SKUs.
- Storefront price.
- Branch-scoped stock.
- Strict approved sale media.

No checkout yet.

## Phase 2 — Storefront UI shell

In `/var/www/linxen`:

- Home/grid.
- Swipe feed.
- PDP.
- Color/size selection.
- Cart local UI.
- API client with typed errors/cache.

## Phase 3 — Customer/auth/address

- Customer session boundary.
- Guest customer.
- Member upgrade.
- Strict address ownership.
- No phone headers.

## Phase 4 — Quote and shipping

- Quote TTL.
- Server totals.
- Stock/price revalidation.
- Shipping resolver.
- Requote responses.

## Phase 5 — Order + idempotency

- Local order aggregate.
- Provider attempts.
- KiotViet writer.
- Ambiguous timeout state.
- Reconciliation worker.
- Ownership-safe order list/detail.

## Phase 6 — Attribution and campaigns

- `/go/{token}`.
- First/last touch.
- Launch item snapshot.
- Order attribution.
- 3MADS integration.

## Phase 7 — Reservation and scale

- Stock reservation.
- Search index.
- Collection engine V2.
- CDN/cache invalidation.
- Observability and performance.

---

# 19. Acceptance criteria

## Catalog

- No legacy Storefront controller called.
- Listing keyed by RS public ID.
- Color keyed by Variant Group.
- Size maps to exact SKU.
- All stock filtered by branch 332318.
- All prices resolved with channel storefront.
- All media pass strict public gate.

## Customer

- Profile/address/order endpoints require resolved CustomerUser.
- No ownership by phone header.
- Mixed KiotViet customer mapping handled safely.

## Quote

- Client cannot set price/shipping/total.
- Quote has expiry.
- Commit revalidates price and stock.
- Changed price/stock returns requote, not silent mutation.

## Order

- Same idempotency key never creates two provider orders.
- Provider timeout becomes `provider_unknown`.
- Reconciliation runs before retry.
- Local order stores customer/storefront/quote/attribution ownership.
- KiotViet mirror is not the only local order state.

## Security

- Storefront scope cannot query another brand/connection.
- Address/order detail cannot be read by phone/code guessing.
- Provider secrets never reach Storefront.
- Raw Meta IDs from client are not trusted.

---

# 20. Patch/deployment rules for the build chat

- Debug commands/scripts: send inline in chat.
- Downloadable `.sh`: only for actual source/deployment patches.
- ERP patches go to `sh/`.
- Storefront patches go to `lxsh/`.
- Patch must run from repo root and check required root files.
- State clearly: local, production or both.
- State prerequisites and recommended order.
- State effects:
  - migration,
  - cache clear,
  - provider calls,
  - DB/business mutation.
- Do not run migration unless explicitly approved.
- No `.bak/.backup/.orig` beside source.
- Backups go under:

```text
storage/app/ai_patch_backups/<patch_name>_<timestamp>/
```

- Add rollback trap.
- Run `php -l` for PHP.
- Targeted Blade compile if Blade changes.
- `view:clear` and `optimize:clear` only after syntax checks pass.
- Do not claim completion if required checks did not pass.

---

# 21. Source evidence index

Primary audit contexts used to build this handoff:

```text
0000_UPLOAD_FIRST_8215834512_20260716_083127_ai_patch_context_
audit-canonical-erp-v2-commerce-model-cho-lin-xen-storefront-v2-....md

0000_UPLOAD_FIRST_8215833909_20260716_084130_ai_patch_context_
doc-not-cac-boundary-con-thieu-de-thiet-ke-lin-xen-commerce-v2-....md
```

Important source paths:

```text
app/Http/Controllers/ErpV2/Sales/ProductAdvisorController.php
app/Http/Controllers/Sales/POSV2Controller.php
app/Services/Pricing/PriceResolverService.php
app/Services/ErpV2/Marketing/CustomerMessaging/CustomerMessagingStockContextBuilder.php
app/Services/ErpV2/Marketing/CustomerMessaging/CustomerMessagingPriceResolverService.php
app/Services/ErpV2/Marketing/CustomerMessaging/CustomerMessagingOrderRequestService.php
app/Services/ErpV2/Marketing/AdImageCodeResolverService.php

app/Models/KiotViet/KiotVietPublicConnection.php
app/Models/Customer/CustomerUser.php
app/Models/Customer/CustomerShippingAddress.php
app/Services/KiotViet/Public/KiotVietPublicAuthService.php
app/Services/KiotViet/Public/KiotVietCustomerService.php
app/Services/KiotViet/Public/KiotVietOrderService.php
config/kiotviet.php
```

---

# 22. Open issues — do not guess

- Exact CustomerUser auth/OTP implementation.
- Final schema of site commerce scope.
- Shipping business rules.
- Reservation policy and TTL.
- Brand 7 full record/category allowlist.
- Public media stale/superseded contract.
- Product ID/provider ID opaque token implementation.
- Collection/search index V2.
- Provider reconciliation matching strategy details.

These are modules/decisions still to build, not reasons to return to V1.

---

# 23. First response required from the new build chat

The new build chat must respond with:

1. Confirmation that this document was fully read.
2. A concise restatement of the canonical chain:

```text
Research Set
→ Product Variant Group
→ Variant Group Item
→ exact KiotViet sellable SKU
```

3. Confirmation that legacy Storefront V1 APIs will not be used as truth.
4. Proposed phase to implement first.
5. Exact repo affected:
   - ERP `/var/www/3mgate`
   - Storefront `/var/www/linxen`
   - or both.
6. Source files it needs to inspect before patching.
7. Whether migration is required.
8. Runtime effects and tests.
9. No patch until the above is clear.

---

# 24. Canonical one-line summary

> Lin Xén V2 is a mobile-first storefront backed by an ERP-owned Commerce Product Projection keyed by Research Set; colors are Product Variant Groups, sizes bind to exact KiotViet sellable SKUs, price/stock/media are server-resolved under the Lin Xén site scope, and checkout must use a local quote/order/idempotency/reconciliation boundary before writing to KiotViet.
