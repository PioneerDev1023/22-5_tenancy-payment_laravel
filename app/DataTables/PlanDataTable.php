<?php

namespace App\DataTables;

use App\Facades\UtilityFacades;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PlanDataTable extends DataTable
{

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return UtilityFacades::date_time_format($request->created_at);
            })
            ->editColumn('duration', function (plan $plan) {
                return $plan->duration . ' ' . $plan->durationtype;
            })

            ->addColumn('action', function (plan $plan) {
                return view('plans.action', compact('plan'));
            })
            ->editColumn('price', function (plan $plan) {
                return UtilityFacades::getsettings('currency_symbol') . '' . $plan->price;
            })
            ->rawColumns(['action']);
    }


    public function query(Plan $model)
    {
        return $model->newQuery()->where('tenant_id', null);
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('users-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->language([
                "paginate" => [
                    "next" => '<i class="fas fa-angle-right"></i>',
                    "previous" => '<i class="fas fa-angle-left"></i>'
                ]
            ])
            ->parameters([
                "dom" =>  "
                                <'row'<'col-sm-12'><'col-sm-9 text-left'B><'col-sm-3'f>>
                                <'row'<'col-sm-12'tr>>
                                <'row mt-3'<'col-sm-5'i><'col-sm-7'p>>
                                ",

                'buttons'   => [
                    ['extend' => 'create', 'className' => 'btn btn-primary btn-sm no-corner add_module', 'action' => " function ( e, dt, node, config ) {
                        window.location = '" . route('plans.create') . "';

                   }"],
                    ['extend' => 'export', 'className' => 'btn btn-primary btn-sm no-corner',],
                    ['extend' => 'print', 'className' => 'btn btn-primary btn-sm no-corner',],
                    ['extend' => 'reset', 'className' => 'btn btn-primary btn-sm no-corner',],
                    ['extend' => 'reload', 'className' => 'btn btn-primary btn-sm no-corner',],
                    ['extend' => 'pageLength', 'className' => 'btn btn-danger btn-sm no-corner',],
                ],
                "scrollX" => true
            ])->language([
                'buttons' => [
                    'create' => __('Create'),
                    'export' => __('Export'),
                    'print' => __('Print'),
                    'reset' => __('Reset'),
                    'reload' => __('Reload'),
                    'excel' => __('Excel'),
                    'csv' => __('CSV'),
                    'pageLength' => __('Show %d rows'),
                ]
            ]);
    }

    protected function getColumns()
    {
        if (Auth::user()->type == 'Super Admin') {
            return [
                Column::make('No')->data('DT_RowIndex')->name('DT_RowIndex')->searchable(false)->orderable(false),
                Column::make('name'),
                Column::make('price'),
                Column::make('duration'),
                Column::computed('action')
                    ->exportable(false)
                    ->printable(false)
                    ->width(60)
                    ->addClass('text-center')
                    ->width('20%'),
            ];
        } else {
            return [
                Column::make('No')->title(__('No'))->data('DT_RowIndex')->name('DT_RowIndex')->searchable(false)->orderable(false),
                Column::make('name')->title(__('Name')),
                Column::make('price')->title(__('Price')),
                Column::make('duration')->title(__('Duration')),
                Column::make('max_users')->title(__('Max Users')),
                Column::computed('action')->title(__('Action'))
                    ->exportable(false)
                    ->printable(false)
                    ->width(60)
                    ->addClass('text-center')
                    ->width('20%'),
            ];
        }
    }
    protected function filename()
    {
        return 'Plan_' . date('YmdHis');
    }
}
