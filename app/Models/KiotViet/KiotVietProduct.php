<?php

namespace App\Models\KiotViet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse\WarehouseLocationItem;

class KiotVietProduct extends Model
{
    protected $table = 'kiotviet_products';

    protected $fillable = [
        'product_id',
        'kiotviet_id',
        'connection_id',
        'brand_id',
        'parent_id',
        'code',
        'name',
        'full_name',
        'base_price',
        'retail_price',
        'unit',
        'barcode',
        'category',
        'category_id',
        'has_variants',
        'is_master',
        'description',
        'raw_data',
        'master_kv_id',
        'trademark_id',
        'trademark_name',
    ];

    protected $casts = [
        'raw_data'      => 'array',
        'is_master'     => 'boolean',
        'has_variants'  => 'boolean',
        'base_price'    => 'decimal:2',
        'retail_price'  => 'decimal:2',
    ];

    protected $appends = ['thumb_url', 'photos', 'on_hand'];

    /* =======================================================
     |  BOOT
     ======================================================= */
    protected static function booted()
    {
        static::saving(function ($model) {
            // Chuẩn hoá quan hệ master / variant
            if (!empty($model->master_kv_id)) {
                $model->parent_id = $model->master_kv_id;
                $model->is_master = false;
            } else {
                $model->parent_id = null;
                $model->is_master = true;
            }
        });
    }

    /* =======================================================
     |  VARIANT HELPERS (🔥 QUAN TRỌNG)
     ======================================================= */

    /**
     * Lấy SKU cha (master) trong mọi trường hợp
     */
    public function getMasterProduct(): self
    {
        return $this->is_master
            ? $this
            : ($this->parent ?? $this);
    }

    /**
     * Lấy danh sách SKU = [CHA + CON]
     */
    public function getVariantSkus()
    {
        $master = $this->getMasterProduct();

        return collect([$master])
            ->merge($master->children ?? collect())
            ->unique('product_id')
            ->values();
    }

    /**
     * Resolve variants + attributes cho Storefront / POS / ERP
     */
    public function resolveVariants(): array
    {
        $variants   = [];
        $attributes = [];

        foreach ($this->getVariantSkus() as $sku) {

            // Nguồn chuẩn attributes
            $attrs = DB::table('kiotviet_product_attributes')
                ->where('product_id', $sku->product_id)
                ->pluck('attribute_value', 'attribute_name')
                ->toArray();

            $variants[] = [
                'id'        => $sku->id,
                'sku'       => $sku->code,
                'price'     => $sku->retail_price ?? $sku->base_price ?? 0,
                'stock'     => $sku->on_hand ?? 0,
                'attrs'     => $attrs,               // có thể []
                'is_master' => (bool) $sku->is_master,
            ];

            foreach ($attrs as $k => $v) {
                if ($v) {
                    $attributes[$k][] = $v;
                }
            }
        }

        // unique attribute values
        foreach ($attributes as $k => $vals) {
            $attributes[$k] = array_values(array_unique($vals));
        }

        return [
            'variants'   => $variants,
            'attributes' => $attributes,
        ];
    }

    /* =======================================================
     |  ACCESSORS
     ======================================================= */

    public function getOnHandAttribute()
    {
        $branchId = config('kiotviet.default_branch_id');

        return $this->inventories
            ->firstWhere('branch_id', $branchId)
            ->on_hand ?? 0;
    }

    public function getThumbUrlAttribute()
    {
        return $this->getMedia()['thumb'];
    }

    public function getPhotosAttribute()
    {
        return $this->getMedia()['photos'];
    }

    /* =======================================================
     |  MEDIA
     ======================================================= */

    public function getMedia(): array
    {
        // 1️⃣ Research Set
        $rsId = DB::table('kiotviet_product_research_map')
            ->where('product_id', $this->product_id)
            ->value('research_set_id');

        if ($rsId) {
            $rsMedia = \App\Models\Design\ResearchMedia::where('research_set_id', $rsId)
                ->select(['file_path', 'thumbnail_path', 'created_at'])
                ->orderBy('created_at', 'asc')
                ->get();

            if ($rsMedia->isNotEmpty()) {
                $mediaDomain = rtrim(config('services.media_api.url', ''), '/');
                $thumbPath  = $rsMedia[0]->thumbnail_path ?: $rsMedia[0]->file_path;

                $thumb = $mediaDomain
                    ? $mediaDomain . '/storage/' . ltrim($thumbPath, '/')
                    : asset('storage/' . ltrim($thumbPath, '/'));

                $photos = $rsMedia->map(function ($m) use ($mediaDomain) {
                    return $mediaDomain
                        ? $mediaDomain . '/storage/' . ltrim($m->file_path, '/')
                        : asset('storage/' . ltrim($m->file_path, '/'));
                })->toArray();

                return [
                    'thumb'  => $thumb,
                    'photos' => $photos,
                ];
            }
        }

        // 2️⃣ Fallback ảnh KiotViet
        $kvPhotos = $this->images()->pluck('url')->toArray();
        if (!empty($kvPhotos)) {
            return [
                'thumb'  => $kvPhotos[0],
                'photos' => $kvPhotos,
            ];
        }

        // 3️⃣ Không có ảnh
        return [
            'thumb'  => asset('images/no-image.png'),
            'photos' => [],
        ];
    }

    /* =======================================================
     |  RELATIONS
     ======================================================= */

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'kiotviet_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'kiotviet_id');
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand\Brand::class, 'brand_id');
    }

    public function inventories()
    {
        return $this->hasMany(
            KiotVietProductInventory::class,
            'product_id',
            'product_id'
        );
    }

    public function attributes()
    {
        return $this->hasMany(
            KiotVietProductAttribute::class,
            'product_id',
            'product_id'
        );
    }

    public function images()
    {
        return $this->hasMany(
            KiotVietProductImage::class,
            'product_id',
            'product_id'
        );
    }

    public function locationItems()
    {
        return $this->hasMany(
            WarehouseLocationItem::class,
            'product_id',
            'product_id'
        );
    }
}
