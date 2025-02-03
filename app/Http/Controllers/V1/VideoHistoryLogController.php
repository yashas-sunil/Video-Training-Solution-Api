<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\VideoHistoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoHistoryLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user=Auth::id();
        $data= VideoHistoryLog::with('package','video')
                                ->where('package_id',$request->package_id)
                                ->where('user_id',$user)
                                ->where('video_id',$request->video_id)
                                ->latest()
                                ->paginate(10);

        return $this->jsonResponse('Video History Logs',$data);
    }

    public function getVideoHistoriesSession()
    {
        $userId = Auth::id();
        $datas = VideoHistoryLog::with('package','video.chapter')
            ->where('user_id', $userId)
            ->latest()
            ->paginate(10);

        return $this->jsonResponse('Video History Logs',$datas);
    }
}
