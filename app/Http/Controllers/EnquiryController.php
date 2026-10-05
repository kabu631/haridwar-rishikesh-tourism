<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Http\Requests\StoreEnquiryRequest;
use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use App\Models\Page;
use App\Support\Seo\SeoBuilder;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class EnquiryController extends Controller
{
    public function store(StoreEnquiryRequest $request, SiteSettings $settings): JsonResponse|RedirectResponse
    {
        // Honeypot: bots fill every field. Pretend success, store nothing.
        if (filled($request->input('website'))) {
            return $this->success($request);
        }

        $data = $request->safe()->except(['website', 'source']);
        $message = (string) ($data['message'] ?? '');

        // Sent from a tour package page or its "Book this tour now" link: the tour is that package, whatever the form says.
        $page = filled($data['page_id'] ?? null) ? Page::query()->published()->find($data['page_id']) : null;
        $data['page_id'] = $page?->id;

        if ($page?->type === PageType::Package) {
            $data['tour'] = Str::limit($page->title, 190, '');
        }

        $enquiry = Enquiry::query()->create(array_merge($data, [
            'type' => $data['type'] ?? ($request->routeIs('booking.store') ? 'booking' : 'quick'),
            'source_url' => Str::limit((string) ($request->input('source') ?: $request->headers->get('referer')), 250, ''),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'status' => preg_match_all('#https?://#i', $message) >= 3 ? 'spam' : 'new',
        ]));

        if ($enquiry->status === 'new') {
            $recipient = $settings->get('enquiry_recipient');

            defer(function () use ($enquiry, $recipient): void {
                try {
                    Mail::to($recipient)->send(new EnquiryReceived($enquiry));
                } catch (Throwable $exception) {
                    Log::error('Enquiry notification failed', ['enquiry' => $enquiry->id, 'error' => $exception->getMessage()]);
                }
            });
        }

        $request->session()->flash('enquiry_name', $enquiry->name);

        return $this->success($request);
    }

    public function thankYou(Request $request, SeoBuilder $seo): View
    {
        return view('pages.thank-you', [
            'name' => $request->session()->get('enquiry_name'),
            'seo' => $seo->forView('Thank you – Haridwar Rishikesh Tourism', 'Your enquiry has been received.'),
        ]);
    }

    private function success(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Thank you! Our Haridwar travel expert will contact you shortly.',
                'redirect' => route('enquiry.thanks'),
            ]);
        }

        return redirect()->route('enquiry.thanks');
    }
}
