@props(['status' => null])

@if ($status)
    <div role="status" {{ $attributes->class('rounded-[10px] bg-saldo-green-tint px-3.5 py-3 text-sm font-medium leading-normal text-saldo-green') }}>
        {{ $status }}
    </div>
@endif
