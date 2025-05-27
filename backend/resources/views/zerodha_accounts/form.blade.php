@csrf

<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $account->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">API Key</label>
    <input type="text" name="api_key" class="form-control" value="{{ old('api_key', $account->api_key ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">API Secret</label>
    <input type="text" name="api_secret" class="form-control" value="{{ old('api_secret', $account->api_secret ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Access Token</label>
    <input type="text" name="access_token" class="form-control" value="{{ old('access_token', $account->access_token ?? '') }}">
</div>

<div class="form-check form-switch mb-3">
    <input type="checkbox" class="form-check-input" name="is_active" value="1"
           {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label">Active</label>
</div>

<button type="submit" class="btn btn-success">Save</button>
<a href="{{ route('zerodha_accounts.index') }}" class="btn btn-secondary">Cancel</a>
