@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <h1 class="text-xl font-bold text-[#333]">{{ $title }}</h1>
    <p class="text-center text-sm text-[#6c757d]">{{ $description }}</p>
</div>
