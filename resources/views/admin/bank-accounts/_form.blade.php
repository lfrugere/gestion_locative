@if ($errors->any())
    <div class="errors" role="alert"><strong>Veuillez corriger les erreurs suivantes :</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form class="card" method="POST" action="{{ $action }}">
    @csrf
    @isset($bankAccount) @method('PUT') @endisset
    <div class="form-grid">
        <div class="form-field"><label for="label">Libellé</label><input id="label" name="label" value="{{ old('label', $bankAccount->label ?? '') }}" required autofocus></div>
        <div class="form-field">
            <label for="country">Pays</label>
            <select id="country" name="country" required>
                @foreach ($countries as $code => $name)
                    <option value="{{ $code }}" @selected(old('country', $bankAccount->country ?? 'FR') === $code)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field"><label for="iban">IBAN</label><input id="iban" name="iban" value="{{ old('iban', $bankAccount->iban ?? '') }}" placeholder="FR76 XXXX XXXX XXXX XXXX XXXX XXX"></div>
        <div class="form-field"><label for="initial_balance">Solde initial</label><input id="initial_balance" type="number" step="0.01" name="initial_balance" value="{{ old('initial_balance', $bankAccount->initial_balance ?? '0.00') }}" required></div>
        <div class="form-field"><label for="initial_balance_date">Date du solde initial</label><input id="initial_balance_date" type="date" name="initial_balance_date" value="{{ old('initial_balance_date', optional($bankAccount->initial_balance_date ?? null)->format('Y-m-d')) }}" required></div>
        <div class="form-field">
            <label for="manager_id">Gestionnaire</label>
            <select id="manager_id" name="manager_id">
                <option value="">— Aucun —</option>
                @foreach ($managers as $manager)
                    <option value="{{ $manager->id }}" @selected(old('manager_id', $bankAccount->manager_id ?? '') == $manager->id)>{{ $manager->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-actions"><a class="button secondary" href="{{ route('bank-accounts.index') }}">Annuler</a><button class="button" type="submit">{{ $submitLabel }}</button></div>
</form>
