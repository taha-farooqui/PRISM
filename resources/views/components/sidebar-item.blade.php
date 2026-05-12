@props(['href' => '#', 'active' => false, 'label' => ''])

{{-- Expanded state --}}
<a href="{{ $href }}"
   x-show="sidebarExpanded"
   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active ? 'text-purple-600 font-semibold bg-purple-50' : 'text-gray-700 hover:bg-purple-50 hover:text-purple-600' }}">
    <span class="w-7 h-7 flex items-center justify-center shrink-0 [&>svg]:w-[22px] [&>svg]:h-[22px]">
        {{ $icon }}
    </span>
    <span class="text-[15px] font-medium">{{ $label }}</span>
</a>

{{-- Collapsed state --}}
<a href="{{ $href }}"
   x-show="!sidebarExpanded"
   title="{{ $label }}"
   class="flex items-center justify-center p-2.5 rounded-xl transition-colors {{ $active ? 'bg-purple-100 text-purple-600' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-600' }}">
    <span class="w-6 h-6 flex items-center justify-center shrink-0 [&>svg]:w-[22px] [&>svg]:h-[22px]">
        {{ $icon }}
    </span>
</a>
