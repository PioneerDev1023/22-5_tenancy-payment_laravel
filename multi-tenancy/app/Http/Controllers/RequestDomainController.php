<?php

namespace App\Http\Controllers;

use App\DataTables\RequestDomainDataTable;
use App\Facades\UtilityFacades;
use App\Models\Order;
use App\Models\Plan;
use App\Models\RequestDomain;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Stancl\Tenancy\Database\Models\Domain;
use Stripe\Stripe;
use App\Mail\ApproveMail;
use App\Mail\ConatctMail;
use App\Mail\DisapprovedMail;

class RequestDomainController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function landingPage()
    {
        $central_domain = config('tenancy.central_domains')[0];
        $current_domain = tenant('domains');
        if (!empty($current_domain)) {
            $current_domain = $current_domain->pluck('domain')->toArray()[0];
        }
        if ($current_domain == null) {
            if (!file_exists(storage_path() . "/installed")) {
                header('location:install');
                die;
            }
            $plans = Plan::all();
            return view('welcome', compact('plans'));
        } else {

            return redirect()->route('home');
        }
    }

    public function index(RequestDomainDataTable $dataTable)
    {
        if (\Auth::user()->hasrole('Super Admin')) {
            return $dataTable->render('requestdomain.index');
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($data)
    {
        try {
            $data = Crypt::decrypt($data);
            $plan_id = $data['plan_id'];
        } catch (DecryptException $e) {
            return redirect()->back()->with('failed', $e->getMessage());
        }

        return view('requestdomain.create', compact('plan_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->agree == 'on') {
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email,',
                    'domains' => 'required|unique:domains,domain',
                    'password' => 'same:password_confirmation',

                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('errors', $messages->first());
            }

            $domain = new RequestDomain();
            $domain->name = $request->name;
            $domain->email = $request->email;
            $domain->password = Hash::make($request->password);
            $domain->domain_name = $request->domains;
            $domain->type = 'Admin';
            $domain->save();


            $order = tenancy()->central(function ($tenant) use ($request, $domain) {
                $plan_details = Plan::find($request->plan_id);

                // dd($user);
                $data = Order::create([
                    'plan_id' => $request->plan_id,
                    'domainrequest_id' => $domain->id,
                    'amount' => $plan_details->price,
                    'status' => 0,
                ]);
                return $data;
            });
            $response = array(
                'status' => 0,
                'order_id' => $order->id,
                'domainrequest_id' => $domain->id,

            );
            if ($request->plan_id != 1) {
                echo json_encode($response);
                die;
            } else {
                return redirect()->route('landingpage')->with('success', __('Thanks for registration, your account is in review and you get email when your account active.'));
            }
        } else {
            return redirect()->back()->with('status', 'Please check terms and conditions');
        }
    }


    public function approveStatus($id)
    {
        // dd($id);
        $requestdomain = RequestDomain::find($id);
        if ($requestdomain->is_approved == 0) {

            return view('requestdomain.edit', compact('requestdomain'));
        } else {
            return redirect()->back();
        }
    }
    public function disapproveStatus($id)
    {
        $requestdomain = RequestDomain::find($id);
        if ($requestdomain->is_approved == 0) {
            $view =   view('requestdomain.reason', compact('requestdomain'));
            return ['html' => $view->render()];
        } else {
            return redirect()->back();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'reason' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('errors', $messages->first());
        }

        $requestdomain = RequestDomain::find($id);
        $requestdomain->reason = $request->reason;
        $requestdomain->is_approved = 2;
        $requestdomain->update();
        try {
            Mail::to($requestdomain->email)->send(new DisapprovedMail($requestdomain));
        } catch (\Exception $e) {
            return redirect()->back()->with('errors', __($e->getMessage()));
        }
        return redirect()->back()->with('success', __('Domain Request Disapprove successfully'));
    }

    public function prestripeSession(Request $request)
    {

        Stripe::setApiKey(env('STRIPE_SECRET'));
        $currency = UtilityFacades::getsettings('currency');


        if (!empty($request->createCheckoutSession)) {

            $plan_details = tenancy()->central(function ($tenant) use ($request) {
                return Plan::find($request->plan_id);
            });
            // Create new Checkout Session for the order
            try {
                $checkout_session = \Stripe\Checkout\Session::create([
                    'line_items' => [[
                        'price_data' => [
                            'product_data' => [
                                'name' => $plan_details->name,
                                'metadata' => [
                                    'plan_id' => $request->plan_id,
                                    'domainrequest_id' => $request->domain_id
                                ]
                            ],
                            'unit_amount' => $plan_details->price * 100,
                            'currency' => $currency,
                        ],
                        'quantity' => 1,
                        'description' => $plan_details->name,
                    ]],
                    'mode' => 'payment',
                    'success_url' => route('pre.stripe.success.pay', Crypt::encrypt(['plan_id' => $plan_details->id, 'price' => $plan_details->price, 'domainrequest_id' => $request->domain_id, 'order_id' => $request->order_id])),
                    'cancel_url' => route('pre.stripe.cancel.pay', Crypt::encrypt(['plan_id' => $plan_details->id, 'price' => $plan_details->price, 'domainrequest_id' => $request->domain_id, 'order_id' => $request->order_id])),
                ]);

                // dd($checkout_session);

            } catch (Exception $e) {
                $api_error = $e->getMessage();
                // dd($api_error);

            }

            if (empty($api_error) && $checkout_session) {
                $response = array(
                    'status' => 1,
                    'message' => 'Checkout Session created successfully!',
                    'sessionId' => $checkout_session->id
                );
            } else {
                $response = array(
                    'status' => 0,
                    'error' => array(
                        'message' => 'Checkout Session creation failed! ' . $api_error
                    )
                );
            }
        }

        echo json_encode($response);
        die;
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    function prepaymentCancel($data)
    {
        $data = Crypt::decrypt($data);

        $order = tenancy()->central(function ($tenant) use ($data) {
            $datas = Order::find($data['order_id']);
            $datas->status = 2;
            $datas->update();
        });



        return redirect()->route('landingpage')->with('error', 'Payment canceled!');
    }

    function prepaymentSuccess($data)
    {
        $data = Crypt::decrypt($data);

        $order = tenancy()->central(function ($tenant) use ($data) {
            $datas = Order::find($data['order_id']);
            $datas->status = 1;
            $datas->update();
        });

        return redirect()->route('landingpage')->with('status', 'Thanks for registration, your account is in review and you get email when your account active.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $requestdomain = RequestDomain::find($id);
        return view('requestdomain.data_edit', compact('requestdomain'));
    }

    public function data_update(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'email' => 'required|email|unique:users,email,',
                'domains' => 'required|unique:domains,domain',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('errors', $messages->first());
        }

        $requestdomain = RequestDomain::find($id);

        $requestdomain['name'] = $request->name;
        $requestdomain['email'] = $request->email;
        $requestdomain['domain_name'] = $request->domains;
        // $requestdomain['password'] = Hash::make($request->password);
        if (!empty($request->password)) {
            $requestdomain->password = Hash::make($request->password);
        }
        $requestdomain->update();
        return redirect()->back()->with('success', __('Domain Request updated successfully'));
    }

    public function destroy($id)
    {
        $requestdomain = RequestDomain::find($id);

        $requestdomain->delete();

        return redirect()->route('requestdomain.index')->with('success', __('Domain Request deleted successfully'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $req = RequestDomain::where('email', $request->email)->first();

        $data = Order::where('domainrequest_id', $req->id)->first();
        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'email' => 'required|email|unique:users,email,',
                'domains' => 'required|unique:domains,domain',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('errors', $messages->first());
        }
        $input['name'] = $request->name;
        $input['email'] = $request->email;
        $input['password'] = $request->password;
        $input['type'] = 'Admin';
        $input['plan_id'] = 1;
        $user = User::create($input);
        $user->assignRole('Admin');
        if (tenant('id') == null) {
            try {
                $tenant = Tenant::create([
                    'id' => $user->id,
                    'tenancy_db_name' => $request->db_name,
                    'tenancy_db_username' => $request->db_username,
                    'tenancy_db_password' => $request->db_password,
                ]);
                $domain = Domain::create([
                    'domain' => $request->domains,
                    'tenant_id' => $tenant->id,
                ]);
                $user->tenant_id = $tenant->id;
                $user->save();
            } catch (\Exception $e) {
                return redirect()->back()->with('errors', $e->getMessage());
            }
        }
        $user = User::find($tenant->id);
        $plan = Plan::find($data['plan_id']);

        $user->plan_id = $plan->id;
        if ($plan->durationtype == 'Month' && $plan->id != '1') {
            $user->plan_expired_date = Carbon::now()->addMonths($plan->duration)->isoFormat('YYYY-MM-DD');
        } elseif ($plan->durationtype == 'Year' && $plan->id != '1') {
            $user->plan_expired_date = Carbon::now()->addYears($plan->duration)->isoFormat('YYYY-MM-DD');
        } else {
            $user->plan_expired_date = null;
        }

        $user->save();
        $req->is_approved = 1;
        $req->save();
        $data->user_id = $user->id;
        $data->save();

        try {
            Mail::to($req->email)->send(new ApproveMail($req));
        } catch (\Exception $e) {
             return redirect()->route('requestdomain.index')->with('errors', $e->getMessage());
        }
        return redirect()->route('requestdomain.index')->with('success', __('User created successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function contactus()
    {
        return view('contactus');
    }

    public function termsandconditions()
    {
        return view('termsandconditions');
    }

    public function privacypolicy()
    {
        return view('privacypolicy');
    }

    public function faq()
    {
        return view('faq');
    }

    public function contact_mail(Request $request)
    {
        if ($request) {
            $details = $request->all();
            Mail::to(UtilityFacades::getsettings('contact_us_email'))->send(new ConatctMail($request->all()));
            return redirect()->back()->with('success', 'Email sent Successfully');
        } else {
            return redirect()->back()->with('failed', __('Please check Recaptch'));
        }
    }
}
