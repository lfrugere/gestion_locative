<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\Property;
use App\Models\Tag;
use App\Services\MediaManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function store(Request $request, Property $property, MediaManager $manager): RedirectResponse
    {
        $this->authorize('create', [Invoice::class, $property]);

        $validated = $request->validate([
            'supplier' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $property, $manager): void {
            $invoice = $property->invoices()->create($validated);

            $invoice->tags()->sync(Tag::idsFromText($validated['tags'] ?? null));

            if ($property->bank_account_id) {
                $label = 'Facture '.($validated['number'] ?: '(sans numéro)').' — '.$validated['label'];
                $transaction = $property->bankAccount->transactions()->create([
                    'label' => $label,
                    'date' => $validated['date'],
                    'amount' => -$validated['amount'],
                ]);
                $property->bankAccount->increment('balance', -$validated['amount']);
                $invoice->update(['bank_transaction_id' => $transaction->id]);
            }

            foreach ($validated['attachments'] ?? [] as $attachment) {
                $manager->store($invoice, $attachment, Media::KIND_DOCUMENT, Media::TYPE_OTHER, null, null);
            }
        });

        return back()->with('success', 'La facture a été ajoutée.');
    }

    public function destroy(Property $property, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->property_id === $property->id, 404);
        $this->authorize('delete', $invoice);

        $transaction = $invoice->bankTransaction;

        if ($transaction?->isLocked()) {
            return back()->with('error', 'Cette facture est liée à une écriture rapprochée et ne peut pas être supprimée.');
        }

        DB::transaction(function () use ($invoice, $transaction): void {
            if ($transaction) {
                $transaction->bankAccount->decrement('balance', $transaction->amount);
                $transaction->delete();
            }
            $invoice->delete();
        });

        return back()->with('success', 'La facture a été supprimée.');
    }
}
