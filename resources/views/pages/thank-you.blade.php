@extends('layouts.app')

@section('content')
    <section class="container-x grid min-h-[60vh] place-items-center py-16">
        <div class="max-w-xl text-center">
            <span class="mx-auto grid size-20 place-items-center rounded-full bg-leaf-50 text-leaf-600 ring-8 ring-leaf-50/50"><x-glyph name="check" class="size-10" /></span>
            <h1 class="mt-6 font-display text-4xl font-semibold text-ink-900">Thank you{{ $name ? ', '.$name : '' }}!</h1>
            <p class="mt-4 text-lg text-ink-500">Your enquiry has reached our Haridwar office. A travel expert from {{ $site->get('legal_name') }} will get back to you by email or phone.</p>
            <p class="mt-2 text-ink-500">Need an answer right now? Call <a href="{{ $site->phoneHref() }}" class="link-underline font-semibold text-ink-900">{{ $site->get('phone') }}</a>.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="/tour-packages.html" class="btn-primary">Browse tour packages</a>
                <a href="/" class="btn-ghost">Back to home</a>
            </div>
        </div>
    </section>
@endsection
