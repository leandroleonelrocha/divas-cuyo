                <fieldset class="registration-modalities" aria-describedby="publication-price-note">
                    <legend>Elegí tu modalidad de publicación</legend>
                    <div class="registration-modality-options">
                    @forelse ($publicationTypes as $type)
                        <label class="registration-modality">
                            <input type="radio" name="publication_type_id" value="{{ $type->id }}" required
                                @checked((string) old('publication_type_id', $profile?->publication_type_id) === (string) $type->id)>
                            <span>
                                <strong>{{ $type->name }}</strong>
                                <span>{{ $type->slug === 'virtual' ? 'Modelo virtual' : 'Modalidad presencial' }}</span>
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

                <label class="registration-field">Nombre público
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/></svg>
                        <input type="text" name="name" value="{{ old('name', $profile?->name) }}" required>
                    </span>
                </label>
                @error('name')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-field">WhatsApp
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.5 3h3l1.2 4-1.8 1.5a13.5 13.5 0 0 0 6.6 6.6l1.5-1.8 4 1.2v3c0 1.1-.9 2-2 2C10.7 19.5 4.5 13.3 4.5 5a2 2 0 0 1 2-2Z"/></svg>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $profile?->whatsapp) }}" required>
                    </span>
                </label>
                @error('whatsapp')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-field">Ubicación · Provincia
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 22s7-6.1 7-12A7 7 0 0 0 5 10c0 5.9 7 12 7 12Zm0-9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
                        <select name="province_id" id="province_id" required>
                            <option value="">Seleccioná una provincia</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}" @selected((string) old('province_id', $profile?->province_id) === (string) $province->id)>{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </span>
                </label>
                @error('province_id')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-field">Ubicación · Ciudad
                    <span class="registration-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 22s7-6.1 7-12A7 7 0 0 0 5 10c0 5.9 7 12 7 12Zm0-9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
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

                <p class="registration-publication-note">La documentación que presentes para verificar tu identidad y mayoría de edad será privada. Los datos destinados a tu perfil público serán visibles cuando este sea aprobado y publicado.</p>

                <label class="registration-consent">
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    <span>Leí y acepto los <a href="{{ route('terms.show') }}" target="_blank" rel="noopener">términos y condiciones de permanencia y uso de imagen de Divas Cuyo</a>.</span>
                </label>
                @error('terms_accepted')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                <label class="registration-consent">
                    <input type="checkbox" name="privacy_accepted" value="1" required>
                    <span>Acepto la <a href="{{ route('privacy.show') }}" target="_blank" rel="noopener">política de privacidad</a>.</span>
                </label>
                @error('privacy_accepted')<p class="registration-error" role="alert">{{ $message }}</p>@enderror

                @if ($hasInPersonServices)
                <label class="registration-consent">
                    <input type="checkbox" name="confirm_in_person_removal" value="1">
                    <span>Si cambio a Solo Virtual, confirmo quitar los servicios presenciales que ya tenga seleccionados.</span>
                </label>
                @error('confirm_in_person_removal')<p class="registration-error" role="alert">{{ $message }}</p>@enderror
                @endif
