<?php namespace Bedard\Shop\Models;

use Bedard\Shop\Models\Customer;
use Bedard\Shop\Models\Order;
use Cookie;
use DB;
use Model;
use Session;

/**
 * Cart Model
 */
class Cart extends Model
{

    /**
     * @var string  The database table used by the model.
     */
    public $table = 'bedard_shop_carts';

    /**
     * @var array   Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array   Fillable fields
     */
    protected $fillable = ['key'];

    /**
     * @var array   Relations
     */
    public $belongsTo = [
        'coupon' => ['Bedard\Shop\Models\Coupon', 'table' => 'bedard_shop_coupons']
    ];
    public $hasMany = [
        'items' => ['Bedard\Shop\Models\CartItem', 'table' => 'bedard_shop_cart_items']
    ];

    /**
     * Query Scopes
     */
    public function scopeIsComplete($query)
    {
        $query->whereNotNull('order_id');
    }

    /**
     * Ensures that cart quantities do not exceed their available inventories
     * @return  boolean
     */
    public function fixQuantities()
    {
        // Check if any inventories were invalid
        $fixQuantities = FALSE;
        foreach ($this->items as $item) {
            if ($item->quantity > $item->inventory->quantity) {
                $fixQuantities = TRUE;
            }
        }

        // Run a query to fix invalid quantities
        if ($fixQuantities) {
            $updated = DB::table('bedard_shop_cart_items AS item')
                ->join('bedard_shop_inventories AS inventory', 'item.inventory_id', '=', 'inventory.id')
                ->where('item.quantity', '>', DB::raw('`inventory`.`quantity`'))
                ->where('item.cart_id', '=', $this->id)
                ->update(['item.quantity' => DB::raw('`inventory`.`quantity`')]);

            // If anything was changed, update the relationship
            if ($updated > 0) {
                $this->load(['items' => function($cartItem) {
                    $cartItem->inCart();
                }]);
            }
        }

        return $fixQuantities;
    }

    /**
     * Completes a shopping cart
     * @param   Order   $order
     */
    public function markAsComplete(Order $order)
    {
        $this->load('items.inventory');
        foreach ($this->items as $item) {
            $item->inventory->quantity -= $item->quantity;
            $item->inventory->save();
        }

        $order->is_complete = true;
        $order->save();

        if (!$this->couponIsApplied || !isset($this->coupon->name))
            $this->coupon_id = null;
        else
            $this->backup_couponName = $this->coupon->name;

        $this->backup_total = $this->total;
        $this->backup_totalBeforecoupon = $this->totalBeforeCoupon;
        $this->backup_fullTotal = $this->fullTotal;
        $this->order_id = $order->id;
        $this->save();

        Session::forget('bedard_shop_order');
    }

    /**
     * Checks if the coupon is being applied or not
     * @return  boolean
     */
    public function getCouponIsAppliedAttribute()
    {
        return $this->totalBeforeCoupon > $this->total;
    }

    /**
     * Returns the value of the applied
     * @return  float
     */
    public function getCouponValueAttribute()
    {
        return $this->total - $this->totalBeforeCoupon;
    }

    /**
     * Determine if the cart is at full price or not
     * @return  boolean
     */
    public function getIsDiscountedAttribute()
    {
        return $this->total < $this->fullTotal;
    }

    /**
     * Returns the total value of the cart before discounts or promotions
     * @return  string (numeric)
     */
    public function getFullTotalAttribute()
    {
        if (!is_null($this->attributes['backup_fullTotal']))
            return $this->attributes['backup_fullTotal'];
        
        $fullTotal = 0;
        foreach ($this->items as $item)
            $fullTotal += $item->quantity * $item->fullPrice;
        return $fullTotal;
    }

    /**
     * Returns the total value of the cart
     * @return  string (numeric)
     */
    public function getTotalAttribute()
    {
        if (!is_null($this->attributes['backup_total']))
            return $this->attributes['backup_total'];
        
        $total = $this->totalBeforeCoupon;
        if (!empty($this->attributes['coupon_id']) && $this->coupon && $this->coupon->cart_value <= $total) {
            $total -= $this->coupon->is_percentage
                ? $total * ($this->coupon->amount / 100)
                : $this->coupon->amount;
            if ($total < 0) $total = 0;
        }
        return round($total, 2);
    }

    /**
     * Returns the total value of the cart before coupons are applied
     * @return  string (numeric)
     */
    public function getTotalBeforeCouponAttribute()
    {
        if (!is_null($this->attributes['backup_totalBeforeCoupon']))
            return $this->attributes['backup_totalBeforeCoupon'];

        $totalBeforeCoupon = 0;
        foreach ($this->items as $item)
            $totalBeforeCoupon += $item->quantity * $item->price;
        return $totalBeforeCoupon;
    }

}