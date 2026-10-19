@extends('layouts.app')

@section('title', 'Contact Us')

@section('content')
<section class="relative overflow-hidden bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50 py-16 sm:py-20">
    <div aria-hidden="true" class="pointer-events-none absolute -left-20 top-12 h-96 w-96 rounded-full bg-gradient-to-br from-pink-300 via-violet-200 to-cyan-200 opacity-70 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute right-10 top-28 h-64 w-64 rounded-full bg-gradient-to-br from-indigo-300 via-cyan-200 to-slate-100 opacity-70 blur-2xl"></div>
    <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] items-start">
            <div>
                <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-indigo-700">Contact Us</p>
                <h1 class="mb-6 text-4xl font-semibold leading-tight tracking-tight text-slate-950 sm:text-5xl">Get in touch with the Campus Reserve team.</h1>
                <p class="text-lg leading-8 text-slate-600 mb-8">Have questions about reservations, account access, or campus scheduling? Find our campus location below or prepare a message for the Campus Reserve team.</p>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                        <h2 class="mb-3 text-xl font-semibold text-slate-950">Campus location</h2>
                        <p class="text-slate-600 leading-7">FEU Roosevelt Marikina</p>
                        <p class="text-slate-600 leading-7">J.P. Rizal St., Lamuan, Marikina City</p>
                        <p class="text-slate-600 leading-7">Philippines</p>
                    </div>
                </div>
            </div>

            <div class="rounded-4xl border border-slate-200/80 bg-white/95 p-8 shadow-xl shadow-indigo-950/10">
                <div class="mb-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-700">Send a message</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">How can we help?</h2>
                </div>

                <form class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Your name</label>
                        <input id="name" name="name" type="text" placeholder="Jane Doe" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email address</label>
                        <input id="email" name="email" type="email" placeholder="jane.doe@example.com" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium text-slate-700 mb-2">Subject</label>
                        <input id="subject" name="subject" type="text" placeholder="Schedule request, account access, other" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700 mb-2">Message</label>
                        <textarea id="message" name="message" rows="5" placeholder="Write your question here..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-2xl bg-indigo-700 px-6 py-3 text-sm font-semibold text-white shadow transition hover:bg-indigo-800">Submit message</button>
                    <p class="text-sm text-slate-500">This form is interactive for demo purposes. Submission is not yet active.</p>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection