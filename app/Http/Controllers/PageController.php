<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public const FAQS = [
        ['How long does delivery take?', 'Inside Dhaka orders arrive in 1–2 working days. Outside Dhaka usually takes 2–4 working days.'],
        ['What are the delivery charges?', 'Delivery is charged separately for inside Dhaka and outside Dhaka, and is shown automatically at checkout. Orders above the free delivery threshold ship free.'],
        ['Which payment methods do you accept?', 'Cash on Delivery, bKash, Nagad and card payment where enabled.'],
        ['Can I return a product?', 'Yes. Unopened packs can be returned within 7 days of delivery. Damaged or wrong items are replaced free of charge.'],
        ['How do I choose the right food for my cat?', 'Match the life stage first (kitten or adult), then pick dry, wet or a mix. Message us on WhatsApp and we will recommend a portion size.'],
    ];

    private function page(string $view, string $title, string $description, string $path, array $extra = [])
    {
        return view($view, $extra + ['seo' => ($extra['seo'] ?? []) + [
            'title' => $title,
            'description' => $description,
            'canonical' => site_origin().$path,
        ]]);
    }

    public function about()
    {
        return $this->page('pages.about', 'About Kaniva Bangladesh — Premium Pet Nutrition',
            'Kaniva Bangladesh brings grain free, lab tested cat and dog nutrition to pet parents across the country with honest ingredients and fair prices.', '/about');
    }

    public function contact()
    {
        $contact = site_settings('contact');

        return $this->page('pages.contact', 'Contact Kaniva Bangladesh — Support & Orders',
            'Call, email or WhatsApp the Kaniva Bangladesh team for product advice, order help and delivery questions.', '/contact', [
                'seo' => ['jsonld' => [array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'PetStore',
                    'name' => 'Kaniva Bangladesh',
                    'url' => site_origin(),
                    'telephone' => $contact['phone'] ?: null,
                    'email' => $contact['email'] ?: null,
                    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $contact['address'], 'addressCountry' => 'BD'],
                    'image' => asset('images/hero-kaniva.jpg'),
                ])]],
            ]);
    }

    public function faq()
    {
        return $this->page('pages.faq', 'Frequently Asked Questions — Kaniva Bangladesh',
            'Delivery times, delivery charges, payment methods, returns and feeding questions answered for Kaniva customers in Bangladesh.', '/faq', [
                'faqs' => self::FAQS,
                'seo' => ['jsonld' => [[
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], self::FAQS),
                ]]],
            ]);
    }

    public function privacy()
    {
        return $this->page('pages.privacy', 'Privacy Policy — Kaniva Bangladesh',
            'How Kaniva Bangladesh collects, uses and protects your personal and order information.', '/privacy-policy');
    }

    public function terms()
    {
        return $this->page('pages.terms', 'Terms & Conditions — Kaniva Bangladesh',
            'The terms that apply when you order Kaniva pet food in Bangladesh.', '/terms');
    }

    public function returns()
    {
        return $this->page('pages.returns', 'Return & Refund Policy — Kaniva Bangladesh',
            'How to return or exchange Kaniva pet food and how refunds are processed in Bangladesh.', '/returns');
    }

    public function setLang(Request $request, string $lang)
    {
        $request->session()->put('lang', $lang);

        return redirect()->back(fallback: route('home'))->withCookie(cookie()->forever('kaniva_lang', $lang));
    }
}
