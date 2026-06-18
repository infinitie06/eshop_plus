<?php

namespace App\Livewire\Products;

use Livewire\Component;
use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;

class WishlistButton extends Component
{
    public $product_id;
    public $isFavorite = false;

    public function mount($product_id)
    {
        $this->product_id = $product_id;
        $this->isFavorite = Favorite::where('user_id', Auth::id())
            ->where('product_id', $this->product_id)
            ->exists();
    }

public function removeFavorite($id)
{
    Favorite::where('user_id', auth()->id())->where('product_id', $id)->delete();
    $this->dispatch('wishlistUpdated');
    $this->dispatch('validationSuccessShow', ['data' => ['success' => ['Removed from favorite']]]);
}

    public function toggle()
    {
        if (!$this->product_id || !Auth::check()) {
            return redirect()->route('login');
        }

        $favorite = Favorite::where('user_id', Auth::id())
            ->where('product_id', $this->product_id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            $this->dispatch('validationSuccessShow', [
                'data' => [
                    'success' => ['Removed from favorite']
                ]
            ]);




            $this->isFavorite = false;
        } else {
            Favorite::create([
                'user_id' => Auth::id(),
                'product_id' => $this->product_id,
                'product_type' => 'product'
            ]);
            $this->dispatch('validationSuccessShow', [
                'data' => [
                    'success' => ['Added to favorite']
                ]
            ]);




            $this->isFavorite = true;
        }

        $this->dispatch('wishlistUpdated');
        $this->dispatch('$refresh');
    }

    public function render()
    {

        return view('livewire.' . config('constants.theme') . '.products.wishlist-button');
    }
}
