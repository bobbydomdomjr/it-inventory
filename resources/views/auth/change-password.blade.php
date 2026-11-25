@extends('layouts.app')
@section('title', 'Change password')
@section('breadcrumb', 'Account security')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">ACCOUNT SECURITY</div><h1>Change your password</h1><p>Update your password before continuing to the inventory workspace.</p></div>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="panel p-4">
            <form method="POST" action="{{ route('password.update') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current password</label>
                    <input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required>
                    <div class="form-text">At least 12 characters, including uppercase, lowercase, and a number.</div>
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
def
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="12" autocomplete="new-password" required>
                </div>
                <button class="btn btn-primary">Update password</button>
            </form>
        </div>
    </div>
</div>
@endsection
