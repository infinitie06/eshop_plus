<?php

namespace App\Livewire\Blogs;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Livewire\Component;
use Illuminate\Pagination\LengthAwarePaginator;

class Listing extends Component
{

    public $search = "";
    public $category_id = "";

    public function updatedCategoryId()
    {
        // Reset to first page when category changes
    }

    public function render(Request $request)
    {
        $store_id = session('store_id');
        $language_code = app(TranslationService::class)->getLanguageCode();

        // Get blog categories for filter
        $categories = BlogCategory::where('store_id', $store_id)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->get();

        $blogs = [];
        $query = Blog::where('store_id', $store_id)->where('status', 1);

        // Apply category filter
        if ($this->category_id != "") {
            $query->where('category_id', $this->category_id);
        }

        // Apply search filter
        if ($this->search != "") {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('short_description', 'like', '%' . $this->search . '%');
            });
        }

        $blogs = $query->orderBy('id', 'DESC')->get()->toArray();
        $blogs_count = count($blogs);

        $perPage = 9;
        if (isset($request->query()['perPage']) && ($request->query()['perPage'] != null)) {
            $perPage = $request->query()['perPage'];
        }

        if (count($blogs) >= 1) {
            $products = collect($blogs);
            $page = request()->get('page', 1);
            if (isset($page)) {
                $paginator = new LengthAwarePaginator(
                    $products->forPage((int)$page, (int)$perPage),
                    $blogs_count,
                    (int)$perPage,
                    (int)$page,
                    ['path' => url()->current(), 'query' => ['category_id' => $this->category_id, 'search' => $this->search]]
                );
            }
            $blogs['listing'] = $paginator->items();
            $blogs['links'] = $paginator->links();
        }

        return view('livewire.' . config('constants.theme') . '.blogs.listing', [
            'blogs' => $blogs,
            'perPage' => $perPage,
            'blogs_count' => $blogs_count,
            'categories' => $categories,
            'language_code' => $language_code,
        ])->title('Blogs |');
    }
}
