<p class="registration-publication-note">Los campos marcados con * son obligatorios.</p>
                <fieldset class="registration-modalities" aria-describedby="publication-price-note">
                    <legend><span class="enrollment-section-number" aria-hidden="true">01</span> Elegí tu modalidad de publicación <x-account.required-field /></legend>
                    <div class="registration-modality-options">
                    @forelse ($publicationTypes as $type)
                        <label class="registration-modality">
                            <input type="radio" name="publication_type_id" value="{{ $type->id }}" required
                                @checked((string) old('publication_type_id', $profile?->publication_type_id) === (string) $type->id)>
                            <span>
                                <span class="enrollment-modality-icon" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                        @if ($type->slug === 'virtual')
                                            <rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/>
                                        @else
                                            <path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
                                        @endif
                                    </svg>
                                </span>
                                <strong class="enrollment-modality-name">{{ $type->name }}</strong>
                                <span>{{ $type->slug === 'virtual' ? 'Conectá y ofrecé tus servicios de forma virtual.' : 'Publicá tu perfil para encuentros presenciales.' }}</span>
                                <strong class="registration-modality-price">${{ number_format(config('publication.monthly_prices_ars.'.$type->slug), 0, ',', '.') }} ARS mensuales</strong>
                            </span>
                        </label>
                    @empty
                        <p class="registration-error" role="alert">No hay modalidades disponibles. Intentá nuevamente más tarde.</p>
                    @endforelse
                    </div>
                    <p id="publication-price-note" class="registration-publication-note">El valor de cada modalidad se aplica de manera uniforme a sus perfiles, sin distinción de provincia, nacionalidad o modalidad de estadía.</p>
                </fieldset>
                @error('publication_type_id')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <fieldset class="enrollment-information">
                    <legend><span class="enrollment-section-number" aria-hidden="true">02</span> Completá tu información</legend>
                    <div class="enrollment-fields-grid">
                <div class="enrollment-field-group">
                <label class="registration-field"><span>Nombre público <x-account.required-field /></span>
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
                        <input type="text" name="name" placeholder="El nombre que verá tu público" value="{{ old('name', $profile?->name) }}" required>
                    </span>
                </label>
                @error('name')<p class="registration-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="enrollment-field-group">
                <label class="registration-field"><span>WhatsApp <x-account.required-field /></span>
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 2a14 14 0 0 1-7-7l2-2-2-5Z"/></svg>
                        <input type="tel" inputmode="numeric" pattern="[0-9]+" maxlength="50" title="Ingresá solo números, sin espacios ni signos." name="whatsapp" placeholder="Ej.: 5492611234567" value="{{ old('whatsapp', $profile?->whatsapp) }}" required>
                    </span>
                </label>
                @error('whatsapp')<p class="registration-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="enrollment-field-group">
                <label class="registration-field"><span>Provincia <x-account.required-field /></span>
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        <select name="province_id" id="province_id" required>
                            <option value="">Seleccioná una provincia</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}" @selected((string) old('province_id', $profile?->province_id) === (string) $province->id)>{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </span>
                </label>
                @error('province_id')<p class="registration-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="enrollment-field-group">
                <label class="registration-field">Ciudad <span class="enrollment-optional">(opcional)</span>
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 21h18M5 21V7l7-4v18m0-11h7v11M8 8v1m0 3v1m0 3v1m7-3v1m0 3v1"/></svg>
                        <select name="locality_id" id="locality_id">
                            <option value="">Seleccioná una ciudad</option>
                            @foreach ($provinces as $province)
                                @foreach ($province->localities as $locality)
                                    <option value="{{ $locality->id }}" data-province-id="{{ $province->id }}" @selected((string) old('locality_id', $profile?->locality_id) === (string) $locality->id)>{{ $locality->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </span>
                </label>
                @error('locality_id')<p class="registration-error" role="alert">{{ $message }}</p>@enderror
                </div>

                    </div>
                </fieldset>

                <div class="enrollment-privacy">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4m-4 5v2"/></svg>
                    <p class="registration-publication-note"><strong>Tu privacidad, primero.</strong> La documentación que presentes para verificar tu identidad y mayoría de edad será privada. Los datos destinados a tu perfil público serán visibles cuando este sea aprobado y publicado.</p>
                </div>

                <div class="enrollment-consents">

                <label class="registration-consent">
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    <span>Leí y acepto los <a href="{{ route('terms.show') }}" target="_blank" rel="noopener">términos y condiciones de permanencia y uso de imagen de Divas Cuyo</a>. <x-account.required-field /></span>
                </label>
                @error('terms_accepted')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-consent">
                    <input type="checkbox" name="privacy_accepted" value="1" required>
                    <span>Acepto la <a href="{{ route('privacy.show') }}" target="_blank" rel="noopener">política de privacidad</a>. <x-account.required-field /></span>
                </label>
                @error('privacy_accepted')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                @if ($hasInPersonServices)
                <label class="registration-consent">
                    <input type="checkbox" name="confirm_in_person_removal" value="1">
                    <span>Si cambio a Solo Virtual, confirmo quitar los servicios presenciales que ya tenga seleccionados.</span>
                </label>
                @error('confirm_in_person_removal')<p class="registration-error" role="alert">{{ $message }}</p>@enderror
                @endif

                </div>
