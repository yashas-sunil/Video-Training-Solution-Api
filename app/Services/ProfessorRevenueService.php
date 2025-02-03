<?php

namespace App\Services;

use App\Models\Package;
use App\Models\CustomizedPackage;
use App\Models\PackageVideo;
use App\Models\ProfessorRevenue;
use App\Models\SubjectPackage;
use Illuminate\Support\Arr;

class ProfessorRevenueService
{
    public function store($attributes = [])
    {
        $package = $this->getPackage($attributes['package_id']);
        $professorRevenues = $this->getProfessorRevenues($package, $attributes['net_amount']);

        foreach ($professorRevenues as $revenue) {
            $professorRevenue = new ProfessorRevenue();
            $professorRevenue->professor_id = $revenue['id'];
            $professorRevenue->package_id = $attributes['package_id'];
            $professorRevenue->invoice_id = $attributes['invoice_id'];
            $professorRevenue->invoice_date = $attributes['invoice_date'];
            $professorRevenue->package_total = $attributes['net_amount'];
            $professorRevenue->package_revenue_percentage = $package->professor_revenue ?? 0;
            $professorRevenue->professor_contribution_percentage = $revenue['percentage'];
            $professorRevenue->revenue_amount = $revenue['amount'];
            $professorRevenue->save();
        }

        return true;
    }

    public function apiStore($attributes = [])
    {
        $package = $this->getPackage($attributes['package_id']);
        $professorRevenues = $this->getProfessorRevenues($package, $attributes['net_amount']);

        foreach ($professorRevenues as $revenue) {
            $professorRevenue = ProfessorRevenue::where('professor_id',$revenue['id'])->where('package_id',$attributes['package_id'])
            ->where('invoice_id',$attributes['invoice_id'])->first();

            if(!empty($professorRevenue->id)){

            }else{
                $professorRevenue = new ProfessorRevenue();
            }


            $professorRevenue->professor_id = $revenue['id'];
            $professorRevenue->package_id = $attributes['package_id'];
            $professorRevenue->invoice_id = $attributes['invoice_id'];
            $professorRevenue->invoice_date = $attributes['invoice_date'];
            $professorRevenue->package_total = $attributes['net_amount'];
            $professorRevenue->package_revenue_percentage = $package->professor_revenue ?? 0;
            $professorRevenue->professor_contribution_percentage = $revenue['percentage'];
            $professorRevenue->revenue_amount = $revenue['amount'];
            $professorRevenue->save();
        }

        return true;
    }

    public function getPackage($id = null)
    {
        if (! $id) {
            return null;
        }

        return Package::query()->find($id) ?? null;
    }

    public function getProfessorRevenues($package = null, $netAmount = null)
    {
//        info('inside getProfessorRevenues');
        $mixedPackageIDs = [];

        if ($package->type == 1) {
            $mixedPackageIDs[] = $package->id;
        }

        if ($package->type == 2) {
            $mixedPackageIDs = SubjectPackage::query()
                ->where('package_id', $package->id)
                ->get()->pluck('chapter_package_id')->unique()->values();
        }

        if ($package->type == 3) {
            $mixedPackageIDs = CustomizedPackage::query()
                ->where('package_id', $package->id)
                ->get()->pluck('selected_package_id')->unique()->values();
        }

        $mixedPackages = Package::query()
            ->whereIn('id', $mixedPackageIDs)->get();

        $childPackageIDs = [];

        foreach ($mixedPackages as $mixedPackage) {
            if ($mixedPackage->type == 1) {
                $childPackageIDs[] = $mixedPackage->id;
            }

            if ($mixedPackage->type == 2) {
                $childPackageIDs[] = SubjectPackage::query()
                    ->where('package_id', $mixedPackage->id)
                    ->get()->pluck('chapter_package_id')->unique()->values();
            }
        }

        $collapsedChildPackageIDs = Arr::collapse($childPackageIDs);
        $childPackageIDs = count($collapsedChildPackageIDs) > 0 ? $collapsedChildPackageIDs : $childPackageIDs;

        $professorIDs = PackageVideo::query()
            ->whereIn('package_id', $childPackageIDs)
            ->get()->pluck('video.professor_id')->unique()->values();

        $packagePrices = [];

        foreach ($professorIDs as $professorID) {
            $packagePrices[$professorID] = Package::query()->whereIn('id', $childPackageIDs)
                ->whereHas('packageVideos', function($query) use ($professorID) {
                    $query->whereHas('video', function($query) use ($professorID) {
                        $query->where('professor_id', $professorID);
                    });
                })->sum('price');
        }

        $totalPackagePrice = array_sum($packagePrices);
        $professorRevenuesInPercentage = [];

        foreach ($packagePrices as $professorID => $packagePrice) {
            $professorRevenuesInPercentage[$professorID] = round(($packagePrice / $totalPackagePrice) * 100, 2);
        }
//        info('inside getProfessorRevenues');
        $professorRevenueOfPackageInPercentage = $package->professor_revenue ?? 0;
        $professorRevenueOfPackageInAmount = ($professorRevenueOfPackageInPercentage / 100) * $netAmount;
        $professorRevenuesInAmount = [];

        foreach ($professorRevenuesInPercentage as $professorID => $professorRevenueInPercentage) {
            $professorRevenuesInAmount[$professorID] = round(($professorRevenueInPercentage / 100) * $professorRevenueOfPackageInAmount, 2);
        }

        $professorRevenues = [];

        foreach ($professorRevenuesInAmount as $professorID => $professorRevenueInAmount) {
            $professorRevenues[] = [
                'id' => $professorID,
                'percentage' => $professorRevenuesInPercentage[$professorID],
                'amount' => $professorRevenueInAmount,
            ];
        }

        return $professorRevenues;
    }
}
