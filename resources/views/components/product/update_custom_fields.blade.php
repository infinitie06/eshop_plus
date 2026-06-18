@php
    use App\Services\MediaService;
@endphp
@foreach ($customFields as $field)
    @php
        $fieldValues = $productCustomFieldValues[$field->id] ?? collect();
        $fieldValues = $fieldValues->isEmpty() ? collect([null]) : $fieldValues;
    @endphp

    <div class="mb-4">
        <div class="custom_field_repeater" id="repeater-{{ $field->id }}"
            data-required="{{ $field->required ? 'true' : 'false' }}">
            <div data-repeater-list="custom_fields[{{ $field->id }}]">

                @foreach ($fieldValues as $i => $fieldValue)
                    <div data-repeater-item
                        class="mb-2 repeater-item-wrapper d-flex gap-2 align-items-start flex-column">
                        <label class="d-block">
                            {{ $field->name }}
                            @if ($field->required)
                                <span class='text-asterisks text-sm'>*</span>
                            @endif
                        </label>

                        <div class="d-flex gap-2 align-items-center w-100">
                            @switch($field->type)
                                @case('text')
                                    <input type="text" name="custom_fields[{{ $field->id }}][{{ $i }}][value]"
                                        value="{{ $fieldValue->value ?? '' }}" class="form-control"
                                        maxlength="{{ $field->field_length }}" placeholder="Enter text">
                                @break

                                @case('number')
                                    <input type="number" name="custom_fields[{{ $field->id }}][{{ $i }}][value]"
                                        value="{{ $fieldValue->value ?? '' }}" class="form-control" min="{{ $field->min }}"
                                        max="{{ $field->max }}" placeholder="Enter number">
                                @break

                                {{-- @case('file')

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div>
                                                <input type="file" class="filepond"
                                                    name="custom_fields[{{ $field->id }}][{{ $i }}][value]"
                                                    data-max-file-size="300MB" data-max-files="200">
                                            </div>
                                        </div>
                                    </div>
                                    @if (!empty($fieldValue->value))
                                        @php
                                            $isPublicDisk = 1;
                                            $imagePath = $isPublicDisk
                                                ? asset(config('constants.CUSTOM_FIELD_FILE_PATH') . $fieldValue->value)
                                                : $fieldValue->value;
                                        @endphp

                                        <div class="image-upload-section text-center">
                                            <div class="shadow p-3 bg-white rounded image store-image-container">
                                                <div class="image-upload-div">
                                                    <img src="{{ route('admin.dynamic_image', [
                                                        'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                        'width' => 150,
                                                        'quality' => 90,
                                                    ]) }}"
                                                        alt="uploaded image" class="d-block rounded img-fluid"
                                                        id="uploadedAvatar" />
                                                </div>
                                            </div>

                                        </div>
                                    @endif
                                @break --}}
                                @case('file')
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div>
                                                <input type="file" class="filepond"
                                                    name="custom_fields[{{ $field->id }}][{{ $i }}][value]"
                                                    data-max-file-size="300MB" data-max-files="200">

                                                {{-- Add this hidden input to retain old file value --}}
                                                @if (!empty($fieldValue->value))
                                                    <input type="hidden"
                                                        name="custom_fields[{{ $field->id }}][{{ $i }}][old_value]"
                                                        value="{{ $fieldValue->value }}">
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if (!empty($fieldValue->value))
                                        @php
                                            $isPublicDisk = 1;
                                            $imagePath = $isPublicDisk
                                                ? asset(config('constants.CUSTOM_FIELD_FILE_PATH') . $fieldValue->value)
                                                : $fieldValue->value;
                                        @endphp

                                        <div class="image-upload-section text-center">
                                            <div class="shadow p-3 bg-white rounded image store-image-container">
                                                <div class="image-upload-div">
                                                    <img src="{{ route('admin.dynamic_image', [
                                                        'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                        'width' => 150,
                                                        'quality' => 90,
                                                    ]) }}"
                                                        alt="uploaded image" class="d-block rounded img-fluid"
                                                        id="uploadedAvatar" />
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @break

                                @case('date')
                                    <input type="date"
                                        name="custom_fields[{{ $field->id }}][{{ $i }}][value]"
                                        value="{{ $fieldValue->value ?? '' }}" class="form-control">
                                @break

                                @case('dropdown')
                                    <select name="custom_fields[{{ $field->id }}][{{ $i }}][value]"
                                        class="form-select">
                                        <option value="">Select an option</option>
                                        @foreach ($field->options ?? [] as $opt)
                                            <option value="{{ $opt }}" @selected($opt == ($fieldValue->value ?? ''))>
                                                {{ $opt }}
                                            </option>
                                        @endforeach
                                    </select>
                                @break

                                @case('radio')
                                    <input type="hidden" name="custom_fields[{{ $field->id }}][0][value]" value="" />
                                    @foreach ($field->options ?? [] as $opt)
                                        <label class="me-3">
                                            <input type="radio"
                                                name="custom_fields[{{ $field->id }}][{{ $i }}][value]"
                                                value="{{ $opt }}" @if ($opt == ($fieldValue->value ?? '')) checked @endif>
                                            {{ $opt }}
                                        </label>
                                    @endforeach
                                @break

                                @case('checkbox')
                                    <input type="hidden" name="custom_fields[{{ $field->id }}][0][value]" value="" />
                                    @php $selected = json_decode($fieldValue->value ?? '[]', true) ?? []; @endphp
                                    @foreach ($field->options ?? [] as $opt)
                                        <label class="me-3">
                                            <input type="checkbox"
                                                name="custom_fields[{{ $field->id }}][{{ $i }}][value][]"
                                                value="{{ $opt }}" @if (in_array($opt, $selected)) checked @endif>
                                            {{ $opt }}
                                        </label>
                                    @endforeach
                                @break

                                @case('color')
                                    <input type="color" name="custom_fields[{{ $field->id }}][0][value]"
                                        class="form-control" value="{{ $fieldValue->value ?? '#000000' }}">
                                @break

                                @case('textarea')
                                    <textarea name="custom_fields[{{ $field->id }}][0][value]" class="form-control" rows="4"
                                        placeholder="Enter your text here">{{ $fieldValue->value ?? '' }}</textarea>
                                @break
                            @endswitch

                            @if (!$field->required)
                                <button type="button" data-repeater-delete class="btn btn-danger btn-sm">x</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Always show add button --}}
            <button type="button" class="btn btn-sm btn-primary mt-2 repeater-add-btn" data-repeater-create>
                Add {{ ucfirst($field->name) }}
            </button>
        </div>
    </div>
@endforeach
