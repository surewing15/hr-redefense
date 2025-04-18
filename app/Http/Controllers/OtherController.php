<?php

namespace App\Http\Controllers;

use App\Models\MasterlistModel;
use Illuminate\Http\Request;
use App\Models\RequestModel;
use App\Models\SOrequestModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OtherController extends Controller
{
    //tanan function sa so
    public function soindex()
    {
        $so_requests = SOrequestModel::with('masterlist')
            ->where('status', 'pending')
            ->get();
        return view('admin.others.sorequestlist', compact('so_requests'));
    }

    //

    public function soapprove($soreqid)
    {
        $so_request = SOrequestModel::findOrFail($soreqid);
        $so_request->update(['status' => 'approve']);
        return back()->with('success', 'SO Request approved successfully');
    }

    //

    public function soreject($soreqid)
    {
        $so_request = SOrequestModel::findOrFail($soreqid);
        $so_request->update(['status' => 'reject']);
        return back()->with('success', 'SO Request rejected successfully');
    }

    //

    public function soapprovedrequests()
    {
        $so_requests = SOrequestModel::with('masterlist')
            ->where('status', 'approve')
            ->get();
        return view('admin.others.soapprove', compact('so_requests'));
    }

    //

    public function sorejectedrequests()
    {
        $so_requests = SOrequestModel::with('masterlist')
            ->where('status', 'reject')
            ->get();
        return view('admin.others.soreject', compact('so_requests'));
    }


    //tanan function sa coe
    public function coe()
    {
        return view('admin.others.coe');
    }

    // View all requests (pending)
    public function coe_request()
    {
        $requests = RequestModel::where('status', 'pending')->get();
        return view('admin.others.request', compact('requests'));
    }



    public function approved_requests()
    {
        $approvedRequests = RequestModel::join('masterlist', function ($join) {
            $join->on('tbl_coe_req.employee_id', '=', DB::raw('BINARY masterlist.employee_id'));
        })
            ->where('tbl_coe_req.status', 'approve')
            ->select('tbl_coe_req.*', 'masterlist.created_at')
            ->get();

        return view('admin.others.Approve', compact('approvedRequests'));
    }
    public function getDateStarted($employee_id)
    {
        $dateStarted = DB::table('masterlist')
            ->where('employee_id', $employee_id)
            ->value('created_at');

        return response()->json([
            'date_started' => date('F d, Y', strtotime($dateStarted))
        ]);
    }
    // View rejected requests
    public function rejected_requests()
    {
        $requests = RequestModel::where('status', 'reject')->get();
        return view('admin.others.rejected', compact('requests'));
    }

    public function approve($coe_id)
    {
        $request = RequestModel::findOrFail($coe_id);
        $request->update(['status' => 'approve']);
        return back()->with('success', 'Request approved successfully');
    }

    public function reject($coe_id)
    {
        $request = RequestModel::findOrFail($coe_id);
        $request->update(['status' => 'reject']);
        return back()->with('success', 'Request rejected successfully');
    }

    public function edit($coe_id)
    {
        $request = RequestModel::findOrFail($coe_id);
        return view('admin.others.edit', compact('request'));
    }

    public function update(Request $request, $coe_id)
    {
        try {
            Log::info('Update request received for COE ID: ' . $coe_id, $request->all());

            $coeRequest = RequestModel::findOrFail($coe_id);

            // For partial updates (like just updating compensation), we only validate
            // the fields that were sent in the request
            $validationRules = [];

            if ($request->has('MonthlyCompensationText')) {
                $validationRules['MonthlyCompensationText'] = 'required|string|max:255';
            }

            if ($request->has('MonthlyCompensationDigits')) {
                $validationRules['MonthlyCompensationDigits'] = 'required|numeric|min:0';
            }

            // If we're updating all fields (from the full edit form)
            if ($request->has('FirstName')) {
                $validationRules = array_merge($validationRules, [
                    'FirstName' => 'required|string|max:255',
                    'LastName' => 'required|string|max:255',
                    'Email' => 'required|email|max:255',
                    'Position' => 'required|string|max:255',
                    'DateStarted' => 'required|date'
                ]);
            }

            $validated = $request->validate($validationRules);

            // Update only the fields that were validated
            $coeRequest->update($validated);

            Log::info('Update successful for COE ID: ' . $coe_id);

            return response()->json([
                'success' => true,
                'message' => 'Certificate request updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating request COE ID ' . $coe_id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating certificate request: ' . $e->getMessage()
            ], 500);
        }
    }
}