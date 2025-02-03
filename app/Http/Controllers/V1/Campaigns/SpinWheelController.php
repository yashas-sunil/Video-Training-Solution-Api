<?php

namespace App\Http\Controllers\V1\Campaigns;

use App\Http\Controllers\V1\Controller;
use App\Models\SpinWheelCampaign;
use App\Models\SpinWheelSegment;
use Illuminate\Http\Request;

class SpinWheelController extends Controller
{
    public function show($slug)
    {
        $spinWheel = SpinWheelCampaign::query()->with('spinWheelSegments')->where('slug', $slug)->first();

        if (! $spinWheel) {
            abort(404);
        }
        return $this->jsonResponse('Spin Wheel', $spinWheel);
    }

    public function getPrize($id)
    {
        $spinWheelSegments = SpinWheelSegment::query()->where('spin_wheel_campaign_id', $id)
            ->where('hits_in_hundred', '!=' ,0)->get();

        if (count($spinWheelSegments) <= 0) {
            $spinWheelSegments = SpinWheelSegment::query()->where('spin_wheel_campaign_id', $id)->get();

            foreach ($spinWheelSegments as $spinWheelSegment) {
                $spinWheelSegment->hits_in_hundred = $spinWheelSegment->success_percentage;
                $spinWheelSegment->save();
            }

            $spinWheelSegments = SpinWheelSegment::query()->where('spin_wheel_campaign_id', $id)
                ->where('hits_in_hundred', '!=' ,0)->get();
        }

        $segments=null;

        foreach($spinWheelSegments as$spinWheelSegment ){
            $segments[] = $spinWheelSegment->id;
        }

        $segment_number = array_rand($segments);

        $segment_id = $segments[$segment_number];

        $all_segments = SpinWheelSegment::query()->where('spin_wheel_campaign_id', $id)->get()->pluck('id')->toArray();

        $segment_number = array_search($segment_id, $all_segments);

//        info ('SEGMENT_ID: ' . $segment_id);
//        info ('SEGMENT_NUMBER: ' . $segment_number);

        $spinWheelSegments = SpinWheelSegment::query()
            ->find($segment_id);

        $spinWheelSegments->hits_in_hundred = ($spinWheelSegments->hits_in_hundred - 1);
        $spinWheelSegments->save();

        return $this->jsonResponse('Spin Wheel Prize', $segment_number+1);
    }
}
