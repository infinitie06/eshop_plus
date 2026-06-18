@extends('seller/layout')
@section('title')
    {{ labels('admin_labels.account', 'Account') }}
@endsection
@section('content')
    @php
        use App\Models\City;
        use App\Models\Zone;
        use App\Services\TranslationService;
        use App\Services\MediaService;
        $allowModification = config('constants.ALLOW_MODIFICATION') == 1;
    @endphp
    <section class="main-content">
        @if (!$seller_store_exists || empty($store_data) || count($store_data) == 0)
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class='bx bx-store me-2' style="font-size: 24px;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">{{ labels('admin_labels.no_store_found', 'No Store Found') }}</h5>
                        <p class="mb-2">{{ labels('admin_labels.please_create_store_to_manage_account', 'You don\'t have a store yet. Please create a store to manage your account details.') }}</p>
                        <a href="{{ route('seller.stores.create') }}" class="btn btn-primary btn-sm">
                            <i class='bx bx-plus me-1'></i>
                            {{ labels('admin_labels.create_store', 'Create Store') }}
                        </a>
                    </div>
                </div>
            </div>
        @else
        <form class="form-horizontal form-submit-event submit_form"
            action="{{ route('seller.account.update', $seller_data->id) }}" method="POST">
            @method('PUT')
            @csrf

            <input type="hidden" name="edit_store_logo" value="{{ isset($store_data[0]) ? $store_data[0]->logo : '' }}">
            <input type="hidden" name="edit_store_thumbnail" value="{{ isset($store_data[0]) ? $store_data[0]->store_thumbnail : '' }}">
            <input type="hidden" name="edit_address_proof" value="{{ isset($store_data[0]) ? $store_data[0]->address_proof : '' }}">
            <input type="hidden" name="edit_authorized_signature" value="{{ isset($store_data[0]) ? $store_data[0]->authorized_signature : '' }}">
            <input type="hidden" name="edit_national_identity_card" value="{{ isset($store_data[0]) ? $store_data[0]->national_identity_card : '' }}">
            <input type="hidden" name="edit_profile_image"
                value="{{ isset($store_data[0]->edit_profile_image) && !empty($store_data[0]->edit_profile_image) ? $store_data[0]->edit_profile_image : '' }}">
            <div class="row position-relative">
                <div class="seller_account_banner_box">

                    <img alt=""
                        src="{{ isset($store_data[0]) ? app(MediaService::class)->getMediaImageUrl($store_data[0]->store_thumbnail, 'SELLER_IMG_PATH') : '' }}" />
                </div>
                <div class="form-group mt-2">
                    <a class="btn btn-primary btn-md change_banner_button"><i
                            class="bx bx-camera camera_icon"></i>{{ labels('admin_labels.change_banner', 'Change Banner') }}
                    </a>
                    <input id="store_thumbnail_file_upload" name="store_thumbnail" type="file" class="d-none"
                        accept="image/*">
                </div>
                <div class="container-fluid mt-5 mb-5 px-6">
                    <div class="col-md-12 seller_account_page_card">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-center">

                                            <img class="avatar rounded-circle avatar-xxl"
                                                src="
                                        {{ app(MediaService::class)->getMediaImageUrl($store_data[0]->logo, 'SELLER_IMG_PATH') }}"
                                                alt="User">
                                            <div class="camera_icon_div d-flex justify-content-center align-items-center">
                                                <i class="bx bx-camera camera_icon"></i>
                                            </div>

                                            <input id="store_logo_file_upload" name="store_logo" type="file"
                                                class="d-none" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="tab-content p-4" id="pills-tabContent-vertical-pills">
                                        <div class="tab-pane tab-example-design fade show active"
                                            id="pills-vertical-pills-design" role="tabpanel"
                                            aria-labelledby="pills-vertical-pills-design-tab">
                                            <div class="row">
                                                <div class="nav flex-column nav-pills p-0" id="v-pills-tab" role="tablist"
                                                    aria-orientation="vertical">
                                                    <a class="nav-link active border payment_method_title seller_account_tab"
                                                        data-bs-toggle="pill" href="#personal_details" role="tab"
                                                        aria-selected="true">{{ labels('admin_labels.personal_details', 'Personal Details') }}</a>
                                                    <a class="nav-link border payment_method_title seller_account_tab mt-2"
                                                        id="" data-bs-toggle="pill" href="#password_manage"
                                                        role="tab" aria-controls="v-pills-profile" aria-selected="false"
                                                        tabindex="-1">{{ labels('admin_labels.password_manage', 'Password Manage') }}</a>
                                                    <a class="nav-link border payment_method_title seller_account_tab mt-2"
                                                        id="" data-bs-toggle="pill" href="#store_details"
                                                        role="tab" aria-controls="v-pills-profile" aria-selected="false"
                                                        tabindex="-1">{{ labels('admin_labels.store_details', 'Store Details') }}</a>
                                                    <a class="nav-link border payment_method_title seller_account_tab mt-2"
                                                        id="" data-bs-toggle="pill" href="#tax_details"
                                                        role="tab" aria-controls="v-pills-profile" aria-selected="false"
                                                        tabindex="-1">{{ labels('admin_labels.tax_details', 'Tax Details') }}</a>
                                                    <a class="nav-link border payment_method_title seller_account_tab mt-2"
                                                        id="" data-bs-toggle="pill" href="#bank_details"
                                                        role="tab" aria-controls="v-pills-profile" aria-selected="false"
                                                        tabindex="-1">{{ labels('admin_labels.bank_details', 'Bank Details') }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="card">
                                    <div class="tab-content" id="v-pills-tabContent">
                                        <div class="tab-pane fade active show" id="personal_details" role="tabpanel">
                                            <div class="card">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <h5 class="mb-0">
                                                            {{ labels('admin_labels.personal_details', 'Personal Details') }}
                                                        </h5>
                                                    </div>

                                                </div>

                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="mb-3 col-md-6">
                                                            <label for="firstName"
                                                                class="form-label">{{ labels('admin_labels.name', 'Name') }}
                                                                <span class="text-danger text-sm">*</span></label>
                                                            <input class="form-control" type="text" id="name"
                                                                name="name"
                                                                value="{{ isset($seller_data->username) ? $seller_data->username : '' }}"
                                                                autofocus />
                                                        </div>
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label"
                                                                for="phone">{{ labels('admin_labels.mobile', 'Mobile') }}
                                                                <span class="text-danger text-sm">*</span></label>
                                                            <div class="input-group input-group-merge">
                                                                <select class="form-select" id="country_code"
                                                                    name="country_code" style="max-width: 90px;">
                                                                    <option value="93">+93</option>
                                                                    <option value="355">+355</option>
                                                                    <option value="213">+213</option>
                                                                    <option value="1684">+1684</option>
                                                                    <option value="376">+376</option>
                                                                    <option value="244">+244</option>
                                                                    <option value="1264">+1264</option>
                                                                    <option value="1268">+1268</option>
                                                                    <option value="54">+54</option>
                                                                    <option value="374">+374</option>
                                                                    <option value="297">+297</option>
                                                                    <option value="61">+61</option>
                                                                    <option value="43">+43</option>
                                                                    <option value="994">+994</option>
                                                                    <option value="1242">+1242</option>
                                                                    <option value="973">+973</option>
                                                                    <option value="880">+880</option>
                                                                    <option value="1246">+1246</option>
                                                                    <option value="375">+375</option>
                                                                    <option value="32">+32</option>
                                                                    <option value="501">+501</option>
                                                                    <option value="229">+229</option>
                                                                    <option value="1441">+1441</option>
                                                                    <option value="975">+975</option>
                                                                    <option value="591">+591</option>
                                                                    <option value="387">+387</option>
                                                                    <option value="267">+267</option>
                                                                    <option value="55">+55</option>
                                                                    <option value="246">+246</option>
                                                                    <option value="673">+673</option>
                                                                    <option value="359">+359</option>
                                                                    <option value="226">+226</option>
                                                                    <option value="257">+257</option>
                                                                    <option value="855">+855</option>
                                                                    <option value="237">+237</option>
                                                                    <option value="1">+1</option>
                                                                    <option value="238">+238</option>
                                                                    <option value="1345">+1345</option>
                                                                    <option value="236">+236</option>
                                                                    <option value="235">+235</option>
                                                                    <option value="56">+56</option>
                                                                    <option value="86">+86</option>
                                                                    <option value="61">+61</option>
                                                                    <option value="61">+61</option>
                                                                    <option value="57">+57</option>
                                                                    <option value="269">+269</option>
                                                                    <option value="682">+682</option>
                                                                    <option value="506">+506</option>
                                                                    <option value="385">+385</option>
                                                                    <option value="53">+53</option>
                                                                    <option value="599">+599</option>
                                                                    <option value="357">+357</option>
                                                                    <option value="420">+420</option>
                                                                    <option value="45">+45</option>
                                                                    <option value="253">+253</option>
                                                                    <option value="1767">+1767</option>
                                                                    <option value="1809">+1809</option>
                                                                    <option value="593">+593</option>
                                                                    <option value="20">+20</option>
                                                                    <option value="503">+503</option>
                                                                    <option value="240">+240</option>
                                                                    <option value="291">+291</option>
                                                                    <option value="372">+372</option>
                                                                    <option value="251">+251</option>
                                                                    <option value="500">+500</option>
                                                                    <option value="298">+298</option>
                                                                    <option value="679">+679</option>
                                                                    <option value="358">+358</option>
                                                                    <option value="33">+33</option>
                                                                    <option value="594">+594</option>
                                                                    <option value="689">+689</option>
                                                                    <option value="241">+241</option>
                                                                    <option value="220">+220</option>
                                                                    <option value="995">+995</option>
                                                                    <option value="49">+49</option>
                                                                    <option value="233">+233</option>
                                                                    <option value="350">+350</option>
                                                                    <option value="30">+30</option>
                                                                    <option value="299">+299</option>
                                                                    <option value="1473">+1473</option>
                                                                    <option value="590">+590</option>
                                                                    <option value="1671">+1671</option>
                                                                    <option value="502">+502</option>
                                                                    <option value="224">+224</option>
                                                                    <option value="245">+245</option>
                                                                    <option value="592">+592</option>
                                                                    <option value="509">+509</option>
                                                                    <option value="504">+504</option>
                                                                    <option value="852">+852</option>
                                                                    <option value="36">+36</option>
                                                                    <option value="354">+354</option>
                                                                    <option value="91" selected>+91</option>
                                                                    <option value="62">+62</option>
                                                                    <option value="98">+98</option>
                                                                    <option value="964">+964</option>
                                                                    <option value="353">+353</option>
                                                                    <option value="972">+972</option>
                                                                    <option value="39">+39</option>
                                                                    <option value="1876">+1876</option>
                                                                    <option value="81">+81</option>
                                                                    <option value="962">+962</option>
                                                                    <option value="7">+7</option>
                                                                    <option value="254">+254</option>
                                                                    <option value="686">+686</option>
                                                                    <option value="82">+82</option>
                                                                    <option value="965">+965</option>
                                                                    <option value="996">+996</option>
                                                                    <option value="856">+856</option>
                                                                    <option value="371">+371</option>
                                                                    <option value="961">+961</option>
                                                                    <option value="266">+266</option>
                                                                    <option value="231">+231</option>
                                                                    <option value="218">+218</option>
                                                                    <option value="423">+423</option>
                                                                    <option value="370">+370</option>
                                                                    <option value="352">+352</option>
                                                                    <option value="853">+853</option>
                                                                    <option value="389">+389</option>
                                                                    <option value="261">+261</option>
                                                                    <option value="265">+265</option>
                                                                    <option value="60">+60</option>
                                                                    <option value="960">+960</option>
                                                                    <option value="223">+223</option>
                                                                    <option value="356">+356</option>
                                                                    <option value="692">+692</option>
                                                                    <option value="596">+596</option>
                                                                    <option value="222">+222</option>
                                                                    <option value="230">+230</option>
                                                                    <option value="262">+262</option>
                                                                    <option value="52">+52</option>
                                                                    <option value="691">+691</option>
                                                                    <option value="373">+373</option>
                                                                    <option value="377">+377</option>
                                                                    <option value="976">+976</option>
                                                                    <option value="382">+382</option>
                                                                    <option value="1664">+1664</option>
                                                                    <option value="212">+212</option>
                                                                    <option value="258">+258</option>
                                                                    <option value="95">+95</option>
                                                                    <option value="264">+264</option>
                                                                    <option value="674">+674</option>
                                                                    <option value="977">+977</option>
                                                                    <option value="31">+31</option>
                                                                    <option value="599">+599</option>
                                                                    <option value="687">+687</option>
                                                                    <option value="64">+64</option>
                                                                    <option value="505">+505</option>
                                                                    <option value="227">+227</option>
                                                                    <option value="234">+234</option>
                                                                    <option value="683">+683</option>
                                                                    <option value="672">+672</option>
                                                                    <option value="850">+850</option>
                                                                    <option value="47">+47</option>
                                                                    <option value="968">+968</option>
                                                                    <option value="92">+92</option>
                                                                    <option value="680">+680</option>
                                                                    <option value="970">+970</option>
                                                                    <option value="507">+507</option>
                                                                    <option value="675">+675</option>
                                                                    <option value="595">+595</option>
                                                                    <option value="51">+51</option>
                                                                    <option value="63">+63</option>
                                                                    <option value="48">+48</option>
                                                                    <option value="351">+351</option>
                                                                    <option value="1787">+1787</option>
                                                                    <option value="974">+974</option>
                                                                    <option value="262">+262</option>
                                                                    <option value="40">+40</option>
                                                                    <option value="7">+7</option>
                                                                    <option value="250">+250</option>
                                                                    <option value="685">+685</option>
                                                                    <option value="378">+378</option>
                                                                    <option value="239">+239</option>
                                                                    <option value="966">+966</option>
                                                                    <option value="221">+221</option>
                                                                    <option value="381">+381</option>
                                                                    <option value="248">+248</option>
                                                                    <option value="232">+232</option>
                                                                    <option value="65">+65</option>
                                                                    <option value="421">+421</option>
                                                                    <option value="386">+386</option>
                                                                    <option value="677">+677</option>
                                                                    <option value="252">+252</option>
                                                                    <option value="27">+27</option>
                                                                    <option value="34">+34</option>
                                                                    <option value="94">+94</option>
                                                                    <option value="249">+249</option>
                                                                    <option value="597">+597</option>
                                                                    <option value="268">+268</option>
                                                                    <option value="46">+46</option>
                                                                    <option value="41">+41</option>
                                                                    <option value="963">+963</option>
                                                                    <option value="886">+886</option>
                                                                    <option value="992">+992</option>
                                                                    <option value="255">+255</option>
                                                                    <option value="66">+66</option>
                                                                    <option value="228">+228</option>
                                                                    <option value="690">+690</option>
                                                                    <option value="676">+676</option>
                                                                    <option value="1868">+1868</option>
                                                                    <option value="216">+216</option>
                                                                    <option value="90">+90</option>
                                                                    <option value="993">+993</option>
                                                                    <option value="1649">+1649</option>
                                                                    <option value="688">+688</option>
                                                                    <option value="256">+256</option>
                                                                    <option value="380">+380</option>
                                                                    <option value="971">+971</option>
                                                                    <option value="44">+44</option>
                                                                    <option value="1">+1</option>
                                                                    <option value="598">+598</option>
                                                                    <option value="998">+998</option>
                                                                    <option value="678">+678</option>
                                                                    <option value="379">+379</option>
                                                                    <option value="58">+58</option>
                                                                    <option value="84">+84</option>
                                                                    <option value="1284">+1284</option>
                                                                    <option value="1340">+1340</option>
                                                                    <option value="681">+681</option>
                                                                    <option value="967">+967</option>
                                                                    <option value="260">+260</option>
                                                                    <option value="263">+263</option>
                                                                </select>
                                                                <input type="number" id="phone" name="mobile"
                                                                    disabled maxlength="16"
                                                                    oninput="validateNumberInput(this)" min='1'
                                                                    class="form-control" placeholder=""
                                                                    value="{{ isset($seller_data->mobile) ? ($allowModification ? $seller_data->mobile : '************') : '' }}" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label"
                                                                for="email">{{ labels('admin_labels.email', 'Email') }}
                                                                <span class="text-danger text-sm">*</span></label>
                                                            <div class="input-group input-group-merge">
                                                                <input class="form-control" type="email" name="email"
                                                                    value="{{ isset($seller_data->email) ? ($allowModification ? $seller_data->email : '************') : '' }}">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3 col-md-6 form-password-toggle">
                                                            <label class="form-label"
                                                                for="address">{{ labels('admin_labels.address', 'Address') }}
                                                                <span class="text-danger text-sm">*</span></label>
                                                            <textarea name="address" class="form-control" placeholder="Write here your address">{{ isset($seller_data->address) ? $seller_data->address : '' }}</textarea>

                                                        </div>
                                                    </div>
                                                    <div class="row">

                                                        <div class="form-group col-md-4">
                                                            <div class="mb-3">

                                                                <label class="form-label"
                                                                    for="basic-default-phone">{{ labels('admin_labels.address_proof', 'Address Proof') }}
                                                                    <span class="text-danger text-sm">*</span></label>

                                                                <input type="file" class="filepond"
                                                                    name="address_proof" multiple
                                                                    data-max-file-size="30MB" accept="image/*,.webp"
                                                                    data-max-files="20" />
                                                                <img src="
                                                            {{ route('seller.dynamic_image', [
                                                                'url' => app(MediaService::class)->getMediaImageUrl($store_data[0]->address_proof, 'SELLER_IMG_PATH'),
                                                                'width' => 100,
                                                                'quality' => 90,
                                                            ]) }}"
                                                                    alt="user-avatar" class="d-block rounded"
                                                                    id="uploadedAvatar" />

                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <div class="mb-3">

                                                                <label class="form-label"
                                                                    for="basic-default-phone">{{ labels('admin_labels.authorized_signature', 'Authorized Signature') }}
                                                                    <span class="text-danger text-sm">*</span></label>

                                                                <input type="file" class="filepond"
                                                                    name="authorized_signature" multiple
                                                                    data-max-file-size="30MB" accept="image/*,.webp"
                                                                    data-max-files="20" />
                                                                <img src="{{ route('seller.dynamic_image', [
                                                                    'url' => app(MediaService::class)->getMediaImageUrl($store_data[0]->authorized_signature, 'SELLER_IMG_PATH'),
                                                                    'width' => 100,
                                                                    'quality' => 90,
                                                                ]) }}"
                                                                    alt="user-avatar" class="d-block rounded"
                                                                    id="uploadedAvatar" />

                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">

                                                                <label for="national_identity_card"
                                                                    class="form-label">{{ labels('admin_labels.national_identity_card', 'National Identity Card') }}
                                                                </label>
                                                                <div>
                                                                    <input type="file" class="filepond"
                                                                        name="national_identity_card" multiple
                                                                        data-max-file-size="30MB" accept="image/*,.webp"
                                                                        data-max-files="20" />
                                                                    <img src="
                                                                    {{ route('seller.dynamic_image', [
                                                                        'url' => app(MediaService::class)->getMediaImageUrl($store_data[0]->national_identity_card, 'SELLER_IMG_PATH'),
                                                                        'width' => 100,
                                                                        'quality' => 90,
                                                                    ]) }}"
                                                                        alt="user-avatar" class="d-block rounded"
                                                                        id="uploadedAvatar" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="form-group col-md-12">
                                                                <div class="mb-3">
                                                                    <label class="form-label"
                                                                        for="basic-default-company">{{ labels('admin_labels.other_documents', 'Other Documents') }}</label>
                                                                    <small>({{ $note_for_necessary_documents }})</small>
                                                                    <input type="file" class="filepond"
                                                                        name="other_documents[]" multiple
                                                                        data-max-file-size="300MB" data-max-files="200" />
                                                                </div>
                                                                @php
                                                                    $other_documents = json_decode(
                                                                        $store_data[0]->other_documents,
                                                                    );
                                                                @endphp

                                                                @if (!empty($other_documents))
                                                                    <label for="" class="text-danger">*Only Choose
                                                                        When Update is necessary</label>
                                                                    <div class="container-fluid">
                                                                        <div class="row g-3">
                                                                            @foreach ($other_documents as $row)
                                                                                @php
                                                                                    $isPublicDisk =
                                                                                        $store_data[0]->disk == 'public'
                                                                                            ? 1
                                                                                            : 0;
                                                                                    $imagePath = $isPublicDisk
                                                                                        ? asset(
                                                                                            config(
                                                                                                'constants.SELLER_IMG_PATH',
                                                                                            ) .
                                                                                                '/' .
                                                                                                $row,
                                                                                        )
                                                                                        : $row;
                                                                                @endphp
                                                                                <div class="col-md-3 col-sm-6 text-center">
                                                                                    <div
                                                                                        class="bg-white grow image rounded shadow text-center p-3 m-2">
                                                                                        <div class='image-upload-div'>
                                                                                            <img class="img-fluid mb-2"
                                                                                                src="{{ route('admin.dynamic_image', [
                                                                                                    'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                                                                    'width' => 150,
                                                                                                    'quality' => 90,
                                                                                                ]) }}"
                                                                                                alt="Not Found" />
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="password_manage" role="tabpanel"
                                            aria-labelledby="v-pills-profile-tab">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <h5 class="mb-0">
                                                        {{ labels('admin_labels.password_manage', 'Password Manage') }}
                                                    </h5>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label"
                                                            for="email">{{ labels('admin_labels.old_password', 'Old Password') }}
                                                            <span class="text-danger text-sm">*</span></label>
                                                        <div class="input-group input-group-merge">
                                                            <input class="form-control" type="password"
                                                                name="old_password">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 col-md-6 form-password-toggle">
                                                        <label class="form-label" for="password">New Password <span
                                                                class="text-danger text-sm">*</span></label>
                                                        <div class="input-group input-group-merge">
                                                            <input type="password" id="password" class="form-control"
                                                                name="password" placeholder="Enter your password"
                                                                aria-describedby="password" />
                                                            <span
                                                                class="input-group-text cursor-pointer toggle-seller-profile-password"><i
                                                                    class="bx bx-hide"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label" for="password_confirmation">Confirm
                                                            Password <span class="text-danger text-sm">*</span></label>
                                                        <div class="input-group input-group-merge">
                                                            <input type="password" id="password_confirmation"
                                                                class="form-control" name="confirm_password"
                                                                placeholder="Enter your password"
                                                                aria-describedby="password" />
                                                            <span
                                                                class="input-group-text cursor-pointer toggle-seller-profile-password"><i
                                                                    class="bx bx-hide"></i></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="store_details" role="tabpanel"
                                            aria-labelledby="v-pills-profile-tab">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <h5 class="mb-0">
                                                        {{ labels('admin_labels.store_details', 'Store Details') }}
                                                    </h5>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label"
                                                            for="store_name">{{ labels('admin_labels.store_name', 'Store Name') }}
                                                            <span class="text-danger text-sm">*</span></label>
                                                        <div class="input-group input-group-merge">
                                                            <input type="text" name="store_name" class="form-control"
                                                                placeholder="starbucks"
                                                                value="{{ $store_data[0]->store_name }}" />
                                                        </div>

                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label"
                                                            for="store_url">{{ labels('admin_labels.store_url', 'Store URL') }}
                                                            <span class="text-danger text-sm">*</span></label>
                                                        <div class="input-group input-group-merge">
                                                            <input type="text" name="store_url" class="form-control"
                                                                placeholder="starbucks"
                                                                value="{{ $store_data[0]->store_url }}" />
                                                        </div>

                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="longitude"
                                                                class="form-label">{{ labels('admin_labels.longitude', 'Longitude') }}</label>
                                                            <div>
                                                                <input type="text" class="form-control" id="longitude"
                                                                    placeholder="Longitude" name="longitude"
                                                                    value="{{ isset($store_data[0]->longitude) ? $store_data[0]->longitude : '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="latitude"
                                                                class="form-label">{{ labels('admin_labels.latitude', 'Latitude') }}</label>
                                                            <div>
                                                                <input type="text" class="form-control" id="latitude"
                                                                    placeholder="Latitude" name="latitude"
                                                                    value="{{ isset($store_data[0]->latitude) ? $store_data[0]->latitude : '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group city_list_parent">
                                                            <label for="city"
                                                                class="control-label mb-2 mt-2">{{ labels('admin_labels.city', 'City') }}
                                                                <span class='text-asterisks text-xs'>*</span></label>
                                                            <select class="form-select city_list" name="city">

                                                                @if (isset($store_data[0]->city))
                                                                    <option value="{{ $store_data[0]->city }}" selected>
                                                                        {{ app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $store_data[0]->city, $language_code) ?? 'Selected City' }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="city"
                                                                class="control-label mb-2 mt-2">{{ labels('admin_labels.zipcode', 'Zipcode') }}
                                                                <span class='text-asterisks text-xs'>*</span></label>
                                                            <select class="form-select zipcode_list" name="zipcode">
                                                                @foreach ($zipcodes as $zipcode)
                                                                    <option value="{{ $zipcode->id }}"
                                                                        @if (isset($store_data[0]->zipcode) && $zipcode->id == $store_data[0]->zipcode) selected @endif>
                                                                        {{ $zipcode->zipcode }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- @dd($store_data[0]); --}}
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="zipcode"
                                                                class="form-label">{{ labels('admin_labels.deliverable_type', 'Deliverable Type') }}</label>
                                                            <select class="form-select deliverable_type"
                                                                name="deliverable_type" id="deliverable_type">
                                                                <option value="1"
                                                                    {{ $store_data[0]->deliverable_type == '1' ? 'selected' : '' }}>
                                                                    All
                                                                </option>
                                                                <option value="2"
                                                                    {{ $store_data[0]->deliverable_type == '2' ? 'selected' : '' }}>
                                                                    Included
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>


                                                    @php
                                                        $zones =
                                                            isset($store_data[0]->deliverable_zones) &&
                                                            $store_data[0]->deliverable_zones != null
                                                                ? explode(',', $store_data[0]->deliverable_zones)
                                                                : [];
                                                    @endphp
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="zipcodes"
                                                                class="form-label">{{ labels('admin_labels.deliverable_zones', 'Deliverable Zones') }}
                                                                <span class="text-asterisks text-sm">*</span></label>
                                                            <select name="deliverable_zones[]"
                                                                class="search_zone form-select w-100" multiple
                                                                id="deliverable_zones"
                                                                {{ isset($store_data[0]->deliverable_type) && ($store_data[0]->deliverable_type == 2 || $store_data[0]->deliverable_type == 3) ? '' : 'disabled' }}>
                                                                @if (isset($store_data[0]->deliverable_type) &&
                                                                        ($store_data[0]->deliverable_type == 2 || $store_data[0]->deliverable_type == 3))
                                                                    @php
                                                                        $zone_names = fetchDetails(
                                                                            \App\Models\Zone::class,
                                                                            '',
                                                                            [
                                                                                'name',
                                                                                'id',
                                                                                'serviceable_city_ids',
                                                                                'serviceable_zipcode_ids',
                                                                            ],
                                                                            '',
                                                                            '',
                                                                            '',
                                                                            '',
                                                                            'id',
                                                                            $zones,
                                                                        );

                                                                        foreach ($zone_names as $zone) {
                                                                            $zone->serviceable_city_names = getCityNamesFromIds(
                                                                                $zone->serviceable_city_ids,
                                                                                $language_code,
                                                                            );
                                                                            $zone->serviceable_zipcodes = getZipcodesFromIds(
                                                                                $zone->serviceable_zipcode_ids,
                                                                            );
                                                                        }
                                                                    @endphp

                                                                    @foreach ($zone_names as $row)
                                                                        <option value="{{ $row->id }}"
                                                                            {{ in_array($row->id, $zones) ? 'selected' : '' }}>
                                                                            ID - {{ $row->id }} | Name -
                                                                            {{ app(TranslationService::class)->getDynamicTranslation(Zone::class, 'name', $row->id, $language_code) }}
                                                                            |
                                                                            Serviceable Cities:
                                                                            {{ implode(', ', $row->serviceable_city_names) }}
                                                                            |
                                                                            Serviceable Zipcodes:
                                                                            {{ implode(', ', $row->serviceable_zipcodes) }}
                                                                        </option>
                                                                    @endforeach
                                                                @endif
                                                            </select>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="form-group col-md-12">
                                                        <div class="mb-3">
                                                            <label class="form-label"
                                                                for="basic-default-company">{{ labels('admin_labels.description', 'Description') }}
                                                                <span class="text-danger text-sm">*</span></label>
                                                            <textarea id="basic-default-message" value="" name="description" class="form-control"
                                                                placeholder="Write some description here">{{ $store_data[0]->store_description }}</textarea>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="tax_details" role="tabpanel"
                                            aria-labelledby="v-pills-profile-tab">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <h5 class="mb-0">
                                                        {{ labels('admin_labels.tax_details', 'Tax Details') }}
                                                    </h5>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="tax_name"
                                                                class="form-label">{{ labels('admin_labels.tax_name', 'Tax Name') }}
                                                                <span class='text-danger text-sm'>*</span></label>
                                                            <div>
                                                                <input type="text" class="form-control" id="tax_name"
                                                                    placeholder="Tax Name" name="tax_name"
                                                                    value="{{ isset($store_data[0]->tax_name) ? $store_data[0]->tax_name : '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="tax_number"
                                                                class="form-label">{{ labels('admin_labels.tax_number', 'Tax Number') }}
                                                                <span class='text-danger text-sm'>*</span></label>
                                                            <div>
                                                                <input type="text" class="form-control"
                                                                    id="tax_number" placeholder="Tax Number"
                                                                    name="tax_number"
                                                                    value="{{ isset($store_data[0]->tax_number) ? $store_data[0]->tax_number : '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="pan_number"
                                                                class="form-label">{{ labels('admin_labels.pan_number', 'Pan Number') }}</label>
                                                            <div>
                                                                <input type="text" class="form-control"
                                                                    id="pan_number" placeholder="Pan Number"
                                                                    name="pan_number"
                                                                    value="{{ isset($store_data[0]->pan_number) ? $store_data[0]->pan_number : '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="bank_details" role="tabpanel"
                                            aria-labelledby="v-pills-profile-tab">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <h5 class="mb-0">
                                                        {{ labels('admin_labels.bank_details', 'Bank Details') }}
                                                    </h5>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="form-group col-md-6">
                                                        <div class="mb-3">
                                                            <label for="tax_name"
                                                                class="col-sm-4 form-label">{{ labels('admin_labels.account_number', 'Account Number') }}
                                                                <span class='text-danger text-sm'>*</span></label>

                                                            <input type="text" class="form-control"
                                                                id="account_number" placeholder="Account Number"
                                                                name="account_number"
                                                                value="{{ isset($store_data[0]->account_number) ? $store_data[0]->account_number : '' }}">

                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <div class="mb-3">
                                                            <label for="tax_name"
                                                                class="col-sm-4 form-label">{{ labels('admin_labels.account_name', 'Account Name') }}
                                                                <span class='text-danger text-sm'>*</span></label>

                                                            <input type="text" class="form-control" id="account_name"
                                                                placeholder="Account Name" name="account_name"
                                                                value="{{ isset($store_data[0]->account_name) ? $store_data[0]->account_name : '' }}">

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="form-group col-md-6">
                                                        <div class="mb-3">
                                                            <label for="tax_name"
                                                                class="col-sm-4 form-label">{{ labels('admin_labels.bank_name', 'Bank Name') }}
                                                                <span class='text-danger text-sm'>*</span></label>

                                                            <input type="text" class="form-control" id="bank_name"
                                                                placeholder="Bank Name" name="bank_name"
                                                                value="{{ isset($store_data[0]->bank_name) ? $store_data[0]->bank_name : '' }}">

                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <div class="mb-3">
                                                            <label for="tax_name"
                                                                class="col-sm-4 form-label">{{ labels('admin_labels.bank_code', 'Bank Code') }}
                                                                <span class='text-danger text-sm'>*</span></label>

                                                            <input type="text" class="form-control" id="bank_code"
                                                                placeholder="Bank Code" name="bank_code"
                                                                value="{{ isset($store_data[0]->bank_code) ? $store_data[0]->bank_code : '' }}">

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-flex justify-content-between mt-4">
                                            {{-- <div>
                                                <button type="button"
                                                    class="btn btn-outline-warning seller-deactivate-store me-2"
                                                    data-url="{{ route('seller.store.deactivate') }}">
                                                    {{ labels('admin_labels.deactivate_store', 'Deactivate Store') }}
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-danger seller-delete-store"
                                                    data-url="{{ route('seller.store.delete') }}">
                                                    {{ labels('admin_labels.delete_store', 'Delete Store') }}
                                                </button>
                                            </div> --}}
                                            <button type="submit" class="btn btn-primary submit_button"
                                                id="submit_btn">{{ labels('admin_labels.update_settings', 'Update Settings') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </section>
@endsection
