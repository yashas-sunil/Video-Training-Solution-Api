<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AskAQuestion extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'video_id' => 'integer'
    ];

    protected $appends = ['order_item_id'];

    use SoftDeletes;

    const TYPE_PENDING = 'pending';
    const TYPE_ANSWERED = 'answered';

    public function getOrderItemIdAttribute()
    {
        $orderItems = OrderItem::where('package_id', $this->package_id)
            ->where('user_id', $this->user_id)
            ->where('payment_status', 2)
            ->get();

        if ($orderItems) {
            foreach ($orderItems as $orderItem) {
                if (!$orderItem->is_completed) {
                    return $orderItem->id;
                }
            }
        }

        return $orderItems->first()->id ?? null;
    }

    public function video() {
        return $this->belongsTo('App\Models\Video');
    }

    public function answer()
    {
        return $this->hasOne('App\Models\Answer', 'question_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    public function package()
    {
        return $this->belongsTo('App\Models\Package');
    }

    public function answered()
    {
        return $this->hasOne('App\Models\Answer', 'question_id');
    }

    /**
     * Scope a query to search question by search text.
     *
     * @param  Builder $query
     * @param $search string
     * @return Builder
     */
    public function scopeSearch($query, $search)
    {
        if (! $search) {
            return $query->where('user_id', Auth::id());
        }

        return $query->where('user_id', Auth::id())
            ->where("name", "LIKE", "%$search%");
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfProfessor($query)
    {
        $professor = Professor::where('user_id',Auth::id())->first();
        return $query->whereHas('video', function($q) use($professor) {
            $q->where('professor_id', $professor->id);
        })->doesnthave('answer')->latest();
    }


    /**
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType($query, $type)
    {
        if ($type) {
            if ($type == self::TYPE_PENDING) {
                $query->doesnthave('answer');
            }

            if ($type == self::TYPE_ANSWERED) {
                $query->has('answer');
            }
        }

        return $query;
    }

    public function scopeOfUser($query)
    {
        $query = $query->where('user_id', Auth::id());

        return $query;
    }

    /**
     * @param Builder $query
     * @param integer $videoID
     * @return Builder
     */
    public function scopeOfVideo($query, $videoID)
    {
        if (! $videoID) {
            return $query;
        }

        return $query->where('video_id', $videoID);
    }

    /**
     * @param Builder $query
     * @param integer $packageID
     * @return Builder
     */
    public function scopeOfPackage($query, $packageID)
    {
        if (! $packageID) {
            return $query;
        }

        return $query->where('package_id', $packageID);
    }

    public function scopeOfSubject($query, $subjectId)
    {
        if (! $subjectId) {
            return $query;
        }

        return $query->whereHas('video', function ($query) use ($subjectId){
            $query->where('subject_id', $subjectId);
        });
    }
//
    public function scopeOfRecent($query, $recent)
    {
        if (! $recent) {
            return $query;
        }

        if($recent == 1){
            $limit = 10;
            return $query;
        }

//        if($recent == 2){
//            $currentDate = \Carbon\Carbon::now();
//            $agoDate = $currentDate->subDays($currentDate->dayOfWeek)->subWeek();
//
//            return $query->whereDate('created_at', '>', $agoDate);
//        }

    }

    public static function getAll($search = null, $type = null, $limit=null, $videoID = null, $packageID = null, $subjectId = null, $recent = null, $professor = null)
    {
        /** @var AskAQuestion|Builder $query */
        $query = AskAQuestion::query()->with('answer');
        $query->search($search);

        $query->ofType($type);

        $query->ofUser();

        $query->ofVideo($videoID);

        $query->ofPackage($packageID);

        $query->ofSubject($subjectId);

        $query->ofRecent($recent);

        if($professor){
            $query = $query->whereHas('video', function($q) use($professor) {
                $q->where('professor_id', $professor);
            });
        }

        $query->latest();

        if($recent == 1){
            $studentNotes = $query->with(['answer', 'video.subject', 'video.professor'])->paginate(10);
        }
        elseif ($recent == 2){
            $currentDate = \Carbon\Carbon::now();
            $agoDate = $currentDate->subDays($currentDate->dayOfWeek)->subWeek();

            $studentNotes = $query->with(['answer', 'video.subject', 'video.professor'])
                ->whereDate('created_at','>', $agoDate)
                ->paginate((int)$limit);
        }
	else{
	    $limit = !empty($limit) ? $limit : 100;
            $studentNotes = $query->with(['answer', 'video.subject', 'video.professor'])
                ->paginate((int)$limit);
        }

        return $studentNotes;
    }

    public static function getQuestion($id = null)
    {
        $question = AskAQuestion::with('video', 'answered')->findOrFail($id);

//        $answer = Answer::whereHas('question', function ($query) use ($question) {
//            $query->where('video_id', $question->video_id);
//        })->with('question.user')->get();

        $answers = Answer::query()
            ->with('question.user')
            ->where('user_id', auth('api')->id())
            ->get();

        $question->answers = $answers;

        return $question;
    }
}
