<div id="page-content">
    @php
        $empty_title =
            '<strong>' . labels('front_messages.sorry', 'SORRY ') . '</strong> ' .
            labels('front_messages.compare_is_currently_empty', 'Compare List is currently empty');

        $compare_i18n = [
            'product' => labels('front_messages.product', 'Product'),
            'price' => labels('front_messages.price', 'Price'),
            'category' => labels('front_messages.category', 'Category'),
            'brand' => labels('front_messages.brand', 'Brand'),
            'rating' => labels('front_messages.rating', 'Rating'),
            'description' => labels('front_messages.description', 'Description'),
            'view' => labels('front_messages.view_details', 'View Details'),
            'remove' => labels('front_messages.remove', 'Remove'),
            'combo' => labels('front_messages.combo_products', 'Combo Products'),
        ];
    @endphp
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />

    <div class="container-fluid">
        <div id="compare_container"
            data-csrf="{{ csrf_token() }}"
            data-i18n='@json($compare_i18n)'
            data-endpoint="{{ url('product/add-to-compare') }}"
            data-product-url="{{ url('products') }}"
            data-combo-url="{{ url('combo-products') }}"
            data-store-slug="{{ session('store_slug') }}">
            <div id="compare_empty_slot">
                <x-utility.others.not-found :title="$empty_title" />
            </div>
        </div>
    </div>

    @script
    <script>
        (function () {
            function initCompareView() {
                var container = document.getElementById('compare_container');
                if (!container) return;

                var i18n;
                try { i18n = JSON.parse(container.dataset.i18n || '{}'); } catch (_) { i18n = {}; }
                var endpoint = container.dataset.endpoint;
                var productBase = container.dataset.productUrl;
                var comboBase = container.dataset.comboUrl;
                var storeSlug = container.dataset.storeSlug || '';
                var csrf = container.dataset.csrf;
                var emptySlotHtml = (function () {
                    var slot = document.getElementById('compare_empty_slot');
                    return slot ? slot.outerHTML : '';
                })();

                function readStored() {
                    try {
                        var raw = localStorage.getItem('compare');
                        var parsed = raw ? JSON.parse(raw) : [];
                        if (!Array.isArray(parsed)) return [];
                        return parsed.filter(function (i) { return i && typeof i === 'object' && i.product_id; });
                    } catch (_) { return []; }
                }

                function saveStored(items) {
                    try { localStorage.setItem('compare', JSON.stringify(items)); } catch (_) {}
                    var countEl = document.getElementById('compare_count');
                    if (countEl) countEl.textContent = String(items.length);
                }

                function esc(s) {
                    if (s === null || s === undefined) return '';
                    return String(s)
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                }

                function stars(rating) {
                    var r = Math.round(parseFloat(rating || 0));
                    var html = '';
                    for (var i = 1; i <= 5; i++) {
                        html += '<i class="icon anm anm-star' + (i <= r ? '' : '-o') + '"></i>';
                    }
                    return html;
                }

                function toNum(v) { return parseFloat(String(v || '').replace(/[^0-9.\-]/g, '')) || 0; }

                function priceHtml(item, isCombo) {
                    if (isCombo) {
                        var p = toNum(item.price), sp = toNum(item.special_price);
                        if (sp > 0 && sp < p) {
                            return '<span class="new-price">' + esc(item.special_price) + '</span> ' +
                                   '<span class="old-price text-decoration-line-through ms-2">' + esc(item.price) + '</span>';
                        }
                        return '<span class="new-price">' + esc(item.price || '-') + '</span>';
                    }
                    var mm = item.min_max_price || {};
                    var max = mm.max_price, specMin = mm.special_min_price;
                    if (specMin && toNum(specMin) > 0 && toNum(specMin) < toNum(max)) {
                        return '<span class="new-price">' + esc(specMin) + '</span> ' +
                               '<span class="old-price text-decoration-line-through ms-2">' + esc(max) + '</span>';
                    }
                    return '<span class="new-price">' + esc(max || '-') + '</span>';
                }

                function hrefFor(slug, isCombo) {
                    var base = (isCombo ? comboBase : productBase) + '/' + encodeURIComponent(slug || '');
                    if (storeSlug) {
                        base += (base.indexOf('?') >= 0 ? '&' : '?') + 'store=' + encodeURIComponent(storeSlug);
                    }
                    return base;
                }

                function renderEmpty() {
                    container.innerHTML = emptySlotHtml;
                }

                function renderTable(regulars, combos) {
                    if ((regulars.length + combos.length) === 0) {
                        renderEmpty();
                        return;
                    }

                    var all = regulars.map(function (p) { return { item: p, isCombo: false }; })
                        .concat(combos.map(function (p) { return { item: p, isCombo: true }; }));

                    var html = '<div class="table-wrapper mt-4 compare-table table-responsive">';
                    html += '<table class="table table-bordered align-middle text-center compare_product_table">';

                    html += '<tr><th class="text-start" style="min-width:180px;">' + esc(i18n.product) + '</th>';
                    all.forEach(function (entry) {
                        var p = entry.item, isCombo = entry.isCombo;
                        var url = hrefFor(p.slug, isCombo);
                        html += '<td class="compare-col">';
                        html += '<div class="d-flex flex-column align-items-center">';
                        html += '<a href="' + esc(url) + '"><img src="' + esc(p.image) + '" alt="' + esc(p.name) + '" class="img-fluid mb-2" style="max-height:160px;"></a>';
                        html += '<a href="' + esc(url) + '" class="fw-semibold">' + esc(p.name) + '</a>';
                        html += '<button type="button" class="btn btn-sm btn-link text-danger compare-remove mt-1" ' +
                                'data-product-id="' + esc(p.id) + '" data-product-type="' + (isCombo ? 'combo' : 'regular') + '">' +
                                '<i class="icon anm anm-times-l"></i> ' + esc(i18n.remove) + '</button>';
                        html += '</div></td>';
                    });
                    html += '</tr>';

                    html += '<tr><th class="text-start">' + esc(i18n.price) + '</th>';
                    all.forEach(function (e) { html += '<td>' + priceHtml(e.item, e.isCombo) + '</td>'; });
                    html += '</tr>';

                    html += '<tr><th class="text-start">' + esc(i18n.category) + '</th>';
                    all.forEach(function (e) {
                        html += '<td>' + esc(e.isCombo ? i18n.combo : (e.item.category_name || '-')) + '</td>';
                    });
                    html += '</tr>';

                    html += '<tr><th class="text-start">' + esc(i18n.brand) + '</th>';
                    all.forEach(function (e) {
                        html += '<td>' + esc(e.isCombo ? '-' : (e.item.brand_name || '-')) + '</td>';
                    });
                    html += '</tr>';

                    html += '<tr><th class="text-start">' + esc(i18n.rating) + '</th>';
                    all.forEach(function (e) {
                        html += '<td>' + (e.isCombo ? '-' : stars(e.item.rating)) + '</td>';
                    });
                    html += '</tr>';

                    html += '<tr><th class="text-start">' + esc(i18n.description) + '</th>';
                    all.forEach(function (e) {
                        var raw = e.item.short_description || e.item.description || '-';
                        var text = String(raw).replace(/<[^>]*>/g, '');
                        if (text.length > 200) text = text.substring(0, 200) + '…';
                        html += '<td class="text-start">' + esc(text) + '</td>';
                    });
                    html += '</tr>';

                    html += '<tr><th class="text-start"></th>';
                    all.forEach(function (e) {
                        html += '<td><a href="' + esc(hrefFor(e.item.slug, e.isCombo)) + '" class="btn btn-secondary btn-sm">' + esc(i18n.view) + '</a></td>';
                    });
                    html += '</tr>';

                    html += '</table></div>';
                    container.innerHTML = html;
                }

                function fetchAndRender() {
                    var stored = readStored();
                    if (stored.length === 0) {
                        renderEmpty();
                        return;
                    }

                    $.ajax({
                        type: 'POST',
                        url: endpoint,
                        data: { product_id: stored, _token: csrf },
                        dataType: 'json',
                        success: function (response) {
                            if (!response || response.error) { renderEmpty(); return; }
                            var data = response.data || {};
                            var regulars = data.regular_product || [];
                            var combos = data.combo_products || [];

                            if (Array.isArray(data.valid_compare_items)) {
                                saveStored(data.valid_compare_items);
                            }

                            renderTable(regulars, combos);
                        },
                        error: function (xhr) {
                            console.warn('Compare fetch failed', xhr && xhr.status);
                            renderEmpty();
                        }
                    });
                }

                $(document).off('click.compareView');
                $(document).on('click.compareView', '#compare_container .compare-remove', function (e) {
                    e.preventDefault();
                    var pid = String($(this).data('product-id'));
                    var ptype = String($(this).data('product-type') || 'regular');

                    var next = readStored().filter(function (i) {
                        return !(String(i.product_id) === pid && String(i.product_type || 'regular') === ptype);
                    });
                    saveStored(next);

                    if (next.length === 0) { renderEmpty(); return; }
                    fetchAndRender();
                });

                fetchAndRender();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCompareView);
            } else {
                initCompareView();
            }
            document.removeEventListener('livewire:navigated', initCompareView);
            document.addEventListener('livewire:navigated', initCompareView);
        })();
    </script>
    @endscript
</div>
