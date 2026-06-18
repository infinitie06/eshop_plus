<a wire:click="toggle"
   class="btn-icon wishlist card_fav_btn"
   title="{{ $isFavorite ? 'Remove' : 'Add To Wishlist' }}">
    <i class="hdr-icon anm {{ $isFavorite ? 'anm-heart text-danger' : 'anm-heart-l' }}"></i>
    <span class="text">{{ $isFavorite ? 'Remove' : 'Add To Wishlist' }}</span>
</a>
