<?php

namespace App\Http\Controllers\V1\Professor;


use App\Http\Controllers\V1\Controller;
use App\Models\CustomizedPackage;
use App\Models\Package;
use App\Models\PackageVideo;
use App\Models\Professor;
use App\Models\ProfessorNote;
use App\Models\SubjectPackage;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Services\Professor\ProfessorNoteService;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{

    /** @var  ProfessorNoteService */
    var $professorNoteService;

    /**
     * StudentNoteController constructor.
     * @param ProfessorNoteService $service
     */
    public function __construct(ProfessorNoteService $service)
    {
        $this->professorNoteService = $service;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $professor = Professor::where('user_id',Auth::id())->first();
        $videos = Video::with('chapter','subject','course','level')
                        ->where('professor_id',$professor->id)
                        ->paginate(5);

        return $this->jsonResponse('Videos', $videos);
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
    public function addNotes(Request $request)
    {
        $validatedData = $request->validate([
            'video_id' => 'required',
            'name' => '',
            'description' => '',
            'time' => ''
        ]);

        $validatedData['user_id'] = Auth::id();

        $professorNote = $this->professorNoteService->create($validatedData);

        return $this->jsonResponse('Professor Note successfully created', $professorNote);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $professor = Professor::where('user_id',Auth::id())->first();

        $video = Video::with('chapter','subject','course','level')
            ->where('id',$request->id)
            ->where('professor_id',$professor->id)
//            ->where('is_published',Video::PUBLISHED)
            ->first();

        return $this->jsonResponse('Video', $video);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function professorNotes(Request $request)
    {
        $professor_notes = ProfessorNote::where('video_id',$request->id)
                                        ->get();

        return $this->jsonResponse('Notes', $professor_notes);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateProfessorNotes(Request $request)
    {
       $professor_note = ProfessorNote::find($request->id);
       $professor_note->name = $request->title;
       $professor_note->description = $request->description;
       $professor_note->update();


       return $this->jsonResponse('notes',$professor_note);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteProfessorNotes(Request $request)
    {
        $note = ProfessorNote::find($request->id);
        $note->delete();

        return 1;
    }

    public function packageVideos()
    {
        $videos = null;

        if (request()->input('published') == 'true') {
            $videos = Video::query()
                ->where('professor_id', request()->input('professor_id'))
                ->where('is_published', true);

            if (request()->filled('query')) {
                $videos->where('title', 'like', '%' . request()->input('query') . '%');
            }
            if (request()->filled('course'))
            {
                $videos->where('course_id', request()->input('course'));
            }
            if (request()->filled('level'))
            {
                $videos->where('level_id', request()->input('level'));
            }
            if (request()->filled('subject'))
            {
                $videos->where('subject_id', request()->input('subject'));
            }
            if (request()->filled('chapter'))
            {
                $videos->where('chapter_id', request()->input('chapter'));
            }
            if (request()->filled('language'))
            {
                $videos->where('language_id', request()->input('language'));
            }
            $videos = $videos->paginate(16);

        }

        if (request()->input('published') == 'false') {
            $videos = Video::query()
                ->where('professor_id', request()->input('professor_id'))
                ->where('is_published', false);

            if (request()->filled('query')) {
                $videos->where('title', 'like', '%' . request()->input('query') . '%');
            }
            if (request()->filled('course'))
            {
                $videos->where('course_id', request()->input('course'));
            }
            if (request()->filled('level'))
            {
                $videos->where('level_id', request()->input('level'));
            }
            if (request()->filled('subject'))
            {
                $videos->where('subject_id', request()->input('subject'));
            }
            if (request()->filled('chapter'))
            {
                $videos->where('chapter_id', request()->input('chapter'));
            }
            if (request()->filled('language'))
            {
                $videos->where('language_id', request()->input('language'));
            }
            $videos = $videos->paginate(16);

        }

        if (request()->filled('package_id')) {

            $package = Package::find(request()->input('package_id'));
            $videoIDs = [];

            if ($package) {
                if ($package->type == 1) {
                    $videoIDs = PackageVideo::query()
                        ->where('package_id', request()->input('package_id'))
                        ->pluck('video_id')
                        ->unique()->values();
                }

                if ($package->type == 2) {
                    $chapterVideoIDs = SubjectPackage::query()
                        ->where('package_id', request()->input('package_id'))
                        ->pluck('chapter_package_id')
                        ->unique()->values();

                    $videoIDs = PackageVideo::query()
                        ->whereIn('package_id', $chapterVideoIDs)
                        ->pluck('video_id')
                        ->unique()->values();
                }

                if ($package->type == 3) {
                    $selectedPackageIDs = CustomizedPackage::query()
                        ->where('package_id', request()->input('package_id'))
                        ->pluck('selected_package_id')
                        ->unique()->values();

                    $selectedPackages = Package::query()
                        ->whereIn('id', $selectedPackageIDs)
                        ->get();

                    foreach ($selectedPackages as $selectedPackage) {
                        if ($selectedPackage->type == 1) {
                            $videoIDs[] = PackageVideo::query()
                                ->where('package_id', $selectedPackage->id)
                                ->pluck('video_id')
                                ->unique()->values();
                        }

                        if ($selectedPackage->type == 2) {
                            $chapterVideoIDs = SubjectPackage::query()
                                ->where('package_id', $selectedPackage->id)
                                ->pluck('chapter_package_id')
                                ->unique()->values();

                            $videoIDs[] = PackageVideo::query()
                                ->whereIn('package_id', $chapterVideoIDs)
                                ->pluck('video_id')
                                ->unique()->values();
                        }
                    }
                }
            }

            $videos = Video::query()
                ->whereIn('id', $videoIDs)
                ->where('professor_id', request()->input('professor_id'));

            if (request()->filled('query'))
            {
                $videos->where('title', 'like', '%' . request()->input('query') . '%');
            }
            if (request()->filled('course'))
            {
                $videos->where('course_id', request()->input('course'));
            }
            if (request()->filled('level'))
            {
                $videos->where('level_id', request()->input('level'));
            }
            if (request()->filled('subject'))
            {
                $videos->where('subject_id', request()->input('subject'));
            }
            if (request()->filled('chapter'))
            {
                $videos->where('chapter_id', request()->input('chapter'));
            }
            if (request()->filled('language'))
            {
                $videos->where('language_id', request()->input('language'));
            }
            $videos = $videos->paginate(16);

        }


        return $this->jsonResponse('Videos', $videos);
    }
}
