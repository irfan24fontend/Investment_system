<details class="mb-5 bg-sky-50 border border-sky-200 rounded-xl text-sm text-sky-900" {{ ($open ?? true) ? 'open' : '' }}>
<summary class="cursor-pointer px-4 py-3 font-semibold">ℹ️ {{ $title }}</summary>
<ul class="px-5 pb-4 list-disc space-y-1">@foreach($lines as $l)<li>{!! $l !!}</li>@endforeach</ul>
</details>
