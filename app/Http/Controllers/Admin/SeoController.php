<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seo;
use App\Models\Product;
use App\Models\Category;
use App\Models\Blog;
use App\Services\SeoService;
use App\Services\SettingService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SeoController extends Controller
{
    protected $seoService;
    protected $settingService;
    protected $translationService;

    public function __construct(
        SeoService $seoService,
        SettingService $settingService,
        TranslationService $translationService
    ) {
        $this->seoService = $seoService;
        $this->settingService = $settingService;
        $this->translationService = $translationService;
    }

    /**
     * Display SEO dashboard
     */
    public function index()
    {
        $globalSeo = Seo::where('seo_type', 'global')->first();
        $productsWithSeo = Seo::where('seo_type', 'product')->count();
        $totalProducts = Product::count();
        $categoriesWithSeo = Seo::where('seo_type', 'category')->count();
        $totalCategories = Category::count();
        $blogsWithSeo = Seo::where('seo_type', 'blog')->count();
        $totalBlogs = Blog::count();

        return view('admin.pages.seo.index', compact(
            'globalSeo',
            'productsWithSeo',
            'totalProducts',
            'categoriesWithSeo',
            'totalCategories',
            'blogsWithSeo',
            'totalBlogs'
        ));
    }

    /**
     * Show global SEO settings form
     */
    public function globalSettings()
    {
        $seo = Seo::where('seo_type', 'global')->where('reference_id', null)->first();
        $systemSettings = $this->settingService->getSettings('system_settings', true);
        $systemSettings = $systemSettings ? json_decode($systemSettings, true) : [];
        
        return view('admin.pages.seo.global', compact('seo', 'systemSettings'));
    }

    /**
     * Store global SEO settings
     */
    public function storeGlobalSettings(Request $request)
    {
        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|in:summary,summary_large_image',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
        ]);

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            $ogImagePath = $request->file('og_image')->store('seo/og-images', 'public');
            $validated['og_image'] = asset('storage/' . $ogImagePath);
        }

        // Handle Twitter image upload
        if ($request->hasFile('twitter_image')) {
            $twitterImagePath = $request->file('twitter_image')->store('seo/twitter-images', 'public');
            $validated['twitter_image'] = asset('storage/' . $twitterImagePath);
        }

        Seo::updateOrCreate(
            ['seo_type' => 'global', 'reference_id' => null],
            $validated
        );

        return redirect()->route('admin.seo.global')->with('success', 'Global SEO settings updated successfully!');
    }

    /**
     * List products with SEO status
     */
    public function productSeoIndex()
    {
        return view('admin.pages.seo.products.index');
    }

    /**
     * Get products list for DataTable
     */
    public function productSeoList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');

        $query = Product::query();
        $languageCode = $this->translationService->getLanguageCode();

        if ($search) {
            $jsonPath = "$." . $languageCode;
            $query->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, ?))) LIKE ?",
                [$jsonPath, '%' . strtolower($search) . '%']
            );
        }

        $total = $query->count();
        $products = $query->offset($offset)->limit($limit)->get();
        $languageCode = $this->translationService->getLanguageCode();

        $rows = $products->map(function ($product) use ($languageCode) {
            $seo = Seo::where('seo_type', 'product')->where('reference_id', $product->id)->first();
            $name = $this->translationService->getDynamicTranslation(Product::class, 'name', $product->id, $languageCode);

            return [
                'id' => $product->id,
                'name' => $name,
                'seo_status' => $seo ? '<span class="badge bg-success">Configured</span>' : '<span class="badge bg-warning">Not Configured</span>',
                'meta_title' => $seo?->meta_title ?? '-',
                'actions' => '<a href="' . route('admin.seo.products.edit', $product->id) . '" class="btn btn-sm btn-primary text-white"><i class="fas fa-edit"></i> ' . labels('admin_labels.edit_seo', 'Edit SEO') . '</a>',
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    /**
     * Edit product SEO
     */
    public function editProductSeo($id)
    {
        $product = Product::findOrFail($id);
        $languageCode = $this->translationService->getLanguageCode();
        $product->setAttribute('name', $this->translationService->getDynamicTranslation(Product::class, 'name', $id, $languageCode));
        $seo = Seo::where('seo_type', 'product')->where('reference_id', $id)->first();

        return view('admin.pages.seo.products.edit', compact('product', 'seo'));
    }

    /**
     * Update product SEO
     */
    public function updateProductSeo(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|in:summary,summary_large_image',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
        ]);

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            $ogImagePath = $request->file('og_image')->store('seo/products/og-images', 'public');
            $validated['og_image'] = asset('storage/' . $ogImagePath);
        }

        // Handle Twitter image upload
        if ($request->hasFile('twitter_image')) {
            $twitterImagePath = $request->file('twitter_image')->store('seo/products/twitter-images', 'public');
            $validated['twitter_image'] = asset('storage/' . $twitterImagePath);
        }

        Seo::updateOrCreate(
            ['seo_type' => 'product', 'reference_id' => $id],
            $validated
        );

        return redirect()->route('admin.seo.products.edit', $id)->with('success', 'Product SEO updated successfully!');
    }

    /**
     * List categories with SEO status
     */
    public function categorySeoIndex()
    {
        return view('admin.pages.seo.categories.index');
    }

    /**
     * Get categories list for DataTable
     */
    public function categorySeoList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');

        $query = Category::query();
        $languageCode = $this->translationService->getLanguageCode();

        if ($search) {
            $jsonPath = "$." . $languageCode;
            $query->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, ?))) LIKE ?",
                [$jsonPath, '%' . strtolower($search) . '%']
            );
        }

        $total = $query->count();
        $categories = $query->offset($offset)->limit($limit)->get();
        $languageCode = $this->translationService->getLanguageCode();

        $rows = $categories->map(function ($category) use ($languageCode) {
            $seo = Seo::where('seo_type', 'category')->where('reference_id', $category->id)->first();
            $name = $this->translationService->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode);

            return [
                'id' => $category->id,
                'name' => $name,
                'seo_status' => $seo ? '<span class="badge bg-success">Configured</span>' : '<span class="badge bg-warning">Not Configured</span>',
                'meta_title' => $seo?->meta_title ?? '-',
                'actions' => '<a href="' . route('admin.seo.categories.edit', $category->id) . '" class="btn btn-sm btn-primary text-white"><i class="fas fa-edit"></i> ' . labels('admin_labels.edit_seo', 'Edit SEO') . '</a>',
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    /**
     * Edit category SEO
     */
    public function editCategorySeo($id)
    {
        $category = Category::findOrFail($id);
        $languageCode = $this->translationService->getLanguageCode();
        $category->setAttribute('name', $this->translationService->getDynamicTranslation(Category::class, 'name', $id, $languageCode));
        $seo = Seo::where('seo_type', 'category')->where('reference_id', $id)->first();

        return view('admin.pages.seo.categories.edit', compact('category', 'seo'));
    }

    /**
     * Update category SEO
     */
    public function updateCategorySeo(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|in:summary,summary_large_image',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
        ]);

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            $ogImagePath = $request->file('og_image')->store('seo/categories/og-images', 'public');
            $validated['og_image'] = asset('storage/' . $ogImagePath);
        }

        // Handle Twitter image upload
        if ($request->hasFile('twitter_image')) {
            $twitterImagePath = $request->file('twitter_image')->store('seo/categories/twitter-images', 'public');
            $validated['twitter_image'] = asset('storage/' . $twitterImagePath);
        }

        Seo::updateOrCreate(
            ['seo_type' => 'category', 'reference_id' => $id],
            $validated
        );

        return redirect()->route('admin.seo.categories.edit', $id)->with('success', 'Category SEO updated successfully!');
    }

    /**
     * List blogs with SEO status
     */
    public function blogSeoIndex()
    {
        return view('admin.pages.seo.blogs.index');
    }

    /**
     * Get blogs list for DataTable
     */
    public function blogSeoList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');

        $query = Blog::query();
        $languageCode = $this->translationService->getLanguageCode();

        if ($search) {
            $jsonPath = "$." . $languageCode;
            $query->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, ?))) LIKE ?",
                [$jsonPath, '%' . strtolower($search) . '%']
            );
        }

        $total = $query->count();
        $blogs = $query->offset($offset)->limit($limit)->get();
        $languageCode = $this->translationService->getLanguageCode();

        $rows = $blogs->map(function ($blog) use ($languageCode) {
            $seo = Seo::where('seo_type', 'blog')->where('reference_id', $blog->id)->first();
            $title = $this->translationService->getDynamicTranslation(Blog::class, 'title', $blog->id, $languageCode);

            return [
                'id' => $blog->id,
                'title' => $title,
                'seo_status' => $seo ? '<span class="badge bg-success">Configured</span>' : '<span class="badge bg-warning">Not Configured</span>',
                'meta_title' => $seo?->meta_title ?? '-',
                'actions' => '<a href="' . route('admin.seo.blogs.edit', $blog->id) . '" class="btn btn-sm btn-primary text-white"><i class="fas fa-edit"></i> ' . labels('admin_labels.edit_seo', 'Edit SEO') . '</a>',
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    /**
     * Edit blog SEO
     */
    public function editBlogSeo($id)
    {
        $blog = Blog::findOrFail($id);
        $languageCode = $this->translationService->getLanguageCode();
        $blog->setAttribute('title', $this->translationService->getDynamicTranslation(Blog::class, 'title', $id, $languageCode));
        $seo = Seo::where('seo_type', 'blog')->where('reference_id', $id)->first();

        return view('admin.pages.seo.blogs.edit', compact('blog', 'seo'));
    }

    /**
     * Update blog SEO
     */
    public function updateBlogSeo(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|in:summary,summary_large_image',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
        ]);

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            $ogImagePath = $request->file('og_image')->store('seo/blogs/og-images', 'public');
            $validated['og_image'] = asset('storage/' . $ogImagePath);
        }

        // Handle Twitter image upload
        if ($request->hasFile('twitter_image')) {
            $twitterImagePath = $request->file('twitter_image')->store('seo/blogs/twitter-images', 'public');
            $validated['twitter_image'] = asset('storage/' . $twitterImagePath);
        }

        Seo::updateOrCreate(
            ['seo_type' => 'blog', 'reference_id' => $id],
            $validated
        );

        return redirect()->route('admin.seo.blogs.edit', $id)->with('success', 'Blog SEO updated successfully!');
    }

    /**
     * Show sitemap and robots.txt management
     */
    public function sitemapAndRobots()
    {
        $robotsTxt = file_exists(public_path('robots.txt')) ? file_get_contents(public_path('robots.txt')) : '';
        
        return view('admin.pages.seo.sitemap', compact('robotsTxt'));
    }

    /**
     * Update robots.txt
     */
    public function updateRobotsTxt(Request $request)
    {
        $validated = $request->validate([
            'robots_content' => 'required|string',
        ]);

        file_put_contents(public_path('robots.txt'), $validated['robots_content']);

        return redirect()->route('admin.seo.sitemap')->with('success', 'Robots.txt updated successfully!');
    }
}
