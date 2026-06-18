@foreach ($languages as $lang)
    @if ($lang->code !== 'en')
        <div class="tab-pane fade" id="content-{{ $lang->code }}" role="tabpanel" aria-labelledby="tab-{{ $lang->code }}">
            <div class="mb-3">
                <label for="translated_name_{{ $lang->code }}" class="form-label">
                    {{ labels($nameKey, $nameValue) }} ({{ $lang->language }})
                </label>
                <input type="text" class="form-control"
                       id="translated_name_{{ $lang->code }}"
                       name="{{ $inputName }}[{{ $lang->code }}]"
                       value="">
            </div>
        </div>
    @endif
@endforeach