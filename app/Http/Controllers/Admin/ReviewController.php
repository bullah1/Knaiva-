<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        return view('admin.reviews', [
            'reviews' => Review::with('product:id,name')->orderBy('is_approved')->latest()->paginate(30),
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);
        $review = Review::create($data + ['is_approved' => true, 'is_featured' => $request->boolean('is_featured')]);
        $this->syncProductRating($review->product_id);

        return back()->with('success', 'Review added');
    }

    public function update(Request $request, Review $review)
    {
        $field = $request->validate(['field' => ['required', 'in:is_approved,is_featured']])['field'];
        $review->update([$field => ! $review->{$field}]);
        $this->syncProductRating($review->product_id);

        return back()->with('success', 'Review updated');
    }

    public function destroy(Review $review)
    {
        $productId = $review->product_id;
        $review->delete();
        $this->syncProductRating($productId);

        return back()->with('success', 'Review deleted');
    }

    /** Keep the product's star rating in line with its approved reviews. */
    private function syncProductRating(?int $productId): void
    {
        if (! $productId) {
            return;
        }
        $approved = Review::where('product_id', $productId)->where('is_approved', true);
        $count = (clone $approved)->count();
        if ($count > 0) {
            Product::whereKey($productId)->update(['rating' => round((clone $approved)->avg('rating'), 1), 'review_count' => $count]);
        }
    }
}
