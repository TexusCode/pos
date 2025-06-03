<?php

namespace App\Livewire;

use App\Models\Buy;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Expence;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnOrder;
use App\Models\ReturnOrderItem;
use Carbon\Carbon;
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
    public $selecteditemdiscount = null;
    public $discounttotal;
    public $discountmodel;
    public $discounttype = 'Фикц';
    public $discountItemModal = false;
    public $discountAllModal = false;
    public $checkoutModal = false;
    public $total;
    public $subtotal;
    public $shift;
    public $selectedCart;
    public $carts;
    public $cash;
    public $name;
    public $phone;
    public $note;
    public $returnModal = false;
    public $debtModal = false;
    public $debtPaymentModal = false;
    public $paymentType = 'Наличными';
    public $paymentTypeDebt = 'Наличными';
    public $phoneDebt;
    public $cashDebt;
    public $customerDebt;
    public $shiftModal = false;
    public $nallCassa;
    public $addProductModal = false;
    public $addProductSection = false;
    public $issetPr = false;
    public $skuPr;
    public $namePr;
    public $selling_pricePr;
    public $quantityPr;
    public $ProductPr;
    public $expenceModal = false;
    public $expenceModel;
    public $expenceDescModel;

    public function addExpenceModal()
    {
        $this->expenceModal = true;
    }
    public function addExpence()
    {
        Expence::create([
            'shift_id' => $this->shift->id,
            'total' => $this->expenceModel,
            'description' => $this->expenceDescModel,
        ]);
        $this->expenceModal = false;
        $this->reset([
            'expenceModel',
            'expenceDescModel',
        ]);
    }
    public function updatedSkuPr()
    {
        $this->reset([
            'addProductSection',
            'issetPr',
            'namePr',
            'selling_pricePr',
            'quantityPr',
            'ProductPr',
        ]);
        $product = Product::where('sku', $this->skuPr)->first();
        if ($product) {
            $this->issetPr = true;
            $this->ProductPr = $product;

        } else {
            $this->issetPr = true;
            $this->addProductSection = true;
        }
    }
    public function addPRoductForm()
    {
        if ($this->ProductPr) {
            $this->ProductPr->quantity += $this->quantityPr;
            $this->ProductPr->save();
            Buy::create([
                'product_id' => $this->ProductPr->id,
                'quantity' => $this->quantityPr,
            ]);
            Flux::toast(
                heading: 'Успешно',
                text: 'Товар успешно обновлено!',
                variant: 'success',
                duration: 5000,
            );
        } else {
            $product = Product::create([
                'sku' => $this->skuPr,
                'name' => $this->namePr,
                'quantity' => $this->quantityPr,
                'selling_price' => $this->selling_pricePr,
            ]);
            Buy::create([
                'product_id' => $product->id,
                'quantity' => $this->quantityPr,
            ]);
            Flux::toast(
                heading: 'Успешно',
                text: 'Товар успешно добавлено!',
                variant: 'success',
                duration: 5000,
            );
        }
        $this->reset([
            'addProductSection',
            'issetPr',
            'skuPr',
            'namePr',
            'selling_pricePr',
            'quantityPr',
            'ProductPr',
        ]);
    }
    public function addProductModalTrue()
    {
        $this->addProductModal = true;
    }
    public function closeShiftModal()
    {
        $this->shiftModal = true;
    }
    public function closeShift()
    {
        $debttotal = 0;
        foreach ($this->shift->debt as $debt) {
            if ($debt->type == 'Браль') {
                $debttotal += $debt->total;
            }
        }
        $subtotal = $this->shift->orders->sum('sub_total_amount');
        $total = $this->shift->orders->sum('total_amount');
        $discounts = $this->shift->orders->sum('discount_amount');
        $expence = $this->shift->expences->sum('total');
        $this->shift->final_cash = $this->nallCassa;
        $this->shift->sub_total = $subtotal;
        $this->shift->total = $total - $expence;
        $this->shift->expence = $expence;
        $this->shift->debts = $debttotal;
        $this->shift->discounts = $discounts;
        $this->shift->end_time = now();
        $this->shift->status = 'closed';
        $this->shift->save();
        Cart::truncate();
        CartItem::truncate();
        return redirect()->route('shift');
    }
    public function payDebtModalTrue()
    {
        $this->debtPaymentModal = true;
    }
    public function payDebt()
    {
        if ($this->customerDebt) {
            Debt::create([
                'customer_id' => $this->customerDebt->id,
                'shift_id' => $this->shift->id,
                'total' => $this->cashDebt,
                'type' => 'Вернуль',
            ]);
            $this->customerDebt;
            $this->customerDebt->debt -= $this->cashDebt;
            $this->customerDebt->save();
        } else {
            $customer = Customer::create([
                'name' => Carbon::now(),
                'phone' => $this->phoneDebt,
            ]);
            Debt::create([
                'customer_id' => $customer->id,
                'shift_id' => $this->shift->id,
                'total' => $this->cashDebt,
                'type' => 'Вернуль',
            ]);
        }
    }
    public function updatedPhoneDebt()
    {
        $customer = Customer::where('phone', $this->phoneDebt)->first();
        if ($customer) {
            $this->cashDebt = $customer->debt;
            $this->customerDebt = $customer;
        } else {
            $this->cashDebt = null;
            $this->customerDebt = null;
        }
    }
    public function returnModalTrue()
    {
        if ($this->selectedCart->items->count() >= 1) {
            $this->returnModal = true;
        } else {
            Flux::toast(
                heading: 'Ошибка',
                text: 'Нет товары в корзину!',
                variant: 'danger',
                duration: 5000,
            );
        }
    }
    public function orderReturn()
    {
        $return = ReturnOrder::create([
            'shift_id' => $this->shift->id,
            'total' => $this->total,
            'discount' => $this->discounttotal,
        ]);
        foreach ($this->selectedCart->items as $item) {
            ReturnOrderItem::create([
                'return_order_id' => $return->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->selling_price,
                'discount' => $item->discount,
                'subtotal' => $item->quantity * $item->product->selling_price,
            ]);
        }
        $this->truncate();
        return redirect()->route('pos');
    }
    public function checkout()
    {
        if ($this->name && $this->phone) {
            $customer = Customer::where('phone', $this->phone)->first();
            $total = $this->total - $this->cash;
            if ($customer) {
                $customer->debt += $total;
                $customer->save();
            } else {
                $customer = Customer::create([
                    'name' => $this->name,
                    'phone' => $this->phone,
                    'debt' => $total,
                ]);

            }

        }
        if ($this->paymentType == 'В долг') {
            $payment = 'debt';
        } else {
            $payment = 'paid';
        }
        $order = Order::create([
            'customer_id' => $customer->id ?? null,
            'total_amount' => $this->total,
            'sub_total_amount' => $this->subtotal,
            'discount_amount' => $this->discounttotal,
            'payment_method' => $this->paymentType,
            'shift_id' => $this->shift->id,
            'payment_status' => $payment,
            'notes' => $this->note ?? null,
        ]);
        foreach ($this->selectedCart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->selling_price,
                'discount' => $item->discount,
                'subtotal' => $item->quantity * $item->product->selling_price,
            ]);
        }
        if ($this->paymentType == 'В долг') {
            Debt::create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'shift_id' => $this->shift->id,
                'total' => $total,
            ]);
        }
        $this->truncate();
        return redirect()->route('pos');
    }
    public function updatedPaymentType()
    {
        if ($this->paymentType == 'В долг') {
            $this->debtModal = true;
        } else {
            $this->debtModal = false;

        }
    }
    public function closeCheckoutModal()
    {
        $this->checkoutModal = false;
        $this->debtPaymentModal = false;
        $this->shiftModal = false;
        $this->addProductModal = false;
        $this->expenceModal = false;
        $this->reset([
            'addProductSection',
            'issetPr',
            'skuPr',
            'namePr',
            'selling_pricePr',
            'quantityPr',
            'ProductPr',
            'expenceModel',
            'expenceDescModel',
        ]);
    }
    public function openCheckoutModal()
    {
        if ($this->selectedCart->items->count() >= 1) {
            $this->checkoutModal = true;
        } else {
            Flux::toast(
                heading: 'Ошибка',
                text: 'Нет товары в корзину!',
                variant: 'danger',
                duration: 5000,
            );
        }
    }
    public function discountitem($id)
    {
        $item = CartItem::find($id);
        $this->selecteditemdiscount = $item;
        $this->discountAllModal = true;
    }
    public function discount_all()
    {
        if ($this->selecteditemdiscount) {

            $selecteditemdiscount = CartItem::find($this->selecteditemdiscount->id);
        }
        if (isset($selecteditemdiscount)) {
            $itemDiscountValue = 0;

            if ($this->discounttype == 'Фикц') {
                $selecteditemdiscount->discount = (float) $this->discountmodel;
            } else {
                $itemLineTotalBeforeDiscount = $selecteditemdiscount->quantity * $selecteditemdiscount->product->selling_price;
                $percentage = (float) $this->discountmodel;
                $itemDiscountValue = ($itemLineTotalBeforeDiscount * $percentage) / 100;
                $selecteditemdiscount->discount = $itemDiscountValue;
            }

            $selecteditemdiscount->save();
            $this->calc(); // Обязательно пересчитайте все суммы корзины после изменения скидки на товар
            $this->selecteditemdiscount = null;
        } else {
            $cartDiscountAmount = 0;

            if ($this->discounttype == 'Фикц') {
                $cartDiscountAmount = $this->discountmodel;
            } else {
                $percentage = (float) $this->discountmodel; // Ensure it's a number
                $cartDiscountAmount = ($this->subtotal * $percentage) / 100;
            }
            $cart = Cart::find($this->selectedCart->id);
            $cart->discount = $cartDiscountAmount;
            $cart->save();
        }
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

        if ($this->selectedCart->items->count() >= 1) {
            $cart = Cart::create([
                'user_id' => Auth::id(),
                'discount' => 0,
                'subtotal' => 0,
                'total' => 0,
            ]);
            $this->selectedCart = $cart;
            $this->mount();
        } else {
            Flux::toast(
                heading: 'Ошибка',
                text: 'Нет товары в корзину!',
                variant: 'danger',
                duration: 5000,
            );
        }
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
                    $itemDiscount = $item->discount;
                    $totalDiscountAmount += $itemDiscount; // Накапливаем общую сумму скидки
                }
            }
        }
        if ($this->selectedCart->discount) {
            $totalDiscountAmount = ($totalDiscountAmount + $this->selectedCart->discount);
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
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');

    }
    public function render()
    {
        return view('livewire.pos');
    }
}
