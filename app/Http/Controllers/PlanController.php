<?php

namespace App\Http\Controllers;

use App\DataTables\PlanDataTable;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    public function index(PlanDataTable $dataTable)
    {

        if (\Auth::user()->can('manage-plan')) {
            if (Auth::user()->type == 'Super Admin') {
                return $dataTable->render('plans.index');
            } else if (Auth::user()->type == 'Admin') {
                $plans = tenancy()->central(function ($tenant) {
                    return Plan::all();
                });
                $user = tenancy()->central(function ($tenant) {
                    return User::find($tenant->id);
                });
                return view('plans.index', compact('user', 'plans'));
            } else {

                $plans =  Plan::all();
                $user = User::find(Auth::user()->id);

                return view('plans.index', compact('user', 'plans'));
            }
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }

    public function myPlan(PlanDataTable $dataTable)
    {
        if (\Auth::user()->can('manage-plan')) {
            if (Auth::user()->type == 'Admin') {
                return $dataTable->render('plans.myplans');
            } else {
                $plans = Plan::where('tenant_id', null)->get();
                $user = User::where('tenant_id', tenant('id'))->where('type', 'Admin')->first();
                return view('plans.index', compact('user', 'plans'));
            }
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create-plan')) {
            return view('plans.create');
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create-plan')) {
            if (Auth::user()->type == 'Super Admin') {
                request()->validate([
                    'name' => 'required',
                    'price' => 'required',
                    'duration' => 'required',
                    'durationtype' => 'required',
                ]);
                Plan::create([
                    'name' => $request->name,
                    'price' => $request->price,
                    'duration' => $request->duration,
                    'durationtype' => $request->durationtype,
                ]);
            } else {
                if (\Auth::user()->can('create-plan')) {
                    request()->validate([
                        'name' => 'required',
                        'price' => 'required',
                        'duration' => 'required',
                        'durationtype' => 'required',
                        'max_users' => 'required',
                    ]);
                    Plan::create([
                        'name' => $request->name,
                        'price' => $request->price,
                        'duration' => $request->duration,
                        'durationtype' => $request->durationtype,
                        'max_users' => $request->max_users,
                    ]);
                }
            }
            if (Auth::user()->type == 'Admin') {
                return redirect()->route('plans.myplan')->with('success', __('Plan Added successfully.'));
            } else {
                return redirect()->route('plans.index')->with('success', __('Plan Added successfully.'));
            }
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }

    public function show(Plan $plan)
    {
        if (\Auth::user()->can('show-plan')) {

            $lan = Plan::find($plan);
            return view('plans.show', compact('plan'));
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit-plan')) {
            $plan = Plan::find($id);
            return view('plans.edit', compact('plan'));
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit-plan')) {
            if (Auth::user()->type == 'Super Admin') {

                request()->validate([
                    'name' => 'required',
                    'price' => 'required',
                    'duration' => 'required',
                ]);
                $plan = Plan::find($id);
                $plan->name = $request->input('name');
                $plan->price = $request->input('price');
                $plan->duration = $request->input('duration');
                $plan->durationtype = $request->input('durationtype');
                $plan->save();
            } else {
                request()->validate([
                    'name' => 'required',
                    'price' => 'required',
                    'duration' => 'required',
                    'max_users' => 'required',
                ]);
                $plan = Plan::find($id);
                $plan->name = $request->input('name');
                $plan->price = $request->input('price');
                $plan->duration = $request->input('duration');
                $plan->durationtype = $request->input('durationtype');
                $plan->max_users = $request->input('max_users');
                $plan->save();
            }
            if (Auth::user()->type == 'Admin') {
                return redirect()->route('plans.myplan')->with('success', __('Plan updated successfully.'));
            } else {
                return redirect()->route('plans.index')->with('success', __('Plan updated successfully.'));
            }
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete-plan')) {
            $plan = Plan::find($id);

            if ($plan->id != 1) {
                $plan_exist_in_order = Order::where('plan_id', $plan->id)->first();
                if (empty($plan_exist_in_order)) {
                    $plan->delete();
                    if (Auth::user()->type == 'Admin') {
                        return redirect()->route('plans.myplan')->with('success', __('Plan deleted successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('success', __('Plan deleted successfully.'));
                    }
                } else {
                    return redirect()->back()->with('failed', __('Can not delete this plan Because its Purchased by users.'));
                }
            } else {
                return redirect()->back()->with('failed', __('Can not delete this plan Because its free plan.'));
            }
        } else {
            return redirect()->back()->with('failed', __('Permission Denied.'));
        }
    }
}
