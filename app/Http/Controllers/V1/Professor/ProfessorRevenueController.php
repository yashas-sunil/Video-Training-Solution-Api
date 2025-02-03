<?php

namespace App\Http\Controllers\V1\Professor;

use App\Http\Controllers\V1\Controller;
use App\Models\Package;
use App\Models\ProfessorRevenue;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProfessorRevenueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data= ProfessorRevenue::with('package', 'payment.user')->where('professor_id', $request->professor_id)
            ->orderBy('invoice_date', 'desc');

        if (request()->filled('search'))
        {
//            info(request()->filled('search'));

            $data->whereHas('package', function ($query){
                $query->where('name', 'like', '%' . request()->input('search') . '%');
            });
        }

        if (request()->filled('date'))
        {
            $start_date = Carbon::parse(request()->input('date'))->toDateTimeString();
//            info($start_date);
            $data->whereDate('invoice_date', $start_date);
        }

        if (request()->filled('from_date') && request()->filled('to_date')) {
            $data->whereBetween('invoice_date', [Carbon::parse(request()->input('from_date')), Carbon::parse(request()->input('to_date'))]);
        } else {
            $data->whereBetween('invoice_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        }

        $data = $data->paginate(10);

        return $this->jsonResponse('Professor Revenue',$data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
