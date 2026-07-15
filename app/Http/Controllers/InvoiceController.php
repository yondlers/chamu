<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $f_date = $request['from_date'];
        $t_date = $request['to_date'];
        $tenant = $request['tenant_id'];
        $unit = $request['unit_id'];
        $property = $request['property_id'];


        
        $leases = Lease::where('team_id', auth()->user()->current_team_id);

        if ($tenant) {
            $leases->where('tenant_id', $tenant);
        }
    
        if ($property) {
            $leases->where('property_id', $property);
        }   

        $leases->get();

        // Fetch invoices, order by date (oldest first), then group by lease
        // $invoices = Invoice::where('team_id', auth()->user()->current_team_id)
        //     ->orderBy('invoice_date', 'asc') // Oldest first, newest last
        //     ->get()
        //     ->groupBy('lease_id'); // Group invoices by lease


        // Fetch invoices with optional date filtering, order by date (oldest first), then group by lease
        $invoicesQuery = Invoice::where('team_id', auth()->user()->current_team_id)
            ->orderBy('invoice_date', 'asc');
    
        if ($f_date) {
            $invoicesQuery->whereDate('invoice_date', '>=', $f_date);
        }
    
        if ($t_date) {
            $invoicesQuery->whereDate('invoice_date', '<=', $t_date);
        }
        if ($tenant) {
            $invoicesQuery->whereHas('lease', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant);
            });
        }
    
        if ($property) {
            $invoicesQuery->whereHas('lease', function ($query) use ($property) {
                $query->where('property_id', $property);
            });
        }
    
        $invoices = $invoicesQuery->get()->groupBy('lease_id');

        $invoiceFilterHtml = Invoice::htmlFilter($f_date, $t_date, $tenant, $property);

        return view('invoices.index', [
            'leases' => $leases,
            'invoices' => $invoices,
            'invoiceFilterHtml' => $invoiceFilterHtml
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $user = auth()->user();
        $leases = Lease::where('team_id', $user->current_team_id)->get();
        if ($leases->isEmpty()) {
            return view('invoices.create', [
                'invoiceHtml' => HtmlError::htmlTemplate('Something is Missing', 'You do not have any active lease in the system to generate an Inspection list. Please ensure Leases are registerd in the system to associate an Inspection list with a lease.')
            ]);
        }

        return view('invoices.create', [
            'invoiceHtml' => Invoice::htmlForm(new Invoice())
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $user = auth()->user();

        $invoice = Invoice::where('team_id', auth()->user()->current_team_id)->latest()->first();
        $latest_balance = floatval($invoice->balance);
        $amount = floatval($request->input('amount'));
        $type = $request['type'];

        if ($type == 'Debit') {
            $balance = $latest_balance - $amount;
        } else if ($type == 'Credit') {
            $balance = $latest_balance + $amount;
        } else {
            return redirect('/invoices');
        }

        //Generate random text and number of 10 characters
        $random_character = Str::random(10);

        Invoice::create([
            'active' => true,
            'name' => $request['name'],
            'amount' => $request['amount'],
            'balance' => $balance,
            'type' => $type,
            'invoice_date' => $request['invoice_date'],
            'note' => $request['note'],
            'lease_id' => $request['lease_id'],
            'account_type' => $request['account_type'],
            'description' => $request['description'],
            'transaction_id' => $random_character,

            'team_id' => $user->current_team_id,

        ]);

        AuditLog::log('Transaction Captured', $request['name'] . ' amount of R' . $request['amount']);

        return redirect()->route('invoices.index')->with('success', 'Invoice updated.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lease $lease)
    {
        //
        return view('invoices.show', [
            'lease' => $lease
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lease $lease)
    {
        //
        return view('invoices.create', [
            'invoiceHtml' => Invoice::htmlForm($lease)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
        $latest_balance = Invoice::where('team_id', auth()->user()->current_team_id)->select('balance')->latest()->first();
        $amount = intval($request->input('amount'));
        $type = $request['type'];

        if ($type == 'Debit') {
            $balance = $latest_balance - $amount;
        } else if ($type == 'Credit') {
            $balance = $latest_balance + $amount;
        } else {
            return redirect('/invoices');
        }

        //Generate random text and number of 10 characters
        $random_character = Str::random(10);

        $invoice->active = true;
        $invoice->amount = $request['amount'];
        $invoice->balance = $balance;
        $invoice->type = $type;
        $invoice->invoice_date = $request['invoice_date'];
        $invoice->note = $request['note'];
        $invoice->lease_id = $request['lease_id'];
        $invoice->account_type = $request['account_type'];
        $invoice->description = $request['description'];
        $invoice->transaction_id = $random_character;

        $invoice->save();

        return redirect()->route('invoices.index')->with('success', 'Invoice updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lease $lease)
    {
        //
    }
}
