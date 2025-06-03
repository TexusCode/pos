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
    public $mergente = null;
    public $auditModal = true;
    public function skuCheck()
    {
        $product = Product::where('sku', $this->skuPr)->first();
        if ($product) {
            $audit = AuditItem::where('product_id', $product->id)->where('audit_id', $this->selectedAudit->id)->first();
            if ($audit) {
                $this->mergente = '1';
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
            if ($this->mergente == '1') {
                $this->product->quantity += $this->quantityPr;
                $this->product->save();
            } else {
                $this->product->quantity = $this->quantityPr;
                $this->product->save();
            }
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
            // 1. Обработка продуктов, которые не были учтены в аудите
            $allProducts = Product::all();

            foreach ($allProducts as $product) {
                $auditItem = AuditItem::where('audit_id', $this->selectedAudit->id)
                    ->where('product_id', $product->id)
                    ->first();

                // Если продукт не был найден в текущем аудите
                if (!$auditItem) {
                    AuditItem::create([
                        'audit_id' => $this->selectedAudit->id,
                        'product_id' => $product->id,
                        'user_id' => Auth::id(),
                        'old_quantity' => $product->quantity,
                        'new_quantity' => 0,
                        'difference' => 0 - $product->quantity, // Разница (старое количество - 0) будет отрицательной
                    ]);
                }
            }

            // 2. Рассчитываем общие показатели недостачи
            // Убедитесь, что 'auditItems' загружены.
            // Если вы не перенаправляете сразу после этой функции,
            // и auditItems могут быть не загружены в $this->selectedAudit,
            // лучше загрузить их явно для расчета.
            $this->selectedAudit->load('auditItems');

            $negativeAuditItems = $this->selectedAudit->auditItems->where('difference', '<', 0);

            // Общее количество позиций с недостачей
            $this->selectedAudit->total_negative_items_count = $negativeAuditItems->count();

            // Общая сумма недостачи (берем абсолютное значение)
            // Важно: если difference - это количество штук, то decimal(8,2) для суммы может быть не лучшим выбором,
            // возможно, int или float подойдет лучше, или пересчитайте difference в деньги.
            // Сейчас он суммирует количество штук.
            $this->selectedAudit->total_negative_difference_sum = abs($negativeAuditItems->sum('difference'));

            $totalNegativeValue = 0;
            foreach ($negativeAuditItems as $item) {
                // Убедитесь, что $item->product существует и у него есть поле 'price'
                if ($item->product && isset($item->product->selling_price)) {
                    $totalNegativeValue += abs($item->difference) * $item->product->selling_price;
                }
            }
            $this->selectedAudit->total_negative_value_sum = $totalNegativeValue;
            // 3. Обновляем статус аудита и сохраняем все вычисленные поля
            $this->selectedAudit->status = 'closed';
            $this->selectedAudit->save(); // Сохраняем все изменения в модели Audit (включая новые поля)

            // 4. Обновляем реальное количество товаров на складе после аудита
            foreach ($this->selectedAudit->auditItems as $auditItem) {
                $product = Product::find($auditItem->product_id);
                if ($product) {
                    $product->quantity = $auditItem->new_quantity;
                    $product->save();
                }
            }

        }); // Конец транзакции
        Auth::logout();
        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.audit');
    }
}
