<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\Cart;
use App\Models\Shift;
use App\Models\Product;
use Livewire\Component;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class Pos extends Component
{
    public $products;
    public $barcode;
    public $discounttotal;
    public $discountmodel;
    public $discounttype = 'Проценть';
    public $discountItemModal = false;
    public $discountAllModal = true;
    public $total;
    public $subtotal;
    public $shift;
    public $selectedCart;
    public $carts;
    public function discount_all()
    {
        $cartDiscountAmount = 0;

        if ($this->discounttype == 'Фикц') {
            $cartDiscountAmount = (float) $this->discountmodel;
        } else {
            $percentage = (float) $this->discountmodel; // Ensure it's a number
            $cartDiscountAmount = ($this->subtotal * $percentage) / 100;
        }
        $cart = Cart::find($this->selectedCart->id);
        $cart->discount = $cartDiscountAmount;
        $cart->save();
        $this->discountAllModal = false;
        $this->discountmodel = null;
        $this->mount();
    }
    public function discountalltrue()
    {
        $this->discountAllModal = ($this->discountAllModal == true ? false : true);
    }
    public function addItemBarcode()
    {
        $product = Product::where('sku', $this->barcode)->first();
        if ($product) {
            $item = CartItem::where('product_id', $product->id)->first();
            if ($item) {
                $item->quantity += 1;
                $item->save();
            } else {
                CartItem::create([
                    'product_id' => $product->id,
                    'cart_id' => $this->selectedCart->id,
                    'discount' => '0',
                    'quantity' => '1',
                ]);
            }
        } else {
            Flux::toast(
                heading: 'Ошибка',
                text: 'Товар не найдено!',
                variant: 'danger',
                duration: 5000,
            );
        }
        $this->calc();
        $this->barcode = null;
    }
    public function selectCart($id)
    {
        $this->selectedCart = Cart::find($id);
        $this->calc();

    }
    public function hand()
    {
        $cart = Cart::create([
            'user_id' => Auth::id(),
            'discount' => 0,
            'subtotal' => 0,
            'total' => 0,
        ]);
        $this->selectedCart = $cart;
        $this->mount();
    }
    public function mount()
    {
        $this->carts = Cart::all();
        $cart = Cart::latest()->first();
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => Auth::id(),
                'discount' => 0,
                'subtotal' => 0,
                'total' => 0,
            ]);
        }

        $this->selectedCart = $cart;
        $this->shift = Shift::latest()->first();
        $this->loadItems();
        $this->calc();

    }
    public function loadItems()
    {
        $this->products = Product::all();
    }
    public function calc()
    {
        $rawSubtotal = 0;
        $totalDiscountAmount = 0;

        if ($this->selectedCart) {
            foreach ($this->selectedCart->items as $item) {
                $productSellingPrice = $item->product->selling_price;
                $rawSubtotal += ($productSellingPrice * $item->quantity);
                if (isset($item->discount) && $item->discount > 0) {
                    $itemDiscount = ($productSellingPrice * ($item->discount / 100)) * $item->quantity;
                    $totalDiscountAmount += $itemDiscount; // Накапливаем общую сумму скидки
                }
            }
        }
        if ($this->selectedCart->discount) {
            $totalDiscountAmount = $totalDiscountAmount + $this->selectedCart->discount;
        }

        $this->subtotal = $rawSubtotal;
        $this->discounttotal = $totalDiscountAmount;
        $total = $rawSubtotal - $totalDiscountAmount;

        $this->total = $total;
    }
    public function increment($id)
    {
        $item = CartItem::find($id);
        if ($item->product->quantity >= $item->quantity) {
            $item->quantity += 1;
            $item->save();
        }
        $this->calc();

    }
    public function deleteitem($id)
    {
        $item = CartItem::find($id)->delete();
        $this->calc();

    }
    public function decrement($id)
    {
        $item = CartItem::find($id);
        if ($item->quantity > 1) {
            $item->quantity -= 1;
            $item->save();
        }
        $this->calc();

    }
    public function truncate()
    {
        $items = CartItem::where('cart_id', $this->selectedCart->id)->delete();
        Cart::find($this->selectedCart->id)->delete();
        $this->mount();
        $this->calc();

    }
    public function addItemToCart($id)
    {
        $item = CartItem::where('product_id', $id)->where('cart_id', $this->selectedCart->id)->first();
        if ($item) {
            $item->quantity += 1;
            $item->save();
        } else {
            CartItem::create([
                'product_id' => $id,
                'cart_id' => $this->selectedCart->id,
                'discount' => '0',
                'quantity' => '1',
            ]);
        }
        $this->calc();

    }
    public function render()
    {
        return view('livewire.pos');
    }
}
