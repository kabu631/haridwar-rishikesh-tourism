<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Site-wide values: defaults from config/site.php and config/seo.php,
 * overridden by anything saved in Admin → Settings.
 */
class SiteSettings
{
    /**
     * Keys editable in the admin panel, mapped to their config defaults.
     */
    public const EDITABLE = [
        'name' => 'site.name',
        'legal_name' => 'site.legal_name',
        'tagline' => 'site.tagline',
        'phone' => 'site.phone',
        'landline' => 'site.landline',
        'whatsapp' => 'site.whatsapp',
        'email' => 'site.email',
        'address' => 'site.address',
        'geo' => 'site.geo',
        'opening_hours' => 'site.opening_hours',
        'social' => 'site.social',
        'network' => 'site.network',
        'enquiry_recipient' => 'site.enquiry_recipient',
        'topbar_text' => 'site.topbar_text',
        'trust' => 'site.trust',
        'cta' => 'site.cta',
        'footer_about' => 'site.footer_about',
        'credentials' => 'site.credentials',
        'google_ads_id' => 'seo.analytics.google_ads_id',
        'ga4_id' => 'seo.analytics.ga4_id',
        'google_verification' => 'seo.verification.google',
        'bing_verification' => 'seo.verification.bing',
        'announcement' => null,
    ];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $stored = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $root = explode('.', $key)[0];
        $stored = $this->stored();

        if (array_key_exists($root, $stored) && $stored[$root] !== null && $stored[$root] !== '') {
            $value = $root === $key ? $stored[$root] : Arr::get([$root => $stored[$root]], $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        $configKey = self::EDITABLE[$root] ?? null;

        if ($configKey === null) {
            return $default;
        }

        return config($configKey.substr($key, strlen($root)), $default);
    }

    /**
     * Forget loaded values so the next read sees freshly saved settings.
     */
    public function refresh(): void
    {
        $this->stored = null;
    }

    public function phoneHref(?string $number = null): string
    {
        return 'tel:'.preg_replace('/[^0-9+]/', '', $number ?? (string) $this->get('phone'));
    }

    public function whatsappUrl(?string $message = null): string
    {
        $url = 'https://wa.me/'.preg_replace('/\D/', '', (string) $this->get('whatsapp'));

        return $message ? $url.'?text='.rawurlencode($message) : $url;
    }

    /**
     * Single line postal address.
     */
    public function addressLine(): string
    {
        $address = (array) $this->get('address');

        return implode(', ', array_filter([
            $address['street'] ?? null,
            ($address['locality'] ?? '').(filled($address['postal_code'] ?? null) ? '-'.$address['postal_code'] : ''),
            $address['region'] ?? null,
            'India',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        if ($this->stored === null) {
            try {
                $this->stored = Setting::allValues();
            } catch (Throwable) {
                $this->stored = [];
            }
        }

        return $this->stored;
    }
}
