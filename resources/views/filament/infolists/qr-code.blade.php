<div style="display: inline-flex; align-items: center; justify-content: center; padding: 16px; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px;">
    {!! QrCode::format('svg')->size(128)->margin(0)->generate($getRecord()->sku) !!}
</div>
