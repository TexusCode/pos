<?php

namespace App\Livewire;

use Auth;
use Flux\Flux;
use App\Models\Product;
use Livewire\Component;
use App\Models\AuditItem;
use Illuminate\Support\Facades\DB;
use App\Models\Audit as ModelAudit;

class Audit extends Component
{
    public $audit = true;
    public $selectedAudit;
    public $auditName;
    public $auditNote;
    public $notPrSc = false;
    public $skuPr;
    public $quantityPr;
    public $namePr;
    public $pricePr;
    public $product;
    public $auditModal = true;
    public function skuCheck()
    {
        $product = Product::where('sku', $this->skuPr)->first();
        if ($product) {
            $audit = AuditItem::where('product_id', $product->id)->where('audit_id', $this->selectedAudit->id)->first();
            if ($audit) {
                Flux::toast(
                    heading: 'Ошибка',
                    text: 'Товар уже есть в списке продолжайте!',
                    variant: 'danger',
                    duration: 5000,
                );
                return;
            }
            $this->auditModal = false;
            $this->product = $product;
        } else {
            $this->auditModal = false;
            $this->notPrSc = true;
        }
    }
    public function addAuditItem()
    {
        if ($this->product) {
            AuditItem::create([
                'audit_id' => $this->selectedAudit->id,
                'product_id' => $this->product->id,
                'user_id' => Auth::id(),
                'old_quantity' => $this->product->quantity,
                'new_quantity' => $this->quantityPr,
                'difference' => $this->quantityPr - $this->product->quantity,
            ]);
        } else {
            $product = Product::create([
                'sku' => $this->skuPr,
                'name' => $this->namePr,
                'quantity' => $this->quantityPr,
                'selling_price' => $this->pricePr,
            ]);
            AuditItem::create([
                'audit_id' => $this->selectedAudit->id,
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'old_quantity' => $product->quantity,
                'new_quantity' => $this->quantityPr,
                'difference' => $this->quantityPr - $product->quantity,
            ]);
        }
        return redirect()->route('audit');

    }
    public function addAudit()
    {
        $audit = ModelAudit::create([
            'name' => $this->auditName,
            'note' => $this->auditNote ?? null,
            'audit_date' => now(),
            'user_id' => Auth::id(),
            'status' => 'open',
        ]);

        return redirect()->route('audit');

    }
    public function mount()
    {
        $audit = ModelAudit::latest()->first();

        if ($audit && $audit->status == 'open') {
            $this->audit = false;
            $this->selectedAudit = $audit;
        } else {
            $this->auditName = Auth::user()->name . ' - ' . now()->format('d.m.Y');
        }
    }
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');

    }
    public function closeAudit()
    {
        DB::transaction(function () {
            $allProducts = Product::all();

            foreach ($allProducts as $product) {
                $auditItem = AuditItem::where('audit_id', $this->selectedAudit->id)
                    ->where('product_id', $product->id)
                    ->first();
                if (!$auditItem) {
                    AuditItem::create([
                        'audit_id' => $this->selectedAudit->id,
                        'product_id' => $product->id,
                        'user_id' => Auth::id(), // Записываем ID пользователя, который закрывает аудит
                        'old_quantity' => $product->quantity, // Текущее количество товара до аудита
                        'new_quantity' => 0, // Новое количество после аудита (если не учтен, значит 0)
                        'difference' => 0 - $product->quantity, // Разница (0 - старое количество)
                    ]);
                }
            }

            $this->selectedAudit->status = 'closed';
            $this->selectedAudit->save();

            foreach ($this->selectedAudit->auditItems as $auditItem) {
                $product = Product::find($auditItem->product_id);
                if ($product) {
                    $product->quantity = $auditItem->new_quantity;
                    $product->save();
                }
            }
        });
    }

    public function render()
    {
        return view('livewire.audit');
    }
}
