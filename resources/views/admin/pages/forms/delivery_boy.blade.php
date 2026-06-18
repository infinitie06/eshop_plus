@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.delivery_boys', 'Delivery Boys') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.delivery_boys', 'Delivery Boys')" :subtitle="labels(
            'admin_labels.optimize_and_control_your_fleet_of_delivery_personnel',
            'Optimize and Control Your Fleet of Delivery Personnel',
        )" :breadcrumbs="[['label' => labels('admin_labels.delivery_boys', 'Delivery Boys')]]" />

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">
                        {{ !trans()->has('admin_labels.add_delivery_boy') ? 'Add Delivery Boy' : trans('admin_labels.add_delivery_boy') }}
                    </h5>
                    <div class="row">
                        <div class="form-group">
                            <form class="form-horizontal form-submit-event submit_form"
                                action="{{ route('delivery_boys.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="mb-3 col-lg-6 col-md-12 ">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.name') ? 'Name' : trans('admin_labels.name') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <input type="text" class="form-control" placeholder="" name="name"
                                            value="{{ old('name') }}">

                                    </div>
                                    <div class="mb-3 col-lg-6 col-md-12 ">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.mobile') ? 'Mobile' : trans('admin_labels.mobile') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <div class="input-group input-group-merge">
                                            <select class="form-select" id="country_code" name="country_code"
                                                style="max-width: 90px;">
                                                <option value="93">+93 Afghanistan</option>
                                                <option value="355">+355 Albania</option>
                                                <option value="213">+213 Algeria</option>
                                                <option value="1684">+1684 American Samoa</option>
                                                <option value="376">+376 Andorra</option>
                                                <option value="244">+244 Angola</option>
                                                <option value="1264">+1264 Anguilla</option>
                                                <option value="1268">+1268 Antigua & Barbuda</option>
                                                <option value="54">+54 Argentina</option>
                                                <option value="374">+374 Armenia</option>
                                                <option value="297">+297 Aruba</option>
                                                <option value="61">+61 Australia</option>
                                                <option value="43">+43 Austria</option>
                                                <option value="994">+994 Azerbaijan</option>
                                                <option value="1242">+1242 Bahamas</option>
                                                <option value="973">+973 Bahrain</option>
                                                <option value="880">+880 Bangladesh</option>
                                                <option value="1246">+1246 Barbados</option>
                                                <option value="375">+375 Belarus</option>
                                                <option value="32">+32 Belgium</option>
                                                <option value="501">+501 Belize</option>
                                                <option value="229">+229 Benin</option>
                                                <option value="1441">+1441 Bermuda</option>
                                                <option value="975">+975 Bhutan</option>
                                                <option value="591">+591 Bolivia</option>
                                                <option value="387">+387 Bosnia & Herzegovina</option>
                                                <option value="267">+267 Botswana</option>
                                                <option value="55">+55 Brazil</option>
                                                <option value="246">+246 British Indian Ocean Territory</option>
                                                <option value="673">+673 Brunei</option>
                                                <option value="359">+359 Bulgaria</option>
                                                <option value="226">+226 Burkina Faso</option>
                                                <option value="257">+257 Burundi</option>
                                                <option value="855">+855 Cambodia</option>
                                                <option value="237">+237 Cameroon</option>
                                                <option value="1">+1 Canada</option>
                                                <option value="238">+238 Cape Verde</option>
                                                <option value="1345">+1345 Cayman Islands</option>
                                                <option value="236">+236 Central African Republic</option>
                                                <option value="235">+235 Chad</option>
                                                <option value="56">+56 Chile</option>
                                                <option value="86">+86 China</option>
                                                <option value="61">+61 Christmas Island</option>
                                                <option value="61">+61 Cocos Islands</option>
                                                <option value="57">+57 Colombia</option>
                                                <option value="269">+269 Comoros</option>
                                                <option value="682">+682 Cook Islands</option>
                                                <option value="506">+506 Costa Rica</option>
                                                <option value="385">+385 Croatia</option>
                                                <option value="53">+53 Cuba</option>
                                                <option value="599">+599 Curacao</option>
                                                <option value="357">+357 Cyprus</option>
                                                <option value="420">+420 Czech Republic</option>
                                                <option value="45">+45 Denmark</option>
                                                <option value="253">+253 Djibouti</option>
                                                <option value="1767">+1767 Dominica</option>
                                                <option value="1809">+1809 Dominican Republic</option>
                                                <option value="593">+593 Ecuador</option>
                                                <option value="20">+20 Egypt</option>
                                                <option value="503">+503 El Salvador</option>
                                                <option value="240">+240 Equatorial Guinea</option>
                                                <option value="291">+291 Eritrea</option>
                                                <option value="372">+372 Estonia</option>
                                                <option value="251">+251 Ethiopia</option>
                                                <option value="500">+500 Falkland Islands</option>
                                                <option value="298">+298 Faroe Islands</option>
                                                <option value="679">+679 Fiji</option>
                                                <option value="358">+358 Finland</option>
                                                <option value="33">+33 France</option>
                                                <option value="594">+594 French Guiana</option>
                                                <option value="689">+689 French Polynesia</option>
                                                <option value="241">+241 Gabon</option>
                                                <option value="220">+220 Gambia</option>
                                                <option value="995">+995 Georgia</option>
                                                <option value="49">+49 Germany</option>
                                                <option value="233">+233 Ghana</option>
                                                <option value="350">+350 Gibraltar</option>
                                                <option value="30">+30 Greece</option>
                                                <option value="299">+299 Greenland</option>
                                                <option value="1473">+1473 Grenada</option>
                                                <option value="590">+590 Guadeloupe</option>
                                                <option value="1671">+1671 Guam</option>
                                                <option value="502">+502 Guatemala</option>
                                                <option value="224">+224 Guinea</option>
                                                <option value="245">+245 Guinea-Bissau</option>
                                                <option value="592">+592 Guyana</option>
                                                <option value="509">+509 Haiti</option>
                                                <option value="504">+504 Honduras</option>
                                                <option value="852">+852 Hong Kong</option>
                                                <option value="36">+36 Hungary</option>
                                                <option value="354">+354 Iceland</option>
                                                <option value="91" selected>+91 India</option>
                                                <option value="62">+62 Indonesia</option>
                                                <option value="98">+98 Iran</option>
                                                <option value="964">+964 Iraq</option>
                                                <option value="353">+353 Ireland</option>
                                                <option value="972">+972 Israel</option>
                                                <option value="39">+39 Italy</option>
                                                <option value="1876">+1876 Jamaica</option>
                                                <option value="81">+81 Japan</option>
                                                <option value="962">+962 Jordan</option>
                                                <option value="7">+7 Kazakhstan</option>
                                                <option value="254">+254 Kenya</option>
                                                <option value="686">+686 Kiribati</option>
                                                <option value="82">+82 South Korea</option>
                                                <option value="965">+965 Kuwait</option>
                                                <option value="996">+996 Kyrgyzstan</option>
                                                <option value="856">+856 Laos</option>
                                                <option value="371">+371 Latvia</option>
                                                <option value="961">+961 Lebanon</option>
                                                <option value="266">+266 Lesotho</option>
                                                <option value="231">+231 Liberia</option>
                                                <option value="218">+218 Libya</option>
                                                <option value="423">+423 Liechtenstein</option>
                                                <option value="370">+370 Lithuania</option>
                                                <option value="352">+352 Luxembourg</option>
                                                <option value="853">+853 Macau</option>
                                                <option value="389">+389 North Macedonia</option>
                                                <option value="261">+261 Madagascar</option>
                                                <option value="265">+265 Malawi</option>
                                                <option value="60">+60 Malaysia</option>
                                                <option value="960">+960 Maldives</option>
                                                <option value="223">+223 Mali</option>
                                                <option value="356">+356 Malta</option>
                                                <option value="692">+692 Marshall Islands</option>
                                                <option value="596">+596 Martinique</option>
                                                <option value="222">+222 Mauritania</option>
                                                <option value="230">+230 Mauritius</option>
                                                <option value="262">+262 Mayotte</option>
                                                <option value="52">+52 Mexico</option>
                                                <option value="691">+691 Micronesia</option>
                                                <option value="373">+373 Moldova</option>
                                                <option value="377">+377 Monaco</option>
                                                <option value="976">+976 Mongolia</option>
                                                <option value="382">+382 Montenegro</option>
                                                <option value="1664">+1664 Montserrat</option>
                                                <option value="212">+212 Morocco</option>
                                                <option value="258">+258 Mozambique</option>
                                                <option value="95">+95 Myanmar</option>
                                                <option value="264">+264 Namibia</option>
                                                <option value="674">+674 Nauru</option>
                                                <option value="977">+977 Nepal</option>
                                                <option value="31">+31 Netherlands</option>
                                                <option value="599">+599 Netherlands Antilles</option>
                                                <option value="687">+687 New Caledonia</option>
                                                <option value="64">+64 New Zealand</option>
                                                <option value="505">+505 Nicaragua</option>
                                                <option value="227">+227 Niger</option>
                                                <option value="234">+234 Nigeria</option>
                                                <option value="683">+683 Niue</option>
                                                <option value="672">+672 Norfolk Island</option>
                                                <option value="850">+850 North Korea</option>
                                                <option value="47">+47 Norway</option>
                                                <option value="968">+968 Oman</option>
                                                <option value="92">+92 Pakistan</option>
                                                <option value="680">+680 Palau</option>
                                                <option value="970">+970 Palestine</option>
                                                <option value="507">+507 Panama</option>
                                                <option value="675">+675 Papua New Guinea</option>
                                                <option value="595">+595 Paraguay</option>
                                                <option value="51">+51 Peru</option>
                                                <option value="63">+63 Philippines</option>
                                                <option value="48">+48 Poland</option>
                                                <option value="351">+351 Portugal</option>
                                                <option value="1787">+1787 Puerto Rico</option>
                                                <option value="974">+974 Qatar</option>
                                                <option value="262">+262 Reunion</option>
                                                <option value="40">+40 Romania</option>
                                                <option value="7">+7 Russia</option>
                                                <option value="250">+250 Rwanda</option>
                                                <option value="685">+685 Samoa</option>
                                                <option value="378">+378 San Marino</option>
                                                <option value="239">+239 Sao Tome & Principe</option>
                                                <option value="966">+966 Saudi Arabia</option>
                                                <option value="221">+221 Senegal</option>
                                                <option value="381">+381 Serbia</option>
                                                <option value="248">+248 Seychelles</option>
                                                <option value="232">+232 Sierra Leone</option>
                                                <option value="65">+65 Singapore</option>
                                                <option value="421">+421 Slovakia</option>
                                                <option value="386">+386 Slovenia</option>
                                                <option value="677">+677 Solomon Islands</option>
                                                <option value="252">+252 Somalia</option>
                                                <option value="27">+27 South Africa</option>
                                                <option value="34">+34 Spain</option>
                                                <option value="94">+94 Sri Lanka</option>
                                                <option value="249">+249 Sudan</option>
                                                <option value="597">+597 Suriname</option>
                                                <option value="268">+268 Eswatini</option>
                                                <option value="46">+46 Sweden</option>
                                                <option value="41">+41 Switzerland</option>
                                                <option value="963">+963 Syria</option>
                                                <option value="886">+886 Taiwan</option>
                                                <option value="992">+992 Tajikistan</option>
                                                <option value="255">+255 Tanzania</option>
                                                <option value="66">+66 Thailand</option>
                                                <option value="228">+228 Togo</option>
                                                <option value="690">+690 Tokelau</option>
                                                <option value="676">+676 Tonga</option>
                                                <option value="1868">+1868 Trinidad & Tobago</option>
                                                <option value="216">+216 Tunisia</option>
                                                <option value="90">+90 Turkey</option>
                                                <option value="993">+993 Turkmenistan</option>
                                                <option value="1649">+1649 Turks & Caicos Islands</option>
                                                <option value="688">+688 Tuvalu</option>
                                                <option value="256">+256 Uganda</option>
                                                <option value="380">+380 Ukraine</option>
                                                <option value="971">+971 United Arab Emirates</option>
                                                <option value="44">+44 United Kingdom</option>
                                                <option value="1">+1 United States</option>
                                                <option value="598">+598 Uruguay</option>
                                                <option value="998">+998 Uzbekistan</option>
                                                <option value="678">+678 Vanuatu</option>
                                                <option value="379">+379 Vatican City</option>
                                                <option value="58">+58 Venezuela</option>
                                                <option value="84">+84 Vietnam</option>
                                                <option value="1284">+1284 British Virgin Islands</option>
                                                <option value="1340">+1340 US Virgin Islands</option>
                                                <option value="681">+681 Wallis & Futuna</option>
                                                <option value="967">+967 Yemen</option>
                                                <option value="260">+260 Zambia</option>
                                                <option value="263">+263 Zimbabwe</option>

                                            </select>

                                            <input type="text" class="form-control" id="phone" name="mobile" maxlength="16"
                                                placeholder="{{ labels('admin_labels.mobile_number_example', '8787878787') }}" oninput="validateNumberInput(this)"
                                                value="{{ old('mobile') }}">
                                        </div>

                                    </div>
                                    <div class="mb-3 col-lg-6 col-md-12 ">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.email') ? 'Email' : trans('admin_labels.email') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <input type="text" class="form-control" placeholder="" name="email"
                                            value="{{ old('email') }}">

                                    </div>
                                    <div class="mb-3 col-lg-6 col-md-12">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.password') ? 'Password' : trans('admin_labels.password') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control show_seller_password" name="password"
                                                placeholder="{{ labels('admin_labels.enter_your_password_placeholder', 'Enter Your Password') }}">
                                            <span class="input-group-text cursor-pointer toggle_password"><i
                                                    class="bx bx-hide"></i></span>
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="mb-3 col-lg-6 col-md-12">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.confirm_password') ? 'Confirm Password' : trans('admin_labels.confirm_password') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="confirm_password"
                                                placeholder="{{ labels('admin_labels.enter_your_password_placeholder_alt', 'Enter your password') }}" aria-describedby="password" />
                                            <span class="input-group-text cursor-pointer toggle_confirm_password"><i
                                                    class="bx bx-hide"></i></span>
                                        </div>
                                    </div>

                                    <div class="mb-3 col-lg-6 col-md-12 ">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.serviceable_zones') ? 'Serviceable zones' : trans('admin_labels.serviceable_zones') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <select name="serviceable_zones[]" class="form-control search_zone w-100" multiple
                                            onload="multiselect()" id="zone_list">
                                            <option value="">
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="mb-3 col-lg-6 col-md-12 ">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.address') ? 'Address' : trans('admin_labels.address') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <textarea type="text" class="form-control" placeholder="" name="address"
                                            value="">{{ old('address') }}</textarea>

                                    </div>
                                    <div class="mb-3 col-lg-6 col-md-12 ">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.bonus_type') ? 'Bonus Type' : trans('admin_labels.bonus_type') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <select class="form-select form-select-md mb-3 bonus_type"
                                            aria-label=".form-select-md example" name="bonus_type">
                                            <option value="0">
                                                {{ !trans()->has('admin_labels.select_type') ? 'Select Type' : trans('admin_labels.select_type') }}
                                            </option>
                                            <option value="fixed_amount_per_order_item">{{ labels('admin_labels.fixed_amount_per_order_item', 'Fixed Amount Per Order Item') }}
                                            </option>
                                            <option value="percentage_per_order_item">{{ labels('admin_labels.percentage_per_order_item', 'Percentage Per Order Item') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-lg-6 col-md-12  fixed_amount_per_order_item d-none">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.bonus_amount') ? 'Bonus Amount' : trans('admin_labels.bonus_amount') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <input type="number" min=0 class="form-control"
                                            placeholder="{{ labels('admin_labels.placeholder_enter_bonus_amount_db_success', 'Enter amount to be given to the delivery boy on successful order item delivery') }}"
                                            placeholder="" name="bonus_amount" value="{{ old('bonus_amount') }}">

                                    </div>
                                    <div class="mb-3 col-lg-6 col-md-12  percentage_per_order_item d-none">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.bonus_percentage') ? 'Bonus Percentage' : trans('admin_labels.bonus_percentage') }}<span
                                                class='text-asterisks text-sm'>*</span></label>
                                        <input type="number" class="form-control" min=1 max=100
                                            placeholder="{{ labels('admin_labels.placeholder_enter_bonus_percentage_db_success', 'Enter Bonus(%) to be given to the delivery boy on successful order item delivery') }}"
                                            placeholder="" name="bonus_percentage" value="{{ old('bonus_percentage') }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 ">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.driving_licence_front_image') ? 'Driving Licence Front Image' : trans('admin_labels.driving_licence_front_image') }}<span
                                                class="text-asterisks text-sm">*</span></label>

                                        <div class="col-md-12  text-center form-group">
                                            <input type="file" class="filepond" name="front_licence_image"
                                                data-max-file-size="300MB" data-max-files="20" accept="image/*,.webp" />
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-12 ">
                                        <label
                                            class="form-label">{{ !trans()->has('admin_labels.driving_licence_back_image') ? 'Driving Licence Back Image' : trans('admin_labels.driving_licence_back_image') }}<span
                                                class="text-asterisks text-sm">*</span></label>

                                        <div class="col-md-12  text-center form-group">
                                            <input type="file" class="filepond" name="back_licence_image"
                                                data-max-file-size="300MB" data-max-files="20" accept="image/*,.webp" />
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="reset"
                                        class="btn mx-2 reset_button">{{ !trans()->has('admin_labels.reset') ? 'Reset' : trans('admin_labels.reset') }}</button>
                                    <button type="submit"
                                        class="btn btn-primary submit_button">{{ !trans()->has('admin_labels.add_delivery_boy') ? 'Add Delivery Boy' : trans('admin_labels.add_delivery_boy') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ======================= modal for fund transfer ====================== -->


    <div class="modal fade" id="fund_transfer_delivery_boy" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ labels('admin_labels.fund_transfer', 'Fund Transfer') }}</h5>
                    <div class="d-flex justify-content-end"><button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button></div>
                </div>
                <form class="form-horizontal submit_form" action="/admin/fund_transfer/add_fund_transfer" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body row">
                        <input type="hidden" name='delivery_boy_id' id="delivery_boy_id">
                        <div class="form-group col-md-6">
                            <label for="name"
                                class="col-sm-2 col-form-label">{{ labels('admin_labels.name', 'Name') }}</label>

                            <input type="text" class="form-control" id="name" name="name" readonly>

                        </div>
                        <div class="form-group col-md-6">
                            <label for="mobile"
                                class="col-sm-2 col-form-label">{{ labels('admin_labels.mobile', 'Mobile') }}</label>

                            <input type="text" maxlength="16" oninput="validateNumberInput(this)" class="form-control"
                                id="mobile" name="mobile" readonly>

                        </div>
                        <div class="form-group col-md-6">
                            <label for="balance"
                                class="col-sm-2 col-form-label">{{ labels('admin_labels.balance', 'Balance') }}</label>

                            <input type="number" class="form-control" id="balance" min=1 name="balance" readonly>

                        </div>
                        <div class="form-group col-md-6">
                            <label for="transfer_amt"
                                class="col-sm-6 col-form-label">{{ labels('admin_labels.amount', 'Amount') }}</label>

                            <input type="number" min='1' class="form-control" id="transfer_amt" name="transfer_amt">

                        </div>
                        <div class="form-group col-md-12">
                            <label for="message"
                                class="col-sm-2 col-form-label">{{ labels('admin_labels.message', 'Message') }}</label>

                            <input type="text" class="form-control" id="message" name="message">

                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit"
                                class="btn btn-primary">{{ labels('admin_labels.fund_transfer', 'Fund Transfer') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ========================= modal for update delivery boy =========================== -->

    <div class="modal fade" id="edit_delivery_boy" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ labels('admin_labels.update_delivery_boy', 'Update Delivery Boy') }}
                    </h5>
                    <div class="d-flex justify-content-end"><button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button></div>
                </div>
                <div class="modal-body p-4">
                    <form class="form-horizontal form-submit-event submit_form" action="" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="edit_id" value="">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="" class="form-label">{{ labels('admin_labels.name', 'Name') }}<span
                                        class='text-asterisks text-sm'>*</span></label>
                                <input type="text" class="form-control" id="name" placeholder="" name="name" value="">

                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="" class="form-label">{{ labels('admin_labels.mobile', 'Mobile') }}<span
                                        class='text-asterisks text-sm'>*</span></label><br>
                                <div class="input-group input-group-merge">

                                    <select class="form-select" id="edit_country_code" name="country_code"
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

                                    <input type="text" class="form-control" id="edit_phone" name="mobile" maxlength="16"
                                        placeholder="8787878787" oninput="validateNumberInput(this)"
                                        value="{{ old('mobile') }}">
                                </div>

                            </div>

                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="" class="form-label">{{ labels('admin_labels.email', 'Email') }}<span
                                        class='text-asterisks text-sm'>*</span></label>
                                <input type="text" class="form-control" id="email" placeholder="" name="email" value="">

                            </div>
                            <div class="mb-3 col-md-6">
                                <label
                                    class="form-label">{{ labels('admin_labels.serviceable_zones', 'Serviceable Zones') }}<span
                                        class='text-asterisks text-sm'>*</span></label>
                                <select name="serviceable_zones[]"
                                    class="form-control edit_serviceable_zones search_zone w-100" multiple
                                    onload="multiselect()">
                                    <option value="">
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="" class="form-label">{{ labels('admin_labels.address', 'Address') }}<span
                                        class='text-asterisks text-sm'>*</span></label>
                                <textarea type="text" class="form-control" placeholder="" name="address"
                                    value=""></textarea>

                            </div>
                        </div>
                        <div class="row">

                            <div class="mb-3 col-md-6">
                                <label for="" class="form-label">{{ labels('admin_labels.bonus_type', 'Bonus Type') }}<span
                                        class='text-asterisks text-sm'>*</span></label>
                                <select class="form-select form-select-md mb-3 bonus_type"
                                    aria-label=".form-select-md example" name="bonus_type">
                                    <option value="0">{{ labels('admin_labels.select_type_default', 'Select Type') }}</option>
                                    <option value="fixed_amount_per_order_item">{{ labels('admin_labels.fixed_amount_per_order', 'Fixed Amount Per Order') }}</option>
                                    <option value="percentage_per_order_item">{{ labels('admin_labels.percentage_per_order', 'Percentage Per Order') }}</option>
                                </select>
                            </div>
                            <div class="mb-3 col-md-6 edit_fixed_amount_per_order_item fixed_amount_per_order_item d-none">
                                <label for=""
                                    class="form-label">{{ labels('admin_labels.bonus_amount', 'Bonus Amount') }}<span
                                        class='text-asterisks text-sm'>*</span></label>
                                <input type="text" class="form-control edit_bonus_amount"
                                    placeholder="{{ labels('admin_labels.placeholder_enter_bonus_amount_order_success', 'Enter amount to be given to the delivery boy on successful order delivery') }}"
                                    placeholder="" name="bonus_amount" value="">

                            </div>
                            <div class="mb-3 col-md-6 edit_percentage_per_order_item percentage_per_order_item d-none">
                                <label
                                    class="form-label">{{ labels('admin_labels.bonus_percentage', 'Bonus Percentage') }}<span
                                        class='text-asterisks text-sm'>*</span></label>
                                <input type="text" class="form-control edit_bonus_percentage"
                                    placeholder="{{ labels('admin_labels.placeholder_enter_bonus_percentage_order_success', 'Enter Bonus(%) to be given to the delivery boy on successful order delivery') }}"
                                    placeholder="" name="bonus_percentage" value="">

                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label
                                        for="image">{{ labels('admin_labels.driving_licence_front_image', 'Driving Licence Front Image') }}
                                        <span class='text-asterisks text-sm'>*</span></label>
                                    <div class="col-sm-10">

                                        <div class="col-md-12  text-center form-group">
                                            <input type="file" class="filepond" name="front_licence_image"
                                                data-max-file-size="300MB" data-max-files="20" accept="image/*,.webp" />
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label
                                        for="image">{{ labels('admin_labels.driving_licence_back_image', 'Driving Licence Back Image') }}<span
                                            class='text-asterisks text-sm'>*</span></label>

                                    <div class="col-md-12  text-center form-group">
                                        <input type="file" class="filepond" name="back_licence_image"
                                            data-max-file-size="300MB" data-max-files="20" accept="image/*,.webp" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="" class="text-danger mt-3">*{{ labels('admin_labels.only_choose_when_update_necessary', 'Only Choose When Update is necessary') }}</label>
                                <div class="container-fluid row image-upload-section">
                                    <div
                                        class="col-md-8 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image">
                                        <div class='image-upload-div'><img class="img-fluid edit_front_licence_image mb-2"
                                                src="" alt="{{ labels('admin_labels.not_found_alt', 'Not Found') }}"></div>
                                        <input type="hidden" name="image" value=''>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="" class="text-danger mt-3">*{{ labels('admin_labels.only_choose_when_update_necessary', 'Only Choose When Update is necessary') }}</label>
                                <div class="container-fluid row image-upload-section">
                                    <div
                                        class="col-md-8 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image">
                                        <div class='image-upload-div'><img class="img-fluid edit_back_licence_image mb-2"
                                                src="" alt="{{ labels('admin_labels.not_found_alt', 'Not Found') }}"></div>
                                        <input type="hidden" name="image" value=''>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end ">
                            <button type="reset"
                                class="btn reset-btn mx-2">{{ labels('admin_labels.reset', 'Reset') }}</button>
                            <button type="submit"
                                class="btn btn-primary">{{ labels('admin_labels.update_delivery_boy', 'Update Delivery Boy') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    {{-- table --}}

    <section
        class="overview-data {{ $user_role == 'super_admin' || $logged_in_user->hasPermissionTo('view delivery_boy') ? '' : 'd-none' }}">
        <div class="card content-area p-4 ">
            <div class="row align-items-center d-flex heading mb-5">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <h4> {{ labels('admin_labels.manage_delivery_boys', 'Manage Delivery Boys') }}
                            </h4>
                        </div>
                        <div class="col-md-6 d-flex justify-content-end ">
                            <div class="input-group me-2 search-input-grp ">
                                <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                                <input type="text" data-table="admin_delivery_boys_table" class="form-control searchInput"
                                    placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                <span class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                            </div>
                            <a class="btn me-2" id="tableFilter" data-bs-toggle="offcanvas"
                                data-bs-target="#columnFilterOffcanvas" data-table="admin_delivery_boys_table"
                                dateFilter='false' orderStatusFilter='false' paymentMethodFilter='false'
                                orderTypeFilter='false'><i class='bx bx-filter-alt'></i></a>
                            <a class="btn me-2" id="tableRefresh" data-table="admin_delivery_boys_table"><i
                                    class='bx bx-refresh'></i></a>
                            <div class="dropdown">
                                <a class="btn dropdown-toggle export-btn" type="button" id="exportOptionsDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class='bx bx-download'></i>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="exportOptionsDropdown">
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('admin_delivery_boys_table','csv')">{{ labels('admin_labels.csv', 'CSV') }}</button></li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('admin_delivery_boys_table','json')">{{ labels('admin_labels.json', 'JSON') }}</button>
                                    </li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('admin_delivery_boys_table','sql')">{{ labels('admin_labels.sql', 'SQL') }}</button></li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('admin_delivery_boys_table','excel')">{{ labels('admin_labels.excel', 'Excel') }}</button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <button type="button" class="btn btn-outline-primary btn-sm delete_selected_data"
                        data-table-id="admin_delivery_boys_table"
                        data-delete-url="{{ route('delivery_boys.delete') }}">{{ labels('admin_labels.delete_selected', 'Delete Selected') }}</button>
                </div>
                <div class="col-md-12">
                    <div class="pt-0">
                        <div class="table-responsive">
                            <table class='table' id="admin_delivery_boys_table" data-loading-template="loadingTemplate"
                                data-toggle="table" data-url="{{ route('delivery_boys.list') }}" data-click-to-select="true"
                                data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false" data-show-columns="false"
                                data-show-refresh="false" data-trim-on-search="false" data-sort-name="id"
                                data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                                data-show-export="false" data-maintain-selected="true" data-export-types='["txt","excel"]'
                                data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-checkbox="true" data-field="delete-checkbox">
                                            <input name="select_all" type="checkbox">
                                        </th>
                                        <th data-field="id" data-sortable="true">
                                            {{ labels('admin_labels.id', 'ID') }}
                                        <th data-field="username" data-disabled="1" data-sortable="false">
                                            {{ labels('admin_labels.name', 'Name') }}
                                        </th>
                                        <th data-field="email" data-sortable="false">
                                            {{ labels('admin_labels.email', 'Email') }}
                                        </th>
                                        <th data-field="mobile" data-sortable="false">
                                            {{ labels('admin_labels.mobile', 'Mobile') }}
                                        </th>
                                        <th data-field="address" data-sortable="false" data-visible='false'>
                                            {{ labels('admin_labels.address', 'Address') }}
                                        </th>
                                        <th data-field="bonus_type" data-sortable="false">
                                            {{ labels('admin_labels.bonus_type', 'Bonus Type') }}
                                        </th>
                                        <th data-field="bonus" data-sortable="false">
                                            {{ labels('admin_labels.bonus', 'Bonus') }}
                                        </th>
                                        <th data-field="balance" data-sortable="false">
                                            {{ labels('admin_labels.balance', 'Balance') }}
                                        </th>
                                        <th data-field="serviceable_zones" data-sortable="false" data-visible="true">
                                            {{ labels('admin_labels.serviceable_zones', 'Serviceable Zones') }}
                                        </th>
                                        <th data-field="front_licence_image" data-sortable="true" data-visible="false">
                                            {{ labels('admin_labels.driving_licence_front_image', 'Driving Licence Front Image') }}
                                        </th>
                                        <th data-field="back_licence_image" data-sortable="true" data-visible="false">
                                            {{ labels('admin_labels.driving_licence_back_image', 'Driving Licence Back Image') }}
                                        </th>
                                        <th data-field="status">
                                            {{ labels('admin_labels.status', 'Status') }}
                                        </th>
                                        <th data-field="action">
                                            {{ labels('admin_labels.action', 'Action') }}
                                        </th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection