<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\User;
use App\Models\Address;
use App\Models\ChatThread;
use App\Models\ProjectBid;
use App\Models\ProjectUser;
use App\Models\UserProfile;
use App\Models\HireInvitation;
use App\Models\PayToFreelancer;
use App\Models\MilestonePayment;
use App\Models\FreelancerAccount;
use App\Models\WalletEscrow;
use App\Models\Wallet;
use Cache;
use Gate;
use DB;
use Session;
use App\Utility\EmailUtility;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show all freelancers'])->only('all_freelancers');
        $this->middleware(['permission:show all clients'])->only('all_clients');

    }

    public function all_freelancers(Request $request)
    {
        $sort_search = null;
        $col_name = null;
        $query = null;
        $freelancers = User::query()->withTrashed();;

        $user_ids = User::where(function($user) use ($sort_search){
            $user->where('user_type', 'freelancer');
        })->withTrashed()->pluck('id')->toArray();

        $freelancers = $freelancers->where(function($freelancer) use ($user_ids){
            $freelancer->whereIn('id', $user_ids);
        })->withTrashed();

        if ($request->search != null || $request->type != null) {
            if ($request->has('search')){
                $sort_search = $request->search;
                $user_ids = User::where(function($user) use ($sort_search){
                    $user->where('user_type', 'freelancer')->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%');
                })->withTrashed()->pluck('id')->toArray();

                $freelancers = $freelancers->where(function($freelancer) use ($user_ids){
                    $freelancer->whereIn('id', $user_ids);
                });
            }

            $freelancers = $freelancers->paginate(10);
        }
        else {
            $freelancers = $freelancers->orderBy('created_at', 'desc')->paginate(10);
        }

        return view('admin.default.freelancer.freelancers.index', compact('freelancers', 'sort_search', 'col_name', 'query'));

    }

    public function all_clients(Request $request)
    {
        $sort_search = null;
        $col_name = null;
        $query = null;
        $clients = User::query()->withTrashed();
        //dd($clients);

        $user_ids = User::where(function($user) use ($sort_search){
            $user->where('user_type', 'client');
        })->withTrashed()->pluck('id')->toArray();

        $freelancers = $clients->where(function($freelancer) use ($user_ids){
            $freelancer->whereIn('id', $user_ids);
        })->withTrashed();

        if ($request->search != null || $request->type != null) {
            if ($request->has('search')){
                $sort_search = $request->search;
                $user_ids = User::where(function($user) use ($sort_search){
                    $user->where('user_type', 'client')->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%');
                })->withTrashed()->pluck('id')->toArray();

                $clients = $clients->where(function($client) use ($user_ids){
                    $client->whereIn('id', $user_ids);
                });
            }
            $clients = $clients->paginate(10);
        }
        else {
            $clients = $clients->orderBy('created_at', 'desc')->paginate(10);
        }
        return view('admin.default.client.clients.index', compact('clients', 'sort_search', 'col_name', 'query'));
    }

    public function all_payments(Request $request){
        $sort_search=null;
        if($request->transaction){
            $all_payments=WalletEscrow::join('users','wallet_escrows.receiver_id','users.id')
            ->join('projects','wallet_escrows.project_id','projects.id')
            ->select('wallet_escrows.*','users.id','users.name','users.user_type', 'projects.name as project_name');
            if($request->search!= null){
                $sort_search=$request->search;
                $all_payments->where('users.name', 'like', '%'.$sort_search.'%');
            }
            $all_payments=$all_payments->paginate(10);
           /* dd($wallet_escrows); */
            //$all_payments = DB::table("wallet_escrows")->select("wallet_escrows.*")->paginate(2);
        }else{
            $all_payments=Wallet::join('users','wallets.user_id','users.id')->select('wallets.*','users.id','users.name','users.user_type');
            if($request->search!= null){
                $sort_search=$request->search;
                $all_payments->where('users.name', 'like', '%'.$sort_search.'%');
            }
            $all_payments=$all_payments->paginate(10);
            //$all_payments = DB::table("wallets")->select("wallets.*")->paginate(2);
        }
        return view('admin.default.payment_history.payment_list',compact('all_payments','sort_search'));
    }
    public function freelancer_details($user_name)
    {
        $user = User::where('user_name', $user_name)->first();
        $user_profile = UserProfile::where('user_id', $user->id)->first();
        $user_account = FreelancerAccount::where('user_id', $user->id)->first();
        return view('admin.default.freelancer.freelancers.show', compact('user', 'user_profile', 'user_account'));
    }

    public function client_details($user_name)
    {
        $user = User::where('user_name', $user_name)->first();
        $user_profile = UserProfile::where('user_id', $user->id)->first();
        $user_account = FreelancerAccount::where('user_id', $user->id)->first();
        $projects = $user->number_of_projects()->paginate(10);
        return view('admin.default.client.clients.show', compact('user', 'user_profile', 'user_account','projects'));
    }

    public function userOnlineStatus()
    {
        $users = User::all();

        foreach ($users as $user) {
            if (Cache::has('user-is-online-' . $user->id))
                echo "User " . $user->name . " is online.";
            else
                echo "User " . $user->name . " is offline.";
        }
    }

    public function destroy($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        if($user->banned){
            $user->banned = 0;
            $user->save();
            flash(translate('User has been unbanned successfully'))->success();
            EmailUtility::send_email(
            "Account has been unbanned",
            "Your account has been successfully unbanned by Admin.",
            get_email_by_user_id($user->id),
            );
        }
        else{
            $user->banned = 1;
            $user->save();
            flash(translate('User has been banned successfully'))->success();
            EmailUtility::send_email(
            "Your Account has been banned",
            "Your account has been banned by Admin please contact admin for unbanned account.",
            get_email_by_user_id($user->id),
            route('contactPost')
            );
        }
        return back();
    }
    public function login_active_action($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        if($user->deleted_at){
            $user->deleted_at = null;
            $user->save();
            flash(translate('User has been enable successfully'))->success();
        }
        else{
            $user->deleted_at = now();
            $user->save();
            flash(translate('User has been disble successfully'))->success();
        }
        return back();
    }

    public function set_account_type(Request $request)
    {
        auth()->user()->user_type = $request->user_type;

        if(auth()->user()->save()) {
            session()->forget('new_user');
        }

        flash('User account type set successfully')->success();
        return back();

    }
}
