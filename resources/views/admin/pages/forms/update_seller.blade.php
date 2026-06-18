@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.update_seller', 'Update Seller') }}
@endsection
@section('content')
    @php
        use App\Models\City;
        use App\Models\Zone;
        use App\Services\TranslationService;
        use App\Services\MediaService;
    @endphp
    <x-admin.breadcrumb :title="labels('admin_labels.update_seller', 'Update Seller')" :subtitle="labels(
        'admin_labels.empower_your_marketplace_with_seamless_seller_integration',
        'Empower Your Marketplace with Seamless Seller Integration.',
    )" :breadcrumbs="[
        ['label' => labels('admin_labels.sellers', 'Sellers'), 'url' => route('sellers.index')],
        ['label' => labels('admin_labels.update_seller', 'Update Seller')],
    ]" />

    <div>
        <form class="form-horizontal form-submit-event submit_form"
            action="{{ route('admin.sellers.update', $seller_data->id) }}" method="POST">
            @method('PUT')
            @csrf
            <input type="hidden" name="edit_store_ids"
                value="{{ isset($store_data[0]->store_id) && !empty($store_data[0]->store_id) ? $store_data[0]->store_id : '' }}">
            <input type="hidden" name="edit_store_id" value="{{ isset($store_id) && !empty($store_id) ? $store_id : '' }}">
            <input type="hidden" name="edit_store_name"
                value="{{ isset($store_data[0]->store_name) && !empty($store_data[0]->store_name) ? $store_data[0]->store_name : '' }}">
            <input type="hidden" name="edit_store_url"
                value="{{ isset($store_data[0]->store_url) && !empty($store_data[0]->store_url) ? $store_data[0]->store_url : '' }}">
            <input type="hidden" name="edit_store_description"
                value="{{ isset($store_data[0]->store_description) && !empty($store_data[0]->store_description) ? $store_data[0]->store_description : '' }}">
            <input type="hidden" name="edit_store_logo"
                value="{{ isset($store_data[0]->logo) && !empty($store_data[0]->logo) ? $store_data[0]->logo : '' }}">
            <input type="hidden" name="edit_store_thumbnail"
                value="{{ isset($store_data[0]->store_thumbnail) && !empty($store_data[0]->store_thumbnail) ? $store_data[0]->store_thumbnail : '' }}">
            <input type="hidden" name="edit_address_proof"
                value="{{ isset($store_data[0]->address_proof) && !empty($store_data[0]->address_proof) ? $store_data[0]->address_proof : '' }}">
            <input type="hidden" name="edit_profile_image"
                value="{{ isset($store_data[0]->edit_profile_image) && !empty($store_data[0]->edit_profile_image) ? $store_data[0]->edit_profile_image : '' }}">
            <input type="hidden" name="edit_authorized_signature"
                value="{{ isset($store_data[0]->seller->authorized_signature) && !empty($store_data[0]->seller->authorized_signature) ? $store_data[0]->seller->authorized_signature : '' }}">
            <input type="hidden" name="edit_national_identity_card"
                value="{{ isset($store_data[0]->seller->national_identity_card) && !empty($store_data[0]->seller->national_identity_card) ? $store_data[0]->seller->national_identity_card : '' }}">

            <textarea cols="20" rows="20" id="cat_data" name="commission_data" class="image-upload-btn"></textarea>
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    {{ labels('admin_labels.seller_details', 'Seller Details') }}
                                </h5>
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label for="firstName" class="form-label">
                                            {{ labels('admin_labels.name', 'Name') }}
                                            <span class="text-asterisks text-sm">*</span>
                                        </label>
                                        <input class="form-control" type="text" id="name" name="name"
                                            value="{{ isset($seller_data->username) ? $seller_data->username : '' }}"
                                            autofocus />
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="phone">
                                            {{ labels('admin_labels.mobile', 'Mobile') }}
                                            <span class="text-asterisks text-sm">*</span>
                                        </label>
                                        <div class="input-group input-group-merge">
                                            <select class="form-select" id="country_code" name="country_code"
                                                style="max-width: 90px;">
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
                                                <option value="91">+91</option>
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
                                            @php
                                                $sellerCountryCode = isset($seller_data->country_code) && !empty($seller_data->country_code) ? $seller_data->country_code : '91';
                                            @endphp
                                            <script>
                                                (function() {
                                                    var ccSelect = document.getElementById('country_code');
                                                    if (ccSelect) {
                                                        var stored = '{{ $sellerCountryCode }}';
                                                        var opt = ccSelect.querySelector('option[value="' + stored + '"]');
                                                        if (opt) {
                                                            ccSelect.value = stored;
                                                        }
                                                    }
                                                })();
                                            </script>

                                            <input type="number" id="phone" name="mobile" min='1'
                                                maxlength="16" oninput="validateNumberInput(this)" class="form-control"
                                                placeholder=""
                                                value="{{ isset($seller_data->mobile) ? $seller_data->mobile : '' }}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="email">
                                            {{ labels('admin_labels.email', 'Email') }}
                                            <span class="text-asterisks text-sm">*</span>
                                        </label>
                                        <div class="input-group input-group-merge">
                                            <input class="form-control" type="email" name="email"
                                                value="{{ isset($seller_data->email) ? $seller_data->email : '' }}">
                                        </div>
                                    </div>
                                    <div class="mb-3 col-md-6 form-password-toggle">
                                        <label class="form-label"
                                            for="address">{{ labels('admin_labels.address', 'Address') }}
                                            <span class="text-asterisks text-sm">*</span>
                                        </label>
                                        <textarea name="address" class="form-control" placeholder="{{ labels('admin_labels.write_here_your_address', 'Write here your address') }}">{{ isset($seller_data->address) ? $seller_data->address : '' }}</textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label"
                                                for="basic-default-phone">{{ labels('admin_labels.profile_image', 'Profile Image') }}
                                                <span class="text-asterisks text-sm">*</span>
                                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                    data-bs-placement="right"
                                                    data-bs-content="{{ labels('admin_labels.upload_profile_image_for_seller_popover', 'Upload a profile image for the seller.') }}"></i>
                                            </label>
                                            <input type="file" class="filepond" name="profile_image"
                                                data-max-file-size="30MB" data-max-files="20" accept="image/*,.webp" />
                                            @if (isset($seller_data->image) && !empty($seller_data->image))
                                                @php
                                                    $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;
                                                    $imagePath = $isPublicDisk
                                                        ? asset(
                                                            config('constants.SELLER_IMG_PATH') . $seller_data->image,
                                                        )
                                                        : $seller_data->image;
                                                @endphp
                                                <div class="col-md-12">
                                                    <label for="" class="text-danger">*Only Choose When Update is
                                                        necessary</label>
                                                    <div class="container-fluid row image-upload-section">
                                                        <div
                                                            class="col-md-9 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image store-image-container">
                                                            <div class='image-upload-div'>
                                                                <img src="{{ route('admin.dynamic_image', [
                                                                    'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                                    'width' => 150,
                                                                    'quality' => 90,
                                                                ]) }}"
                                                                    alt="user-avatar" class="d-block rounded"
                                                                    id="uploadedAvatar" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"
                                                for="basic-default-phone">{{ labels('admin_labels.address_proof', 'Address Proof') }}
                                                <span class="text-asterisks text-sm">*</span>
                                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                    data-bs-placement="right"
                                                    data-bs-content="{{ labels('admin_labels.upload_valid_address_proof_document_popover', 'Upload a valid address proof document.') }}"></i>
                                            </label>
                                            <input type="file" class="filepond" name="address_proof"
                                                data-max-file-size="30MB" data-max-files="20" accept="image/*,.webp" />
                                            @if (isset($store_data[0]->address_proof) && !empty($store_data[0]->address_proof))
                                                @php
                                                    $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;
                                                    $imagePath = $isPublicDisk
                                                        ? asset(
                                                            config('constants.SELLER_IMG_PATH') .
                                                                $store_data[0]->address_proof,
                                                        )
                                                        : $store_data[0]->address_proof;
                                                @endphp
                                                <div class="col-md-12">
                                                    <label for="" class="text-danger">*Only Choose When Update is
                                                        necessary</label>
                                                    <div class="container-fluid row image-upload-section">
                                                        <div
                                                            class="col-md-9 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image store-image-container">
                                                            <div class='image-upload-div'>
                                                                <img src="{{ route('admin.dynamic_image', [
                                                                    'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                                    'width' => 150,
                                                                    'quality' => 90,
                                                                ]) }}"
                                                                    alt="user-avatar" class="d-block rounded"
                                                                    id="uploadedAvatar" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label"
                                                for="basic-default-phone">{{ labels('admin_labels.authorized_signature', 'Authorized Signature') }}
                                                <span class="text-asterisks text-sm">*</span>
                                            </label>
                                            <input type="file" class="filepond" name="authorized_signature" multiple
                                                data-max-file-size="30MB" data-max-files="20" accept="image/*,.webp" />
                                            @if (isset($store_data[0]->seller->authorized_signature) && !empty($store_data[0]->seller->authorized_signature))
                                                @php
                                                    $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;
                                                    $imagePath = $isPublicDisk
                                                        ? asset(
                                                            config('constants.SELLER_IMG_PATH') .
                                                                $store_data[0]->seller->authorized_signature,
                                                        )
                                                        : $store_data[0]->seller->authorized_signature;
                                                @endphp
                                                <div class="col-md-12">
                                                    <label for="" class="text-danger">*Only Choose When Update is
                                                        necessary</label>
                                                    <div class="container-fluid row image-upload-section">
                                                        <div
                                                            class="col-md-9 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image store-image-container">
                                                            <div class='image-upload-div'>
                                                                <img src="{{ route('admin.dynamic_image', [
                                                                    'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                                    'width' => 150,
                                                                    'quality' => 90,
                                                                ]) }}"
                                                                    alt="user-avatar" class="d-block rounded"
                                                                    id="uploadedAvatar" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    {{ labels('admin_labels.comission', 'Commission') }}
                                </h5>
                                <div class="form-group col-md-12">
                                    <label for="commission"
                                        class="col-sm-12 form-label">{{ labels('admin_labels.comission', 'Commission') }}(%)
                                        <small>(Commission(%) to be given to the Super Admin on order item
                                            globally.)</small>
                                    </label>
                                    <input type="number" class="form-control" max=100 min=0 id="global_commission"
                                        placeholder="{{ labels('admin_labels.enter_commission_to_be_given_to_super_admin', 'Enter Commission(%) to be given to the Super Admin on order item.') }}"
                                        name="global_commission"
                                        value="{{ isset($store_data[0]->commission) ? $store_data[0]->commission : '' }}">
                                </div>
                                @php
                                    $category_html = getCategoriesOptionHtml($categories, $existing_category_ids);
                                @endphp
                                <div class="form-group row">
                                    <label for="commission"
                                        class="col-sm-12 form-label">{{ labels('admin_labels.choose_categories_and_commission', 'Choose Categories & Commission') }}(%)
                                    </label>
                                    <div class="image-upload-btn" id="cat_html">
                                        <?= $category_html ?>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <div class="">
                                        {{-- @dd($store_data[0]->seller_id, $store_data[0]->category_ids); --}}
                                        <a href="javascript:void(0)" id="seller_model"
                                            data-seller_id="<?= isset($store_data[0]->seller_id) && !empty($store_data[0]->seller_id) ? $store_data[0]->seller_id : '' ?>"
                                            data-cat_ids="<?= isset($store_data[0]->id) && !empty($store_data[0]->id) && isset($store_data[0]->category_ids) && !empty($store_data[0]->category_ids) ? $store_data[0]->category_ids : '' ?>"
                                            class="btn text-white btn-primary btn-sm"
                                            title="{{ labels('admin_labels.manage_categories_and_commission', 'Manage Categories & Commission') }}" data-bs-toggle="offcanvas"
                                            data-bs-target="#set_commission_offcanvas">
                                            {{ labels('admin_labels.add_category_comission', 'Add Category Commission') }}
                                        </a>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-3 mb-3" role="alert">
                                    <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-info-circle me-2"></i>How to Add & Save Commission:</h6>
                                    <hr class="my-2 text-primary">
                                    <ul class="mb-0 small text-dark">
                                        <li><strong>Step 1:</strong> Click the <strong>"Add Category Commission"</strong> button.</li>
                                        <li><strong>Step 2:</strong> In the popup, select your categories and enter the commission percentage for each.</li>
                                        <li><strong>Step 3:</strong> Click <strong>"Save"</strong> inside the popup to confirm your selection.</li>
                                        <li><strong>Step 4:</strong> <span class="text-danger fw-bold">IMPORTANT:</span> Finally, click the <strong>"Update Seller"</strong> button at the bottom of this page to permanently save all changes.</li>
                                    </ul>
                                    <hr class="my-2 text-primary">
                                    <h6 class="alert-heading fw-bold mb-1 mt-2">{{ labels('admin_labels.commission_logic', 'Commission Logic:') }}</h6>
                                    <ul class="mb-0 small text-dark">
                                        <li><strong>Global Commission:</strong> Applies to all products by default if no specific category commission is set.</li>
                                        <li><strong>Category Commission:</strong> Overrides Global Commission for specific categories.</li>
                                    </ul>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <strong>Note:</strong> The system first checks for a <strong>Category Commission</strong>. If one exists, it is used. If not, the <strong>Global Commission</strong> is applied.
                                </small>
                            </div>
                        </div>
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    {{ labels('admin_labels.bank_details', 'Bank Details') }}
                                </h5>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <div class="mb-3">
                                            <label for="tax_name"
                                                class="col-sm-12 form-label">{{ labels('admin_labels.account_number', 'Account Number') }}
                                                <span class='text-asterisks text-sm'>*</span>
                                            </label>
                                            <input type="text" class="form-control" id="account_number"
                                                placeholder="{{ labels('admin_labels.account_number_placeholder', 'Account Number') }}" name="account_number"
                                                value="{{ isset($store_data[0]->account_number) ? $store_data[0]->account_number : '' }}">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="mb-3">
                                            <label for="tax_name"
                                                class="col-sm-4 form-label">{{ labels('admin_labels.account_name', 'Account Name') }}
                                                <span class='text-asterisks text-sm'>*</span>
                                            </label>
                                            <input type="text" class="form-control" id="account_name"
                                                placeholder="{{ labels('admin_labels.account_name_placeholder', 'Account Name') }}" name="account_name"
                                                value="{{ isset($store_data[0]->account_name) ? $store_data[0]->account_name : '' }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <div class="mb-3">
                                            <label for="tax_name"
                                                class="col-sm-4 form-label">{{ labels('admin_labels.bank_name', 'Bank Name') }}
                                                <span class='text-asterisks text-sm'>*</span>
                                            </label>
                                            <input type="text" class="form-control" id="bank_name"
                                                placeholder="{{ labels('admin_labels.bank_name_placeholder', 'Bank Name') }}" name="bank_name"
                                                value="{{ isset($store_data[0]->bank_name) ? $store_data[0]->bank_name : '' }}">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="mb-3">
                                            <label for="tax_name"
                                                class="col-sm-4 form-label">{{ labels('admin_labels.bank_code', 'Bank Code') }}
                                                <span class='text-asterisks text-sm'>*</span>
                                            </label>
                                            <input type="text" class="form-control" id="bank_code"
                                                placeholder="{{ labels('admin_labels.bank_code_placeholder', 'Bank Code') }}" name="bank_code"
                                                value="{{ isset($store_data[0]->bank_code) ? $store_data[0]->bank_code : '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    {{ labels('admin_labels.store_details', 'Store Details') }}
                                </h5>
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label"
                                            for="store_name">{{ labels('admin_labels.store_name', 'Store Name') }}
                                            <span class="text-asterisks text-sm">*</span></label>
                                        <div class="input-group input-group-merge">
                                            <input type="text" name="store_name" class="form-control"
                                                placeholder="{{ labels('admin_labels.starbucks_placeholder', 'starbucks') }}"
                                                value="{{ isset($store_data[0]->store_name) && !empty($store_data[0]->store_name) ? $store_data[0]->store_name : '' }}" />
                                        </div>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label"
                                            for="store_url">{{ labels('admin_labels.store_url', 'Store URL') }}
                                        </label>
                                        <div class="input-group input-group-merge">
                                            <input type="text" name="store_url" class="form-control"
                                                placeholder="{{ labels('admin_labels.starbucks_placeholder', 'starbucks') }}" value="{{ $store_data[0]->store_url }}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"
                                                for="basic-default-phone">{{ labels('admin_labels.logo', 'Logo') }}
                                                <span class="text-asterisks text-sm">*</span></label>
                                            <input type="file" class="filepond" name="store_logo" multiple
                                                data-max-file-size="30MB" data-max-files="20" accept="image/*,.webp" />
                                            @if ($store_data[0]->logo && !empty($store_data[0]->logo))
                                                @php
                                                    $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;
                                                    $imagePath = $isPublicDisk
                                                        ? asset(
                                                            config('constants.SELLER_IMG_PATH') . $store_data[0]->logo,
                                                        )
                                                        : $store_data[0]->logo;
                                                @endphp
                                                <div class="col-md-12">
                                                    <label for="" class="text-danger">*Only Choose When Update is
                                                        necessary</label>
                                                    <div class="container-fluid row image-upload-section">
                                                        <div
                                                            class="col-md-9 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image store-image-container">
                                                            <div class='image-upload-div'>
                                                                <img src="{{ route('admin.dynamic_image', [
                                                                    'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                                    'width' => 150,
                                                                    'quality' => 90,
                                                                ]) }}"
                                                                    alt="user-avatar" class="d-block rounded mt-2"
                                                                    id="uploadedAvatar" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"
                                                for="basic-default-phone">{{ labels('admin_labels.store_thumbnail', 'Store Thumbnail') }}
                                                <span class="text-asterisks text-sm">*</span></label>
                                            <input type="file" class="filepond" name="store_thumbnail" multiple
                                                data-max-file-size="30MB" data-max-files="20" accept="image/*,.webp" />
                                            @if ($store_data[0]->store_thumbnail && !empty($store_data[0]->store_thumbnail))
                                                @php
                                                    $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;
                                                    $imagePath = $isPublicDisk
                                                        ? asset(
                                                            config('constants.SELLER_IMG_PATH') .
                                                                $store_data[0]->store_thumbnail,
                                                        )
                                                        : $store_data[0]->store_thumbnail;
                                                @endphp
                                                <div class="col-md-12">
                                                    <label for="" class="text-danger">*Only Choose When Update is
                                                        necessary</label>
                                                    <div class="container-fluid row image-upload-section">
                                                        <div
                                                            class="col-md-9 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image store-image-container">
                                                            <div class='image-upload-div'>
                                                                <img src="{{ route('admin.dynamic_image', [
                                                                    'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                                    'width' => 150,
                                                                    'quality' => 90,
                                                                ]) }}"
                                                                    alt="user-avatar" class="d-block rounded mt-2"
                                                                    id="uploadedAvatar" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label"
                                                for="basic-default-company">{{ labels('admin_labels.other_documents', 'Other Documents') }}</label>
                                            <small>({{ $note_for_necessary_documents }})</small>
                                            <input type="file" class="filepond" name="other_documents[]" multiple
                                                data-max-file-size="300MB" data-max-files="200" />
                                        </div>
                                        @php
                                            $other_documents = json_decode($store_data[0]->other_documents);
                                        @endphp
                                        @if (!empty($other_documents))
                                            <label for="" class="text-danger">*Only Choose When Update is
                                                necessary</label>
                                            <div class="container-fluid">
                                                <div class="row g-3">
                                                    @foreach ($other_documents as $row)
                                                        @php
                                                            $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;
                                                            $imagePath = $isPublicDisk
                                                                ? asset(
                                                                    config('constants.SELLER_IMG_PATH') . '/' . $row,
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
                                                                        alt="{{ labels('admin_labels.not_found_alt', 'Not Found') }}" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label"
                                                for="basic-default-company">{{ labels('admin_labels.description', 'Description') }}
                                                <span class="text-asterisks text-sm">*</span></label>
                                            <textarea id="basic-default-message" value="" name="description" class="form-control"
                                                placeholder="{{ labels('admin_labels.write_some_description_here', 'Write some description here') }}">{{ $store_data[0]->store_description }}</textarea>
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
                                                        {{ app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $store_data[0]->city, $language_code) ?? labels('admin_labels.selected_city_default', 'Selected City') }}
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
                                            <select class="form-select zipcode_list" name="zipcode"
                                                data-selected-id="{{ $selected_zipcode_id }}"
                                                data-selected-text="{{ $selected_zipcode_text }}">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="zipcode" class="form-label">
                                                {{ labels('admin_labels.deliverable_type', 'Deliverable Type') }}
                                            </label>
                                            <select class="form-select deliverable_type" name="deliverable_type"
                                                id="deliverable_type">
                                                <option value="1"
                                                    {{ $store_data[0]->deliverable_type == '1' ? 'selected' : '' }}>{{ labels('admin_labels.all_option', 'All') }}
                                                </option>
                                                <option value="2"
                                                    {{ $store_data[0]->deliverable_type == '2' ? 'selected' : '' }}>
                                                    {{ labels('admin_labels.included', 'Included') }}</option>
                                            </select>
                                            <small class="text-danger d-block mt-1">
                                                <strong>Note:</strong> Changing this setting will affect product
                                                deliverability settings. If you modify this, ensure that product
                                                deliverability settings are updated accordingly, or it may result in errors
                                                where products are marked as non-deliverable.
                                            </small>
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
                                            <label for="zipcodes" class="form-label">
                                                {{ labels('admin_labels.deliverable_zones', 'Deliverable Zones') }}
                                                <span class="text-asterisks text-sm">*</span>
                                            </label>
                                            <select name="deliverable_zones[]" class="search_zone form-select w-100"
                                                multiple id="deliverable_zones"
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
                                                            {{ implode(', ', $row->serviceable_city_names) }} |
                                                            Serviceable Zipcodes:
                                                            {{ implode(', ', $row->serviceable_zipcodes) }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for=""
                                        class="form-label">{{ labels('admin_labels.status', 'Status') }}
                                    </label>
                                    <div class="mt-2">
                                        <div id="stsatus" class="btn-group" role="group" aria-label="Status">
                                            <label class="btn btn-outline-secondary flex-fill">
                                                <input type="radio" name="status" class="mx-1" value="0"
                                                    {{ isset($store_data[0]->seller->status) && $store_data[0]->seller->status == '0' ? 'Checked' : '' }}>
                                                Deactive
                                            </label>
                                            <label class="btn btn-outline-primary flex-fill">
                                                <input type="radio" name="status" class="mx-1" value="1"
                                                    {{ isset($store_data[0]->seller->status) && $store_data[0]->seller->status == '1' ? 'Checked' : '' }}>
                                                Approved
                                            </label>
                                            <label class="btn btn-outline-danger flex-fill">
                                                <input type="radio" name="status" class="mx-1" value="2"
                                                    {{ isset($store_data[0]->seller->status) && $store_data[0]->seller->status == '2' ? 'Checked' : '' }}>
                                                Not-Approved
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for=""
                                        class="form-label">{{ labels('admin_labels.store_status', 'Store Status') }}
                                    </label>
                                    <div class="mt-2">
                                        <div id="stsatus" class="btn-group" role="group" aria-label="Status">
                                            <label class="btn btn-outline-primary flex-fill">
                                                <input type="radio" name="store_status" class="mx-1" value="1"
                                                    {{ isset($store_data[0]->status) && $store_data[0]->status == '1' ? 'Checked' : '' }}>
                                                Approved
                                            </label>
                                            <label class="btn btn-outline-danger flex-fill">
                                                <input type="radio" name="store_status" class="mx-1" value="2"
                                                    {{ isset($store_data[0]->status) && $store_data[0]->status == '2' ? 'Checked' : '' }}>
                                                Not-Approved
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    {{ labels('admin_labels.other_details', 'Other Details') }}
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_name"
                                                class="form-label">{{ labels('admin_labels.tax_name', 'Tax Name') }}
                                            </label>
                                            <div>
                                                <input type="text" class="form-control" id="tax_name"
                                                    placeholder="{{ labels('admin_labels.tax_name_placeholder', 'Tax Name') }}" name="tax_name"
                                                    value="{{ isset($store_data[0]->tax_name) ? $store_data[0]->tax_name : '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_number"
                                                class="form-label">{{ labels('admin_labels.tax_number', 'Tax Number') }}
                                            </label>
                                            <div>
                                                <input type="text" class="form-control" id="tax_number"
                                                    placeholder="{{ labels('admin_labels.tax_number_placeholder', 'Tax Number') }}" name="tax_number"
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
                                                <input type="text" class="form-control" id="pan_number"
                                                    placeholder="{{ labels('admin_labels.pan_number_placeholder', 'Pan Number') }}" name="pan_number"
                                                    value="{{ isset($store_data[0]->pan_number) ? $store_data[0]->pan_number : '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="latitude"
                                                class="form-label">{{ labels('admin_labels.latitude', 'Latitude') }}</label>
                                            <div>
                                                <input type="text" class="form-control" id="latitude"
                                                    placeholder="{{ labels('admin_labels.latitude_placeholder', 'Latitude') }}" name="latitude"
                                                    value="{{ isset($store_data[0]->latitude) ? $store_data[0]->latitude : '' }}">
                                            </div>
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
                                                    placeholder="{{ labels('admin_labels.longitude_placeholder', 'Longitude') }}" name="longitude"
                                                    value="{{ isset($store_data[0]->longitude) ? $store_data[0]->longitude : '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="national_identity_card"
                                                class="form-label">{{ labels('admin_labels.national_identity_card', 'National Identity Card') }}
                                            </label>
                                            <div>
                                                <input type="file" class="filepond" name="national_identity_card"
                                                    multiple data-max-file-size="30MB" data-max-files="20"
                                                    accept="image/*,.webp" />
                                                @if ($store_data[0]->seller->national_identity_card && !empty($store_data[0]->seller->national_identity_card))
                                                    @php
                                                        $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;
                                                        $imagePath = $isPublicDisk
                                                            ? asset(
                                                                config('constants.SELLER_IMG_PATH') .
                                                                    $store_data[0]->seller->national_identity_card,
                                                            )
                                                            : $store_data[0]->seller->national_identity_card;
                                                    @endphp
                                                    <div class="col-md-12">
                                                        <label for="" class="text-danger">*Only Choose When Update
                                                            is necessary</label>
                                                        <div class="container-fluid row image-upload-section">
                                                            <div
                                                                class="col-md-9 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image store-image-container">
                                                                <div class='image-upload-div'>
                                                                    <img src="{{ route('admin.dynamic_image', [
                                                                        'url' => app(MediaService::class)->getMediaImageUrl($imagePath),
                                                                        'width' => 150,
                                                                        'quality' => 90,
                                                                    ]) }}"
                                                                        alt="user-avatar" class="d-block rounded"
                                                                        id="uploadedAvatar" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php
                            if (isset($store_data[0]->permissions) && !empty($store_data[0]->permissions)) {
                                $permit = json_decode($store_data[0]->permissions, true);
                            }
                        @endphp
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="mb-3">{{ labels('admin_labels.permissions', 'Permissions') }}</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="require_products_approval"
                                                class="col-sm-6 col-form-label">{{ labels('admin_labels.require_product_approvel', 'Require Product Approvel') }}?
                                            </label>
                                            <div class="col-sm-6 form-check form-switch">
                                                <input type="checkbox" class="form-check-input mx-2 float-end"
                                                    {{ isset($permit['require_products_approval']) && $permit['require_products_approval'] == '1' ? 'Checked' : '' }}
                                                    id="require_products_approval" name="require_products_approval">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="customer_privacy"
                                                class="col-sm-5 col-form-label">{{ labels('admin_labels.view_customer_details', 'View Customer Details') }}?
                                            </label>
                                            <div class="col-sm-7 form-check form-switch">
                                                <input type="checkbox" name="customer_privacy"
                                                    {{ isset($permit['customer_privacy']) && $permit['customer_privacy'] == '1' ? 'Checked' : '' }}
                                                    class="form-check-input mx-2 float-end">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row d-none">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="view_order_otp"
                                                class="col-sm-8 col-form-label">{{ labels('admin_labels.view_order_otp_and_change_delivery_status', 'View Order OTP & Can Change Delivery Status') }}?
                                            </label>
                                            <div class="col-sm-4 form-check form-switch">
                                                <input type="checkbox" name="view_order_otp"
                                                    {{ isset($permit['view_order_otp']) && $permit['view_order_otp'] == '1' ? 'Checked' : '' }}
                                                    class="form-check-input mx-2 float-end">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button type="submit"
                    class="btn btn-primary submit_button">{{ labels('admin_labels.update', 'Update') }}</button>
            </div>
        </form>
    </div>

    {{-- commission offcanvas --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="set_commission_offcanvas"
        aria-labelledby="set_commission_offcanvasLabel" role="dialog">
        <div class="offcanvas-header">
            <h5 id="set_commission_offcanvasLabel">
                {{ labels('admin_labels.categories_and_commission', 'Categories & Commission (%)') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <form class="form-horizontal overflow-auto" id="add-seller-commission-form" action="" method="POST"
            enctype="multipart/form-data">
            @csrf
            <div class="offcanvas-body">
                <label for="Categories"
                    class="col-sm-12 form-label">{{ labels('admin_labels.categories', 'Categories') }}</label>
                <div id="category_section"></div>
                <div class="form-group col-md-12">
                    <button type="button" id="add_category" class="btn btn-primary btn-xs">
                        <i class="fa fa-plus"></i> {{ labels('admin_labels.add_more_category', 'Add More Category') }}
                    </button>
                </div>
                <a href="{{ route('categories.store') }}" class="btn btn-outline-primary btn-xs" target="_blank">
                    <i class="fa fa-plus-circle"></i> {{ labels('admin_labels.add_new_category', 'Add New Category') }}
                </a>
            </div>
            <div class="offcanvas-footer d-flex justify-content-end">
                <button type="reset"
                    class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                <button type="submit" class="btn btn-primary mx-2"
                    id="save_btn">{{ labels('admin_labels.save', 'Save') }}</button>
            </div>
        </form>
    </div>
@endsection
