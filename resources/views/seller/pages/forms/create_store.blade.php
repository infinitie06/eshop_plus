@extends('seller/layout')
@section('title')
    {{ labels('admin_labels.create_store', 'Create Store') }}
@endsection
@section('content')
    @php
        use App\Models\Store;
        use App\Models\Zone;
        use App\Services\TranslationService;
        use App\Services\SettingService;
        $language_code = app(TranslationService::class)->getLanguageCode();
        $settings = app(SettingService::class)->getSettings('admin_preference', true);
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }
        $storeMode = $settings['store_mode'] ?? null;
    @endphp
    <section class="main-content">
        <x-seller.breadcrumb :title="labels('admin_labels.create_store', 'Create Store')" :subtitle="labels('admin_labels.create_new_store_for_your_account', 'Create a new store for your account')" :breadcrumbs="[
            ['label' => labels('admin_labels.stores', 'Stores'), 'url' => route('seller.stores.index')],
            ['label' => labels('admin_labels.create_store', 'Create Store')],
        ]" />

        <div class="card content-area p-4">
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('seller.stores.store') }}" enctype="multipart/form-data" class="submit_form" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">{{ labels('admin_labels.store_details', 'Store Details') }}</h5>

                                <div class="mb-3">
                                    <label class="form-label" for="store_id">
                                        {{ labels('admin_labels.select_store', 'Select Store') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    @if ($storeMode === 'single')
                                        <input type="hidden" name="store_id" value="{{ $available_stores->first()->id }}">
                                        <p class="form-control">
                                            {{ app(TranslationService::class)->getDynamicTranslation(Store::class, 'name', $available_stores->first()->id, $language_code) }}
                                        </p>
                                    @else
                                        <select class="form-select" name="store_id" id="store_id" required>
                                            @foreach ($available_stores as $store)
                                                <option value="{{ $store->id }}" @if ($loop->first) selected @endif>
                                                    {{ app(TranslationService::class)->getDynamicTranslation(Store::class, 'name', $store->id, $language_code) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @error('store_id')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="store_name">
                                        {{ labels('admin_labels.store_name', 'Store Name') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input type="text" name="store_name" class="form-control @error('store_name') is-invalid @enderror" placeholder="Enter store name" value="{{ old('store_name') }}" required />
                                    @error('store_name')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="store_url">
                                        {{ labels('admin_labels.store_url', 'Store URL') }}
                                    </label>
                                    <input type="text" name="store_url" class="form-control @error('store_url') is-invalid @enderror" placeholder="Enter store URL" value="{{ old('store_url') }}" />
                                    @error('store_url')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="description">
                                        {{ labels('admin_labels.description', 'Description') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Write some description here" required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="store_logo">
                                        {{ labels('admin_labels.logo', 'Logo') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input type="file" class="filepond" name="store_logo" data-max-file-size="30MB" accept="image/*,.webp" data-max-files="1" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="store_thumbnail">
                                        {{ labels('admin_labels.store_thumbnail', 'Store Thumbnail') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input type="file" class="filepond" name="store_thumbnail" data-max-file-size="30MB" accept="image/*,.webp" data-max-files="1" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="address_proof">
                                        {{ labels('admin_labels.address_proof', 'Address Proof') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input type="file" class="filepond" name="address_proof" data-max-file-size="30MB" accept="image/*,.webp" data-max-files="1" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">{{ labels('admin_labels.location_details', 'Location Details') }}</h5>

                                <div class="form-group city_list_parent mb-3">
                                    <label class="form-label" for="city">
                                        {{ labels('admin_labels.city', 'City') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <select class="form-select city_list" name="city" id="city" required>
                                        <option value=" ">{{ labels('admin_labels.select_city', 'Select City') }}</option>
                                        @if(old('city'))
                                            @php
                                                $oldCity = \App\Models\City::find(old('city'));
                                            @endphp
                                            @if($oldCity)
                                                <option value="{{ $oldCity->id }}" selected>
                                                    {{ app(TranslationService::class)->getDynamicTranslation(\App\Models\City::class, 'name', $oldCity->id, $language_code) }}
                                                </option>
                                            @endif
                                        @endif
                                    </select>
                                    @error('city')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label" for="zipcode">
                                        {{ labels('admin_labels.zipcode', 'Zipcode') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <select class="form-select zipcode_list" name="zipcode" id="zipcode" required>
                                        <option value=" ">{{ labels('admin_labels.select_zipcode', 'Select Zipcode') }}</option>
                                        @if(old('zipcode'))
                                            @php
                                                $oldZipcode = \App\Models\Zipcode::find(old('zipcode'));
                                            @endphp
                                            @if($oldZipcode)
                                                <option value="{{ $oldZipcode->id }}" selected>
                                                    {{ $oldZipcode->zipcode }}
                                                </option>
                                            @endif
                                        @endif
                                    </select>
                                    @error('zipcode')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="deliverable_type">
                                        {{ labels('admin_labels.deliverable_type', 'Deliverable Type') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <select class="form-select @error('deliverable_type') is-invalid @enderror" name="deliverable_type" id="deliverable_type" required>
                                        <option value="1" {{ old('deliverable_type') == '1' ? 'selected' : '' }}>All</option>
                                        <option value="2" {{ old('deliverable_type') == '2' ? 'selected' : '' }}>Specific</option>
                                    </select>
                                    @error('deliverable_type')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="deliverable_zones">
                                        {{ labels('admin_labels.deliverable_zones', 'Deliverable Zones') }}
                                    </label>
                                    <select name="deliverable_zones[]" class="search_all_zone form-select w-100" multiple id="deliverable_zones" disabled>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="latitude">
                                        {{ labels('admin_labels.latitude', 'Latitude') }}
                                    </label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" placeholder="Latitude" value="{{ old('latitude') }}" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="longitude">
                                        {{ labels('admin_labels.longitude', 'Longitude') }}
                                    </label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" placeholder="Longitude" value="{{ old('longitude') }}" />
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="mb-3">{{ labels('admin_labels.bank_details', 'Bank Details') }}</h5>

                                <div class="mb-3">
                                    <label class="form-label" for="account_number">
                                        {{ labels('admin_labels.account_number', 'Account Number') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('account_number') is-invalid @enderror" name="account_number" placeholder="Enter account number" value="{{ old('account_number') }}" required />
                                    @error('account_number')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="account_name">
                                        {{ labels('admin_labels.account_name', 'Account Name') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('account_name') is-invalid @enderror" name="account_name" placeholder="Enter account name" value="{{ old('account_name') }}" required />
                                    @error('account_name')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="bank_name">
                                        {{ labels('admin_labels.bank_name', 'Bank Name') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror" name="bank_name" placeholder="Enter bank name" value="{{ old('bank_name') }}" required />
                                    @error('bank_name')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="bank_code">
                                        {{ labels('admin_labels.bank_code', 'Bank Code') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('bank_code') is-invalid @enderror" name="bank_code" placeholder="Enter bank code" value="{{ old('bank_code') }}" required />
                                    @error('bank_code')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('seller.stores.index') }}" class="btn btn-secondary me-2">
                                {{ labels('admin_labels.cancel', 'Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                {{ labels('admin_labels.create_store', 'Create Store') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            // Initialize city select2 (will be handled by custom.js, but ensure it's initialized)
            if (typeof initializeCitySelect2 === 'function') {
                initializeCitySelect2();
            } else {
                // Fallback initialization
                $(".city_list").select2({
                    ajax: {
                        url: "{{ route('seller.city') }}",
                        type: "GET",
                        dataType: "json",
                        delay: 250,
                        data: function (params) {
                            return {
                                search: params.term,
                            };
                        },
                        processResults: function (response) {
                            return {
                                results: response.map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.text || item.name,
                                    };
                                }),
                            };
                        },
                        cache: true,
                    },
                    dropdownParent: $(".city_list_parent").last(),
                    placeholder: "Search for cities",
                });
            }

            // Initialize zipcode select2
            $(".zipcode_list").select2({
                ajax: {
                    url: "{{ route('seller.zipcodes') }}",
                    type: "GET",
                    dataType: "json",
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                        };
                    },
                    processResults: function (response) {
                        return {
                            results: response.map(function (item) {
                                return {
                                    id: item.id,
                                    text: item.text || item.zipcode,
                                };
                            }),
                        };
                    },
                    cache: true,
                },
                placeholder: "Search for zipcodes",
            });

            // Handle city change to filter zipcodes
            $('#city').on('change', function() {
                var cityId = $(this).val();
                // When city changes, you might want to reload zipcodes filtered by city
                // This is handled by the backend in the zipcode endpoint
            });

            // Handle deliverable type change
            $('#deliverable_type').on('change', function() {
                if ($(this).val() == '2') {
                    $('#deliverable_zones').prop('disabled', false);
                } else {
                    $('#deliverable_zones').prop('disabled', true);
                    $('#deliverable_zones').val(null).trigger('change');
                }
            });

            // Initialize zones select2 if deliverable type is specific
            if ($('#deliverable_type').val() == '2') {
                $('#deliverable_zones').prop('disabled', false);
            }
        });
    </script>
@endsection
