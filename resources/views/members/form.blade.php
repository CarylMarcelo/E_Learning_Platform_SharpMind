@php
    $editing = isset($member);
    $selectedPhoneCountry = old('phone_country', $member->phone_country ?? config('phone.default_country'));
    $selectedPhoneCountry = is_string($selectedPhoneCountry) ? $selectedPhoneCountry : config('phone.default_country');
    $selectedPhoneCountry = array_key_exists($selectedPhoneCountry, $phoneCountries) ? $selectedPhoneCountry : config('phone.default_country');
    $phoneNumber = old('phone', $member->phone ?? '');
    $phoneNumber = is_string($phoneNumber) ? $phoneNumber : '';
    $dialCode = $phoneCountries[$selectedPhoneCountry]['dial_code'] ?? '';
    $phoneNumber = $dialCode !== '' && str_starts_with($phoneNumber, '+'.$dialCode) ? substr($phoneNumber, strlen($dialCode) + 1) : $phoneNumber;
    $hasStructuredAddress = $editing && (filled($member->street_name) || filled($member->city) || filled($member->state) || filled($member->zip_code));
    $legacyAddress = $editing && ! $hasStructuredAddress ? $member->address : '';
@endphp
<form class="panel form-panel" action="{{ $editing ? route('members.update', $member) : route('members.store') }}" method="POST">
    @csrf @if($editing) @method('PUT') @endif
    <section class="form-section">
        <div class="form-section-heading"><h2>Personal information</h2></div>
        <div class="form-grid">
            <div class="field"><label for="first_name">First name <span class="required">*</span></label><input id="first_name" name="first_name" value="{{ old('first_name', $member->first_name ?? '') }}" placeholder="First name" autocomplete="given-name" maxlength="255" required></div>
            <div class="field"><label for="last_name">Last name <span class="required">*</span></label><input id="last_name" name="last_name" value="{{ old('last_name', $member->last_name ?? '') }}" placeholder="Last name" autocomplete="family-name" maxlength="255" required></div>
            <div class="field"><label for="member_email">Email address <span class="required">*</span></label><input type="email" id="member_email" name="email" value="{{ old('email', $member->email ?? '') }}" placeholder="example@gmail.com" autocomplete="email" maxlength="255" required><span class="field-help">Membership updates will be sent here.</span></div>
            <div class="field">
                <label for="phone">Phone number <span class="required">*</span></label>
                <div class="phone-input">
                    <label class="sr-only" for="phone_country">Phone country calling code</label>
                    <div class="phone-country">
                        <select id="phone_country" name="phone_country" autocomplete="off" data-flags-url="{{ asset('phone-country-flags.svg') }}" title="{{ $phoneCountries[$selectedPhoneCountry]['name'] }} (+{{ $dialCode }})" required>
                            @foreach($phoneCountries as $countryCode => $country)
                                <option value="{{ $countryCode }}" data-dial-code="{{ $country['dial_code'] }}" @selected($selectedPhoneCountry === $countryCode)>{{ $country['name'] }} (+{{ $country['dial_code'] }})</option>
                            @endforeach
                        </select>
                        <span class="phone-country-display" aria-hidden="true">
                            <svg class="phone-country-flag" viewBox="0 0 512 512"><use id="phone-country-flag" href="{{ asset('phone-country-flags.svg').'#'.strtolower($selectedPhoneCountry) }}"></use></svg>
                            <span id="phone-country-code">(+{{ $dialCode }})</span>
                            <x-icon name="chevron-down" />
                        </span>
                    </div>
                    <input type="tel" id="phone" name="phone" value="{{ $phoneNumber }}" placeholder="912 345 6789" autocomplete="tel-national" maxlength="50" required>
                </div>
            </div>
            <div class="field"><label for="street_name">Barangay / Village / District</label><input id="street_name" name="street_name" value="{{ old('street_name', $member->street_name ?? $legacyAddress) }}" placeholder="Barangay, village, or district" autocomplete="address-level3" maxlength="255" data-address-field></div>
            <div class="field"><label for="city">City</label><input id="city" name="city" value="{{ old('city', $member->city ?? '') }}" placeholder="City" autocomplete="address-level2" maxlength="120" data-address-field></div>
            <div class="field"><label for="state">State / Province</label><input id="state" name="state" value="{{ old('state', $member->state ?? '') }}" placeholder="State or province" autocomplete="address-level1" maxlength="120" data-address-field></div>
            <div class="field"><label for="zip_code">ZIP code</label><input id="zip_code" name="zip_code" value="{{ old('zip_code', $member->zip_code ?? '') }}" placeholder="ZIP or postal code" autocomplete="postal-code" maxlength="20" data-address-field></div>
            @if(!$editing)
            <div class="field field-wide">
                <div class="between"><span class="field-label">Pin a location</span><button type="button" id="findAddressBtn" class="text-link map-sync"><x-icon name="search" />Find on map</button></div>
                <span id="geocodeStatus" class="field-help" aria-live="polite"></span>
                <div id="map" class="member-map" data-weather-url="{{ route('weather.show') }}" aria-label="Choose member location on map"></div>
                <input type="hidden" id="lat" name="latitude" value="{{ old('latitude', '14.5764') }}"><input type="hidden" id="lon" name="longitude" value="{{ old('longitude', '121.0851') }}">
                <div class="map-cards">
                    <section class="map-card" aria-labelledby="coordinates-heading">
                        <span class="map-card-icon"><x-icon name="map-pin" /></span>
                        <div><h3 id="coordinates-heading">Map coordinates</h3><p id="coordinates" class="map-card-value" aria-live="polite">Choose a location on the map.</p></div>
                    </section>
                    <section class="map-card weather-card" aria-labelledby="weather-heading">
                        <span class="map-card-icon"><x-icon name="cloud-sun" /></span>
                        <div><h3 id="weather-heading">Local weather</h3><p id="weather" class="map-card-value" aria-live="polite">Loading local weather...</p></div>
                    </section>
                </div>
            </div>
            @endif
        </div>
    </section>
    <section class="form-section">
        <div class="form-section-heading"><h2>Membership details</h2></div>
        <div class="form-grid">
            <div class="field"><label for="categorySelect">Category <span class="required">*</span></label><select name="membership_category_id" id="categorySelect" required>
                    <option value="" data-discount="0">Choose a category</option>@foreach($categories as $category)<option value="{{ $category->id }}" data-discount="{{ $category->discount_percent }}" data-name="{{ $category->name }}" @selected(old('membership_category_id', $member->membership_category_id ?? '') == $category->id)>{{ $category->name }}{{ $category->discount_percent > 0 ? ' · '.number_format($category->discount_percent, 0).'% discount' : '' }}</option>@endforeach
                </select></div>
            <div class="field"><label for="levelSelect">Membership level <span class="required">*</span></label><select name="membership_level_id" id="levelSelect" required>
                    <option value="" data-fee="0">Choose a level</option>@foreach($levels as $level)<option value="{{ $level->id }}" data-fee="{{ $level->fee }}" data-days="{{ $level->validity_days }}" data-benefits="{{ json_encode($level->benefitItems()) }}" @selected(old('membership_level_id', $member->membership_level_id ?? '') == $level->id)>{{ $level->name }} · ₱{{ number_format($level->fee, 2) }}</option>@endforeach
                </select></div>
            @if($editing)<div class="field field-wide"><label for="member-status">Membership status</label><select name="status" id="member-status" required>@foreach(['Pending', 'Active', 'Inactive'] as $status)<option @selected(old('status', $member->status) === $status)>{{ $status }}</option>@endforeach</select></div>@endif
            @if($categories->isEmpty() || $levels->isEmpty())<div class="alert alert-info field-wide"><x-icon name="info" /><p>Add a <a href="{{ route('categories.index') }}">category</a> and a <a href="{{ route('levels.index') }}">membership level</a> before registering a member.</p></div>@endif
            <div id="levelSummary" class="summary-box field-wide" hidden>
                <h3 class="summary-heading">Payment breakdown</h3>
                <div class="summary-row"><span>Base membership fee</span><span>₱<span id="summaryBaseFee">0.00</span></span></div>
                <div class="summary-row" id="summaryDiscountRow" hidden><span><span id="summaryCategoryName">Category</span> discount (<span id="summaryDiscountPercent">0</span>%)</span><span>−₱<span id="summaryDiscountAmount">0.00</span></span></div>
                <div class="summary-row summary-total"><strong>Total payable</strong><strong>₱<span id="summaryFee">0.00</span></strong></div>
                <div class="summary-row"><span>Validity period</span><span><span id="summaryDays">0</span> days</span></div>
                <div class="summary-benefits"><h3>Benefits</h3><x-benefits-list id="summaryBenefits" /><template id="summary-benefit-template"><li><x-icon name="check-circle" /><span></span></li></template></div>
            </div>
        </div>
    </section>
    <div class="form-actions"><a class="button button-secondary" href="{{ route('members.index') }}">Cancel</a><button class="button button-primary" type="submit" @disabled($categories->isEmpty() || $levels->isEmpty())><x-icon :name="$editing ? 'check' : 'plus'" />{{ $editing ? 'Save changes' : 'Register member' }}</button></div>
</form>
