const D = {
  shopNow: ['Shop Now', 'এখনই কিনুন'], home: ['Home', 'হোম'], shop: ['Shop', 'শপ'], blog: ['Pet Tips', 'পেট টিপস'],
  about: ['About Us', 'আমাদের সম্পর্কে'], contact: ['Contact', 'যোগাযোগ'], faq: ['FAQ', 'জিজ্ঞাসা'], cart: ['Cart', 'কার্ট'],
  checkout: ['Checkout', 'চেকআউট'], addToCart: ['Add to Cart', 'কার্টে যোগ করুন'], details: ['Details', 'বিস্তারিত'],
  inStock: ['In stock', 'স্টকে আছে'], outOfStock: ['Out of stock', 'স্টকে নেই'], popularProducts: ['Popular Products', 'জনপ্রিয় পণ্য'],
  categories: ['Shop by Category', 'ক্যাটাগরি অনুযায়ী'], whyChooseUs: ['Why Choose Kaniva', 'কেন কানিভা'],
  certificates: ['Certificates', 'সার্টিফিকেট'], reviews: ['Customer Reviews', 'কাস্টমার রিভিউ'],
  latestTips: ['Latest Pet Tips', 'সাম্প্রতিক পেট টিপস'], readMore: ['Read More', 'আরও পড়ুন'], search: ['Search products', 'পণ্য খুঁজুন'],
  subtotal: ['Subtotal', 'সাবটোটাল'], delivery: ['Delivery charge', 'ডেলিভারি চার্জ'], discount: ['Discount', 'ডিসকাউন্ট'],
  total: ['Total', 'মোট'], placeOrder: ['Place Order', 'অর্ডার করুন'], continueShopping: ['Continue Shopping', 'শপিং চালিয়ে যান'],
  emptyCart: ['Your cart is empty', 'আপনার কার্ট খালি'],
} as const;

export type Key = keyof typeof D;
export type Lang = 'en' | 'bn';

export const makeT = (lang: Lang) => ({
  lang,
  t: (k: Key) => D[k][lang === 'bn' ? 1 : 0],
  pick: (en?: string | null, bn?: string | null) => (lang === 'bn' ? bn || en || '' : en || ''),
});
export type T = ReturnType<typeof makeT>;
