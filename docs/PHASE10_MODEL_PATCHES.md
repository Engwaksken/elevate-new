# Phase 10 required model patches

Add to `Quotation`:

```php
public function evaluation()
{
    return $this->hasOne(\App\Models\QuotationEvaluation::class);
}
```

Add to `PurchaseOrder`:

```php
public function purchaseRequest()
{
    return $this->belongsTo(\App\Models\PurchaseRequest::class);
}
```

Add to `AssetMaintenance`:

```php
public function asset()
{
    return $this->belongsTo(\App\Models\Asset::class);
}
```

Add to `Asset`:

```php
public function disposal()
{
    return $this->hasOne(\App\Models\AssetDisposal::class);
}
```

Add to `StaffExit`/exit workflow:
- resolve assigned assets before final clearance
- call `AssetExitClearanceService::syncClearance($exit)` before allowing completion

Important procurement control:
Do not allow the same user to request, approve, evaluate and receive the same procurement transaction in production. Enforce segregation of duties using roles/permissions and workflow checks.
