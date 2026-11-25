<?php
namespace App\Http\Controllers;

use App\Models\{Asset,SupportTicket,Employee};
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PortalController extends Controller
{
    private function employee(Request $request): Employee
    {
        $employee=$request->user()->employee;
        abort_unless($employee && $employee->status==='active',403,'Your employee profile has not been linked to an active employee record. Contact the IT inventory administrator.');
        return $employee;
    }
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('portal.self'),403);
        $employee=$this->employee($request);
        $assignments=$employee->assets()->with(['category','location'])->get();
        $tickets=SupportTicket::where('requester_email',$request->user()->email)->latest()->limit(10)->get();
        return view('portal.dashboard',compact('employee','assignments','tickets'));
    }
    public function ticket(Request $request)
    {
        abort_unless($request->user()->hasPermission('portal.self'),403);
        $employee=$this->employee($request);
        $data=$request->validate(['subject'=>'required|string|max:180','category'=>'required|in:hardware,software,network,printer,account,security,other','priority'=>'required|in:low,normal,high,urgent','asset_id'=>'nullable|exists:assets,id','description'=>'required|string|max:5000']);
        if(!empty($data['asset_id']) && !$employee->assets()->whereKey($data['asset_id'])->exists())abort(403,'You may only select equipment assigned to your employee record.');
        $ticket=SupportTicket::create($data+['ticket_no'=>'HD-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),'requester_name'=>$employee->full_name,'requester_email'=>$request->user()->email,'status'=>'open','sla_due_at'=>now()->addHours(match($data['priority']){'urgent'=>4,'high'=>8,'low'=>72,default=>24})]);
        Audit::record('ticket_created','tickets',SupportTicket::class,$ticket->id);
        return redirect()->route('portal.index')->with('success','Your IT support ticket has been submitted. Reference: '.$ticket->ticket_no);
    }
}
