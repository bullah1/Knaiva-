<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\HeroBanner;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\TrustPoint;
use App\Support\ShopifyImporter;
use Illuminate\Database\Seeder;

/** Categories, the real Kaniva catalogue and starter content. Safe to run again. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['cat-dry-food', 'Cat Dry Food', 'বিড়ালের ড্রাই ফুড', 1,
                'Complete dry food for kittens, adult and senior cats — indoor, urinary care, skin & coat, grain free and more. Pick by your cat\'s age and need.',
                'Cat Dry Food Price in Bangladesh — Kaniva Cat Food', 'Buy Kaniva dry cat food in Bangladesh: kitten, adult, senior, indoor, urinary and grain free formulas. Cash on delivery and fast home delivery.'],
            ['cat-wet-food', 'Cat Wet Food', 'বিড়ালের ওয়েট ফুড', 2,
                'Tasty pouches in jelly, gravy and mousse — high moisture meals your cat will love. Serve alone or mix with dry food.',
                'Cat Wet Food & Pouch Price in BD — Kaniva & Kandy', 'Kaniva and Kandy wet cat food pouches in jelly, gravy and mousse. Kitten and adult recipes with home delivery across Bangladesh.'],
            ['cat-treats', 'Cat Treats', 'ক্যাট ট্রিট', 3,
                'Creamy lickable treats for rewards, training, bonding and hiding medicine.',
                'Cat Creamy Treats Price in BD — Kaniva Lickable Treats', 'Kaniva creamy lickable cat treats in tuna, chicken, lamb and seafood flavours. Order online in Bangladesh.'],
            ['cat-litter', 'Cat Litter', 'ক্যাট লিটার', 4,
                'Plant-based tofu cat litter that clumps fast, controls odour and is low in dust.',
                'Tofu Cat Litter Price in BD — Kasty Pop 6L', 'Kasty Pop tofu cat litter 6L in six scents. Fast clumping, odour control and low dust. Delivered across Bangladesh.'],
            ['pet-wipes', 'Pet Wipes', 'পেট ওয়াইপস', 5,
                'Soft pet wipes for quick cleaning of paws, face, ears and coat between baths.',
                'Pet Wipes Price in BD — Kanimal 100 Sheets', 'Kanimal pet wipes, 100 soft sheets in six scents for cats and dogs. Order online in Bangladesh.'],
            ['pet-accessories', 'Accessories', 'অ্যাক্সেসরিজ', 6,
                'Everyday essentials like feeding bowls for cats and dogs.',
                'Pet Accessories in Bangladesh — Kaniva', 'Kaniva pet accessories including feeding bowls. Order online with home delivery in Bangladesh.'],
            ['dog-food', 'Dog Food', 'কুকুরের খাবার', 7,
                'Complete dry dog food with tuna and rice in salmon, lamb and chicken recipes.',
                'Dog Food Price in Bangladesh — Kaniva Dry Dog Food', 'Kaniva dry dog food with tuna and rice. Salmon, lamb and chicken recipes with home delivery across Bangladesh.'],
        ];
        foreach ($categories as [$slug, $name, $bn, $order, $desc, $seoTitle, $seoDesc]) {
            Category::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'name_bn' => $bn, 'sort_order' => $order, 'is_active' => true,
                'description' => $desc, 'seo_title' => $seoTitle, 'seo_description' => $seoDesc,
            ]);
        }

        // Real Kaniva catalogue (70 products) from the Shopify export.
        (new ShopifyImporter)->import(database_path('data/kaniva_products.csv'));

        // A few products to feature on the homepage — change any time in Admin → Products.
        Product::whereIn('slug', [
            'kaniva-fusion-salmon-sweet-potato-cat-food', 'kaniva-grain-free-chicken-tuna-cat-food', 'kaniva-urinary-care',
            'kaniva-mother-cat-kitten-cat-food', 'kandy-student-pouch-mousse-tuna-and-salmon', 'kaniva-tuna-chicken-in-jelly-pouch',
            'kaniva-creamy-chicken', 'kasty-pop-yuzu-black-tea-cat-litter-6l',
        ])->update(['is_popular' => true]);

        if (TrustPoint::count() === 0) {
            foreach ([['wheat-off', 'Grain Free', 'গ্রেইন ফ্রি', 'Gentle on sensitive tummies.'], ['flask-conical', 'Lab Tested', 'ল্যাব টেস্টেড', 'Every batch quality checked.'], ['sprout', 'Non-GMO', 'নন-জিএমও', 'Clean, honest ingredients.'], ['shield-check', 'Quality Ingredients', 'মানসম্মত উপাদান', 'Real meat as the first ingredient.']] as $i => [$icon, $t, $bn, $d]) {
                TrustPoint::create(['icon' => $icon, 'title' => $t, 'title_bn' => $bn, 'description' => $d, 'sort_order' => $i + 1]);
            }
        }
        if (Certificate::count() === 0) {
            foreach (['ISO 22000', 'HACCP', 'GMP Certified', 'BSTI Approved'] as $i => $t) {
                Certificate::create(['title' => $t, 'sort_order' => $i + 1]);
            }
        }
        if (HeroBanner::count() === 0) {
            HeroBanner::create([
                'title' => 'PREMIUM NUTRITION FOR BANGLADESHI PETS', 'title_bn' => 'বাংলাদেশের পোষা প্রাণীর জন্য প্রিমিয়াম পুষ্টি',
                'subtitle' => 'Grain free, lab tested Kaniva food delivered all over Bangladesh.', 'subtitle_bn' => 'সারা বাংলাদেশে ডেলিভারি সহ গ্রেইন ফ্রি, ল্যাব টেস্টেড কানিভা খাবার।',
                'cta_label' => 'Shop Now', 'cta_link' => '/shop', 'sort_order' => 1,
            ]);
        }
        $posts = [
            ['best-cat-food-in-bangladesh', 'Best Cat Food in Bangladesh: A Simple Buying Guide', "How to pick the right cat food for your cat's age, taste and your budget in Bangladesh.",
                "Choosing cat food in Bangladesh can feel confusing with so many imported brands on the shelf. Start with your cat's life stage, then look at protein source and moisture.\n\n## Match the life stage\nKittens need higher protein and DHA. Adult cats do well on a balanced grain free recipe.\n\n## Read the first ingredient\nReal meat should be first on the list, not corn or by-product meal.\n\n## Mix wet and dry\nBangladesh's heat means hydration matters. A wet pouch a day helps urinary health.",
                'Buying Guide', 'Best Cat Food in Bangladesh — Buying Guide 2026', 'A practical guide to choosing the best cat food in Bangladesh by life stage, ingredients and budget.', 'best cat food in bangladesh, cat food bangladesh', 3],
            ['kitten-feeding-schedule', 'Kitten Feeding Schedule: Month by Month', 'A clear feeding chart for kittens from two months to one year.',
                "Kittens grow fast and need frequent small meals.\n\n## 2-4 months\nThree to four meals a day of high protein kitten food.\n\n## 4-9 months\nThree meals a day, slowly increasing portion size.\n\n## 9-12 months\nMove to two meals a day and start transitioning to adult food.",
                'Pet Tips', 'Kitten Feeding Schedule Month by Month — Kaniva Bangladesh', 'How much and how often to feed your kitten from two months to one year.', 'kitten food, kitten feeding schedule', 2],
            ['dry-vs-wet-cat-food', 'Dry vs Wet Cat Food: Which One Does Your Cat Need?', 'Both have a place. Here is how to balance them for a healthy cat.',
                "Dry food is convenient and good for dental health. Wet food adds moisture that supports the urinary tract.\n\n## The simple answer\nUse dry food as the base and add a wet pouch several times a week.",
                'Pet Tips', 'Dry vs Wet Cat Food — Kaniva Bangladesh', 'Compare dry and wet cat food and learn how to balance both for your cat.', 'dry cat food, wet cat food', 1],
        ];
        foreach ($posts as [$slug, $title, $excerpt, $content, $category, $st, $sd, $sk, $daysAgo]) {
            BlogPost::updateOrCreate(['slug' => $slug], [
                'title' => $title, 'excerpt' => $excerpt, 'content' => $content, 'category' => $category, 'is_published' => true,
                'published_at' => now()->subDays($daysAgo), 'seo_title' => $st, 'seo_description' => $sd, 'seo_keywords' => $sk,
            ]);
        }

        $defaults = [
            'delivery' => ['inside_dhaka' => 60, 'outside_dhaka' => 120, 'free_delivery_threshold' => 2000],
            'payments' => ['cod' => true, 'bkash' => true, 'nagad' => true, 'card' => false, 'bkash_number' => '01XXXXXXXXX', 'nagad_number' => '01XXXXXXXXX'],
            'contact' => ['phone' => '+8801XXXXXXXXX', 'whatsapp' => '8801XXXXXXXXX', 'email' => 'hello@kanivacatfood.com', 'address' => 'Dhaka, Bangladesh', 'facebook' => '', 'instagram' => '', 'youtube' => ''],
            'seo' => ['site_name' => 'Kaniva Bangladesh', 'default_title' => 'Kaniva Bangladesh — Premium Cat & Dog Food', 'default_description' => 'Buy Kaniva cat food and dog food online in Bangladesh. Grain free, lab tested, nationwide delivery with cash on delivery, bKash and Nagad.', 'canonical_base' => ''],
        ];
        foreach ($defaults as $key => $value) {
            if (! SiteSetting::find($key)) {
                SiteSetting::put($key, $value);
            }
        }
    }
}
