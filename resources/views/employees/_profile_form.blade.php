@php
    $photoUrl = $employee->photoUrl();
@endphp

<div class="profile-editor">
    <div class="photo-uploader">
        <div class="photo-uploader__preview" id="photo-preview">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $employee->name }} profile photo">
            @else
                <span class="avatar avatar--xl">{{ $employee->initials() }}</span>
            @endif
        </div>

        <div class="photo-uploader__controls">
            <p class="section-heading">Profile photo</p>
            <p class="section-copy">JPG, PNG, or WEBP up to 2MB.</p>

            <label class="btn btn-secondary btn-sm photo-upload-btn">
                Choose photo
                <input
                    id="photo"
                    name="photo"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    class="sr-only"
                >
            </label>

            @if ($photoUrl)
                <label class="photo-remove">
                    <input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo'))>
                    Remove current photo
                </label>
            @endif

            <p id="photo-filename" class="meta-text"></p>
        </div>
    </div>

    <div class="form-grid">
        <div class="field">
            <label class="field-label" for="phone">Phone</label>
            <input id="phone" name="phone" type="text" class="field-input" value="{{ old('phone', $employee->phone) }}" placeholder="+255 700 000 000">
        </div>

        <div class="field">
            <label class="field-label" for="city">City</label>
            <input id="city" name="city" type="text" class="field-input" value="{{ old('city', $employee->city) }}" placeholder="Dar es Salaam">
        </div>

        <div class="field span-2">
            <label class="field-label" for="address">Address</label>
            <input id="address" name="address" type="text" class="field-input" value="{{ old('address', $employee->address) }}" placeholder="Street, building, office">
        </div>

        <div class="field span-2">
            <label class="field-label" for="emergency_contact">Emergency contact</label>
            <input id="emergency_contact" name="emergency_contact" type="text" class="field-input" value="{{ old('emergency_contact', $employee->emergency_contact) }}" placeholder="Name · phone">
        </div>

        <div class="field span-2">
            <label class="field-label" for="bio">About</label>
            <textarea id="bio" name="bio" class="field-input field-textarea" rows="5" placeholder="Short profile notes, skills, or role focus…">{{ old('bio', $employee->bio) }}</textarea>
        </div>
    </div>
</div>
