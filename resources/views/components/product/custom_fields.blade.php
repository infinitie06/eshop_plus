@foreach ($customFields as $field)
    <div class="mb-4">
        <div class="custom_field_repeater" id="repeater-{{ $field->id }}"
            data-required="{{ $field->required ? 'true' : 'false' }}">
            <div data-repeater-list="custom_fields[{{ $field->id }}]">
                <div data-repeater-item class="mb-2 repeater-item-wrapper d-flex gap-2 align-items-start flex-column">

                    <label class="d-block">{{ $field->name }} @if ($field->required)
                            <span class='text-asterisks text-sm'>*</span>
                        @endif
                    </label>

                    <div class="d-flex gap-2 align-items-center w-100">

                        @switch($field->type)
                            @case('text')
                                <input type="text" name="custom_fields[{{ $field->id }}][0][value]" class="form-control"
                                    @if ($field->required)  @endif maxlength="{{ $field->field_length }}"
                                    placeholder="Enter text">
                            @break

                            @case('number')
                                <input type="number" name="custom_fields[{{ $field->id }}][0][value]" class="form-control"
                                    min="{{ $field->min }}" max="{{ $field->max }}"
                                    value="{{ old('custom_fields.' . $field->id . '.0.value') }}"
                                    {{ $field->required ? 'required' : '' }}
                                    placeholder="Enter number between {{ $field->min }} and {{ $field->max }}">
                            @break

                            {{-- @case('file')
                                <input type="file" name="custom_fields[{{ $field->id }}][0][value]" class="form-control"
                                    @if ($field->required)  @endif>
                            @break --}}
                            @case('file')
                                {{-- <input type="file" name="custom_fields[{{ $field->id }}][0][value]" class="form-control"> --}}

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div>
                                            <input type="file" class="filepond"
                                                name="custom_fields[{{ $field->id }}][][value]" data-max-file-size="300MB"
                                                data-max-files="200">
                                        </div>
                                    </div>
                                </div>
                            @break

                            @case('date')
                                <input type="date" name="custom_fields[{{ $field->id }}][0][value]" class="form-control"
                                    @if ($field->required)  @endif>
                            @break

                            @case('dropdown')
                                <select name="custom_fields[{{ $field->id }}][0][value]" class="form-select"
                                    @if ($field->required)  @endif>
                                    <option value="">Select an option</option>
                                    @foreach ($field->options ?? [] as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @break

                            @case('radio')
                                <div>
                                    {{-- Add a hidden input for radios to ensure the key exists even if not selected --}}
                                    <input type="hidden" name="custom_fields[{{ $field->id }}][0][value]" value="" />

                                    @foreach ($field->options ?? [] as $opt)
                                        <label class="me-3">
                                            <input type="radio" name="custom_fields[{{ $field->id }}][0][value]"
                                                value="{{ $opt }}">
                                            {{ $opt }}
                                        </label>
                                    @endforeach
                                </div>
                            @break

                            @case('checkbox')
                                <div>
                                    {{-- Ensure an empty array is submitted if no checkbox is selected --}}
                                    <input type="hidden" name="custom_fields[{{ $field->id }}][0][value]" value="" />

                                    @foreach ($field->options ?? [] as $opt)
                                        <label class="me-3">
                                            <input type="checkbox" name="custom_fields[{{ $field->id }}][0][value][]"
                                                value="{{ $opt }}">
                                            {{ $opt }}
                                        </label>
                                    @endforeach
                                </div>
                            @break

                            @case('color')
                                <input type="color" name="custom_fields[{{ $field->id }}][0][value]" class="form-control">
                            @break

                            @case('textarea')
                                <textarea name="custom_fields[{{ $field->id }}][0][value]" class="form-control" rows="4"
                                    placeholder="Enter your text here"></textarea>
                            @break
                        @endswitch
                        @if (!$field->required)
                            <button type="button" data-repeater-delete class="btn btn-danger btn-sm">×</button>
                        @endif
                    </div>
                </div>
            </div>
            <button type="button" data-repeater-create style="display: none;"></button>
            <button type="button" class="btn btn-sm btn-primary mt-2 repeater-add-btn" data-repeater-create>
                Add {{ ucfirst($field->name) }}
            </button>
        </div>
    </div>
@endforeach
