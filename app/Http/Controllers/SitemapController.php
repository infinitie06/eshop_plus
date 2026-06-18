<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Generate and return XML sitemap
     */
    public function index()
    {
        // Cache sitemap for 24 hours
        $sitemap = Cache::remember('sitemap_xml', 86400, function () {
            return $this->generateSitemap();
        });

        return response($sitemap, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Generate sitemap XML
     */
    private function generateSitemap()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= '<url>';
        $xml .= '<loc>' . url('/') . '</loc>';
        $xml .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
        $xml .= '<changefreq>daily</changefreq>';
        $xml .= '<priority>1.0</priority>';
        $xml .= '</url>';

        // Products
        $products = Product::where('status', 1)->get();
        foreach ($products as $product) {
            $xml .= '<url>';
            $xml .= '<loc>' . url('/product/' . $product->slug) . '</loc>';
            $xml .= '<lastmod>' . $product->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        // Categories
        $categories = Category::where('status', 1)->get();
        foreach ($categories as $category) {
            $xml .= '<url>';
            $xml .= '<loc>' . url('/category/' . $category->slug) . '</loc>';
            $xml .= '<lastmod>' . $category->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        // Blogs
        $blogs = Blog::where('status', 1)->get();
        foreach ($blogs as $blog) {
            $xml .= '<url>';
            $xml .= '<loc>' . url('/blog/' . $blog->slug) . '</loc>';
            $xml .= '<lastmod>' . $blog->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Return robots.txt content
     */
    public function robots()
    {
        $robotsTxt = file_exists(public_path('robots.txt')) 
            ? file_get_contents(public_path('robots.txt'))
            : $this->getDefaultRobotsTxt();

        return response($robotsTxt, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Get default robots.txt content
     */
    private function getDefaultRobotsTxt()
    {
        return "User-agent: *\nAllow: /\nSitemap: " . url('/sitemap.xml');
    }
}
