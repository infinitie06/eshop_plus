@livewireStyles
@vite([
    'public/frontend/elegant/css/plugins.css',
    'public/frontend/elegant/css/vendor/photoswipe.min.css',
    'public/frontend/elegant/css/style.css',
    'public/frontend/elegant/css/theme.min.css',
    'public/frontend/elegant/css/theme.min.css',
    'public/frontend/elegant/css/star-rating.css',
    'public/frontend/elegant/css/star-rating.min.css',
    'public/frontend/elegant/css/select2.min.css',
    'public/frontend/elegant/css/responsive.css',
    'public/frontend/elegant/css/lightbox.css',
    'public/frontend/elegant/css/dropzone.css',
    'public/frontend/elegant/css/app.css'
])
<input type="hidden" id="currency" name="currency" value="{{$currency_symbol}}">
<style>
    /* Inactive Store Product Blur - Retry */
    .store-inactive .product-box,
    .store-inactive .product-single {
        filter: blur(5px);
        pointer-events: none;
        user-select: none;
        position: relative;
    }

    .product-not-available {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 100;
        background: rgba(255, 255, 255, 0.95);
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        text-align: center;
        width: max-content;
        max-width: 90%;
        border: 1px solid rgba(0,0,0,0.1);
    }

    .product-not-available h2 {
        margin: 0;
        color: #dc3545;
        font-size: 1.1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Ensure parent has relative positioning for overlay */
    .item.col-item,
    .product-single {
        position: relative;
    }
</style>