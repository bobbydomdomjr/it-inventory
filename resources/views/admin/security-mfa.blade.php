@extends('layouts.app')
@section('title','Multi-factor authentication')
@section('breadcrumb','Security')
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">ACCOUNT SECURITY</div>
        <h1>Multi-factor authentication</h1>
        <p>Protect this account with a time-based authenticator code.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mfa-layout">
    <div class="col-xl-7">
        <section class="panel mfa-panel" aria-labelledby="mfa-authenticator-heading">
            <div class="panel-header mfa-panel-header">
                <div>
                    <h2 id="mfa-authenticator-heading">Authenticator app</h2>
                    <p>Use any TOTP-compatible authenticator application.</p>
                </div>
                <span class="status-pill {{ auth()->user()->mfa_enabled ? 'active' : 'pending' }}">
                    {{ auth()->user()->mfa_enabled ? 'Enabled' : 'Not enabled' }}
                </span>
            </div>

            <div class="mfa-panel-body">
                @if(auth()->user()->mfa_enabled)
                    <div class="mfa-enabled-note">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <p>A second verification step is required whenever this account signs in.</p>
                    </div>

                    <form method="POST" action="{{ route('mfa.disable') }}" class="mfa-disable-form">
                        @csrf
                        <div class="form-field">
                            <label class="form-label" for="mfa-current-password">Current password</label>
                            <input id="mfa-current-password" type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="mfa-disable-code">Authenticator code</label>
                            <input id="mfa-disable-code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control mfa-code-input" autocomplete="one-time-code" required>
                        </div>
                        <button class="btn btn-outline-danger" type="submit">Disable MFA</button>
                    </form>
                @else
                    <p class="mfa-intro">Add this account to your authenticator app using the setup key below. Enter the app's current code to confirm enrollment.</p>

                    <div class="mfa-form-stack">
                        <div class="form-field">
                            <label class="form-label" for="mfa-secret">Account setup key</label>
                            <div class="input-group mfa-secret-group">
                                <input id="mfa-secret" type="password" class="form-control font-monospace" readonly value="{{ $secret }}" autocomplete="off" spellcheck="false" aria-describedby="mfa-secret-help">
                                <button type="button" class="btn btn-outline-secondary" id="mfa-secret-toggle" aria-controls="mfa-secret" aria-pressed="false">Show</button>
                                <button type="button" class="btn btn-outline-secondary" data-copy-target="mfa-secret">Copy</button>
                            </div>
                            <div id="mfa-secret-help" class="form-text">Keep this key private. Anyone who has it may be able to generate your verification codes.</div>
                        </div>

                        <details class="mfa-advanced-setup">
                            <summary>Show manual setup URI</summary>
                            <div class="mfa-uri-content">
                                <label class="form-label" for="mfa-uri">Manual setup URI</label>
                                <textarea id="mfa-uri" class="form-control font-monospace" rows="3" readonly autocomplete="off" spellcheck="false">{{ $uri }}</textarea>
                                <div class="mfa-uri-actions">
                                    <span class="form-text">Use this only when your authenticator supports URI-based setup.</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-target="mfa-uri">Copy URI</button>
                                </div>
                            </div>
                        </details>

                        <form method="POST" action="{{ route('mfa.enable') }}" class="mfa-confirm-form">
                            @csrf
                            <div class="form-field">
                                <label class="form-label" for="mfa-enable-code">Six-digit verification code</label>
                                <input id="mfa-enable-code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control mfa-code-input" autocomplete="one-time-code" placeholder="000000" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Confirm and enable MFA</button>
                        </form>
                        <span class="visually-hidden" id="mfa-copy-status" role="status" aria-live="polite"></span>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div class="col-xl-5">
        <aside class="panel mfa-panel mfa-guidance-panel" aria-labelledby="mfa-guidance-heading">
            <div class="panel-header mfa-panel-header">
                <div>
                    <h2 id="mfa-guidance-heading">Enrollment guidance</h2>
                    <p>Complete these steps in order.</p>
                </div>
                <span class="mfa-guidance-icon" aria-hidden="true"><i class="bi bi-list-check"></i></span>
            </div>
            <div class="mfa-panel-body mfa-guidance-body">
                <ol class="mfa-guidance-list">
                    <li><span>Open your authenticator app.</span></li>
                    <li><span>Add a new time-based account manually.</span></li>
                    <li><span>Enter the account setup key exactly as shown.</span></li>
                    <li><span>Submit the six-digit code to confirm enrollment.</span></li>
                </ol>
            </div>
            <div class="mfa-guidance-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <p>Store your organization's approved recovery procedure separately. This version does not generate recovery codes.</p>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const secretInput = document.getElementById('mfa-secret');
    const toggleButton = document.getElementById('mfa-secret-toggle');
    const copyStatus = document.getElementById('mfa-copy-status');

    if (secretInput && toggleButton) {
        toggleButton.addEventListener('click', () => {
            const isVisible = secretInput.type === 'text';
            secretInput.type = isVisible ? 'password' : 'text';
            toggleButton.textContent = isVisible ? 'Show' : 'Hide';
            toggleButton.setAttribute('aria-pressed', String(!isVisible));
        });
    }

    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.getElementById(button.dataset.copyTarget);
            if (!target) return;

            const value = target.value || '';
            if (!value) return;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const temporaryField = document.createElement('textarea');
                    temporaryField.value = value;
                    temporaryField.setAttribute('readonly', '');
                    temporaryField.style.position = 'fixed';
                    temporaryField.style.opacity = '0';
                    temporaryField.style.pointerEvents = 'none';
                    document.body.appendChild(temporaryField);
                    temporaryField.select();
                    const copied = document.execCommand('copy');
                    temporaryField.remove();
                    if (!copied) throw new Error('Clipboard copy was not available.');
                }
                const previousLabel = button.textContent;
                button.textContent = 'Copied';
                if (copyStatus) copyStatus.textContent = 'Copied to clipboard.';
                window.setTimeout(() => { button.textContent = previousLabel; }, 1600);
            } catch (error) {
                if (copyStatus) copyStatus.textContent = 'Unable to copy automatically. Select and copy the value manually.';
            }
        });
    });
})();
</script>
@endpush
