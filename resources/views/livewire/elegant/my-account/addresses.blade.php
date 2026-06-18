@props(['user_info'])
<?php

use App\Models\City;
use App\Services\TranslationService;

$bread_crumb['page_main_bread_crumb'] = labels('front_messages.addresses', 'Addresses');
$language_code = app(TranslationService::class)->getLanguageCode();
$google_map_key = $system_settings['google_map_key'] ?? '';
?>

<div>
    <div id="page-content">
        <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />
        <div class="container-fluid">
            <div class="row">
                <x-utility.my_account_slider.account_slider :$user_info />
                <div class="col-12 col-sm-12 col-md-12 col-lg-9">
                    <div class="dashboard-conten h-100">
                        <div class="h-100" id="address">
                            <div class="address-card mt-0 h-100">
                                <div class="top-sec d-flex-justify-center justify-content-between mb-4">
                                    <h2 class="mb-0">{{ labels('front_messages.address_book', 'Address Book') }}</h2>
                                    <button type="button" wire:click="resetForm"
                                        onclick="window._addressFormReset && window._addressFormReset()"
                                        class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#addNewModal">
                                        <ion-icon name="add-outline" class="me-1 fs-5"></ion-icon>
                                        {{ labels('front_messages.add_new', 'Add New') }}
                                    </button>
                                </div>

                                <div class="address-book-section dashboard-content">
                                    @if (count($addresses) < 1)
                                        <div class="d-flex flex-column justify-content-center align-items-center py-5">
                                        <div class="opacity-50">
                                            <ion-icon name="location-outline"
                                                class="address-location-icon text-muted"></ion-icon>
                                        </div>
                                        <div class="fs-6 fw-500">
                                            {{ labels('front_messages.delivery_address_not_added', 'Delivery Address is Not Added Yet') }}
                                        </div>
                                </div>
                                @endif

                                <div class="row g-4 row-cols-lg-3 row-cols-md-2 row-cols-sm-2 row-cols-1">
                                    @foreach ($addresses as $address)
                                    @php $address = json_decode(json_encode($address), true); @endphp
                                    <div
                                        class="address-select-box {{ $address['is_default'] == 1 ? 'active' : '' }}">
                                        <div class="address-box bg-block">
                                            <div class="top d-flex-justify-center justify-content-between mb-3">
                                                <h5 class="m-0">{{ $address['name'] }}</h5>
                                                <span class="product-labels start-auto end-0">
                                                    <span class="lbl pr-label1">{{ $address['type'] }}</span>
                                                </span>
                                            </div>
                                            <div class="middle">
                                                <div class="address mb-2 text-muted">
                                                    <address class="m-0">
                                                        {{ $address['landmark'] }}<br />
                                                        {{ $address['address'] }},
                                                        {{ app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $address['city_id'], $language_code) }},
                                                        <br />{{ $address['state'] }} ,
                                                        {{ $address['pincode'] }}
                                                    </address>
                                                </div>
                                                @php
                                                    // Legacy rows store phonecode without "+" (e.g. "20"); new rows save "+20".
                                                    // Normalise to a single "+prefix" form for both display and tel:.
                                                    $cc = (string) ($address['country_code'] ?? '');
                                                    $cc = $cc === '' ? '' : '+' . ltrim($cc, '+');
                                                @endphp
                                                <div class="number">
                                                    <p>
                                                        {{ labels('front_messages.mobile', 'Mobile') }}:
                                                        <a
                                                            href="tel:{{ $cc }}{{ $address['mobile'] }}">
                                                            ({{ $cc }})
                                                            &nbsp; {{ $address['mobile'] }}
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="bottom d-flex-justify-center justify-content-between">
                                                <button type="button" wire:click="edit({{ $address['id'] }})"
                                                    class="bottom-btn btn btn-gray btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#addNewModal">
                                                    {{ labels('front_messages.edit', 'Edit') }}
                                                </button>

                                                <button wire:click.prevent="setDefault({{ $address['id'] }})"
                                                    class="bottom-btn btn btn-sm {{ $address['is_default'] == 1 ? '' : 'btn-gray' }}">
                                                    {{ labels('front_messages.default', 'Default') }}
                                                </button>
                                                <!-- <button class="bottom-btn btn btn-gray btn-sm delete_address"
                                                    data-address-id="{{ $address['id'] }}">
                                                    {{ labels('front_messages.remove', 'Remove') }}
                                                </button> -->
                                                <button
                                                    wire:click="deleteAddress({{ $address['id'] }})"
                                                    class="bottom-btn btn btn-gray btn-sm">
                                                    {{ labels('front_messages.remove', 'Remove') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Modal still inside same root but ignored by Livewire -->
<div wire:ignore class="modal fade" id="addNewModal" tabindex="-1" aria-labelledby="addNewModalLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="addNewModalLabel">
                    {{ labels('front_messages.address_details', 'Address details') }}
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="address-error-box" class="alert alert-danger mb-3" style="display:none;"></div>
                <div class="form-row row-cols-lg-2 row-cols-md-2 row-cols-sm-1 row-cols-1">
                    <div class="form-group">
                        <input wire:model='name' name="name" placeholder="{{ labels('front_messages.name', 'Name') }}" id="name" type="text" />
                    </div>
                    <div class="form-group">
                        <select name="type" wire:model="type" id="type">
                            <option value="">
                                {{ labels('front_messages.select_address_type', 'Select Address type') }}
                            </option>
                            <option value="home">{{ labels('front_messages.home', 'Home') }}</option>
                            <option value="office">{{ labels('front_messages.office', 'Office') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input wire:model='mobile' name="mobile" placeholder="{{ labels('front_messages.mobile_number', 'Mobile Number') }}" id="mobile"
                            type="number">
                    </div>
                    <div class="form-group">
                        <input wire:model='alternate_mobile' name="alternate_mobile"
                            placeholder="{{ labels('front_messages.alternative_mobile_number', 'Alternative mobile number') }}" id="alternate_mobile" type="number">
                    </div>
                    <div class="form-group">
                        <input wire:model='address' name="address" placeholder="{{ labels('front_messages.address', 'Address') }}" id="form_address"
                            type="text" />
                    </div>
                    <div class="form-group">
                        <input wire:model='landmark' name="landmark" placeholder="{{ labels('front_messages.landmark', 'Landmark') }}" id="landmark"
                            type="text" />
                    </div>
                    <div class="form-group city_list_div">
                        <div wire:ignore>
                            <select wire:model="city_name" class="col-md-12 form-control city_list" id="city_list"
                                name="city">
                                @if (!empty($city_name))
                                    <option value="{{ $city_name }}" selected>{{ $city_name }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <input wire:model='pincode' name="pincode" placeholder="{{ labels('front_messages.post_code', 'Post Code') }}" id="postcode"
                            type="text" />
                    </div>
                    <div class="form-group">
                        <input wire:model='state' name="state" placeholder="{{ labels('front_messages.state', 'State') }}" id="state"
                            type="text" />
                    </div>
                    <div class="form-group country_list_div">
                        <div wire:ignore>
                            <select wire:model="country" class="col-md-12 form-control country_list" id="country_list"
                                name="country">
                                @if (!empty($country))
                                    <option value="{{ $country }}" selected>{{ $country }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <input wire:model='latitude' name="latitude" placeholder="{{ labels('front_messages.latitude', 'Latitude') }}" id="latitude"
                            type="text" readonly>
                    </div>
                    <div class="form-group">
                        <input wire:model='longitude' name="longitude" placeholder="{{ labels('front_messages.longitude', 'Longitude') }}" id="longitude"
                            type="text" readonly>
                    </div>
                </div>

                @if (!empty($google_map_key))
                    <style>
                        /* Places Autocomplete dropdown is appended to <body>; lift above Bootstrap modal (z-index 1055). */
                        .pac-container { z-index: 2000 !important; }
                    </style>
                    <div class="form-row">
                        <div class="form-group col-12">
                            <input type="text" id="place_search" class="form-control"
                                placeholder="{{ labels('front_messages.search_place', 'Search place') }}"
                                autocomplete="off" />
                        </div>
                        <div class="form-group col-12">
                            <div wire:ignore id="address-map" style="width:100%;height:320px;border-radius:6px;"></div>
                        </div>
                    </div>
                @endif
                <input type="hidden" name="edit_address_id" id="edit_address_id" value="">
                <div class="modal-footer justify-content-center">
                    <button type="button" wire:click="save" class="btn btn-primary">
                        <span>{{ labels('front_messages.add_address', 'Add Address') }}</span>
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>
</div>



<script>
    // Delegated events on document survive Livewire re-renders and navigation.
    // Namespaced + off() prevents accumulation on every component re-render.
    $(document).off('change.cityAddr', '#city_list').on('change.cityAddr', '#city_list', function () {
        @this.set('city_name', $(this).val());
    });
    $(document).off('change.countryAddr', '#country_list').on('change.countryAddr', '#country_list', function () {
        @this.set('country', $(this).val());
    });

    // Modal is wire:ignore, so wire:model on these inputs doesn't sync to the server.
    // Mirror each field to its Livewire property (deferred — bundled with the next action).
    window._addressInputSync = [
        { id: 'name', prop: 'name' },
        { id: 'type', prop: 'type' },
        { id: 'mobile', prop: 'mobile' },
        { id: 'alternate_mobile', prop: 'alternate_mobile' },
        { id: 'form_address', prop: 'address' },
        { id: 'landmark', prop: 'landmark' },
        { id: 'postcode', prop: 'pincode' },
        { id: 'state', prop: 'state' }
    ];
    window._addressInputSync.forEach(function (f) {
        var ns = 'addrInp_' + f.prop;
        $(document).off('input.' + ns + ' change.' + ns, '#' + f.id)
            .on('input.' + ns + ' change.' + ns, '#' + f.id, function () {
                @this.set(f.prop, this.value, false);
            });
    });

    // Named handler pattern: remove then re-add prevents duplicate window listeners
    // when the component blade re-renders (this script re-executes on every render).
    window._closeAddressModal = function () {
        var modal = bootstrap.Modal.getInstance(document.getElementById('addNewModal'));
        if (modal) modal.hide();
        document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    };
    window.removeEventListener('close-modal', window._closeAddressModal);
    window.addEventListener('close-modal', window._closeAddressModal);

    // Modal is wire:ignore — DOM never repaints from server. We sync every field by hand.
    // Map server-side property name → DOM element id (they don't all match).
    window._addressFieldMap = {
        name: 'name',
        mobile: 'mobile',
        alternate_mobile: 'alternate_mobile',
        address: 'form_address',
        landmark: 'landmark',
        pincode: 'postcode',
        state: 'state',
        latitude: 'latitude',
        longitude: 'longitude'
    };

    window._addressSetDom = function (id, val) {
        var el = document.getElementById(id);
        if (el) el.value = (val == null ? '' : val);
    };

    window._addressEditLoaded = function (event) {
        var detail = event.detail || {};
        var get = function (k) { return detail[k] != null ? detail[k] : (detail.data && detail.data[k]); };

        // Plain <input>s
        Object.keys(window._addressFieldMap).forEach(function (prop) {
            window._addressSetDom(window._addressFieldMap[prop], get(prop));
        });

        // <select id="type"> — wire:model doesn't repaint under wire:ignore
        var typeEl = document.getElementById('type');
        if (typeEl) typeEl.value = get('type') || '';

        // Select2 dropdowns — inject the saved option so it renders as selected
        var country = get('country');
        if (country) {
            var $country = $('#country_list');
            if (!$country.find('option[value="' + country + '"]').length) {
                $country.append(new Option(country, country, true, true));
            }
            $country.val(country).trigger('change');
        }
        var city = get('city');
        if (city) {
            var $city = $('#city_list');
            if (!$city.find('option[value="' + city + '"]').length) {
                $city.append(new Option(city, city, true, true));
            }
            $city.val(city).trigger('change');
        }

        if (typeof window._addressMapRecenter === 'function') {
            window._addressMapRecenter(parseFloat(get('latitude')), parseFloat(get('longitude')));
        }
    };
    window.removeEventListener('address-edit-loaded', window._addressEditLoaded);
    window.addEventListener('address-edit-loaded', window._addressEditLoaded);

    // Called synchronously from the Add-New button's onclick so the modal opens blank.
    // (Running via a server-dispatched event instead would race with user typing.)
    window._addressFormReset = function () {
        Object.keys(window._addressFieldMap).forEach(function (prop) {
            window._addressSetDom(window._addressFieldMap[prop], '');
        });
        var typeEl = document.getElementById('type');
        if (typeEl) typeEl.value = '';
        var searchEl = document.getElementById('place_search');
        if (searchEl) searchEl.value = '';

        // Clear Select2 display without firing our change.cityAddr / change.countryAddr handlers
        // (they'd queue a redundant @this.set to the server while we're already mid-request).
        var $city = $('#city_list');
        if ($city.length) { $city.val(null).trigger('change.select2'); }
        var $country = $('#country_list');
        if ($country.length) { $country.val(null).trigger('change.select2'); }

        if (typeof window._addressMapRecenter === 'function') {
            window._addressMapRecenter(NaN, NaN);
        }

        var errBox = document.getElementById('address-error-box');
        if (errBox) { errBox.style.display = 'none'; errBox.innerHTML = ''; }
    };

    // Render validation errors dispatched from save() — modal is wire:ignore, so
    // Blade error directives inside it don't repaint automatically.
    window._addressSaveErrors = function (event) {
        var detail = event.detail || {};
        var messages = detail.messages || (detail.data && detail.data.messages) || [];
        var box = document.getElementById('address-error-box');
        if (!box) return;
        if (!messages.length) { box.style.display = 'none'; box.innerHTML = ''; return; }
        var escape = function (s) { return String(s).replace(/[&<>"']/g, function (c) {
            return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c];
        }); };
        box.innerHTML = '<ul class="mb-0 ps-3">' + messages.map(function (m) {
            return '<li>' + escape(m) + '</li>';
        }).join('') + '</ul>';
        box.style.display = 'block';
    };
    window.removeEventListener('address-save-errors', window._addressSaveErrors);
    window.addEventListener('address-save-errors', window._addressSaveErrors);

    @if (!empty($google_map_key))
    // ── Google Maps integration ─────────────────────────────────────────
    // Script re-executes on every Livewire render; guard all one-time setup.
    window._addressMapDefault = window._addressMapDefault || { lat: 24.7136, lng: 46.6753 }; // Riyadh fallback

    window._addressMapSyncLivewire = function (lat, lng) {
        @this.set('latitude', lat);
        @this.set('longitude', lng);
        var latEl = document.getElementById('latitude');
        var lngEl = document.getElementById('longitude');
        if (latEl) latEl.value = lat;
        if (lngEl) lngEl.value = lng;
    };

    // Modal is wire:ignore, so wire:model updates don't repaint inputs.
    // Set both the Livewire property AND the DOM value so users see what was filled and can still edit.
    window._addressSetField = function (prop, domId, value) {
        if (value == null) value = '';
        @this.set(prop, value);
        var el = document.getElementById(domId);
        if (el) el.value = value;
    };

    window._addressGetComponent = function (components, type) {
        if (!components) return '';
        for (var i = 0; i < components.length; i++) {
            if (components[i].types && components[i].types.indexOf(type) !== -1) {
                return components[i].long_name;
            }
        }
        return '';
    };

    // Fills address / landmark / pincode / state from a place or geocoder result.
    // `place` may come from Places Autocomplete (has .name) or Geocoder (no .name).
    window._addressFillFromPlace = function (place) {
        if (!place) return;
        var comps = place.address_components || [];
        var get = window._addressGetComponent;

        var streetNumber = get(comps, 'street_number');
        var route = get(comps, 'route');
        var sublocality = get(comps, 'sublocality') || get(comps, 'sublocality_level_1') || get(comps, 'sublocality_level_2');
        var neighborhood = get(comps, 'neighborhood');
        var locality = get(comps, 'locality');
        var state = get(comps, 'administrative_area_level_1');
        var postal = get(comps, 'postal_code');

        var parts = [];
        if (streetNumber) parts.push(streetNumber);
        if (route) parts.push(route);
        if (sublocality) parts.push(sublocality);
        var addressStr = parts.join(', ') || place.formatted_address || '';

        var landmark = (place.name && place.name !== addressStr) ? place.name : (neighborhood || sublocality || locality || '');

        window._addressSetField('address', 'form_address', addressStr);
        window._addressSetField('landmark', 'landmark', landmark);
        window._addressSetField('state', 'state', state);
        window._addressSetField('pincode', 'postcode', postal);
    };

    // Reverse-geocode a lat/lng and fill fields.
    window._addressReverseGeocode = function (latLng) {
        if (!window.google || !window.google.maps) return;
        window._addressGeocoder = window._addressGeocoder || new google.maps.Geocoder();
        window._addressGeocoder.geocode({ location: latLng }, function (results, status) {
            if (status === 'OK' && results && results[0]) {
                window._addressFillFromPlace(results[0]);
            }
        });
    };

    window._addressMapRecenter = function (lat, lng) {
        if (!window._addressMap || !window._addressMarker) return;
        var hasCoords = !isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0;
        var pos = hasCoords ? { lat: lat, lng: lng } : window._addressMapDefault;
        window._addressMarker.setPosition(pos);
        window._addressMap.setCenter(pos);
        if (hasCoords) window._addressMap.setZoom(15);
    };

    window.initAddressMap = function () {
        var mapEl = document.getElementById('address-map');
        if (!mapEl || window._addressMap) return;

        var latVal = parseFloat(document.getElementById('latitude') && document.getElementById('latitude').value);
        var lngVal = parseFloat(document.getElementById('longitude') && document.getElementById('longitude').value);
        var start = (!isNaN(latVal) && !isNaN(lngVal) && latVal !== 0 && lngVal !== 0)
            ? { lat: latVal, lng: lngVal } : window._addressMapDefault;

        window._addressMap = new google.maps.Map(mapEl, {
            center: start,
            zoom: (!isNaN(latVal) && !isNaN(lngVal) && latVal !== 0 && lngVal !== 0) ? 15 : 5,
            mapTypeControl: false,
            streetViewControl: false
        });
        window._addressMarker = new google.maps.Marker({
            position: start,
            map: window._addressMap,
            draggable: true
        });

        var searchInput = document.getElementById('place_search');
        if (searchInput && google.maps.places) {
            var ac = new google.maps.places.Autocomplete(searchInput, {
                fields: ['geometry', 'name', 'formatted_address', 'address_components']
            });
            ac.bindTo('bounds', window._addressMap);
            ac.addListener('place_changed', function () {
                var place = ac.getPlace();
                if (!place.geometry || !place.geometry.location) return;
                var loc = place.geometry.location;
                window._addressMap.setCenter(loc);
                window._addressMap.setZoom(16);
                window._addressMarker.setPosition(loc);
                window._addressMapSyncLivewire(loc.lat(), loc.lng());
                window._addressFillFromPlace(place);
            });
        }

        window._addressMarker.addListener('dragend', function (e) {
            window._addressMapSyncLivewire(e.latLng.lat(), e.latLng.lng());
            window._addressReverseGeocode(e.latLng);
        });
        window._addressMap.addListener('click', function (e) {
            window._addressMarker.setPosition(e.latLng);
            window._addressMapSyncLivewire(e.latLng.lat(), e.latLng.lng());
            window._addressReverseGeocode(e.latLng);
        });
    };

    window._loadGoogleMaps = function () {
        if (window.google && window.google.maps) { window.initAddressMap(); return; }
        if (window._gmapLoading) return;
        window._gmapLoading = true;
        var s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key={{ $google_map_key }}&libraries=places&callback=initAddressMap';
        s.async = true;
        s.defer = true;
        document.head.appendChild(s);
    };

    // Tiles are blank when the map container is hidden at init. Trigger resize + recenter on modal open.
    window._addressModalShown = function () {
        if (!window.google || !window.google.maps) { window._loadGoogleMaps(); return; }
        if (!window._addressMap) { window.initAddressMap(); return; }
        google.maps.event.trigger(window._addressMap, 'resize');
        var lat = parseFloat(document.getElementById('latitude').value);
        var lng = parseFloat(document.getElementById('longitude').value);
        window._addressMapRecenter(lat, lng);
    };
    var _modalEl = document.getElementById('addNewModal');
    if (_modalEl) {
        _modalEl.removeEventListener('shown.bs.modal', window._addressModalShown);
        _modalEl.addEventListener('shown.bs.modal', window._addressModalShown);
    }
    @endif
</script>